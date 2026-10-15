@extends('cms.layout.container')

@section('content')
<div class="col-sm-9 main">
    @include('cms.newsletter.partials.heading', ['subtitle' => __('app.newsletter_step_content')])
    @include('cms.partials.alerts')
    @include('cms.newsletter.partials.stepper', ['current' => 2])

    <form method="post" action="{{ route('newsletter.content.store', $newsletter) }}">
        @csrf
        <div class="form__wrap">
            <div class="row form__title">
                <h3>{{ __('app.newsletter_step_content') }}</h3>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left" for="nlTitle">{{ __('app.title') }}</label>
                </div>
                <div class="col-md-9">
                    <input id="nlTitle" type="text" name="title" class="input-default" maxlength="191" value="{{ old('title', $newsletter->title) }}" {!! $errors->has('title') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left" for="nlText">{{ __('app.newsletter_text') }}</label>
                </div>
                <div class="col-md-9">
                    <textarea id="nlText" name="text" rows="8" class="textarea-default" maxlength="5000" {!! $errors->has('text') ? 'style="border-color:red;"' : '' !!}>{{ old('text', $newsletter->text) }}</textarea>
                </div>
            </div>

            <div class="row form__title">
                <h3>{{ __('app.newsletter_ai') }}</h3>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left" for="nlInstruction">{{ __('app.ai_instruction') }}</label>
                </div>
                <div class="col-md-9">
                    <textarea id="nlInstruction" rows="3" class="textarea-default" maxlength="2000" placeholder="{{ __('app.newsletter_ai_placeholder') }}"></textarea>
                    <p class="nl-help">{{ __('app.newsletter_ai_help') }}</p>
                    <button type="button" class="button" id="nlAi">{{ __('app.newsletter_ai_generate') }}</button>
                    <p class="nl-status" id="nlAiStatus"></p>
                </div>
            </div>

            <div class="row form__submit">
                <div class="col-md-9 col-md-offset-3">
                    <a href="{{ route('newsletter.blogs', $newsletter) }}" class="button button-white">{{ __('app.newsletter_back') }}</a>
                    <button type="submit" class="button">Next</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    (function () {
        var button = document.getElementById('nlAi');
        var status = document.getElementById('nlAiStatus');
        var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        button.addEventListener('click', function () {
            button.disabled = true;
            status.className = 'nl-status';
            status.textContent = @json(__('app.ai_working'));

            fetch(@json(route('newsletter.ai', $newsletter)), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    instruction: document.getElementById('nlInstruction').value
                })
            }).then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) {
                        var message = data.message || 'Error';
                        if (data.errors) {
                            message = Object.keys(data.errors).map(function (key) {
                                return data.errors[key][0];
                            }).join(' ');
                        }
                        throw new Error(message);
                    }
                    return data;
                });
            }).then(function (data) {
                document.getElementById('nlTitle').value = data.title || '';
                document.getElementById('nlText').value = data.text || '';
                status.textContent = @json(__('app.newsletter_ai_done'));
            }).catch(function (error) {
                status.className = 'nl-status is-error';
                status.textContent = error.message;
            }).then(function () {
                button.disabled = false;
            });
        });
    })();
</script>
<style>
    .nl-help, .nl-status { margin-top: 8px; color: #666; }
    .nl-status.is-error { color: #c0392b; }
</style>
@endsection
