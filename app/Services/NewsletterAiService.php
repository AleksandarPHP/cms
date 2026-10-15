<?php

namespace App\Services;

use App\Models\Newsletter;
use Gemini\Data\Content;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Part;
use Gemini\Data\Schema;
use Gemini\Data\ThinkingConfig;
use Gemini\Enums\DataType;
use Gemini\Enums\ResponseMimeType;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class NewsletterAiService
{
    public function generate(Newsletter $newsletter, string $instruction): array
    {
        set_time_limit(180);

        $newsletter->load('blogs');

        if ($newsletter->blogs->isEmpty()) {
            throw new RuntimeException('Select at least one blog first.');
        }

        $modelName = (string) config('gemini.model');
        $json = $this->complete(
            $modelName,
            $this->config($modelName),
            'You write newsletter subject lines and short introductions. Keep the language of the blog posts. Reply with JSON only. Never wrap the JSON in markdown fences.',
            [$this->prompt($newsletter, trim($instruction))],
        );

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini returned an unreadable response.');
        }

        $title = $this->clean((string) ($decoded['title'] ?? ''), 191);
        $text = $this->clean((string) ($decoded['text'] ?? ''), 5000);

        if ($title === '' || $text === '') {
            throw new RuntimeException('Gemini did not return a title and text.');
        }

        return [
            'title' => $title,
            'text' => $text,
        ];
    }

    private function config(string $modelName): GenerationConfig
    {
        return new GenerationConfig(
            maxOutputTokens: 2048,
            temperature: 0.7,
            responseMimeType: ResponseMimeType::APPLICATION_JSON,
            responseSchema: new Schema(
                type: DataType::OBJECT,
                properties: [
                    'title' => new Schema(
                        type: DataType::STRING,
                        description: 'Newsletter subject, under 90 characters.',
                    ),
                    'text' => new Schema(
                        type: DataType::STRING,
                        description: 'Plain-text introduction of 2 to 4 sentences. No HTML and no Markdown.',
                    ),
                ],
                required: ['title', 'text'],
            ),
            thinkingConfig: str_contains($modelName, '2.5')
                ? new ThinkingConfig(includeThoughts: false, thinkingBudget: 0)
                : null,
        );
    }

    private function prompt(Newsletter $newsletter, string $instruction): string
    {
        $lines = [
            'Write a newsletter title and a short introduction for the blog posts below.',
            'Keep every fact that is already in the posts. Do not invent prices, dates, statistics, quotes, or offers.',
            'Write in the language of the blog posts.',
            'Title: one line, under 90 characters.',
            'Text: 2 to 4 sentences of plain text. No HTML, no Markdown.',
            '',
            'Instruction:',
            $instruction !== '' ? $instruction : 'Connect the selected posts in one introduction.',
            '',
            'Selected posts:',
        ];

        foreach ($newsletter->blogs as $blog) {
            $lines[] = '- Title: '.$this->plain((string) $blog->title);
            $lines[] = '  Subtitle: '.$this->plain((string) $blog->subtitle);
            $excerpt = $this->plain((string) $blog->description);

            if ($excerpt !== '') {
                $lines[] = '  Excerpt: '.$excerpt;
            }
        }

        return implode("\n", $lines);
    }

    private function plain(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        if (mb_strlen($value) > 1200) {
            $value = rtrim(mb_substr($value, 0, 1200));
        }

        return $value;
    }

    private function clean(string $value, int $limit): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');

        if (mb_strlen($value) > $limit) {
            $value = rtrim(mb_substr($value, 0, $limit));
        }

        return $value;
    }

    /**
     * @param  array<int, string>  $parts
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

        Log::error('Gemini newsletter generation failed.', [
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
