<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPost extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PENDING = 'pending_approval';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'content_plan_id',
        'plan_position',
        'post_title',
        'generation_attempts',
        'status',
        'user_prompt',
        'source_url',
        'image_path',
        'enable_web_search',
        'tone',
        'post_type',
        'fb_content',
        'ig_content',
        'hashtags',
        'ai_visual_suggestion',
        'user_image_instructions',
        'generated_image_prompt',
        'platforms',
        'timezone',
        'meta_response',
        'scheduled_at',
        'published_at',
        'error_message',
    ];

    protected $casts = [
        'enable_web_search' => 'boolean',
        'hashtags' => 'array',
        'platforms' => 'array',
        'meta_response' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'generation_attempts' => 'integer',
        'plan_position' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contentPlan(): BelongsTo
    {
        return $this->belongsTo(ContentPlan::class);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now());
    }
}
