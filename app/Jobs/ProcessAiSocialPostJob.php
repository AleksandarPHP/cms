<?php

namespace App\Jobs;

use App\Models\SocialPost;
use App\Services\GeminiSocialAgentService;
use App\Services\SocialPostService;
use App\Services\WebScraperService;
use App\Services\WebSearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAiSocialPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    public function __construct(
        public SocialPost $post,
        public ?string $instruction = null,
    ) {}

    public function handle(
        WebScraperService $scraper,
        WebSearchService $search,
        GeminiSocialAgentService $agent,
        SocialPostService $posts,
    ): void {
        set_time_limit(180);
        $post = $this->post->fresh();

        if (! $post) {
            return;
        }

        try {
            $sourceText = '';
            $searchSummary = '';

            if (is_string($post->source_url) && $post->source_url !== '') {
                $sourceText = $scraper->extract($post->source_url);
            }

            if ($post->enable_web_search) {
                $searchSummary = $search->summarize((string) $post->user_prompt);
            }

            $generated = $agent->generate(
                $post,
                $sourceText,
                $searchSummary,
                $this->instruction,
                $post->user_image_instructions,
            );

            $post->fb_content = $generated['fb_content'];
            $post->ig_content = $generated['ig_content'];
            $post->hashtags = $generated['hashtags'];
            $post->generated_image_prompt = $generated['generated_image_prompt'];
            $post->ai_visual_suggestion = $generated['ai_visual_suggestion'];
            $post->status = SocialPost::STATUS_PENDING;
            $post->error_message = null;
            $post->save();

            if (trim((string) $post->user_image_instructions) !== '') {
                $posts->generateImage($post->fresh(), $agent);
            }
        } catch (Throwable $e) {
            Log::error('ProcessAiSocialPostJob failed.', [
                'post_id' => $post->id,
                'message' => $e->getMessage(),
            ]);

            $post->status = SocialPost::STATUS_FAILED;
            $post->error_message = $e->getMessage();
            $post->save();
        }
    }
}
