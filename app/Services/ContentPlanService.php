<?php

namespace App\Services;

use App\Models\ContentPlan;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ContentPlanService
{
    private const DAY_INDEX = [
        'monday' => Carbon::MONDAY,
        'tuesday' => Carbon::TUESDAY,
        'wednesday' => Carbon::WEDNESDAY,
        'thursday' => Carbon::THURSDAY,
        'friday' => Carbon::FRIDAY,
        'saturday' => Carbon::SATURDAY,
        'sunday' => Carbon::SUNDAY,
    ];

    private const DEFAULT_DAYS = [
        1 => ['wednesday'],
        2 => ['tuesday', 'thursday'],
        3 => ['monday', 'wednesday', 'friday'],
        4 => ['monday', 'tuesday', 'thursday', 'friday'],
        5 => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        6 => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
        7 => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
    ];

    public function __construct(
        private SocialPostService $posts,
        private GeminiSocialAgentService $agent,
        private WebSearchService $search,
    ) {}

    public function create(array $data, ?int $userId): ContentPlan
    {
        $days = $this->normalizeDays($data['publish_days'] ?? []);
        $dayTypes = $this->resolvedDayPostTypes($days, $data['day_post_types'] ?? []);
        $postsPerWeek = $days !== [] ? count($days) : (int) $data['posts_per_week'];
        $timezone = $data['timezone'] ?? 'Europe/Zurich';
        $time = $data['publish_time'];
        $platforms = array_values($data['platforms'] ?? []);

        if ($platforms === []) {
            $platforms = [SocialAccount::FACEBOOK, SocialAccount::INSTAGRAM];
        }

        $slots = $this->buildSlots((int) $data['duration_months'], $postsPerWeek, $days, $time, $timezone);

        if ($slots === []) {
            throw ValidationException::withMessages([
                'duration_months' => 'No publishing times fall inside this plan. Choose an earlier time or another day.',
            ]);
        }

        return DB::transaction(function () use ($data, $userId, $days, $dayTypes, $postsPerWeek, $timezone, $time, $platforms, $slots) {
            $plan = ContentPlan::query()->create([
                'user_id' => $userId,
                'title' => $this->title((string) $data['topic']),
                'topic' => trim((string) $data['topic']),
                'duration_months' => (int) $data['duration_months'],
                'posts_per_week' => $postsPerWeek,
                'publish_days' => $days,
                'day_post_types' => $dayTypes !== [] ? $dayTypes : null,
                'publish_time' => $time,
                'tone' => $data['tone'] ?? 'professional',
                'platforms' => $platforms,
                'timezone' => $timezone,
                'enable_web_search' => (bool) ($data['enable_web_search'] ?? true),
                'status' => ContentPlan::STATUS_GENERATING,
                'posts_expected' => count($slots),
            ]);

            foreach ($slots as $index => $moment) {
                $postType = $this->postTypeForMoment($moment, $timezone, $dayTypes);

                $plan->posts()->create([
                    'user_id' => $userId,
                    'status' => SocialPost::STATUS_PROCESSING,
                    'user_prompt' => $this->brief($plan, '', $postType),
                    'enable_web_search' => false,
                    'tone' => $plan->tone,
                    'post_type' => $postType,
                    'platforms' => $platforms,
                    'timezone' => $timezone,
                    'scheduled_at' => $moment,
                    'plan_position' => $index + 1,
                ]);
            }

            return $plan->fresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function generateNextBatch(ContentPlan $plan): array
    {
        $plan->refresh();

        if ($plan->status !== ContentPlan::STATUS_GENERATING) {
            return $this->present($plan, false);
        }

        $locked = ContentPlan::query()
            ->whereKey($plan->id)
            ->where('status', ContentPlan::STATUS_GENERATING)
            ->where(function ($query) {
                $query->whereNull('batch_locked_at')
                    ->orWhere('batch_locked_at', '<', now()->subMinutes(3));
            })
            ->update(['batch_locked_at' => now()]);

        if ($locked === 0) {
            return $this->present($plan->fresh(), false);
        }

        $plan->refresh();

        try {
            if ($plan->enable_web_search && $plan->research_summary === null) {
                $plan->research_summary = $this->search->summarize($plan->topic);
                $plan->save();
            }

            $batch = $plan->posts()
                ->where('status', SocialPost::STATUS_PROCESSING)
                ->orderBy('plan_position')
                ->limit($this->batchSize())
                ->get();

            if ($batch->isEmpty()) {
                $this->finish($plan);

                return $this->present($plan->fresh(), true);
            }

            $previousTitles = $plan->posts()
                ->whereNotNull('post_title')
                ->orderBy('plan_position')
                ->pluck('post_title')
                ->all();

            $generated = $this->agent->generatePlanBatch(
                $plan,
                $batch,
                (string) $plan->research_summary,
                $previousTitles,
            );

            foreach ($batch as $post) {
                $item = $generated[$post->plan_position] ?? null;

                if (! is_array($item)) {
                    $this->markMissed($post);

                    continue;
                }

                $post->post_title = $item['title'];
                $post->user_prompt = $this->brief($plan, $item['title'], $post->post_type);
                $post->fb_content = $item['fb_content'];
                $post->ig_content = $item['ig_content'];
                $post->hashtags = $item['hashtags'];
                $post->generated_image_prompt = $item['generated_image_prompt'] ?: null;
                $post->ai_visual_suggestion = $item['generated_image_prompt'] ?: null;
                $post->status = SocialPost::STATUS_PENDING;
                $post->error_message = null;
                $post->generation_attempts = 0;
                $post->save();
            }

            $plan->failure_count = 0;
            $plan->error_message = null;
            $this->finish($plan);
        } catch (Throwable $e) {
            Log::error('Content plan batch failed.', [
                'plan_id' => $plan->id,
                'message' => $e->getMessage(),
            ]);

            $plan->refresh();
            $plan->failure_count = (int) $plan->failure_count + 1;
            $plan->error_message = $e->getMessage();

            if ($plan->failure_count >= $this->maxFailures()) {
                $plan->posts()
                    ->where('status', SocialPost::STATUS_PROCESSING)
                    ->update([
                        'status' => SocialPost::STATUS_DRAFT,
                        'error_message' => 'This draft could not be generated. Edit it or try generation again.',
                    ]);
                $plan->status = ContentPlan::STATUS_FAILED;
            }

            $plan->save();
        } finally {
            ContentPlan::query()->whereKey($plan->id)->update(['batch_locked_at' => null]);
        }

        return $this->present($plan->fresh(), true);
    }

    public function retry(ContentPlan $plan): ContentPlan
    {
        $plan->posts()
            ->where('status', SocialPost::STATUS_DRAFT)
            ->where(function ($query) {
                $query->whereNull('fb_content')->orWhere('fb_content', '');
            })
            ->where(function ($query) {
                $query->whereNull('ig_content')->orWhere('ig_content', '');
            })
            ->update([
                'status' => SocialPost::STATUS_PROCESSING,
                'error_message' => null,
                'generation_attempts' => 0,
            ]);

        $stillOpen = $plan->posts()->where('status', SocialPost::STATUS_PROCESSING)->exists();

        $plan->status = $stillOpen ? ContentPlan::STATUS_GENERATING : ContentPlan::STATUS_READY;
        $plan->failure_count = 0;
        $plan->error_message = null;
        $plan->batch_locked_at = null;
        $plan->save();

        return $plan->fresh();
    }

    public function updatePost(ContentPlan $plan, SocialPost $post, array $data): SocialPost
    {
        $this->owned($plan, $post);
        $this->ensureEditable($post);

        if (array_key_exists('post_title', $data)) {
            $post->post_title = $data['post_title'] !== '' ? $data['post_title'] : null;
            $post->user_prompt = $this->brief($plan, (string) $post->post_title, $post->post_type);
        }

        if (array_key_exists('facebook', $data)) {
            $post->fb_content = $data['facebook'];
        }

        if (array_key_exists('instagram', $data)) {
            $post->ig_content = $data['instagram'];
        }

        if (array_key_exists('hashtags', $data)) {
            $post->hashtags = array_values($data['hashtags'] ?? []);
        }

        if (array_key_exists('generated_image_prompt', $data)) {
            $prompt = trim((string) $data['generated_image_prompt']);
            $post->generated_image_prompt = $prompt !== '' ? $prompt : null;
            $post->ai_visual_suggestion = $post->generated_image_prompt;
        }

        if ($post->status === SocialPost::STATUS_DRAFT && $this->hasCopy($post)) {
            $post->status = SocialPost::STATUS_PENDING;
            $post->error_message = null;
        }

        $post->save();

        if (! empty($data['scheduled_at'])) {
            return $this->posts->proposeSchedule($post, $data['scheduled_at'], $data['timezone'] ?? $post->timezone);
        }

        return $post->fresh();
    }

    public function approvePost(ContentPlan $plan, SocialPost $post, array $data): SocialPost
    {
        $post = $this->updatePost($plan, $post, $data);

        if (! $this->hasCopy($post)) {
            throw ValidationException::withMessages([
                'facebook' => 'Add Facebook or Instagram copy before approving.',
            ]);
        }

        if ($post->scheduled_at === null) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Choose a date and time before approving.',
            ]);
        }

        $timezone = $post->timezone ?: 'Europe/Zurich';
        $local = $post->scheduled_at->copy()->timezone($timezone)->format('Y-m-d H:i');

        return $this->posts->schedule($post, $local, $timezone);
    }

    /**
     * @param  array<int, int>  $ids
     * @return array{approved: int, skipped: int}
     */
    public function approveMany(ContentPlan $plan, array $ids): array
    {
        $approved = 0;
        $skipped = 0;

        foreach ($this->selected($plan, $ids) as $post) {
            if (! $this->hasCopy($post) || $post->scheduled_at === null || $post->status === SocialPost::STATUS_PUBLISHED) {
                $skipped++;

                continue;
            }

            if ($post->status === SocialPost::STATUS_SCHEDULED) {
                $skipped++;

                continue;
            }

            $this->approvePost($plan, $post, []);
            $approved++;
        }

        return ['approved' => $approved, 'skipped' => $skipped];
    }

    public function rejectPost(ContentPlan $plan, SocialPost $post): void
    {
        $this->owned($plan, $post);
        $this->ensureEditable($post);
        $this->posts->delete($post);
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function rejectMany(ContentPlan $plan, array $ids): int
    {
        $count = 0;

        foreach ($this->selected($plan, $ids) as $post) {
            if ($post->status === SocialPost::STATUS_PUBLISHED) {
                continue;
            }

            $this->rejectPost($plan, $post);
            $count++;
        }

        return $count;
    }

    public function regeneratePost(ContentPlan $plan, SocialPost $post): SocialPost
    {
        $this->owned($plan, $post);
        $this->ensureEditable($post);

        $post->user_prompt = $this->brief($plan, (string) $post->post_title, $post->post_type);
        $post->enable_web_search = (bool) $plan->enable_web_search;
        $post->save();

        return $this->posts->refine($post, 'Write a fresh angle for this planned post. Keep it distinct and on the same topic.');
    }

    public function regenerateImagePrompt(ContentPlan $plan, SocialPost $post): SocialPost
    {
        $this->owned($plan, $post);
        $this->ensureEditable($post);

        $prompt = $this->agent->regenerateImagePrompt($post);
        $post->generated_image_prompt = $prompt;
        $post->ai_visual_suggestion = $prompt;
        $post->save();

        return $post->fresh();
    }

    public function delete(ContentPlan $plan): void
    {
        $plan->posts()
            ->whereNotIn('status', [SocialPost::STATUS_SCHEDULED, SocialPost::STATUS_PUBLISHED])
            ->get()
            ->each(fn (SocialPost $post) => $this->posts->delete($post));

        $plan->delete();
    }

    /**
     * @param  array<int, string>  $days
     * @return array<int, Carbon>
     */
    public function buildSlots(int $months, int $postsPerWeek, array $days, string $time, string $timezone): array
    {
        $days = $this->normalizeDays($days);

        if ($days === []) {
            $days = self::DEFAULT_DAYS[$postsPerWeek] ?? self::DEFAULT_DAYS[2];
        }

        $allowed = array_map(fn (string $day) => self::DAY_INDEX[$day], $days);
        [$hour, $minute] = array_pad(explode(':', $time), 2, '0');
        $now = Carbon::now($timezone);
        $end = $now->copy()->addMonths($months);
        $cursor = $now->copy()->startOfDay();
        $slots = [];
        $max = (int) config('social.planner.max_posts', 72);

        while ($cursor->lte($end) && count($slots) < $max) {
            if (in_array($cursor->dayOfWeek, $allowed, true)) {
                $moment = $cursor->copy()->setTime((int) $hour, (int) $minute);

                if ($moment->gt($now) && $moment->lte($end)) {
                    $slots[] = $moment->copy()->timezone((string) config('app.timezone'));
                }
            }

            $cursor->addDay();
        }

        return $slots;
    }

    /**
     * @return array<string, mixed>
     */
    public function present(ContentPlan $plan, bool $advanced = false): array
    {
        $plan->load(['posts' => fn ($query) => $query->orderBy('plan_position')]);

        $posts = $plan->posts->map(function (SocialPost $post) {
            $row = $this->posts->toCmsArray($post);
            $row['plan_position'] = $post->plan_position;
            $row['has_image'] = (bool) $post->image_path;

            return $row;
        })->values()->all();

        return [
            'advanced' => $advanced,
            'plan' => [
                'id' => $plan->id,
                'title' => $plan->title,
                'topic' => $plan->topic,
                'duration_months' => $plan->duration_months,
                'posts_per_week' => $plan->posts_per_week,
                'publish_days' => $plan->publish_days ?? [],
                'day_post_types' => $plan->day_post_types ?? [],
                'day_type_summary' => $this->dayTypeSummary($plan->day_post_types ?? []),
                'publish_time' => $plan->publish_time,
                'tone' => $plan->tone,
                'platforms' => $plan->platforms ?? [],
                'timezone' => $plan->timezone,
                'status' => $plan->status,
                'status_label' => ContentPlan::STATUSES[$plan->status] ?? $plan->status,
                'research_summary' => (string) $plan->research_summary,
                'posts_expected' => $plan->posts_expected,
                'failure_count' => $plan->failure_count,
                'error_message' => (string) $plan->error_message,
            ],
            'counts' => [
                'total' => $plan->posts->count(),
                'generating' => $plan->posts->where('status', SocialPost::STATUS_PROCESSING)->count(),
                'review' => $plan->posts->where('status', SocialPost::STATUS_PENDING)->count(),
                'scheduled' => $plan->posts->where('status', SocialPost::STATUS_SCHEDULED)->count(),
                'published' => $plan->posts->where('status', SocialPost::STATUS_PUBLISHED)->count(),
                'draft' => $plan->posts->where('status', SocialPost::STATUS_DRAFT)->count(),
            ],
            'posts' => $posts,
            'statuses' => SocialPostService::UI_STATUSES,
        ];
    }

    private function finish(ContentPlan $plan): void
    {
        $remaining = $plan->posts()->where('status', SocialPost::STATUS_PROCESSING)->count();

        if ($remaining > 0) {
            $plan->status = ContentPlan::STATUS_GENERATING;
            $plan->save();

            return;
        }

        $usable = $plan->posts()->where(function ($query) {
            $query->where(function ($inner) {
                $inner->whereNotNull('fb_content')->where('fb_content', '!=', '');
            })->orWhere(function ($inner) {
                $inner->whereNotNull('ig_content')->where('ig_content', '!=', '');
            });
        })->exists();

        $plan->status = $usable ? ContentPlan::STATUS_READY : ContentPlan::STATUS_FAILED;

        if (! $usable && ! $plan->error_message) {
            $plan->error_message = 'The content plan could not be generated.';
        }

        $plan->save();
    }

    private function markMissed(SocialPost $post): void
    {
        $post->generation_attempts = (int) $post->generation_attempts + 1;

        if ($post->generation_attempts >= 2) {
            $post->status = SocialPost::STATUS_DRAFT;
            $post->error_message = 'This draft could not be generated. Edit it or try generation again.';
        }

        $post->save();
    }

    private function brief(ContentPlan $plan, string $angle, ?string $postType = null): string
    {
        $parts = [trim((string) $plan->topic)];
        $type = is_string($postType) ? $postType : '';

        if ($type !== '' && isset(ContentPlan::POST_TYPES[$type])) {
            $parts[] = 'Post type: '.ContentPlan::POST_TYPES[$type].'. '.ContentPlan::POST_TYPE_GUIDANCE[$type];
        }

        $angle = trim($angle);

        if ($angle !== '') {
            $parts[] = 'Angle: '.$angle;
        }

        return implode("\n\n", $parts);
    }

    private function hasCopy(SocialPost $post): bool
    {
        return trim((string) $post->fb_content) !== '' || trim((string) $post->ig_content) !== '';
    }

    private function owned(ContentPlan $plan, SocialPost $post): void
    {
        if ((int) $post->content_plan_id !== (int) $plan->id) {
            throw (new ModelNotFoundException)->setModel(SocialPost::class, [$post->id]);
        }
    }

    private function ensureEditable(SocialPost $post): void
    {
        if ($post->status === SocialPost::STATUS_PUBLISHED) {
            throw ValidationException::withMessages([
                'status' => 'Published posts stay as they were sent.',
            ]);
        }
    }

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, SocialPost>
     */
    private function selected(ContentPlan $plan, array $ids): Collection
    {
        return $plan->posts()->whereIn('id', $ids)->get();
    }

    /**
     * Keep a post type only for selected weekdays, in Monday-to-Sunday order.
     *
     * @param  array<int, string>  $days
     * @param  array<string, mixed>  $types
     * @return array<string, string>
     */
    public function resolvedDayPostTypes(array $days, array $types): array
    {
        $days = $this->normalizeDays($days);

        if ($days === []) {
            return [];
        }

        $resolved = [];

        foreach ($days as $day) {
            $type = strtolower(trim((string) ($types[$day] ?? '')));

            if (! array_key_exists($type, ContentPlan::POST_TYPES)) {
                throw ValidationException::withMessages([
                    'day_post_types.'.$day => 'Choose a post type for '.(ContentPlan::WEEKDAYS[$day] ?? ucfirst($day)).'.',
                ]);
            }

            $resolved[$day] = $type;
        }

        return $resolved;
    }

    /**
     * @param  array<string, string>  $dayTypes
     */
    public function postTypeForMoment(Carbon $moment, string $timezone, array $dayTypes): ?string
    {
        $day = strtolower($moment->copy()->timezone($timezone)->englishDayOfWeek);
        $type = $dayTypes[$day] ?? null;

        return is_string($type) && array_key_exists($type, ContentPlan::POST_TYPES) ? $type : null;
    }

    /**
     * @param  array<string, string>  $types
     * @return array<int, string>
     */
    private function dayTypeSummary(array $types): array
    {
        $summary = [];

        foreach ($this->normalizeDays(array_keys($types)) as $day) {
            $type = $types[$day] ?? null;

            if (! is_string($type) || ! isset(ContentPlan::POST_TYPES[$type])) {
                continue;
            }

            $summary[] = (ContentPlan::WEEKDAY_SHORT[$day] ?? ucfirst($day)).' '.ContentPlan::POST_TYPES[$type];
        }

        return $summary;
    }

    /**
     * @param  array<int, string>  $days
     * @return array<int, string>
     */
    private function normalizeDays(array $days): array
    {
        $wanted = array_map(fn ($day) => strtolower(trim((string) $day)), $days);
        $ordered = [];

        foreach (array_keys(self::DAY_INDEX) as $name) {
            if (in_array($name, $wanted, true)) {
                $ordered[] = $name;
            }
        }

        return $ordered;
    }

    private function title(string $topic): string
    {
        $title = trim((string) preg_replace('/\s+/', ' ', $topic));

        if (mb_strlen($title) > 80) {
            return rtrim(mb_substr($title, 0, 80)).'…';
        }

        return $title;
    }

    private function batchSize(): int
    {
        return max(1, (int) config('social.planner.batch_size', 3));
    }

    private function maxFailures(): int
    {
        return max(1, (int) config('social.planner.max_failures', 3));
    }
}
