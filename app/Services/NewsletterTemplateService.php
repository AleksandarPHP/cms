<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Models\Blog;
use App\Models\Company;
use App\Models\Newsletter;
use App\Models\Settings;

class NewsletterTemplateService
{
    public function __construct(private BrandService $brand) {}

    /**
     * @return array<string, mixed>
     */
    public function present(Newsletter $newsletter, ?string $browserUrl = null): array
    {
        $previous = app()->getLocale();
        app()->setLocale($this->locale($newsletter));

        $data = $this->build($newsletter, $browserUrl);

        app()->setLocale($previous);

        return $data;
    }

    /**
     * @param  array<int, string>  $colors
     * @return array<string, string>
     */
    public function paletteFromColors(array $colors): array
    {
        $usable = [];

        foreach ($colors as $color) {
            if (! is_string($color) || ! preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                continue;
            }

            $hex = strtolower($color);

            if ($this->isNearWhite($hex) || in_array($hex, $usable, true)) {
                continue;
            }

            $usable[] = $hex;
        }

        if ($usable === []) {
            $accent = '#e8872a';
            $dark = '#0d2744';
        } else {
            usort($usable, function (string $left, string $right) {
                return $this->saturation($right) <=> $this->saturation($left);
            });

            $accent = $usable[0];

            foreach ($usable as $hex) {
                $luminance = $this->luminance($hex);

                if ($luminance >= 0.22 && $luminance <= 0.82) {
                    $accent = $hex;
                    break;
                }
            }

            $dark = null;
            $darkest = 2.0;

            foreach ($usable as $hex) {
                if ($hex === $accent) {
                    continue;
                }

                $luminance = $this->luminance($hex);

                if ($luminance < $darkest) {
                    $darkest = $luminance;
                    $dark = $hex;
                }
            }

            if ($dark === null || $darkest > 0.5 || $this->distance($accent, $dark) < 40) {
                $dark = $this->scale($accent, 0.28);
            }
        }

        $link = $this->luminance($dark) < 0.55 ? $dark : $accent;
        $mark = $this->distance($accent, $dark) >= 80 ? $accent : $this->ink($dark);

        return [
            'accent' => $accent,
            'dark' => $dark,
            'link' => $link,
            'mark' => $mark,
            'on_accent' => $this->ink($accent),
            'on_dark' => $this->ink($dark),
            'text' => '#243140',
            'muted' => '#5c6b7a',
            'page' => '#e7eef3',
            'line' => '#e4e8ee',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function build(Newsletter $newsletter, ?string $browserUrl): array
    {
        if ($newsletter->exists) {
            $newsletter->load('blogs');
        }

        $settings = Settings::query()->first();
        $company = Company::query()->first();
        $palette = $this->paletteFromColors($this->brand->colors());
        $fonts = $this->fonts($settings);

        $name = trim((string) ($company?->name ?: $settings?->webtitle ?: ''));
        $place = trim(((string) ($company?->plz ?? '')).' '.((string) ($company?->ort ?? '')));
        $address = implode(' | ', array_values(array_filter([
            trim((string) ($company?->street ?? '')),
            $place,
        ])));

        $about = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($company?->description ?? ''))) ?? '');

        if (mb_strlen($about) > 140) {
            $about = rtrim(mb_substr($about, 0, 137)).'…';
        }

        $footer = implode(' | ', array_values(array_filter([$name, $about !== '' ? $about : $address])));
        $moment = ($newsletter->published_at ?? $newsletter->scheduled_at ?? $newsletter->created_at ?? now())
            ->copy()
            ->timezone(Newsletter::TIMEZONE)
            ->locale(app()->getLocale());

