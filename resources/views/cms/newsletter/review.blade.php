@extends('cms.layout.container')

@section('content')
<div class="col-sm-9 main">
    @include('cms.newsletter.partials.heading', ['subtitle' => __('app.newsletter_step_review')])
    @include('cms.partials.alerts')
    @if($newsletter->status !== 'published')
        @include('cms.newsletter.partials.stepper', ['current' => 3])
    @endif

    <div class="form__wrap">
        <div class="row form__title">
            <h3>{{ __('app.newsletter_step_review') }}</h3>
        </div>
        @if($newsletter->status === 'published')
        <div class="row form__block">
            <div class="col-md-12">
                <p>{{ __('app.newsletter_published') }}</p>
                <a href="{{ route('newsletter.public', $newsletter) }}" target="_blank" class="button">{{ __('app.newsletter_public') }}</a>
            </div>
        </div>
        @elseif($newsletter->status === 'scheduled')
        <div class="row form__block">
            <div class="col-md-12">
                <p>{{ __('app.newsletter_scheduled_for') }} {{ $newsletter->scheduled_at->timezone(\App\Models\Newsletter::TIMEZONE)->format('d.m.Y H:i') }}</p>
            </div>
        </div>
        @endif
        <div class="row form__block">
            <div class="col-md-12">
                <iframe class="nl-preview" src="{{ route('newsletter.preview', $newsletter) }}" title="{{ __('app.newsletter_step_review') }}"></iframe>
            </div>
        </div>
        <div class="row form__submit">
            <div class="col-md-12">
                @if($newsletter->status !== 'published')
                <a href="{{ route('newsletter.content', $newsletter) }}" class="button button-white">{{ __('app.newsletter_back') }}</a>
                <a href="{{ route('newsletter.publish', $newsletter) }}" class="button">Next</a>
                @else
                <a href="{{ route('newsletter.index') }}" class="button button-white">{{ __('app.list') }}</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    .nl-preview { width: 100%; height: 1100px; border: 1px solid #ddd; background: #e7eef3; }
</style>
@endsection
