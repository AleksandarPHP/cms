<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Newsletter;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class NewsletterService
{
    public function start(User $user): Newsletter
    {
        $locale = app()->getLocale();

        if (! in_array($locale, ['de', 'fr', 'it'], true)) {
            $locale = 'de';
        }

        return Newsletter::query()->create([
            'user_id' => $user->id,
            'issue_number' => ((int) Newsletter::query()->max('issue_number')) + 1,
            'locale' => $locale,
            'status' => Newsletter::STATUS_DRAFT,
            'step' => 1,
        ]);
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function saveBlogs(Newsletter $newsletter, array $ids): void
    {
        $this->guardEditable($newsletter);

        $allowed = Blog::query()
            ->whereIn('id', $ids)
            ->where(function ($query) use ($newsletter) {
                $query->where('is_active', true)
                    ->orWhereIn('id', $newsletter->blogs()->pluck('blogs.id'));
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ordered = [];

        foreach ($ids as $id) {
            $id = (int) $id;

            if (in_array($id, $allowed, true) && ! in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }

        if ($ordered === []) {
            throw ValidationException::withMessages([
                'blog_ids' => __('app.newsletter_need_blogs'),
            ]);
        }

        $sync = [];

        foreach ($ordered as $position => $id) {
            $sync[$id] = ['position' => $position];
        }

        $newsletter->blogs()->sync($sync);
        $newsletter->step = max(2, (int) $newsletter->step);
        $newsletter->save();
    }

    public function saveContent(Newsletter $newsletter, string $title, string $text): void
    {
        $this->guardEditable($newsletter);
        $this->guardBlogs($newsletter);

        $newsletter->title = $title;
        $newsletter->text = $text;
        $newsletter->step = max(3, (int) $newsletter->step);
        $newsletter->save();
    }

    public function publishNow(Newsletter $newsletter): void
    {
        $this->guardReady($newsletter);

        $newsletter->status = Newsletter::STATUS_PUBLISHED;
        $newsletter->published_at = now();
        $newsletter->scheduled_at = null;
        $newsletter->step = 4;
        $newsletter->save();
    }

    public function schedule(Newsletter $newsletter, string $localDateTime): void
    {
        $this->guardReady($newsletter);

        $moment = Carbon::createFromFormat('Y-m-d\TH:i', $localDateTime, Newsletter::TIMEZONE);

        if ($moment === false || $moment->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages([
                'scheduled_at' => __('app.newsletter_schedule_future'),
            ]);
        }

        $newsletter->status = Newsletter::STATUS_SCHEDULED;
        $newsletter->scheduled_at = $moment->utc();
        $newsletter->published_at = null;
        $newsletter->step = 4;
        $newsletter->save();
    }

    public function publishDue(): int
    {
        $due = Newsletter::query()
            ->where('status', Newsletter::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('id')
            ->get();

        $count = 0;

        foreach ($due as $newsletter) {
            $count += Newsletter::query()
                ->whereKey($newsletter->id)
                ->where('status', Newsletter::STATUS_SCHEDULED)
                ->update([
                    'status' => Newsletter::STATUS_PUBLISHED,
                    'published_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return $count;
    }

    public function guardEditable(Newsletter $newsletter): void
    {
        if ($newsletter->isPublished()) {
            throw ValidationException::withMessages([
                'newsletter' => __('app.newsletter_locked'),
            ]);
        }
    }

    public function guardBlogs(Newsletter $newsletter): void
    {
        if (! $newsletter->blogs()->exists()) {
            throw ValidationException::withMessages([
                'blog_ids' => __('app.newsletter_need_blogs'),
            ]);
        }
    }

    public function guardReady(Newsletter $newsletter): void
    {
        $this->guardEditable($newsletter);
        $this->guardBlogs($newsletter);

        if (trim((string) $newsletter->title) === '' || trim((string) $newsletter->text) === '') {
            throw ValidationException::withMessages([
                'title' => __('app.newsletter_need_content'),
            ]);
        }
    }
}
