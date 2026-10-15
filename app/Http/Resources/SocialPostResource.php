<?php

namespace App\Http\Resources;

use App\Services\SocialPostService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialPostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'content_plan_id' => $this->content_plan_id,
            'plan_position' => $this->plan_position,
            'post_title' => $this->post_title,
            'status' => $this->status,
            'user_prompt' => $this->user_prompt,
            'source_url' => $this->source_url,
            'image_path' => $this->image_path,
            'image_url' => app(SocialPostService::class)->imageUrl($this->resource),
            'enable_web_search' => $this->enable_web_search,
            'tone' => $this->tone,
            'platforms' => $this->platforms ?? [],
            'fb_content' => $this->fb_content,
            'ig_content' => $this->ig_content,
            'hashtags' => $this->hashtags ?? [],
            'ai_visual_suggestion' => $this->ai_visual_suggestion,
            'user_image_instructions' => $this->user_image_instructions,
            'generated_image_prompt' => $this->generated_image_prompt,
            'scheduled_at' => $this->scheduled_at,
            'published_at' => $this->published_at,
            'error_message' => $this->error_message,
            'timezone' => $this->timezone,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
