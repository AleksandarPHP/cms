<link href="{{ asset('cmsfiles/css/ai-posts.css') }}?v=6" rel="stylesheet">
<div class="row main__title">
    <div class="col-sm-7">
        <h2><i class="fas fa-list"></i>{{ $heading }}</h2>
        <p>{{ $description }}</p>
    </div>
    <div class="col-sm-5 sm-head-actions">
        <div class="shortcuts">
            <a href="{{ url('cms') }}" class="shortcut" data-toggle="tooltip" data-placement="bottom" title="{{ __('app.daschlow') }}"><i class="fas fa-desktop"></i></a>
            <a href="{{ url('cms/ai-posts') }}" class="shortcut" data-toggle="tooltip" data-placement="bottom" title="Posts"><i class="far fa-clone"></i></a>
            <a href="{{ url('cms/ai-posts/plans') }}" class="shortcut" data-toggle="tooltip" data-placement="bottom" title="Content Planner"><i class="fas fa-layer-group"></i></a>
            <a href="{{ url('cms/ai-posts/calendar') }}" class="shortcut" data-toggle="tooltip" data-placement="bottom" title="Calendar"><i class="far fa-calendar-alt"></i></a>
            <a href="{{ url('cms/ai-posts/accounts') }}" class="shortcut" data-toggle="tooltip" data-placement="bottom" title="Accounts"><i class="fas fa-share-alt"></i></a>
            <a href="{{ url('cms/ai-posts/create') }}" class="shortcut" data-toggle="tooltip" data-placement="bottom" title="Create Post"><i class="fas fa-plus"></i></a>
            <a href="https://symple.kayako.com/" target="_blank" class="shortcut" data-toggle="tooltip" data-placement="bottom" title="Support"><i class="fas fa-question"></i></a>
        </div>
    </div>
</div>
<div class="row alert__wrap" id="smAlert" style="display:none">
    <div class="alert alert-success">
        <span class="alert__icon"><i class="fas fa-check"></i></span>
        <p id="smAlertText"></p>
        <span class="alert__btn"><i class="fas fa-times"></i></span>
    </div>
</div>
