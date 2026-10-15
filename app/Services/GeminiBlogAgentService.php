<?php

namespace App\Services;

use Gemini\Data\Blob;
use Gemini\Data\Content;
use Gemini\Data\GenerationConfig;
use Gemini\Data\ImageConfig;
use Gemini\Data\Part;
use Gemini\Data\Schema;
use Gemini\Data\ThinkingConfig;
use Gemini\Enums\DataType;
use Gemini\Enums\MimeType;
use Gemini\Enums\ResponseMimeType;
use Gemini\Enums\ResponseModality;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;
use Throwable;

class GeminiBlogAgentService
{
    public function __construct(private BrandService $brand) {}

    /**
     * @param  array<string, string>  $current
     * @param  array<int, string>  $fields
     * @return array<string, string>
     */
    public function reviseArticle(array $current, array $fields, string $instruction): array
    {
        $modelName = (string) config('gemini.model');
        $json = $this->complete(
            $modelName,
            $this->textConfig($modelName, $fields),
            'You edit blog articles. Keep the article language. If the article is empty, write in the language of the instruction. Reply with JSON only. Never wrap the JSON in markdown fences.',
            [$this->textPrompt($current, $fields, $instruction)],
        );

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini returned an unreadable response.');
        }

        $revised = [];

        foreach ($fields as $field) {
            $revised[$field] = trim((string) ($decoded[$field] ?? ''));
        }

