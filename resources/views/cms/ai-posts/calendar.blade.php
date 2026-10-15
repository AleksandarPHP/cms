@extends('cms.layout.container')

@section('content')
<div class="col-sm-9 main sm-module" id="sm-app" data-page="calendar">
    @include('cms.ai-posts.partials.head', [
        'heading' => 'Calendar',
        'description' => 'Month, week, and day views of scheduled and published posts.',
    ])
    <div class="sm-cal-bar">
        <div class="sm-filters sm-cal-views" id="smCalViews">
            <button type="button" class="button button-white is-active" data-view="month">Month</button>
            <button type="button" class="button button-white" data-view="week">Week</button>
            <button type="button" class="button button-white" data-view="day">Day</button>
        </div>
        <div class="sm-cal-nav">
            <button type="button" class="button button-white" id="smCalPrev" title="Previous"><i class="fas fa-chevron-left"></i></button>
            <strong id="smCalTitle"></strong>
            <button type="button" class="button button-white" id="smCalNext" title="Next"><i class="fas fa-chevron-right"></i></button>
            <button type="button" class="button button-white" id="smCalToday">Today</button>
        </div>
    </div>
    <div class="main__list">
        <div id="smCal"></div>
        <div class="sm-empty is-hidden" id="smCalEmpty">
            <i class="far fa-calendar-alt"></i>
            <h3>No scheduled posts.</h3>
            <p>Approved posts will appear here once they have a publishing time.</p>
            <a href="{{ url('cms/ai-posts/create') }}" class="button">Create Post</a>
        </div>
    </div>
    @include('cms.ai-posts.partials.modals')
</div>
@endsection

@section('scripts')
@include('cms.ai-posts.partials.scripts')
@endsection
