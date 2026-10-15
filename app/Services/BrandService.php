<?php

namespace App\Services;

use App\Models\Settings;
use Gemini\Data\Blob;
use Gemini\Enums\MimeType;
use Illuminate\Support\Facades\Storage;

class BrandService
{
    /** @var array{name: string, description: string, colors: array<int, string>, fonts: array<int, string>, logo_path: ?string}|null */
    private ?array $profile = null;

    /**
     * @return array{name: string, description: string, colors: array<int, string>, fonts: array<int, string>, logo_path: ?string}
     */
    public function profile(): array
    {
        if ($this->profile !== null) {
            return $this->profile;
        }

        $settings = Settings::query()->first();
        $fonts = [];

        foreach (['brand_font_primary', 'brand_font_secondary'] as $field) {
            $font = trim((string) ($settings?->{$field} ?? ''));

            if ($font !== '') {
                $fonts[] = $font;
            }
        }

        return $this->profile = [
            'name' => trim((string) ($settings?->webtitle ?? '')),
            'description' => trim((string) ($settings?->webdescription ?? '')),
            'colors' => $this->colors(),
            'fonts' => $fonts,
            'logo_path' => $settings?->logo ?: null,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function colors(): array
    {
        $path = Settings::query()->value('logo');

        if (! is_string($path) || $path === '' || ! Storage::disk('public')->exists($path)) {
            return [];
        }

        $absolute = Storage::disk('public')->path($path);
        $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));

        if ($extension === 'svg') {
            $svg = file_get_contents($absolute);

            return is_string($svg) ? $this->colorsFromSvg($svg) : [];
        }

        $binary = file_get_contents($absolute);

        if ($binary === false || $binary === '') {
            return [];
        }

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            return [];
        }

        $colors = $this->colorsFromRaster($image);
        imagedestroy($image);

