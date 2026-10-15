@extends('cms.layout.container')

@section('content')
<div class="col-sm-9 main sm-module" id="plan-app" data-page="show">
    @include('cms.ai-posts.partials.head', [
        'heading' => 'Review Plan',
        'description' => 'Approve the posts you want. Approved posts are placed on the calendar and published at their scheduled time.',
    ])

    <div class="sm-plan-summary" id="planSummary"></div>
    <div class="row alert__wrap" id="planAlert" style="display:none">
        <div class="alert alert-danger">
            <span class="alert__icon"><i class="fas fa-exclamation-triangle"></i></span>
            <p id="planAlertText"></p>
            <span class="alert__btn"><i class="fas fa-times"></i></span>
        </div>
    </div>

    <div class="sm-toolbar">
        <div class="sm-filters" id="planFilters">
            <button type="button" class="button button-white sm-filter is-active" data-status="all">All</button>
            <button type="button" class="button button-white sm-filter" data-status="pending">Needs review</button>
            <button type="button" class="button button-white sm-filter" data-status="scheduled">Scheduled</button>
            <button type="button" class="button button-white sm-filter" data-status="processing">Generating</button>
            <button type="button" class="button button-white sm-filter" data-status="draft">Draft</button>
        </div>
        <div class="sm-plan-bulk">
            <label class="sm-check"><input type="checkbox" id="planSelectAll"> Select visible</label>
            <button type="button" class="button" id="planApproveSelected">Approve selected</button>
            <button type="button" class="button button-white" id="planApproveReady">Approve all ready</button>
            <button type="button" class="button button-white" id="planRejectSelected">Reject selected</button>
        </div>
    </div>

    <div class="main__list">
        <div id="planList"></div>
        <div class="sm-empty is-hidden" id="planEmpty">
            <i class="fas fa-layer-group"></i>
            <h3>No posts in this view.</h3>
            <p>Try another filter, or generate the next batch.</p>
        </div>
    </div>

    <noscript>
        <form method="POST" action="{{ url('cms/ai-posts/plans/'.$plan->id.'/advance') }}">
            @csrf
            <button type="submit" class="button">Generate next batch</button>
        </form>
    </noscript>
</div>
@endsection

@section('scripts')
<script>
window.PLAN_BOOT = @json($planBoot);
</script>
<script src="{{ asset('cmsfiles/js/content-plans.js') }}?v=3"></script>
@endsection
