<div class="row main__title">
    <div class="col-sm-8">
        <h2><i class="fas fa-envelope"></i> {{ $title }}</h2>
        <p>{{ $subtitle }}</p>
    </div>
    <div class="col-sm-4 shortcuts">
        <a href="{{ url('cms') }}" class="shortcut" data-toggle="tooltip" data-placement="bottom" title="{{ __('app.daschlow') }}">
            <i class="fas fa-desktop"></i>
        </a>
        <a href="{{ route('newsletter.index') }}" class="shortcut" data-toggle="tooltip" data-placement="bottom" title="{{ __('app.list') }}">
            <i class="far fa-clone"></i>
        </a>
        <a href="{{ route('newsletter.create') }}" class="shortcut" data-toggle="tooltip" data-placement="bottom" title="{{ __('app.add') }}">
            <i class="fas fa-plus"></i>
        </a>
    </div>
</div>
