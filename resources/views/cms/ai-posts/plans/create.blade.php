@extends('cms.layout.container')

@section('content')
<div class="col-sm-9 main sm-module" id="plan-app" data-page="create" data-max-posts="{{ $maxPosts }}">
    @include('cms.ai-posts.partials.head', [
        'heading' => 'New Content Plan',
        'description' => 'Research a topic and draft a social calendar for 1, 3, or 6 months.',
    ])
    @include('cms.partials.alerts')

    <form method="POST" action="{{ route('ai-posts.plans.store') }}" id="planForm">
        @csrf
        <div class="form__wrap">
            <div class="row form__title"><h3>Topic</h3></div>
            <div class="row form__block">
                <div class="col-md-3"><label class="label-left" for="planTopic">What should the plan cover?</label></div>
                <div class="col-md-9">
                    <textarea id="planTopic" name="topic" rows="5" class="textarea-default" required maxlength="5000" placeholder="A six-month content plan about seasonal home textiles, care tips, and new arrivals.">{{ old('topic') }}</textarea>
                </div>
            </div>

            <div class="row form__title"><h3>Horizon</h3></div>
            <div class="row form__block">
                <div class="col-md-3"><label class="label-left">Duration</label></div>
                <div class="col-md-9">
                    <div class="sm-choice">
                        @foreach([1 => '1 month', 3 => '3 months', 6 => '6 months'] as $months => $label)
                        <label class="sm-choice__item">
                            <input type="radio" name="duration_months" value="{{ $months }}" @checked((int) old('duration_months', 1) === $months)>
                            <span>{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3"><label class="label-left" for="planFrequency">Posts per week</label></div>
                <div class="col-md-9">
                    <select id="planFrequency" name="posts_per_week" class="select-default">
                        @for($i = 1; $i <= 7; $i++)
                        <option value="{{ $i }}" @selected((int) old('posts_per_week', 2) === $i)>{{ $i }} {{ $i === 1 ? 'post' : 'posts' }}</option>
                        @endfor
                    </select>
                    <p class="sm-help">Used when no weekdays are selected. Monday to Friday is spread automatically.</p>
                </div>
            </div>
            @php
                $selectedDays = array_values((array) old('publish_days', []));
                $selectedTypes = (array) old('day_post_types', []);
            @endphp
            <div class="row form__block">
                <div class="col-md-3"><label class="label-left">Publishing days</label></div>
                <div class="col-md-9">
                    <div class="sm-choice" id="planDays">
                        @foreach($weekdayShort as $day => $label)
                        <label class="sm-choice__item">
                            <input type="checkbox" name="publish_days[]" value="{{ $day }}" @checked(in_array($day, $selectedDays, true))>
                            <span>{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                    <p class="sm-help">Optional. One post is placed on each selected day, and that schedule replaces posts per week.</p>
                </div>
            </div>
            <div class="row form__block{{ $selectedDays === [] ? ' is-hidden' : '' }}" id="planDayTypes">
                <div class="col-md-3"><label class="label-left">Post type by day</label></div>
                <div class="col-md-9">
                    <div class="sm-day-types">
                        @foreach($weekdays as $day => $label)
                        @php $daySelected = in_array($day, $selectedDays, true); @endphp
                        <div class="sm-day-type{{ $daySelected ? '' : ' is-hidden' }}" data-day="{{ $day }}">
                            <label for="planType{{ ucfirst($day) }}">{{ $label }}</label>
                            <select id="planType{{ ucfirst($day) }}" name="day_post_types[{{ $day }}]" class="select-default" @disabled(! $daySelected) @required($daySelected)>
                                <option value="" @selected(($selectedTypes[$day] ?? '') === '')>Select type</option>
                                @foreach($postTypes as $value => $typeLabel)
                                <option value="{{ $value }}" @selected(($selectedTypes[$day] ?? '') === $value)>{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endforeach
                    </div>
                    <p class="sm-help">Assign a type to every selected day. Monday can be educational while Thursday is promotional. Tone of voice still applies to the whole plan.</p>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3"><label class="label-left" for="planTime">Publishing time</label></div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-sm-4">
                            <input type="time" id="planTime" name="publish_time" class="input-default" value="{{ old('publish_time', '09:00') }}" required>
                        </div>
                        <div class="col-sm-8">
                            <select name="timezone" class="select-default" id="planTimezone">
                                @foreach(['Europe/Zurich', 'UTC'] as $timezone)
                                <option value="{{ $timezone }}" @selected(old('timezone', 'Europe/Zurich') === $timezone)>{{ $timezone }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p class="sm-estimate" id="planEstimate"></p>
                </div>
            </div>

            <div class="row form__title"><h3>Platforms</h3></div>
            <div class="row form__block">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-sm-6">
                            <label class="sm-platform is-on">
                                <i class="fab fa-facebook-f sm-ico sm-ico--fb"></i>
                                <input type="checkbox" name="platforms[]" value="facebook" @checked(in_array('facebook', (array) old('platforms', ['facebook', 'instagram']), true))>
                                <strong>Facebook</strong>
                                <small>Facebook Page</small>
                                <em>{{ $brand['page'] }}</em>
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="sm-platform is-on">
                                <i class="fab fa-instagram sm-ico sm-ico--ig"></i>
                                <input type="checkbox" name="platforms[]" value="instagram" @checked(in_array('instagram', (array) old('platforms', ['facebook', 'instagram']), true))>
                                <strong>Instagram</strong>
                                <small>Instagram</small>
                                <em>{{ $brand['instagram'] }}</em>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row form__title"><h3>Settings</h3></div>
            <div class="row form__block">
                <div class="col-md-3"><label class="label-left">Tone of Voice</label></div>
                <div class="col-md-9">
                    <select class="select-default" name="tone">
                        @foreach(['professional' => 'Professional', 'casual' => 'Casual', 'promotional' => 'Promotional', 'educational' => 'Educational'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('tone', 'professional') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3"><label class="label-left">Web research</label></div>
                <div class="col-md-9">
                    <div class="onof">
                        <label><input type="radio" name="enable_web_search" value="1" @checked(old('enable_web_search', '1') == '1')> <span>On</span></label>
                        <label><input type="radio" name="enable_web_search" value="0" @checked(old('enable_web_search', '1') == '0')> <span>Off</span></label>
                    </div>
                    <p class="sm-help">Search the topic once, then use those notes while the posts are written.</p>
                </div>
            </div>

            <div class="row form__submit sm-actionbar">
                <button type="submit" class="button" id="planSubmit">Generate Plan</button>
                <a href="{{ route('ai-posts.plans.index') }}" class="button button-white">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script src="{{ asset('cmsfiles/js/content-plans.js') }}?v=3"></script>
@endsection
