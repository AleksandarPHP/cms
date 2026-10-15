<?php

namespace App\Http\Controllers;

use App\Http\Requests\Social\PlanPostIdsRequest;
use App\Http\Requests\Social\StoreContentPlanRequest;
use App\Http\Requests\Social\UpdatePlanPostRequest;
use App\Jobs\GenerateContentPlanBatchJob;
use App\Models\ContentPlan;
use App\Models\SocialPost;
use App\Services\ContentPlanService;
use App\Services\SocialPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContentPlanController extends Controller
{
    public function __construct(
        private ContentPlanService $plans,
        private SocialPostService $posts,
    ) {}

    public function index(): View
    {
        $plans = ContentPlan::query()
            ->withCount([
                'posts',
                'posts as review_count' => fn ($query) => $query->where('status', SocialPost::STATUS_PENDING),
                'posts as scheduled_count' => fn ($query) => $query->where('status', SocialPost::STATUS_SCHEDULED),
                'posts as published_count' => fn ($query) => $query->where('status', SocialPost::STATUS_PUBLISHED),
            ])
            ->latest()
            ->get();

        return view('cms.ai-posts.plans.index', [
            'plans' => $plans,
            'statuses' => ContentPlan::STATUSES,
        ]);
    }

    public function create(): View
    {
        return view('cms.ai-posts.plans.create', [
            'brand' => $this->posts->brand(),
            'maxPosts' => (int) config('social.planner.max_posts', 72),
            'postTypes' => ContentPlan::POST_TYPES,
            'weekdays' => ContentPlan::WEEKDAYS,
            'weekdayShort' => ContentPlan::WEEKDAY_SHORT,
        ]);
    }

    public function store(StoreContentPlanRequest $request): JsonResponse|RedirectResponse
    {
        $plan = $this->plans->create($request->validated(), $request->user()?->id);

        if ($request->expectsJson()) {
            return response()->json($this->plans->present($plan), 201);
        }

        return redirect()->route('ai-posts.plans.show', $plan);
    }

    public function show(ContentPlan $plan): View
    {
        $payload = $this->plans->present($plan);
        $payload['urls'] = $this->urls($plan);
        $payload['max_posts'] = (int) config('social.planner.max_posts', 72);

        return view('cms.ai-posts.plans.show', [
            'plan' => $plan,
            'planBoot' => $payload,
        ]);
    }

    public function advance(ContentPlan $plan): JsonResponse|RedirectResponse
    {
        $waiting = $plan->posts()->where('status', SocialPost::STATUS_PROCESSING)->count();
        $status = $plan->status;
        $error = $plan->error_message;

        GenerateContentPlanBatchJob::dispatchSync($plan);

        $plan = $plan->fresh();
        $advanced = $status !== $plan->status
            || $error !== $plan->error_message
            || $waiting !== $plan->posts()->where('status', SocialPost::STATUS_PROCESSING)->count();

        if (request()->expectsJson()) {
            return response()->json($this->plans->present($plan, $advanced));
        }

        return redirect()->route('ai-posts.plans.show', $plan);
    }

    public function retry(ContentPlan $plan): JsonResponse|RedirectResponse
    {
        $this->plans->retry($plan);

        if (request()->expectsJson()) {
            return response()->json($this->plans->present($plan->fresh()));
        }

        return redirect()->route('ai-posts.plans.show', $plan);
    }

    public function updatePost(UpdatePlanPostRequest $request, ContentPlan $plan, SocialPost $post): JsonResponse
    {
        $this->plans->updatePost($plan, $post, $request->validated());

        return response()->json($this->plans->present($plan->fresh()));
    }

    public function approvePost(UpdatePlanPostRequest $request, ContentPlan $plan, SocialPost $post): JsonResponse
    {
        $this->plans->approvePost($plan, $post, $request->validated());

        return response()->json($this->plans->present($plan->fresh()));
    }

    public function rejectPost(ContentPlan $plan, SocialPost $post): JsonResponse
    {
        $this->plans->rejectPost($plan, $post);

        return response()->json($this->plans->present($plan->fresh()));
    }

    public function regeneratePost(ContentPlan $plan, SocialPost $post): JsonResponse
    {
        $this->plans->regeneratePost($plan, $post);

        return response()->json($this->plans->present($plan->fresh()));
    }

    public function regenerateImagePrompt(ContentPlan $plan, SocialPost $post): JsonResponse
    {
        $this->plans->regenerateImagePrompt($plan, $post);

        return response()->json($this->plans->present($plan->fresh()));
    }

    public function approveMany(PlanPostIdsRequest $request, ContentPlan $plan): JsonResponse
    {
        $result = $this->plans->approveMany($plan, $request->validated('ids'));

        return response()->json($this->plans->present($plan->fresh()) + ['result' => $result]);
    }

    public function rejectMany(PlanPostIdsRequest $request, ContentPlan $plan): JsonResponse
    {
        $removed = $this->plans->rejectMany($plan, $request->validated('ids'));

        return response()->json($this->plans->present($plan->fresh()) + [
            'result' => ['removed' => $removed],
        ]);
    }

    public function destroy(ContentPlan $plan): RedirectResponse
    {
        $this->plans->delete($plan);

        return redirect()
            ->route('ai-posts.plans.index')
            ->with('success', 'The content plan was deleted. Scheduled and published posts were kept.');
    }

    /**
     * @return array<string, string>
     */
    private function urls(ContentPlan $plan): array
    {
        $base = url('cms/ai-posts/plans/'.$plan->id);

        return [
            'advance' => $base.'/advance',
            'retry' => $base.'/retry',
            'approve_many' => $base.'/approve',
            'reject_many' => $base.'/reject',
            'update' => $base.'/posts/__ID__',
            'approve' => $base.'/posts/__ID__/approve',
            'reject' => $base.'/posts/__ID__/reject',
            'regenerate' => $base.'/posts/__ID__/regenerate',
            'image_prompt' => $base.'/posts/__ID__/image-prompt',
            'image' => url('cms/ai-posts/__ID__/image'),
            'show_post' => url('cms/ai-posts/__ID__'),
            'index' => url('cms/ai-posts/plans'),
            'calendar' => url('cms/ai-posts/calendar'),
        ];
    }
}
