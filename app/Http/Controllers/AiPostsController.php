<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAiSocialPostJob;
use App\Models\SocialPost;
use App\Services\GeminiSocialAgentService;
use App\Services\MetaPublisherService;
use App\Services\SocialPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class AiPostsController extends Controller
{
    public function __construct(
        private SocialPostService $posts,
        private MetaPublisherService $publisher,
        private GeminiSocialAgentService $agent,
    ) {}

    public function index(): View
    {
        return view('cms.ai-posts.index', $this->viewData());
    }

    public function create(Request $request): View
    {
        $intent = $request->filled('duplicate') ? 'duplicate' : 'create';

        return view('cms.ai-posts.create', $this->viewData([
            'intent' => $intent,
            'duplicateId' => $intent === 'duplicate' ? (int) $request->query('duplicate') : null,
            'step' => (int) $request->query('step', 1),
        ]));
    }

    public function calendar(): View
    {
        return view('cms.ai-posts.calendar', $this->viewData());
    }

    public function accounts(): View
    {
        return view('cms.ai-posts.accounts', $this->viewData());
    }

    public function show(SocialPost $post): View
    {
        return view('cms.ai-posts.show', $this->viewData([
            'post' => $this->posts->toCmsArray($post),
        ]));
    }

    public function edit(Request $request, SocialPost $post): View
    {
        return view('cms.ai-posts.create', $this->viewData([
            'intent' => 'edit',
            'postId' => $post->id,
            'step' => (int) $request->query('step', 1),
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $post = $this->posts->saveFromCms($this->validatedPayload($request));

        return $this->postResponse($post);
    }

    public function update(Request $request, SocialPost $post): JsonResponse
    {
        $post = $this->posts->saveFromCms($this->validatedPayload($request), $post);

        return $this->postResponse($post);
    }

    public function status(SocialPost $post): JsonResponse
    {
        return response()->json(['post' => $this->posts->toCmsArray($post)]);
    }

    public function refine(Request $request, SocialPost $post): JsonResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $post = $this->posts->refine($post, $data['note']);

        return $this->postResponse($post, true);
    }

    public function generateImage(SocialPost $post): JsonResponse
    {
        try {
            $post = $this->posts->generateImage($post, $this->agent);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'post' => $this->posts->toCmsArray($post->fresh() ?: $post),
            ], 422);
        }

        return response()->json(['post' => $this->posts->toCmsArray($post)]);
    }

    public function schedule(Request $request, SocialPost $post): JsonResponse
    {
        $data = $request->validate([
            'scheduled_at' => ['required', 'date_format:Y-m-d H:i'],
            'timezone' => ['nullable', 'timezone'],
        ]);

        $post = $this->posts->schedule($post, $data['scheduled_at'], $data['timezone'] ?? null);

        return response()->json(['post' => $this->posts->toCmsArray($post)]);
    }

    public function publish(SocialPost $post): JsonResponse
    {
        $post = $this->publisher->publishPost($post);

        return $this->publishResponse($post);
    }

    public function cancel(SocialPost $post): JsonResponse
    {
        $post = $this->posts->cancelSchedule($post);

        return response()->json(['post' => $this->posts->toCmsArray($post)]);
    }

    public function retry(SocialPost $post): JsonResponse
    {
        if ($post->fb_content || $post->ig_content) {
            $post = $this->publisher->publishPost($post);

            return response()->json(['post' => $this->posts->toCmsArray($post)]);
        }

        $post->status = SocialPost::STATUS_PROCESSING;
        $post->error_message = null;
        $post->save();
        ProcessAiSocialPostJob::dispatchSync($post);

        return $this->postResponse($post->fresh());
    }

    public function destroy(SocialPost $post): JsonResponse
    {
        $this->posts->delete($post);

        return response()->json(['message' => 'Post deleted.']);
    }

    public function connect(string $platform): JsonResponse
    {
        try {
            $accounts = $this->posts->connect($platform);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['accounts' => $accounts]);
    }

    public function disconnect(string $platform): JsonResponse
    {
        return response()->json(['accounts' => $this->posts->disconnect($platform)]);
    }

    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'action' => ['required', 'in:draft,generate,review'],
            'prompt' => ['required', 'string', 'max:5000'],
            'source_url' => ['nullable', 'string', 'max:2000'],
            'tone' => ['nullable', 'string', 'max:50'],
            'research' => ['nullable', 'boolean'],
            'platforms' => ['nullable', 'array'],
            'platforms.*' => ['in:facebook,instagram'],
            'facebook' => ['nullable', 'string'],
            'instagram' => ['nullable', 'string'],
            'hashtags' => ['nullable', 'array'],
            'hashtags.*' => ['string', 'max:100'],
            'timezone' => ['nullable', 'timezone'],
            'image_instructions' => ['nullable', 'string', 'max:5000'],
            'generated_image_prompt' => ['nullable', 'string', 'max:8000'],
            'image_data' => ['nullable', 'string'],
            'remove_image' => ['nullable', 'boolean'],
            'duplicate_id' => ['nullable', 'integer'],
        ]);
    }

    private function postResponse(SocialPost $post, bool $strict = false): JsonResponse
    {
        $payload = ['post' => $this->posts->toCmsArray($post)];

        if ($strict && $post->status === SocialPost::STATUS_FAILED) {
            $payload['message'] = $post->error_message ?: 'The post could not be generated.';

            return response()->json($payload, 422);
        }

        return response()->json($payload);
    }

    private function publishResponse(SocialPost $post): JsonResponse
    {
        $payload = ['post' => $this->posts->toCmsArray($post)];

        if ($post->status === SocialPost::STATUS_FAILED) {
            $payload['message'] = $post->error_message ?: 'The post could not be published.';

            return response()->json($payload, 422);
        }

        return response()->json($payload);
    }

    private function viewData(array $boot = []): array
    {
        $presented = SocialPost::query()
            ->with('contentPlan:id,title')
            ->latest()
            ->get()
            ->map(fn (SocialPost $post) => $this->posts->toCmsArray($post))
            ->all();

        $brand = $this->posts->brand();
        $accounts = $this->posts->accountCards();

        return [
            'posts' => $presented,
            'post' => $boot['post'] ?? null,
            'statuses' => SocialPostService::UI_STATUSES,
            'brand' => $brand,
            'accounts' => $accounts,
            'smBoot' => [
                'posts' => $presented,
                'accounts' => $accounts,
                'brand' => $brand,
                'statuses' => SocialPostService::UI_STATUSES,
                'placeholder' => asset('cmsfiles/images/photo.png'),
                'generatedImage' => asset('cmsfiles/images/photo.png'),
                'icons' => [
                    'show' => asset('cmsfiles/images/btn_show.jpg'),
                    'edit' => asset('cmsfiles/images/btn_edit.jpg'),
                    'copy' => asset('cmsfiles/images/btn_copy.jpg'),
                    'delete' => asset('cmsfiles/images/btn_delete.jpg'),
                ],
                'intent' => $boot['intent'] ?? null,
                'postId' => $boot['postId'] ?? null,
                'duplicateId' => $boot['duplicateId'] ?? null,
                'step' => $boot['step'] ?? null,
                'urls' => [
                    'index' => url('cms/ai-posts'),
                    'create' => url('cms/ai-posts/create'),
                    'store' => url('cms/ai-posts'),
                    'update' => url('cms/ai-posts/__ID__'),
                    'status' => url('cms/ai-posts/__ID__/status'),
                    'refine' => url('cms/ai-posts/__ID__/refine'),
                    'image' => url('cms/ai-posts/__ID__/image'),
                    'schedule' => url('cms/ai-posts/__ID__/schedule'),
                    'publish' => url('cms/ai-posts/__ID__/publish'),
                    'cancel' => url('cms/ai-posts/__ID__/cancel'),
                    'retry' => url('cms/ai-posts/__ID__/retry'),
                    'destroy' => url('cms/ai-posts/__ID__'),
                    'connect' => url('cms/ai-posts/accounts/__PLATFORM__/connect'),
                    'disconnect' => url('cms/ai-posts/accounts/__PLATFORM__/disconnect'),
                ],
            ],
        ];
    }
}
