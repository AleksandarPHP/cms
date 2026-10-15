<?php

namespace App\Services;

use App\Models\ContentPlan;
use App\Models\SocialPost;
use Gemini\Data\Blob;
use Gemini\Data\Content;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Part;
use Gemini\Data\Schema;
use Gemini\Data\ThinkingConfig;
use Gemini\Enums\DataType;
use Gemini\Enums\MimeType;
use Gemini\Enums\ResponseMimeType;
use Gemini\Enums\ResponseModality;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GeminiSocialAgentService
{
    public function __construct(private BrandService $brand) {}

    public function generate(SocialPost $post, string $sourceText = '', string $searchSummary = '', ?string $instruction = null, ?string $userImageInstructions = null): array
    {
        $apiKey = (string) config('gemini.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        $last = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $modelName = (string) config('gemini.model');
                $image = $this->imageBlob($post);
                $parts = [];

                if ($image instanceof Blob) {
                    $parts[] = $image;
                }

                $parts[] = $this->prompt($post, $sourceText, $searchSummary, $instruction, $image instanceof Blob, $userImageInstructions);

                $response = \Gemini::client($apiKey)
                    ->generativeModel(model: $modelName)
                    ->withGenerationConfig($this->jsonConfig($modelName))
                    ->withSystemInstruction(new Content(
                        parts: [new Part(text: 'You write social media copy and visual prompts. Captions stay in the brief language, German or English. Never write Serbian. The visual prompt is English only. Reply with JSON only. Never wrap the JSON in markdown fences.')],
                    ))
                    ->generateContent($parts);

                return $this->decode($this->responseText($response));
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

        Log::error('Gemini social generation failed.', [
            'post_id' => $post->id,
            'message' => $last?->getMessage(),
        ]);

        throw new RuntimeException('AI generation failed: '.$last?->getMessage(), previous: $last);
    }

    public function generateImage(SocialPost $post): string
    {
        $apiKey = (string) config('gemini.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        $prompt = $this->brand->withoutSerbian(trim((string) ($post->generated_image_prompt ?: $post->ai_visual_suggestion ?: $post->user_prompt)));

        if ($prompt === '') {
            throw new RuntimeException('Add a prompt before generating an image.');
        }

        try {
            $parts = [];
            $image = $this->imageBlob($post);
            $logo = $this->brand->logoReference();

            if ($image instanceof Blob) {
                $parts[] = $image;
                $parts[] = 'The image above is the photo to edit.';
            }

            foreach ($logo as $part) {
                $parts[] = $part;
            }

            $parts[] = $this->imageInstruction($prompt, $image instanceof Blob, $logo !== []);

            $response = \Gemini::client($apiKey)
                ->generativeModel(model: (string) config('gemini.image_model'))
                ->withGenerationConfig(new GenerationConfig(
                    responseModalities: [ResponseModality::TEXT, ResponseModality::IMAGE],
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

                $extension = $part->inlineData->mimeType === MimeType::IMAGE_PNG ? 'png' : 'jpg';
                $path = 'social_uploads/generated-'.Str::uuid().'.'.$extension;
                Storage::disk('public')->put($path, $binary);

                return $path;
            }

            throw new RuntimeException('Gemini did not return an image.');
        } catch (Throwable $e) {
            Log::error('Gemini image generation failed.', [
                'post_id' => $post->id,
                'message' => $e->getMessage(),
            ]);

            if ($e instanceof RuntimeException && $e->getMessage() === 'Gemini did not return an image.') {
                throw $e;
            }

            throw new RuntimeException('The image could not be generated: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * @param  Collection<int, SocialPost>  $posts
     * @param  array<int, string>  $previousTitles
     * @return array<int, array{title: string, fb_content: string, ig_content: string, hashtags: array<int, string>, generated_image_prompt: string}>
     */
    public function generatePlanBatch(ContentPlan $plan, Collection $posts, string $researchSummary, array $previousTitles): array
    {
        if ($posts->isEmpty()) {
            return [];
        }

        $modelName = (string) config('gemini.model');
        $json = $this->complete(
            $modelName,
            $this->planBatchConfig($modelName),
            'You write a batch of social media posts. Captions stay in the topic language, German or English. Never write Serbian. Each visual prompt is English only. Reply with JSON only. Never wrap the JSON in markdown fences.',
            [$this->planBatchPrompt($plan, $posts, $researchSummary, $previousTitles)],
        );

        return $this->decodePlanBatch($json);
    }

    public function regenerateImagePrompt(SocialPost $post): string
    {
        $modelName = (string) config('gemini.model');
        $json = $this->complete(
            $modelName,
            $this->imagePromptConfig($modelName),
            'You write one English visual prompt for a social image. Do not translate it. Never use Serbian. Reply with JSON only.',
            [$this->imagePromptRequest($post)],
        );
        $decoded = json_decode($json, true);
        $prompt = $this->brand->withoutSerbian(trim((string) (is_array($decoded) ? ($decoded['generated_image_prompt'] ?? '') : '')));

        if ($prompt === '') {
            throw new RuntimeException('Gemini did not return an image prompt.');
        }

        return $prompt;
    }

    private function jsonConfig(string $modelName): GenerationConfig
    {
        return new GenerationConfig(
            maxOutputTokens: 4096,
            temperature: 0.7,
            responseMimeType: ResponseMimeType::APPLICATION_JSON,
            responseSchema: new Schema(
                type: DataType::OBJECT,
                properties: [
                    'fb_content' => new Schema(type: DataType::STRING),
                    'ig_content' => new Schema(type: DataType::STRING),
                    'hashtags' => new Schema(
                        type: DataType::ARRAY,
                        items: new Schema(type: DataType::STRING),
                    ),
                    'generated_image_prompt' => new Schema(type: DataType::STRING),
                ],
                required: ['fb_content', 'ig_content', 'hashtags', 'generated_image_prompt'],
            ),
            thinkingConfig: str_contains($modelName, '2.5')
                ? new ThinkingConfig(includeThoughts: false, thinkingBudget: 0)
                : null,
        );
    }

    private function prompt(SocialPost $post, string $sourceText, string $searchSummary, ?string $instruction, bool $hasImage, ?string $userImageInstructions = null): string
    {
        $platforms = $post->platforms ?: ['facebook', 'instagram'];
        $visualInstructions = trim((string) ($userImageInstructions ?? $post->user_image_instructions));
        $lines = [
            'Write social copy from the brief below.',
            'Tone: '.($post->tone ?: 'professional').'.',
            'Platforms: '.implode(', ', $platforms).'.',
        ];

        $postType = (string) $post->post_type;

        if ($postType !== '' && isset(ContentPlan::POST_TYPES[$postType])) {
            $lines[] = 'Post type: '.ContentPlan::POST_TYPES[$postType].'. '.ContentPlan::POST_TYPE_GUIDANCE[$postType];
            $lines[] = 'Keep the tone above. Let the post type decide the job of this copy.';
        }

        $lines = array_merge($lines, [
            'Write captions in the same language as the brief. The brief is German or English. Never write Serbian.',
            'Facebook copy should stay concise, ideally under 500 characters.',
            'Instagram copy may be longer, but stay under 2200 characters.',
            'Put hashtags only in the hashtags array, without the # character, lowercase, between 4 and 8 tags.',
            'Do not invent prices, dates, or offers in the captions unless they are present in the brief, source, or image instructions.',
            'generated_image_prompt must be one English visual instruction. Do not translate it and do not add a second language.',
            'When on-image text is requested, quote those exact words in the brief language and say which brand font and color to use.',
            'Include badge placement, layout, and background style inside generated_image_prompt.',
            '',
            'Brief:',
            (string) $post->user_prompt,
        ]);

        $brand = $this->brand->copyGuidance();

        if ($brand !== '') {
            $lines[] = '';
            $lines[] = $brand;
            $lines[] = 'Do not invent another company, logo, or palette.';
        }

        if ($visualInstructions !== '') {
            $lines[] = '';
            $lines[] = 'User image instructions:';
            $lines[] = $visualInstructions;
        }

        if ($hasImage && $visualInstructions !== '') {
            $lines[] = '';
            $lines[] = 'An image is attached. Study the product, colors, objects, and composition.';
            $lines[] = 'Use those visual details as the foundation of both captions.';
            $lines[] = 'Write generated_image_prompt as an image-editing instruction: keep the real product recognizable and apply only the changes the user asked for.';
        } elseif (! $hasImage && ($sourceText !== '' || $visualInstructions !== '' || filled($post->source_url))) {
            $lines[] = '';
            $lines[] = 'No image was uploaded.';
            $lines[] = 'Write generated_image_prompt as a text-to-image instruction for a new social visual.';
            $lines[] = 'Base it on the brief, the source page, and the user image instructions.';
            $lines[] = 'When the user asks for a label, quote that exact text, its placement, and the background style.';
        } elseif ($hasImage) {
            $lines[] = '';
            $lines[] = 'An image is attached. Study its colors, objects, setting, and mood, and use those visual details as the foundation of both captions.';
            $lines[] = 'Write generated_image_prompt as a faithful description of the attached image. Do not add text or change the scene.';
        } else {
            $lines[] = '';
            $lines[] = 'No image was uploaded. Write generated_image_prompt as a text-to-image instruction based on the brief: subject, composition, lighting, colors, and mood.';
        }

        if (! in_array('facebook', $platforms, true)) {
            $lines[] = 'Facebook is not selected. Return an empty string for fb_content.';
        }

        if (! in_array('instagram', $platforms, true)) {
            $lines[] = 'Instagram is not selected. Return an empty string for ig_content.';
        }

        if ($sourceText !== '') {
            $lines[] = '';
            $lines[] = 'Source page text:';
            $lines[] = $sourceText;
        }

        if ($searchSummary !== '') {
            $lines[] = '';
            $lines[] = 'Web trend notes:';
            $lines[] = $searchSummary;
        }

        if ($instruction) {
            $lines[] = '';
            $lines[] = 'Revise the current draft with this instruction: '.$instruction;
            $lines[] = 'Current Facebook: '.(string) $post->fb_content;
            $lines[] = 'Current Instagram: '.(string) $post->ig_content;
        }

        return implode("\n", $lines);
    }

    private function imageInstruction(string $prompt, bool $editing, bool $logoAttached): string
    {
        $lines = [
            $editing
                ? 'Edit the photo. Keep the product recognizable. Change only what the instruction requests.'
                : 'Create a square 1:1 social media image.',
            'Visual instruction:',
            $prompt,
        ];
        $brand = $this->brand->imageGuidance($logoAttached);

        if ($brand !== '') {
            $lines[] = $brand;
        }

        $lines[] = 'On-image text may be German or English only, and only the words quoted in the instruction. Do not add Serbian or any other language.';

        return implode("\n", $lines);
    }

    private function imageBlob(SocialPost $post): ?Blob
    {
        if (! $post->image_path || ! Storage::disk('public')->exists($post->image_path)) {
            return null;
        }

        $absolute = Storage::disk('public')->path($post->image_path);
        $mime = mime_content_type($absolute) ?: 'image/jpeg';
        $binary = file_get_contents($absolute);

        if ($binary === false || $binary === '') {
            return null;
        }

        $enum = match ($mime) {
            'image/png' => MimeType::IMAGE_PNG,
            'image/webp' => MimeType::IMAGE_WEBP,
            'image/gif' => null,
            default => MimeType::IMAGE_JPEG,
        };

        if ($enum === null && function_exists('imagecreatefromgif')) {
            $image = @imagecreatefromgif($absolute);

            if ($image !== false) {
                ob_start();
                imagejpeg($image, null, 85);
                $encoded = ob_get_clean();
                imagedestroy($image);

                if (is_string($encoded) && $encoded !== '') {
                    $binary = $encoded;
                    $enum = MimeType::IMAGE_JPEG;
                }
            }
        }

        if (! $enum instanceof MimeType) {
            return null;
        }

        return new Blob(
            mimeType: $enum,
            data: base64_encode($binary),
        );
    }

    private function planBatchConfig(string $modelName): GenerationConfig
    {
        $post = new Schema(
            type: DataType::OBJECT,
            properties: [
                'position' => new Schema(type: DataType::INTEGER),
                'title' => new Schema(type: DataType::STRING),
                'fb_content' => new Schema(type: DataType::STRING),
                'ig_content' => new Schema(type: DataType::STRING),
                'hashtags' => new Schema(
                    type: DataType::ARRAY,
                    items: new Schema(type: DataType::STRING),
                ),
                'generated_image_prompt' => new Schema(type: DataType::STRING),
            ],
            required: ['position', 'title', 'fb_content', 'ig_content', 'hashtags', 'generated_image_prompt'],
        );

        return new GenerationConfig(
            maxOutputTokens: 8192,
            temperature: 0.7,
            responseMimeType: ResponseMimeType::APPLICATION_JSON,
            responseSchema: new Schema(
                type: DataType::OBJECT,
                properties: [
                    'posts' => new Schema(
                        type: DataType::ARRAY,
                        items: $post,
                    ),
                ],
                required: ['posts'],
            ),
            thinkingConfig: str_contains($modelName, '2.5')
                ? new ThinkingConfig(includeThoughts: false, thinkingBudget: 0)
                : null,
        );
    }

    private function imagePromptConfig(string $modelName): GenerationConfig
    {
        return new GenerationConfig(
            maxOutputTokens: 1024,
            temperature: 0.5,
            responseMimeType: ResponseMimeType::APPLICATION_JSON,
            responseSchema: new Schema(
                type: DataType::OBJECT,
                properties: [
                    'generated_image_prompt' => new Schema(type: DataType::STRING),
                ],
                required: ['generated_image_prompt'],
            ),
            thinkingConfig: str_contains($modelName, '2.5')
                ? new ThinkingConfig(includeThoughts: false, thinkingBudget: 0)
                : null,
        );
    }

    /**
     * @param  Collection<int, SocialPost>  $posts
     * @param  array<int, string>  $previousTitles
     */
    private function planBatchPrompt(ContentPlan $plan, Collection $posts, string $researchSummary, array $previousTitles): string
    {
        $platforms = $plan->platforms ?: ['facebook', 'instagram'];
        $lines = [
            'Write '.$posts->count().' distinct social posts for one content plan.',
            'Topic: '.trim((string) $plan->topic),
            'Tone: '.($plan->tone ?: 'professional').'.',
            'Platforms: '.implode(', ', $platforms).'.',
            'Write captions in the same language as the topic. The topic is German or English. Never write Serbian.',
            'Facebook copy should stay concise, ideally under 500 characters.',
            'Instagram copy may be longer, but stay under 2200 characters.',
            'Put hashtags only in the hashtags array, without the # character, lowercase, between 4 and 8 tags.',
            'Do not invent prices, dates, or offers unless they are in the topic or research notes.',
            'Each title is a short angle, not a repeat of the topic and not a repeat of another title.',
            'generated_image_prompt is one English visual instruction for a new square social image. Do not translate it.',
            'Use the brand colors and fonts in that visual instruction. Do not invent another company, logo, or palette.',
            'Return one post for every position listed below. Copy the position number exactly.',
        ];

        if (! in_array('facebook', $platforms, true)) {
            $lines[] = 'Facebook is not selected. Return an empty string for fb_content.';
        }

        if (! in_array('instagram', $platforms, true)) {
            $lines[] = 'Instagram is not selected. Return an empty string for ig_content.';
        }

        $brand = $this->brand->copyGuidance();

        if ($brand !== '') {
            $lines[] = '';
            $lines[] = $brand;
        }

        $previousTitles = array_values(array_filter(array_map(
            fn ($title) => trim((string) $title),
            $previousTitles,
        )));

        if ($previousTitles !== []) {
            $lines[] = '';
            $lines[] = 'Already used titles. Do not reuse these angles:';
            $lines[] = implode("\n", $previousTitles);
        }

        $researchSummary = trim($researchSummary);

        if ($researchSummary !== '') {
            $lines[] = '';
            $lines[] = 'Research notes. Use them only when they support the topic:';
            $lines[] = $researchSummary;
        }

        $lines[] = '';
        $lines[] = 'Posts:';

        foreach ($posts as $post) {
            $type = (string) $post->post_type;
            $label = ContentPlan::POST_TYPES[$type] ?? 'General';
            $guidance = ContentPlan::POST_TYPE_GUIDANCE[$type] ?? 'Stay on the topic.';
            $line = 'Position '.$post->plan_position.': '.$label.'. '.$guidance;

            if ($post->scheduled_at) {
                $line .= ' Publish '.$post->scheduled_at->timezone($post->timezone ?: 'Europe/Zurich')->format('l j F').'.';
            }

            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    private function imagePromptRequest(SocialPost $post): string
    {
        $lines = [
            'Write a new English visual prompt for this social post.',
            'Describe subject, composition, lighting, colors, and mood.',
            'Do not add a translation.',
            'On-image text only if the brief asks for it. Quote those words in the brief language.',
            '',
            'Brief:',
            (string) ($post->user_prompt ?: $post->post_title),
        ];
        $brand = $this->brand->copyGuidance();

        if ($brand !== '') {
            $lines[] = '';
            $lines[] = $brand;
            $lines[] = 'Use these brand colors and fonts. Do not invent another logo or palette.';
        }

        if (trim((string) $post->fb_content) !== '' || trim((string) $post->ig_content) !== '') {
            $lines[] = '';
            $lines[] = 'The image should support this copy:';
            $lines[] = trim((string) ($post->fb_content ?: $post->ig_content));
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<int, array{title: string, fb_content: string, ig_content: string, hashtags: array<int, string>, generated_image_prompt: string}>
     */
    private function decodePlanBatch(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini returned an unreadable response.');
        }

        $rows = $decoded['posts'] ?? (array_is_list($decoded) ? $decoded : []);

        if (! is_array($rows)) {
            throw new RuntimeException('Gemini returned an unreadable response.');
        }

        $posts = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $position = (int) ($row['position'] ?? 0);
            $title = trim((string) ($row['title'] ?? ''));
            $facebook = trim((string) ($row['fb_content'] ?? ''));
            $instagram = trim((string) ($row['ig_content'] ?? ''));

            if ($position < 1 || ($title === '' && $facebook === '' && $instagram === '')) {
                continue;
            }

            $visualPrompt = $this->brand->withoutSerbian(trim((string) ($row['generated_image_prompt'] ?? '')));
            $posts[$position] = [
                'title' => $title,
                'fb_content' => $facebook,
                'ig_content' => $instagram,
                'hashtags' => $this->hashtags($row['hashtags'] ?? []),
                'generated_image_prompt' => $visualPrompt,
            ];
        }

        if ($posts === []) {
            throw new RuntimeException('Gemini returned no posts for this batch.');
        }

        return $posts;
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

        Log::error('Gemini social generation failed.', [
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

    private function decode(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini returned an unreadable response.');
        }

        $visualPrompt = $this->brand->withoutSerbian(trim((string) ($decoded['generated_image_prompt'] ?? $decoded['ai_visual_suggestion'] ?? '')));

        return [
            'fb_content' => trim((string) ($decoded['fb_content'] ?? '')),
            'ig_content' => trim((string) ($decoded['ig_content'] ?? '')),
            'hashtags' => $this->hashtags($decoded['hashtags'] ?? []),
            'generated_image_prompt' => $visualPrompt,
            'ai_visual_suggestion' => $visualPrompt,
        ];
    }

    private function hashtags(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\s,]+/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        $tags = [];

        foreach ($value as $tag) {
            $tag = strtolower(trim(ltrim((string) $tag, '#')));
            $tag = preg_replace('/\s+/', '', $tag) ?? '';

            if ($tag !== '' && ! in_array($tag, $tags, true)) {
                $tags[] = $tag;
            }
        }

        return array_slice($tags, 0, 12);
    }
}