        return $colors;
    }

    public function copyGuidance(): string
    {
        return $this->guidance(false);
    }

    public function imageGuidance(bool $logoAttached): string
    {
        return $this->guidance($logoAttached);
    }

    /**
     * @return array<int, Blob|string>
     */
    public function logoReference(): array
    {
        $blob = $this->logoBlob();

        if (! $blob instanceof Blob) {
            return [];
        }

        return [
            $blob,
            'The image above is the company logo. Use this exact logo when a logo is needed. Do not redraw, recolor, or replace it.',
        ];
    }

    public function withoutSerbian(string $text): string
    {
        $clean = preg_replace(
            '/(?:\r?\n)+\s*(?:serbian|srpski|na srpskom|prevod(?:\s+na\s+srpski)?)\s*:.*$/isu',
            '',
            $text,
        );
        $clean = preg_replace('/^\s*(?:english|engleski)\s*:\s*/iu', '', (string) $clean);

        return trim((string) $clean);
    }

    private function guidance(bool $logoAttached): string
    {
        $profile = $this->profile();
        $lines = [];

        if ($profile['name'] !== '') {
            $lines[] = 'Company: '.$profile['name'];
        }

        if ($profile['description'] !== '') {
            $lines[] = 'What the company does: '.$profile['description'];
        }

        if ($profile['colors'] !== []) {
            $lines[] = 'Logo colors: '.implode(', ', $profile['colors']).'. Use these colors and do not introduce a different palette.';
        }

        if (count($profile['fonts']) >= 2) {
            $lines[] = 'Fonts: headings in '.$profile['fonts'][0].', supporting text in '.$profile['fonts'][1].'.';
        } elseif (count($profile['fonts']) === 1) {
            $lines[] = 'Font: '.$profile['fonts'][0].'.';
        }

        if ($logoAttached) {
            $lines[] = 'A logo image is attached. Use that exact logo when a logo is needed.';
        }

        if ($lines === []) {
            return '';
        }

        return "Brand:\n".implode("\n", $lines);
    }

    private function logoBlob(): ?Blob
    {
        $path = $this->profile()['logo_path'];

        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $absolute = Storage::disk('public')->path($path);
        $mime = mime_content_type($absolute) ?: '';
        $enum = match ($mime) {
            'image/png' => MimeType::IMAGE_PNG,
            'image/webp' => MimeType::IMAGE_WEBP,
            'image/jpeg', 'image/jpg' => MimeType::IMAGE_JPEG,
            default => null,
        };

        if (! $enum instanceof MimeType) {
            return null;
        }

        $binary = file_get_contents($absolute);

        if ($binary === false || $binary === '') {
            return null;
        }

        return new Blob(
            mimeType: $enum,
            data: base64_encode($binary),
        );
    }

    /**
     * @return array<int, string>
     */
    private function colorsFromSvg(string $svg): array
    {
        preg_match_all('/#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})\b/', $svg, $matches);
        $colors = [];

        foreach ($matches[1] as $hex) {
            $this->pushColor($colors, $this->normalizeHex($hex));

            if (count($colors) === 3) {
                break;
            }
        }

        return $colors;
    }

    /**
     * @param  \GdImage  $image
     * @return array<int, string>
     */
    private function colorsFromRaster($image): array
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width < 1 || $height < 1) {
            return [];
        }

        $step = max(1, (int) floor(sqrt(($width * $height) / 12000)));
        $buckets = [];
        $samples = 0;
        $white = 0;

        for ($y = 0; $y < $height; $y += $step) {
            for ($x = 0; $x < $width; $x += $step) {
                $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));

                if ((int) ($color['alpha'] ?? 0) > 40) {
                    continue;
                }

                $samples++;
                $red = (int) $color['red'];
                $green = (int) $color['green'];
                $blue = (int) $color['blue'];

                if ($red > 242 && $green > 242 && $blue > 242) {
                    $white++;

                    continue;
                }

                $key = intdiv($red + 12, 24).'-'.intdiv($green + 12, 24).'-'.intdiv($blue + 12, 24);
                $bucket = $buckets[$key] ?? ['r' => 0, 'g' => 0, 'b' => 0, 'n' => 0];
                $bucket['r'] += $red;
                $bucket['g'] += $green;
                $bucket['b'] += $blue;
                $bucket['n']++;
                $buckets[$key] = $bucket;
            }
        }

        if ($buckets === []) {
            return [];
        }

        $clusters = [];

        foreach ($buckets as $bucket) {
            $red = $bucket['r'] / $bucket['n'];
            $green = $bucket['g'] / $bucket['n'];
            $blue = $bucket['b'] / $bucket['n'];
            $matched = null;

            foreach ($clusters as $index => $cluster) {
                if ($this->distance($red, $green, $blue, $cluster['r'] / $cluster['n'], $cluster['g'] / $cluster['n'], $cluster['b'] / $cluster['n']) < 46) {
                    $matched = $index;
                    break;
                }
            }

            if ($matched === null) {
                $clusters[] = $bucket;

                continue;
            }

            $clusters[$matched]['r'] += $bucket['r'];
            $clusters[$matched]['g'] += $bucket['g'];
            $clusters[$matched]['b'] += $bucket['b'];
            $clusters[$matched]['n'] += $bucket['n'];
        }

        usort($clusters, fn (array $left, array $right) => $right['n'] <=> $left['n']);

        $colored = array_sum(array_column($clusters, 'n'));
        $minimum = max(4, (int) floor($colored * 0.05));
        $colors = [];

        foreach ($clusters as $cluster) {
            if ($cluster['n'] < $minimum) {
                continue;
            }

            $this->pushColor($colors, $this->clusterHex($cluster));

            if (count($colors) === 3) {
                break;
            }
        }

        if (count($colors) < 3) {
            foreach ($clusters as $cluster) {
                if ($cluster['n'] < 4 || $this->saturation($cluster) < 0.28) {
                    continue;
                }

                $this->pushColor($colors, $this->clusterHex($cluster));

                if (count($colors) === 3) {
                    break;
                }
            }
        }

        if (count($colors) < 3 && $samples > 0 && ($white / $samples) >= 0.35 && ! in_array('#ffffff', $colors, true)) {
            $colors[] = '#ffffff';
        }

        return array_slice($colors, 0, 3);
    }

    /**
     * @param  array{r: float|int, g: float|int, b: float|int, n: int}  $cluster
     */
    private function clusterHex(array $cluster): string
    {
        return sprintf(
            '#%02x%02x%02x',
            (int) round($cluster['r'] / $cluster['n']),
            (int) round($cluster['g'] / $cluster['n']),
            (int) round($cluster['b'] / $cluster['n']),
        );
    }

    /**
     * @param  array{r: float|int, g: float|int, b: float|int, n: int}  $cluster
     */
    private function saturation(array $cluster): float
    {
        $red = $cluster['r'] / $cluster['n'];
        $green = $cluster['g'] / $cluster['n'];
        $blue = $cluster['b'] / $cluster['n'];
        $max = max($red, $green, $blue);

        if ($max < 1) {
            return 0.0;
        }

        return ($max - min($red, $green, $blue)) / $max;
    }

    private function distance(float $red, float $green, float $blue, float $otherRed, float $otherGreen, float $otherBlue): float
    {
        return sqrt((($red - $otherRed) ** 2) + (($green - $otherGreen) ** 2) + (($blue - $otherBlue) ** 2));
    }

    /**
     * @param  array<int, string>  $colors
     */
    private function pushColor(array &$colors, string $hex): void
    {
        if ($hex === '' || $this->isNearWhite($hex) || in_array($hex, $colors, true) || ! $this->farEnough($hex, $colors)) {
            return;
        }

        $colors[] = $hex;
    }

    private function normalizeHex(string $hex): string
    {
        $hex = strtolower($hex);

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return '#'.$hex;
    }

    private function isNearWhite(string $hex): bool
    {
        [$red, $green, $blue] = $this->rgb($hex);

        return $red > 245 && $green > 245 && $blue > 245;
    }

    /**
     * @param  array<int, string>  $chosen
     */
    private function farEnough(string $hex, array $chosen): bool
    {
        [$red, $green, $blue] = $this->rgb($hex);

        foreach ($chosen as $other) {
            [$otherRed, $otherGreen, $otherBlue] = $this->rgb($other);
            $distance = sqrt((($red - $otherRed) ** 2) + (($green - $otherGreen) ** 2) + (($blue - $otherBlue) ** 2));

            if ($distance < 48) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
