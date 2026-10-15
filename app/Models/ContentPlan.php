<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentPlan extends Model
{
    public const STATUS_GENERATING = 'generating';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_GENERATING => 'Generating',
        self::STATUS_READY => 'Ready for review',
        self::STATUS_FAILED => 'Needs attention',
    ];

    public const WEEKDAYS = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
    ];

    public const WEEKDAY_SHORT = [
        'monday' => 'Mon',
        'tuesday' => 'Tue',
        'wednesday' => 'Wed',
        'thursday' => 'Thu',
        'friday' => 'Fri',
        'saturday' => 'Sat',
        'sunday' => 'Sun',
    ];

    public const POST_TYPES = [
        'educational' => 'Educational',
        'promotional' => 'Promotional',
        'engagement' => 'Engagement',
        'announcement' => 'Announcement',
        'storytelling' => 'Storytelling',
        'behind_the_scenes' => 'Behind the scenes',
        'social_proof' => 'Social proof',
    ];

    public const POST_TYPE_GUIDANCE = [
        'educational' => 'Teach a useful tip or idea. Avoid a hard sell.',
        'promotional' => 'Present a product, offer, or clear reason to act.',
        'engagement' => 'Invite a reply, question, or other participation.',
        'announcement' => 'Share news or a clear update.',
        'storytelling' => 'Tell a short story or scene connected to the topic.',
        'behind_the_scenes' => 'Show how the work, product, or team actually happens.',
        'social_proof' => 'Use a customer result or real example only when the brief supports it. Do not invent testimonials.',
    ];

    protected $fillable = [
        'user_id',
        'title',
        'topic',
        'duration_months',
        'posts_per_week',
        'publish_days',
        'day_post_types',
        'publish_time',
        'tone',
        'platforms',
        'timezone',
        'enable_web_search',
        'status',
        'research_summary',
        'posts_expected',
        'failure_count',
        'error_message',
        'batch_locked_at',
    ];

    protected $casts = [
        'publish_days' => 'array',
        'day_post_types' => 'array',
        'platforms' => 'array',
        'enable_web_search' => 'boolean',
        'duration_months' => 'integer',
        'posts_per_week' => 'integer',
        'posts_expected' => 'integer',
        'failure_count' => 'integer',
        'batch_locked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(SocialPost::class)->orderBy('plan_position');
    }
}