        return $revised;
    }

    /**
     * @param  array{aspect: string, width: int, height: int, label: string}  $spec
     */
    public function generateImage(array $spec, string $instruction, string $title, string $body, ?string $referencePath): string
    {
        $apiKey = (string) config('gemini.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        try {
            $parts = [];
            $reference = $this->referenceBlob($referencePath);
            $logo = $this->brand->logoReference();

            if ($reference instanceof Blob) {
                $parts[] = $reference;
                $parts[] = 'The image above is the current blog image. Keep its subject recognizable and change only what the instruction requests.';
            }

            foreach ($logo as $part) {
                $parts[] = $part;
            }

            $parts[] = $this->imagePrompt($spec, $instruction, $title, $body, $reference instanceof Blob, $logo !== []);

            $response = \Gemini::client($apiKey)
                ->generativeModel(model: (string) config('gemini.image_model'))
                ->withGenerationConfig(new GenerationConfig(
                    responseModalities: [ResponseModality::TEXT, ResponseModality::IMAGE],
                    imageConfig: new ImageConfig(aspectRatio: $spec['aspect']),
                ))
                ->generateContent($parts);

            foreach ($response->candidates[0]->content->parts as $part) {
                if ($part->inlineData === null) {
                    continue;
                }

                $binary = base64_decode($part->inlineData->data, true);

                if ($binary === false || $binary === '') {
                    continue;
                }

                return $binary;
            }

            throw new RuntimeException('Gemini did not return an image.');
        } catch (Throwable $e) {
            Log::error('Gemini blog image generation failed.', [
                'message' => $e->getMessage(),
            ]);

            if ($e instanceof RuntimeException && $e->getMessage() === 'Gemini did not return an image.') {
                throw $e;
            }

            throw new RuntimeException('The image could not be generated: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * @param  array<int, string>  $fields
     */
    private function textConfig(string $modelName, array $fields): GenerationConfig
    {
        $properties = [];

        foreach ($fields as $field) {
            $properties[$field] = new Schema(
                type: DataType::STRING,
                description: $this->fieldDescription($field),
            );
        }

        return new GenerationConfig(
            maxOutputTokens: 8192,
            temperature: 0.7,
            responseMimeType: ResponseMimeType::APPLICATION_JSON,
            responseSchema: new Schema(
                type: DataType::OBJECT,
                properties: $properties,
                required: $fields,
            ),
            thinkingConfig: str_contains($modelName, '2.5')
                ? new ThinkingConfig(includeThoughts: false, thinkingBudget: 0)
                : null,
        );
    }

    private function fieldDescription(string $field): string
    {
        return match ($field) {
            'meta_title' => 'SEO title, under 60 characters.',
            'meta_description' => 'SEO description, under 155 characters.',
            'title' => 'Article title, under 180 characters.',
            'subtitle' => 'Article subtitle, under 180 characters.',
            'description' => 'Short HTML lead. Allowed tags: p, br, strong, em, a.',
            'content' => 'HTML article body. Allowed tags: p, br, h2, h3, ul, ol, li, strong, em, a, blockquote.',
            default => 'Revised text.',
        };
    }

    /**
     * @param  array<string, string>  $current
     * @param  array<int, string>  $fields
     */
    private function textPrompt(array $current, array $fields, string $instruction): string
    {
        $lines = [
            'Revise this blog article.',
            'Return only these fields: '.implode(', ', $fields).'.',
            'Keep every fact that is already in the article. Do not invent prices, dates, statistics, quotes, or offers.',
            'Keep the language of the current article. If the article is empty, write in the language of the instruction.',
            'description and content must be HTML, not Markdown.',
            '',
            'Instruction:',
            $instruction,
        ];

        $brand = $this->brand->copyGuidance();

        if ($brand !== '') {
            $lines[] = '';
            $lines[] = $brand;
            $lines[] = 'Do not invent another company.';
        }

        $lines[] = '';
        $lines[] = 'Current article:';

        foreach ($current as $field => $value) {
            $excerpt = $value;

            if (mb_strlen($excerpt) > 20000) {
                $excerpt = mb_substr($excerpt, 0, 20000);
            }

            $lines[] = strtoupper($field).':';
            $lines[] = $excerpt !== '' ? $excerpt : '(empty)';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array{aspect: string, width: int, height: int, label: string}  $spec
     */
    private function imagePrompt(array $spec, string $instruction, string $title, string $body, bool $editing, bool $logoAttached): string
    {
        $lines = [
            $editing
                ? 'Edit the attached blog image. Output one finished image.'
                : 'Create one blog image. Output one finished image.',
            'Format: '.$spec['label'].'. Aspect ratio '.$spec['aspect'].'.',
            'No watermark, no fake interface, no extra captions unless the instruction asks for exact words.',
        ];

        if ($instruction !== '') {
            $lines[] = 'Instruction: '.$instruction;
        } else {
            $lines[] = 'No separate instruction. Illustrate the article without adding claims that are not in the text.';
        }

        if ($title !== '') {
            $lines[] = 'Article title: '.$title;
        }

        if ($body !== '') {
            $lines[] = 'Article excerpt: '.$body;
        }

        $brand = $this->brand->imageGuidance($logoAttached);

        if ($brand !== '') {
            $lines[] = $brand;
            $lines[] = 'Do not invent another company, logo, or palette.';
        }

        return implode("\n", $lines);
    }

    private function referenceBlob(?string $path): ?Blob
    {
        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        try {
            $manager = new ImageManager(new Driver());
            $binary = (string) $manager
                ->read(Storage::disk('public')->path($path))
                ->scaleDown(1280, 1280)
                ->toJpeg(80);
        } catch (Throwable $e) {
            Log::info('Blog image was not sent to Gemini.', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if ($binary === '') {
            return null;
        }

        return new Blob(
            mimeType: MimeType::IMAGE_JPEG,
            data: base64_encode($binary),
        );
    }

    /**
     * @param  array<int, Blob|string>  $parts
     */
    private function complete(string $modelName, GenerationConfig $config, string $system, array $parts): string
    {
        $apiKey = (string) config('gemini.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        $last = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = \Gemini::client($apiKey)
                    ->generativeModel(model: $modelName)
                    ->withGenerationConfig($config)
                    ->withSystemInstruction(new Content(
                        parts: [new Part(text: $system)],
                    ))
                    ->generateContent($parts);

                return $this->responseText($response);
            } catch (Throwable $e) {
                $last = $e;
                $retryable = str_contains($e->getMessage(), 'high demand')
                    || str_contains($e->getMessage(), 'UNAVAILABLE')
                    || str_contains($e->getMessage(), '503');

                if ($attempt === 2 || ! $retryable) {
                    break;
                }

                sleep(2);
            }
        }

        Log::error('Gemini blog text generation failed.', [
            'message' => $last?->getMessage(),
        ]);

        throw new RuntimeException('AI generation failed: '.$last?->getMessage(), previous: $last);
    }

    private function responseText(mixed $response): string
    {
        $chunks = [];

        foreach ($response->candidates[0]->content->parts as $part) {
            if ($part->thought || ! is_string($part->text) || trim($part->text) === '') {
                continue;
            }

            $chunks[] = $part->text;
        }

        $text = trim(implode("\n", $chunks));
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;

        return trim($text);
    }
}
