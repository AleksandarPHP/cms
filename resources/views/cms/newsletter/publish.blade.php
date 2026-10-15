@extends('cms.layout.container')

@section('content')
<div class="col-sm-9 main">
    @include('cms.newsletter.partials.heading', ['subtitle' => __('app.newsletter_step_publish')])
    @include('cms.partials.alerts')
    @include('cms.newsletter.partials.stepper', ['current' => 4])

    <form method="post" action="{{ route('newsletter.publish.store', $newsletter) }}">
        @csrf
        <div class="form__wrap">
            <div class="row form__title">
                <h3>{{ __('app.newsletter_step_publish') }}</h3>
            </div>
            <div class="row form__block">
                <div class="col-md-12">
                    <p>{{ __('app.newsletter_publish_help') }}</p>
                    @php $mode = old('mode', $newsletter->status === 'scheduled' ? 'schedule' : 'now'); @endphp
                    <label class="nl-choice">
                        <input type="radio" name="mode" value="now" @checked($mode === 'now')>
                        <span>Push now</span>
                    </label>
                    <label class="nl-choice">
                        <input type="radio" name="mode" value="schedule" @checked($mode === 'schedule')>
                        <span>{{ __('app.newsletter_schedule') }}</span>
                    </label>
                    <div id="nlSchedule" class="{{ $mode === 'schedule' ? '' : 'is-hidden' }}">
                        <label for="nlWhen">{{ __('app.newsletter_scheduled_at') }}</label>
                        <input id="nlWhen" type="datetime-local" name="scheduled_at" class="input-default" value="{{ old('scheduled_at', $scheduledLocal) }}" {!! $errors->has('scheduled_at') ? 'style="border-color:red;"' : '' !!}>
                    </div>
                </div>
            </div>
            <div class="row form__submit">
                <div class="col-md-12">
                    <a href="{{ route('newsletter.review', $newsletter) }}" class="button button-white">{{ __('app.newsletter_back') }}</a>
                    <button type="submit" class="button" id="nlPublishButton">{{ $mode === 'schedule' ? __('app.newsletter_schedule') : 'Push now' }}</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    (function () {
        var schedule = document.getElementById('nlSchedule');
        var button = document.getElementById('nlPublishButton');
        var scheduleLabel = @json(__('app.newsletter_schedule'));

        Array.prototype.forEach.call(document.querySelectorAll('input[name="mode"]'), function (input) {
            input.addEventListener('change', function () {
                var planned = input.value === 'schedule' && input.checked;
                schedule.classList.toggle('is-hidden', !planned);
                button.textContent = planned ? scheduleLabel : 'Push now';
            });
        });
    })();
</script>
<style>
    .nl-choice { display: flex; gap: 8px; align-items: center; margin: 0 0 10px; font-weight: 400; }
    #nlSchedule { margin-top: 12px; max-width: 320px; }
    #nlSchedule label { display: block; margin-bottom: 6px; }
    .is-hidden { display: none; }
</style>
@endsection
