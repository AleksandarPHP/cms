<?php

namespace App\Services;

use App\Jobs\ProcessAiSocialPostJob;
use App\Models\Company;
use App\Models\ContentPlan;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SocialPostService
{
    public const UI_STATUSES = [
        'draft' => 'Draft',
        'processing' => 'Processing',
        'pending' => 'Pending Approval',
        'scheduled' => 'Scheduled',
        'published' => 'Published',
        'failed' => 'Failed',
    ];

    public function __construct(
        private MetaPublisherService $publisher,
        private BrandService $brand,
    ) {}

    public function createAndGenerate(array $data, ?UploadedFile $image, ?int $userId): SocialPost
    {
        $post = new SocialPost([
            'user_id' => $userId,
            'status' => SocialPost::STATUS_PROCESSING,
            'user_prompt' => $data['user_prompt'],
            'source_url' => $data['source_url'] ?? null,
            'enable_web_search' => (bool) ($data['enable_web_search'] ?? false),
            'tone' => $data['tone'] ?? 'professional',
            'user_image_instructions' => $data['image_instructions'] ?? null,
            'platforms' => $data['platforms'] ?? [SocialAccount::FACEBOOK, SocialAccount::INSTAGRAM],
            'timezone' => $data['timezone'] ?? 'Europe/Zurich',
        ]);

        if ($image) {
            $post->image_path = $this->storeUpload($image);
        }

        $post->save();

        ProcessAiSocialPostJob::dispatchSync($post);

        return $post->fresh();
    }

    public function regenerate(SocialPost $post, array $data): SocialPost
    {
        if (! empty($data['user_prompt'])) {
            $post->user_prompt = $data['user_prompt'];
        }

        if (array_key_exists('source_url', $data)) {
            $post->source_url = $data['source_url'];
        }

        if (array_key_exists('enable_web_search', $data)) {
            $post->enable_web_search = (bool) $data['enable_web_search'];
        }

        if (! empty($data['tone'])) {
            $post->tone = $data['tone'];
        }

        if (array_key_exists('image_instructions', $data)) {
            $post->user_image_instructions = $data['image_instructions'] ?: null;
        }

        $post->status = SocialPost::STATUS_PROCESSING;
        $post->error_message = null;
        $post->save();

        ProcessAiSocialPostJob::dispatchSync($post, $data['instruction'] ?? null);

        return $post->fresh();
    }

    public function approve(SocialPost $post, array $data): SocialPost
    {
        if (array_key_exists('fb_content', $data)) {
            $post->fb_content = $data['fb_content'];
        }

        if (array_key_exists('ig_content', $data)) {
            $post->ig_content = $data['ig_content'];
        }

        if (array_key_exists('hashtags', $data)) {
            $post->hashtags = array_values($data['hashtags'] ?? []);
        }

        if (array_key_exists('generated_image_prompt', $data)) {
            $post->generated_image_prompt = $data['generated_image_prompt'] ?: null;
        }

        if (! empty($data['timezone'])) {
            $post->timezone = $data['timezone'];
        }

        if (! empty($data['publish_now'])) {
            $post->save();

            return $this->publisher->publishPost($post->fresh());
        }

        if (! empty($data['scheduled_at'])) {
            $post->scheduled_at = $this->parseSchedule($data['scheduled_at'], $post->timezone ?: 'Europe/Zurich');
            $post->status = SocialPost::STATUS_SCHEDULED;
            $post->error_message = null;
            $post->save();

            return $post->fresh();
        }

        $post->status = SocialPost::STATUS_PENDING;
        $post->scheduled_at = null;
        $post->save();

        return $post->fresh();
    }

    public function saveFromCms(array $data, ?SocialPost $post = null): SocialPost
    {
        $post ??= new SocialPost([
            'user_id' => auth()->id(),
            'status' => SocialPost::STATUS_DRAFT,
        ]);

        $post->user_prompt = $data['prompt'];
        $post->source_url = $data['source_url'] ?: null;
        $post->tone = $data['tone'] ?? 'professional';
        $post->enable_web_search = (bool) ($data['research'] ?? false);
        $platforms = array_values($data['platforms'] ?? []);
        $post->platforms = $platforms !== []
            ? $platforms
            : [SocialAccount::FACEBOOK, SocialAccount::INSTAGRAM];
        $post->timezone = $data['timezone'] ?? ($post->timezone ?: 'Europe/Zurich');
        $post->user_image_instructions = ($data['image_instructions'] ?? '') !== ''
            ? $data['image_instructions']
            : null;

        if (! empty($data['remove_image']) && $post->image_path) {
            $this->deleteImage($post->image_path);
            $post->image_path = null;
        }

        if (! empty($data['image_data'])) {
            $this->deleteImage($post->image_path);
            $post->image_path = $this->storeDataUrl($data['image_data']);
        } elseif (! $post->exists && ! empty($data['duplicate_id']) && empty($data['remove_image'])) {
            $post->image_path = $this->copyImage(SocialPost::query()->find($data['duplicate_id']));
        }

        $action = $data['action'];

        if ($action === 'review' && ($data['generated_image_prompt'] ?? '') !== '') {
            $post->generated_image_prompt = $data['generated_image_prompt'];
        }

        if ($action !== 'generate') {
            $post->fb_content = $data['facebook'] ?? $post->fb_content;
            $post->ig_content = $data['instagram'] ?? $post->ig_content;
            $post->hashtags = array_values($data['hashtags'] ?? []);
        }

        if ($action === 'draft') {
            $post->status = SocialPost::STATUS_DRAFT;
        } elseif ($action === 'generate') {
            $post->status = SocialPost::STATUS_PROCESSING;
            $post->error_message = null;
        } elseif (! in_array($post->status, [SocialPost::STATUS_SCHEDULED, SocialPost::STATUS_PUBLISHED], true)) {
            $post->status = SocialPost::STATUS_PENDING;
        }

        $post->save();

        if ($action === 'generate') {
            ProcessAiSocialPostJob::dispatchSync($post);
            $post->refresh();
        }

        return $post;
    }

    public function refine(SocialPost $post, string $note): SocialPost
    {
        $post->status = SocialPost::STATUS_PROCESSING;
        $post->error_message = null;
        $post->save();

        ProcessAiSocialPostJob::dispatchSync($post, $note);

        return $post->fresh();
    }

    public function generateImage(SocialPost $post, GeminiSocialAgentService $agent): SocialPost
    {
        $path = $agent->generateImage($post);
        $this->deleteImage($post->image_path);
        $post->image_path = $path;
        $post->save();

        return $post->fresh();
    }

    public function schedule(SocialPost $post, string $scheduledAt, ?string $timezone = null): SocialPost
    {
        $post = $this->proposeSchedule($post, $scheduledAt, $timezone);
        $post->status = SocialPost::STATUS_SCHEDULED;
        $post->error_message = null;
        $post->save();

        return $post->fresh();
    }

    public function proposeSchedule(SocialPost $post, string $scheduledAt, ?string $timezone = null): SocialPost
    {
        $post->timezone = $timezone ?: ($post->timezone ?: 'Europe/Zurich');
        $post->scheduled_at = $this->parseSchedule($scheduledAt, $post->timezone);
        $post->save();

        return $post->fresh();
    }

    public function cancelSchedule(SocialPost $post): SocialPost
    {
        $post->status = SocialPost::STATUS_PENDING;
        $post->scheduled_at = null;
        $post->save();

        return $post->fresh();
    }

    public function delete(SocialPost $post): void
    {
        $this->deleteImage($post->image_path);
        $post->delete();
    }

    public function connect(string $provider): array
    {
        $resolved = $this->publisher->resolveAccount($provider);

        SocialAccount::query()->updateOrCreate(
            ['provider' => $provider],
            [
                'user_id' => auth()->id(),
                'account_id' => $resolved['id'],
                'account_name' => $resolved['name'],
                'access_token' => $resolved['token'],
            ],
        );

        return $this->accountCards();
    }

    public function disconnect(string $provider): array
    {
        SocialAccount::query()->where('provider', $provider)->delete();

        return $this->accountCards();
    }

    public function accountCards(): array
    {
        $accounts = SocialAccount::query()->get()->keyBy('provider');

        return [
            SocialAccount::FACEBOOK => $this->card(
                $accounts->get(SocialAccount::FACEBOOK),
                'Facebook',
                'Facebook Page',
                (string) config('social.facebook.page_id'),
            ),
            SocialAccount::INSTAGRAM => $this->card(
                $accounts->get(SocialAccount::INSTAGRAM),
                'Instagram',
                'Instagram',
                (string) config('social.instagram.business_account_id'),
            ),
        ];
    }

    public function brand(): array
    {
        $cards = $this->accountCards();
        $company = Company::query()->first();
        $page = $cards['facebook']['connected'] ? $cards['facebook']['title'] : ($company?->name ?? 'Facebook Page');
        $instagram = $cards['instagram']['connected'] ? $cards['instagram']['title'] : 'Instagram';
        $handle = str_starts_with($instagram, '@') ? $instagram : '@'.Str::slug($instagram, '');

        return [
            'page' => $page,
            'instagram' => $instagram,
            'handle' => $handle,
        ];
    }

    public function toCmsArray(SocialPost $post): array
    {
        $timezone = $post->timezone ?: 'Europe/Zurich';
        $scheduled = $post->scheduled_at
            ? $post->scheduled_at->copy()->timezone($timezone)->format('Y-m-d H:i')
            : null;
        $created = $post->created_at->copy()->timezone($timezone)->format('Y-m-d H:i');
        $status = $this->uiStatus($post);
        $imageUrl = $this->imageUrl($post);

        return [
            'id' => $post->id,
            'title' => $this->title($post),
            'plan_id' => $post->content_plan_id,
            'plan_title' => $post->relationLoaded('contentPlan') ? (string) ($post->contentPlan->title ?? '') : '',
            'excerpt' => $this->excerpt($post),
            'prompt' => (string) $post->user_prompt,
            'source_url' => (string) $post->source_url,
            'image_instructions' => (string) $post->user_image_instructions,
            'generated_image_prompt' => $this->brand->withoutSerbian((string) $post->generated_image_prompt),
            'tone' => $post->tone ?: 'professional',
            'post_type' => (string) $post->post_type,
            'post_type_label' => ContentPlan::POST_TYPES[$post->post_type] ?? '',
            'research' => (bool) $post->enable_web_search,
            'platforms' => $post->platforms ?: [],
            'status' => $status,
            'scheduled_at' => $scheduled,
            'calendar_at' => $scheduled,
            'timezone' => $timezone,
            'created_at' => $created,
            'image' => $post->image_path && str_contains($post->image_path, 'generated-') ? 'generated' : 'upload',
            'image_url' => $imageUrl ?: asset('cmsfiles/images/photo.png'),
            'image_data' => '',
            'facebook' => (string) $post->fb_content,
            'instagram' => (string) $post->ig_content,
            'hashtags' => $post->hashtags ?? [],
            'link' => (string) $post->source_url,
            'error' => (string) $post->error_message,
            'activity' => $this->activity($post, $status, $timezone),
        ];
    }

    public function imageUrl(SocialPost $post): ?string
    {
        if (! $post->image_path) {
            return null;
        }

        return Storage::disk('public')->url($post->image_path);
    }

    private function card(?SocialAccount $account, string $name, string $subtitle, string $fallbackId): array
    {
        return [
            'name' => $name,
            'subtitle' => $subtitle,
            'title' => $account?->account_name ?: $name,
            'account_id' => $account?->account_id ?: $fallbackId,
            'synced_at' => $account?->updated_at?->timezone('Europe/Zurich')->format('j M Y, H:i') ?? '',
            'connected' => $account !== null,
        ];
    }

    private function uiStatus(SocialPost $post): string
    {
        return $post->status === SocialPost::STATUS_PENDING ? 'pending' : $post->status;
    }

    private function title(SocialPost $post): string
    {
        $clean = trim((string) preg_replace('/^write a post about\s+/i', '', (string) $post->user_prompt));
        $clean = rtrim($clean, '.');

        if ($clean === '') {
            return 'Untitled post';
        }

        $clean = mb_strtoupper(mb_substr($clean, 0, 1)).mb_substr($clean, 1);

        if (mb_strlen($clean) > 64) {
            return rtrim(mb_substr($clean, 0, 64)).'…';
        }

        return $clean;
    }

    private function excerpt(SocialPost $post): string
    {
        $text = trim((string) ($post->fb_content ?: $post->ig_content ?: $post->user_prompt));

        if (mb_strlen($text) <= 160) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, 160)).'…';
    }

    private function activity(SocialPost $post, string $status, string $timezone): array
    {
        $stamp = fn ($date) => $date?->copy()->timezone($timezone)->format('j M Y, H:i') ?? '';
        $events = [
            ['label' => 'Created', 'at' => $stamp($post->created_at)],
        ];

        if ($status !== 'draft') {
            $events[] = ['label' => 'AI processing', 'at' => $stamp($post->updated_at)];
        }

        if (in_array($status, ['pending', 'scheduled', 'published'], true)) {
            $events[] = ['label' => 'Content generated', 'at' => $stamp($post->updated_at)];
        }

        if (in_array($status, ['scheduled', 'published'], true)) {
            $events[] = ['label' => 'Approved', 'at' => $stamp($post->updated_at)];
            $events[] = ['label' => 'Scheduled', 'at' => $stamp($post->scheduled_at ?: $post->updated_at)];
        }

        if ($status === 'published') {
            $events[] = ['label' => 'Published', 'at' => $stamp($post->published_at ?: $post->updated_at)];
        }

        return $events;
    }

    private function parseSchedule(string $value, string $timezone): Carbon
    {
        $appTimezone = (string) config('app.timezone');

        try {
            $moment = Carbon::createFromFormat('Y-m-d H:i', $value, $timezone);
        } catch (\Throwable) {
            $moment = Carbon::parse($value, $timezone);
        }

        return $moment->timezone($appTimezone);
    }

    private function storeUpload(UploadedFile $image): string
    {
        return $image->store('social_uploads', 'public');
    }

    private function storeDataUrl(string $dataUrl): string
    {
        if (! preg_match('#^data:image/(jpeg|jpg|png|gif|webp);base64,([A-Za-z0-9+/=\r\n]+)$#', $dataUrl, $matches)) {
            throw ValidationException::withMessages([
                'image_data' => 'Upload a JPG, PNG, GIF, or WebP image.',
            ]);
        }

        $binary = base64_decode(str_replace(["\r", "\n"], '', $matches[2]), true);

        if ($binary === false || strlen($binary) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'image_data' => 'The image must be a valid file under 5 MB.',
            ]);
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $path = 'social_uploads/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    private function copyImage(?SocialPost $source): ?string
    {
        if (! $source?->image_path || ! Storage::disk('public')->exists($source->image_path)) {
            return null;
        }

        $extension = pathinfo($source->image_path, PATHINFO_EXTENSION) ?: 'jpg';
        $path = 'social_uploads/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->copy($source->image_path, $path);

        return $path;
    }

    private function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
