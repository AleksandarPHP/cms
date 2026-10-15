<?php

namespace App\Jobs;

use App\Models\ContentPlan;
use App\Services\ContentPlanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateContentPlanBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    public int $tries = 1;

    public function __construct(public ContentPlan $plan) {}

    public function handle(ContentPlanService $plans): void
    {
        $plans->generateNextBatch($this->plan);
    }
}
