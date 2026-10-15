@extends('cms.layout.container')

@section('content')
<div class="col-sm-9 main">
    @include('cms.newsletter.partials.heading', ['subtitle' => __('app.newsletter_step_blogs')])
    @include('cms.partials.alerts')
    @include('cms.newsletter.partials.stepper', ['current' => 1])

    <form method="post" action="{{ route('newsletter.blogs.store', $newsletter) }}">
        @csrf
        <div class="form__wrap">
            <div class="row form__title">
                <h3>{{ __('app.newsletter_select_blogs') }}</h3>
            </div>
            <div class="row form__block">
                <div class="col-md-12">
                    @if($blogs->isEmpty())
                        <p>{{ __('app.newsletter_no_blogs') }}</p>
                        <a href="{{ url('cms/blog/create') }}" class="button">{{ __('app.add') }}</a>
                    @else
                        <label class="nl-all">
                            <input type="checkbox" id="nlAll">
                            <span>{{ __('app.newsletter_select_all') }}</span>
                        </label>
                        @php $checked = array_map('intval', (array) old('blog_ids', $selected)); @endphp
                        @foreach($blogs as $blog)
                        @php
                            $thumb = $blog->image
                                ? \App\Helpers\Helper::image($blog->image, 144, 144)
                                : ($blog->image2 ? \App\Helpers\Helper::image($blog->image2, 144, 144) : null);
                        @endphp
                        <label class="nl-blog {{ in_array($blog->id, $checked, true) ? 'is-on' : '' }}">
                            <input type="checkbox" name="blog_ids[]" value="{{ $blog->id }}" @checked(in_array($blog->id, $checked, true))>
                            @if($thumb)
                            <img src="{{ $thumb }}" alt="">
                            @else
                            <img src="{{ asset('cmsfiles/images/photo.png') }}" alt="">
                            @endif
                            <span>
                                <strong>{{ $blog->title }}</strong>
                                <small>{{ $blog->subtitle }}</small>
                            </span>
                        </label>
                        @endforeach
                    @endif
                </div>
            </div>
            @if($blogs->isNotEmpty())
            <div class="row form__submit">
                <div class="col-md-12">
                    <button type="submit" class="button">Next</button>
                </div>
            </div>
            @endif
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    (function () {
        var all = document.getElementById('nlAll');
        var boxes = Array.prototype.slice.call(document.querySelectorAll('.nl-blog input'));

        function paint(box) {
            box.closest('.nl-blog').classList.toggle('is-on', box.checked);
        }

        boxes.forEach(function (box) {
            box.addEventListener('change', function () {
                paint(box);
            });
        });

        if (all) {
            all.addEventListener('change', function () {
                boxes.forEach(function (box) {
                    box.checked = all.checked;
                    paint(box);
                });
            });
        }
    })();
</script>
<style>
    .nl-all { display: inline-flex; gap: 8px; align-items: center; margin-bottom: 12px; font-weight: 400; }
    .nl-blog { display: flex; gap: 14px; align-items: center; border: 1px solid #e5e5e5; padding: 10px 12px; margin: 0 0 8px; cursor: pointer; font-weight: 400; }
    .nl-blog.is-on { border-color: #222; background: #fafafa; }
    .nl-blog img { width: 72px; height: 72px; object-fit: cover; flex: 0 0 72px; }
    .nl-blog strong, .nl-blog small { display: block; }
    .nl-blog small { color: #666; margin-top: 4px; }
</style>
@endsection