        return [
            'headline' => trim((string) $newsletter->title),
            'intro' => trim((string) $newsletter->text),
            'issue' => 'Nr. '.$newsletter->issue_number.' - '.mb_strtoupper($moment->translatedFormat('F Y'), 'UTF-8'),
            'blogs' => $newsletter->blogs->map(fn (Blog $blog) => $this->blogCard($blog))->all(),
            'companyName' => $name,
            'phone' => trim((string) ($company?->telefon ?? '')),
            'email' => trim((string) ($company?->email ?? '')),
            'address' => $address,
            'footerLine' => $footer,
            'logo' => $settings?->logo ? asset('storage/'.$settings->logo) : null,
            'facebook' => $this->socialUrl($company?->facebook, 'facebook'),
            'instagram' => $this->socialUrl($company?->instagram, 'instagram'),
            'palette' => $palette,
            'fontFamily' => $fonts['family'],
            'googleFont' => $fonts['google'],
            'browserUrl' => $browserUrl,
            'privacyUrl' => url('/'),
            'subscribeUrl' => $this->mailto($company?->email, 'Newsletter abonnieren'),
            'unsubscribeUrl' => $this->mailto($company?->email, 'Newsletter abmelden'),
            'locale' => app()->getLocale(),
            'labels' => [
                'phone' => __('app.newsletter_phone'),
                'email' => __('app.newsletter_email_label'),
                'more' => __('app.newsletter_more'),
                'unsubscribe' => __('app.newsletter_unsubscribe'),
                'privacy' => __('app.newsletter_privacy'),
                'known' => __('app.newsletter_known'),
                'known_before' => __('app.newsletter_known_before'),
                'subscribe' => __('app.newsletter_subscribe'),
                'known_after' => __('app.newsletter_known_after'),
                'facebook' => __('app.facebook'),
                'view' => __('app.newsletter_view_browser'),
            ],
        ];
    }

    /**
     * @return array{url: string, image: ?string, date: string, title: string, subtitle: string, facebook: string, mail: string}
     */
    private function blogCard(Blog $blog): array
    {
        $slug = $blog->slug ?: 'beitrag';
        $url = route('blog.show', ['id' => $blog->id, 'slug' => $slug]);
        $path = $blog->image2 ?: $blog->image;

        return [
            'url' => $url,
            'image' => $path ? Helper::image($path, 1200, 5000) : null,
            'date' => $blog->created_at
                ? $blog->created_at->copy()->timezone(Newsletter::TIMEZONE)->format('d.m.Y')
                : '',
            'title' => (string) $blog->title,
            'subtitle' => (string) $blog->subtitle,
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($url),
            'mail' => 'mailto:?subject='.rawurlencode((string) $blog->title).'&body='.rawurlencode($url),
        ];
    }

    /**
     * @return array{family: string, google: ?string}
     */
    private function fonts(?Settings $settings): array
    {
        $names = [];

        foreach (['brand_font_primary', 'brand_font_secondary'] as $field) {
            $font = trim((string) ($settings?->{$field} ?? ''));

            if ($font !== '' && preg_match('/^[A-Za-z0-9 \-]+$/', $font) && ! in_array($font, $names, true)) {
                $names[] = $font;
            }
        }

        $family = $names === []
            ? 'Arial, Helvetica, sans-serif'
            : "'".implode("', '", $names)."', Arial, Helvetica, sans-serif";

        $google = null;

        if ($names !== []) {
            $query = implode('&', array_map(
                fn (string $font) => 'family='.rawurlencode($font).':wght@400;600;700',
                $names,
            ));
            $google = 'https://fonts.googleapis.com/css2?'.$query.'&display=swap';
        }

        return ['family' => $family, 'google' => $google];
    }

    private function locale(Newsletter $newsletter): string
    {
        return in_array($newsletter->locale, ['de', 'fr', 'it'], true) ? $newsletter->locale : 'de';
    }

    private function socialUrl(mixed $value, string $network): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        $value = ltrim($value, '@/');

        return $network === 'instagram'
            ? 'https://instagram.com/'.$value
            : 'https://facebook.com/'.$value;
    }

    private function mailto(mixed $email, string $subject): ?string
    {
        $email = trim((string) $email);

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return 'mailto:'.$email.'?subject='.rawurlencode($subject);
    }

    private function scale(string $hex, float $factor): string
    {
        [$red, $green, $blue] = $this->rgb($hex);

        return sprintf(
            '#%02x%02x%02x',
            max(0, min(255, (int) round($red * $factor))),
            max(0, min(255, (int) round($green * $factor))),
            max(0, min(255, (int) round($blue * $factor))),
        );
    }

    private function ink(string $hex): string
    {
        return $this->luminance($hex) > 0.62 ? '#1c1c1c' : '#ffffff';
    }

    private function luminance(string $hex): float
    {
        [$red, $green, $blue] = $this->rgb($hex);

        return ((0.2126 * $red) + (0.7152 * $green) + (0.0722 * $blue)) / 255;
    }

    private function saturation(string $hex): float
    {
        [$red, $green, $blue] = $this->rgb($hex);
        $max = max($red, $green, $blue);

        if ($max < 1) {
            return 0.0;
        }

        return ($max - min($red, $green, $blue)) / $max;
    }

    private function distance(string $left, string $right): float
    {
        [$red, $green, $blue] = $this->rgb($left);
        [$otherRed, $otherGreen, $otherBlue] = $this->rgb($right);

        return sqrt((($red - $otherRed) ** 2) + (($green - $otherGreen) ** 2) + (($blue - $otherBlue) ** 2));
    }

    private function isNearWhite(string $hex): bool
    {
        [$red, $green, $blue] = $this->rgb($hex);

        return $red > 245 && $green > 245 && $blue > 245;
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
