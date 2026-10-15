<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Newsletter extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PUBLISHED = 'published';

    public const TIMEZONE = 'Europe/Zurich';

    protected $fillable = [
        'user_id',
        'issue_number',
        'locale',
        'title',
        'text',
        'status',
        'step',
        'scheduled_at',
        'published_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function blogs(): BelongsToMany
    {
        return $this->belongsToMany(Blog::class, 'newsletter_blog')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function wizardUrl(): string
    {
        if ($this->isPublished()) {
            return route('newsletter.review', $this);
        }

        if ($this->status === self::STATUS_SCHEDULED) {
            return route('newsletter.publish', $this);
        }

        if ((int) $this->step >= 3 && $this->title && $this->text) {
            return route('newsletter.review', $this);
        }

        if ((int) $this->step >= 2) {
            return route('newsletter.content', $this);
        }

        return route('newsletter.blogs', $this);
    }
}
