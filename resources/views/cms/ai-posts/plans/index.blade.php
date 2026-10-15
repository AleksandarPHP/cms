@extends('cms.layout.container')

@section('content')
<div class="col-sm-9 main sm-module" id="plan-app" data-page="index">
    @include('cms.ai-posts.partials.head', [
        'heading' => 'Content Planner',
        'description' => 'Generate a month, quarter, or half-year of social posts, then review and schedule them.',
    ])
    @include('cms.partials.alerts')

    <div class="main__list">
        <div class="row form__title">
            <h3>Plans</h3>
        </div>
        @if($plans->isEmpty())
        <div class="sm-empty">
            <i class="fas fa-layer-group"></i>
            <h3>No content plans yet.</h3>
            <p>Give the planner a topic and it will draft a publishing calendar for you.</p>
            <a href="{{ route('ai-posts.plans.create') }}" class="button">New Plan</a>
        </div>
        @else
        <div class="sm-table-wrap">
            <div class="table-responsive">
                <table class="sm-table">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Horizon</th>
                            <th>Status</th>
                            <th>Posts</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plans as $plan)
                        <tr>
                            <td class="sm-post" data-label="Plan">
                                <a class="sm-post__title" href="{{ route('ai-posts.plans.show', $plan) }}">{{ $plan->title }}</a>
                                <p class="sm-excerpt">{{ \Illuminate\Support\Str::limit($plan->topic, 140) }}</p>
                            </td>
                            <td data-label="Horizon">{{ $plan->duration_months }} {{ $plan->duration_months === 1 ? 'month' : 'months' }} · {{ $plan->posts_per_week }}/week</td>
                            <td data-label="Status"><span class="sm-badge sm-badge--{{ $plan->status }}">{{ $statuses[$plan->status] ?? $plan->status }}</span></td>
                            <td data-label="Posts">{{ $plan->posts_count }} · {{ $plan->scheduled_count }} scheduled</td>
                            <td data-label="Created">{{ $plan->created_at->timezone('Europe/Zurich')->format('j M Y') }}</td>
                            <td class="td_action" data-label="Actions">
                                <a href="{{ route('ai-posts.plans.show', $plan) }}" class="button button-white">Review</a>
                                <form method="POST" action="{{ route('ai-posts.plans.destroy', $plan) }}" class="sm-inline-form" onsubmit="return confirm('Delete this plan? Scheduled and published posts will stay on the calendar.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button button-white">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
