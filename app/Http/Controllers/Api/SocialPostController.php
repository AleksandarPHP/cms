<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Social\ApproveSocialPostRequest;
use App\Http\Requests\Social\GenerateSocialPostRequest;
use App\Http\Requests\Social\RegenerateSocialPostRequest;
use App\Http\Resources\SocialPostResource;
use App\Models\SocialPost;
use App\Services\SocialPostService;
use Illuminate\Http\JsonResponse;

class SocialPostController extends Controller
{
    public function __construct(private SocialPostService $posts) {}

    public function index(): JsonResponse
    {
        $posts = SocialPost::query()->latest()->get();

        return SocialPostResource::collection($posts)->response();
    }

    public function show(SocialPost $post): SocialPostResource
    {
        return new SocialPostResource($post);
    }

    public function generate(GenerateSocialPostRequest $request): JsonResponse
    {
        $post = $this->posts->createAndGenerate(
            $request->validated(),
            $request->file('image'),
            $request->user()?->id,
        );

        return $this->generated($post, 201);
    }

    public function regenerate(RegenerateSocialPostRequest $request, SocialPost $post): JsonResponse
    {
        return $this->generated($this->posts->regenerate($post, $request->validated()));
    }

    public function approve(ApproveSocialPostRequest $request, SocialPost $post): JsonResponse
    {
        $post = $this->posts->approve($post, $request->validated());
        $failed = $post->status === SocialPost::STATUS_FAILED;

        return (new SocialPostResource($post))
            ->additional($failed ? ['message' => $post->error_message] : [])
            ->response()
            ->setStatusCode($failed ? 422 : 200);
    }

    private function generated(SocialPost $post, int $success = 200): JsonResponse
    {
        $failed = $post->status === SocialPost::STATUS_FAILED;

        return (new SocialPostResource($post))
            ->additional($failed ? ['message' => $post->error_message] : [])
            ->response()
            ->setStatusCode($failed ? 422 : $success);
    }
}
