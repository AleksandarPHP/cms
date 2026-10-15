<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;
use Throwable;

class BlogAiService
{
    /** @var array<int, string> */
    public const FIELDS = [
        'meta_title',
        'meta_description',
        'title',
        'subtitle',
        'description',
        'content',
    ];

    /** @var array<string, int> */
    private const TEXT_LIMITS = [
        'meta_title' => 191,
        'meta_description' => 255,
        'title' => 191,
        'subtitle' => 191,
    ];

    /** @var array<string, array{aspect: string, width: int, height: int, label: string}> */
    private const TARGETS = [
        'image' => [
            'aspect' => '1:1',
            'width' => 1024,
            'height' => 1024,
            'label' => 'square thumbnail',
        ],
        'image2' => [
            'aspect' => '21:9',
            'width' => 1920,
            'height' => 863,
            'label' => 'wide hero banner, 1920 by 863',
        ],
    ];

    public function __construct(private GeminiBlogAgentService $agent) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public function refineText(array $input): array
    {
        set_time_limit(180);

        $fields = array_values(array_intersect(self::FIELDS, $input['fields'] ?? []));

        if ($fields === []) {
            throw new RuntimeException('Choose at least one field.');
        }

        $current = [];

        foreach (self::FIELDS as $field) {
            $current[$field] = trim((string) ($input[$field] ?? ''));

            if (in_array($field, $fields, true) && mb_strlen($current[$field]) > 40000) {
                throw new RuntimeException('That field is too long to revise in one pass.');
            }
        }

        $revised = $this->agent->reviseArticle($current, $fields, trim((string) $input['instruction']));
        $result = [];

        foreach ($fields as $field) {
            $value = $this->cleanField($field, (string) ($revised[$field] ?? ''));

            if ($value === '' && $current[$field] !== '') {
                $value = $this->cleanField($field, $current[$field]);
            }

            $result[$field] = $value;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{path: string, url: string}
     */
    public function generateImage(array $input): array
    {
        set_time_limit(180);

        $target = (string) ($input['target'] ?? '');
        $spec = self::TARGETS[$target] ?? null;

        if ($spec === null) {
            throw new RuntimeException('Unknown image target.');
        }

        $instruction = trim((string) ($input['instruction'] ?? ''));
        $title = trim(strip_tags((string) ($input['title'] ?? '')));
        $body = $this->plain((string) ($input['subtitle'] ?? '')."\n".(string) ($input['description'] ?? '')."\n".(string) ($input['content'] ?? ''));

        if ($instruction === '' && $title === '' && $body === '') {
            throw new RuntimeException('Add a title, article text, or an image instruction first.');
        }

        $reference = $this->storedBlogPath($input['current_path'] ?? null);
        $binary = $this->agent->generateImage($spec, $instruction, $title, $body, $reference);
        $path = 'blog/ai/'.Str::uuid().'.jpg';

        try {
            $manager = new ImageManager(new Driver());
            $jpeg = (string) $manager->read($binary)->cover($spec['width'], $spec['height'])->toJpeg(85);
        } catch (Throwable $e) {
            throw new RuntimeException('The image could not be saved.', previous: $e);
        }

        if ($jpeg === '') {
            throw new RuntimeException('The image could not be saved.');
        }

        Storage::disk('public')->put($path, $jpeg);
        $this->deleteUnsavedGeneration($input['previous_path'] ?? null, $input['saved_path'] ?? null, $path);

        return [
            'path' => $path,
            'url' => asset('storage/'.$path),
        ];
    }

    public function usableGeneratedPath(mixed $path): ?string
    {
        if (! is_string($path) || ! preg_match('/^blog\/ai\/[A-Za-z0-9\-]+\.jpg$/', $path)) {
            return null;
        }

        return Storage::disk('public')->exists($path) ? $path : null;
    }

    private function deleteUnsavedGeneration(mixed $previous, mixed $saved, string $created): void
    {
        $previousPath = $this->usableGeneratedPath($previous);
        $savedPath = $this->storedBlogPath($saved);

        if ($previousPath === null || $previousPath === $created || $previousPath === $savedPath) {
            return;
        }

        Storage::disk('public')->delete($previousPath);
    }

    private function storedBlogPath(mixed $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        if ($path === '' || str_contains($path, '..') || ! str_starts_with($path, 'blog/')) {
            return null;
        }

        return Storage::disk('public')->exists($path) ? $path : null;
    }

    private function cleanField(string $field, string $value): string
    {
        if (in_array($field, ['description', 'content'], true)) {
            return $this->html($value);
        }

        $value = trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
        $limit = self::TEXT_LIMITS[$field] ?? 191;

        if (mb_strlen($value) > $limit) {
            $value = rtrim(mb_substr($value, 0, $limit));
        }

        return $value;
    }

    private function html(string $value): string
    {
        $value = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $value) ?? $value;
        $value = strip_tags($value, '<p><br><h2><h3><ul><ol><li><strong><em><b><i><a><blockquote>');
        $value = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $value) ?? $value;
        $value = preg_replace('/\s(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '', $value) ?? $value;

        return trim($value);
    }

    private function plain(string $html): string
    {
        $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        if (mb_strlen($text) > 1500) {
            return mb_substr($text, 0, 1500);
        }

        return $text;
    }
}
