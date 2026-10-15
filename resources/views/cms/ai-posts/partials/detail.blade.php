@php
    $when = !empty($post['scheduled_at']) ? \Illuminate\Support\Carbon::parse($post['scheduled_at']) : null;
    $statusLabel = ($statuses[$post['status']] ?? $post['status']);
@endphp
@if($post['status'] === 'failed')
<div class="row alert__wrap">
    <div class="alert alert-danger">
        <span class="alert__icon"><i class="fas fa-exclamation-triangle"></i></span>
        <p>Something went wrong while publishing this post.</p>
        <p>
            <button type="button" class="button js-retry" data-id="{{ $post['id'] }}">Retry</button>
            <button type="button" class="button button-white js-error" data-id="{{ $post['id'] }}" data-error="{{ $post['error'] }}">View Error</button>
        </p>
        <span class="alert__btn"><i class="fas fa-times"></i></span>
    </div>
</div>
@endif
<div class="form__wrap">
    <div class="row form__title"><h3>Status</h3></div>
    <div class="row form__block">
        <div class="col-md-12">
            <p><span class="sm-badge sm-badge--{{ $post['status'] }}">{{ $statusLabel }}</span></p>
        </div>
    </div>
    <div class="row form__title"><h3>Platforms</h3></div>
    <div class="row form__block">
        <div class="col-md-12">
            <ul class="sm-check">
                @if(in_array('facebook', $post['platforms']))
                <li><i class="fas fa-check"></i> Facebook <span class="sm-ico sm-ico--fb"><i class="fab fa-facebook-f"></i></span> {{ $brand['page'] }}</li>
                @endif
                @if(in_array('instagram', $post['platforms']))
                <li><i class="fas fa-check"></i> Instagram <span class="sm-ico sm-ico--ig"><i class="fab fa-instagram"></i></span> {{ $brand['instagram'] }}</li>
                @endif
            </ul>
        </div>
    </div>
    <div class="row form__title"><h3>Content</h3></div>
    <div class="row form__block">
        <div class="col-md-12">
            <p class="sm-kv"><span>Original prompt</span> {{ $post['prompt'] }}</p>
            <p class="sm-kv"><span>Source URL</span>
                @if($post['source_url'])
                <a href="{{ $post['source_url'] }}" target="_blank" rel="noopener">{{ $post['source_url'] }}</a>
                @else
                —
                @endif
            </p>
            <p class="sm-kv"><span>Tone of voice</span> {{ ucfirst($post['tone']) }}</p>
            <p><strong>Facebook</strong></p>
            <p>{{ $post['facebook'] }}</p>
            <p><strong>Instagram</strong></p>
            <p>{{ $post['instagram'] }}</p>
            <p>
                @foreach($post['hashtags'] as $tag)
                <span class="sm-chip">#{{ $tag }}</span>
                @endforeach
            </p>
        </div>
    </div>
    <div class="row form__title"><h3>Media</h3></div>
    <div class="row form__block">
        <div class="col-md-12">
            <img src="{{ $post['image_url'] }}" alt="" class="sm-detail-image">
        </div>
    </div>
    <div class="row form__title"><h3>Schedule</h3></div>
    <div class="row form__block">
        <div class="col-md-12">
            @if($when)
            <p class="sm-kv"><span>Date</span> {{ $when->format('F j, Y') }}</p>
            <p class="sm-kv"><span>Time</span> {{ $when->format('H:i') }}</p>
            <p class="sm-kv"><span>Timezone</span> {{ $post['timezone'] }}</p>
            @else
            <p>No publishing time yet.</p>
            @endif
        </div>
    </div>
    <div class="row form__title"><h3>Activity</h3></div>
    <ul class="sm-timeline">
        @foreach($post['activity'] as $event)
        <li class="{{ $loop->last ? 'is-last' : '' }}">
            <strong>{{ $event['label'] }}</strong>
            <em>{{ $event['at'] }}</em>
        </li>
        @endforeach
    </ul>
    <div class="row form__submit sm-actionbar">
        <a href="{{ url('cms/ai-posts/'.$post['id'].'/edit') }}" class="button">Edit</a>
        <a href="{{ url('cms/ai-posts/create?duplicate='.$post['id']) }}" class="button button-white">Duplicate</a>
        @if($post['status'] === 'scheduled')
        <button type="button" class="button button-white js-cancel" data-id="{{ $post['id'] }}">Cancel Schedule</button>
        @endif
    </div>
</div>
