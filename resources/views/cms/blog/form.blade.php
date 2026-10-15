@extends('cms.layout.container')

@section('content')
<div class="col-sm-9 main">
    <div class="row main__title">
        <div class="col-sm-8">
            <h2><i class="fas fa-list"></i>{{ $title }}</h2>
            <p>{{ __('app.add') }}</p>
        </div>
        <div class="col-sm-4 shortcuts">
            <a
                href="{{ url('cms') }}"
                class="shortcut"
                data-toggle="tooltip"
                data-placement="bottom"
                title="{{ __('app.daschlow') }}"
            >
                <i class="fas fa-desktop"></i>
            </a>
            <a
                href="{{ url('cms/'.$route) }}"
                class="shortcut"
                data-toggle="tooltip"
                data-placement="bottom"
                title="{{ __('app.list') }}"
            >
                <i class="far fa-clone"></i>
            </a>
            <a
                href="{{ url('cms/'.$route.'/create') }}"
                class="shortcut"
                data-toggle="tooltip"
                data-placement="bottom"
                title="{{ __('app.add') }}"
            >
                <i class="fas fa-plus"></i>
            </a>
            <a
                href="https://symple.kayako.com/"
                target="_blank"
                class="shortcut"
                data-toggle="tooltip"
                data-placement="bottom"
                title="Support"
            >
                <i class="fas fa-question"></i>
            </a>
        </div>
    </div>
    @include('cms.partials.alerts')
    <form method="post" action="@if(!$editing) {{ url('cms/'.$route) }} @else {{ url('cms/'.$route.'/'.$item->id) }} @endif" enctype="multipart/form-data">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="form__wrap">
            <div class="row form__title">
                <h3>{{ $title }}</h3>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        Meta title
                    </label>
                </div>
                <div class="col-md-9">
                    <input type="text" name="meta_title" class="input-default" placeholder="Meta title" value="{{ old('meta_title', $item->meta_title) }}" {!! $errors->has('meta_title') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        Meta description
                    </label>
                </div>
                <div class="col-md-9">
                    <textarea name="meta_description" placeholder="Meta description" id="meta_description" cols="75" rows="10">{{ old('meta_description', $item->meta_description) }}</textarea>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.title')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <input type="text" name="title" class="input-default" placeholder="{{ __('app.title')}}" value="{{ old('title', $item->title) }}" {!! $errors->has('title') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.subtitle')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <input type="text" name="subtitle" class="input-default" placeholder="{{ __('app.subtitle')}}" value="{{ old('subtitle', $item->subtitle) }}" {!! $errors->has('subtitle') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__title">
                <h3> {{ __('app.description')}}</h3>
            </div>
            <textarea name="description" id="description" rows="5" class="textarea-default editor" placeholder="{{ __('app.description')}}" {!! $errors->has('description') ? 'style="border-color:red;"' : '' !!}>
                {{ old('description', $item->description) }}
            </textarea>
            <div class="row form__title">
                <h3> {{ __('app.content')}}</h3>
            </div>
            <textarea name="content" id="content" rows="5" class="textarea-default editor" placeholder="{{ __('app.content')}}" {!! $errors->has('content') ? 'style="border-color:red;"' : '' !!}>
                {{ old('content', $item->content) }}
            </textarea>
            <div class="row form__title">
                <h3>{{ __('app.ai_blog') }}</h3>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left" for="blogAiInstruction">{{ __('app.ai_instruction') }}</label>
                </div>
                <div class="col-md-9">
                    <textarea id="blogAiInstruction" rows="3" class="textarea-default" placeholder="{{ __('app.ai_instruction_placeholder') }}"></textarea>
                    <p class="blog-ai-help">{{ __('app.ai_text_help') }}</p>
                    <div class="blog-ai-fields">
                        @foreach(['meta_title' => 'Meta title', 'meta_description' => 'Meta description', 'title' => __('app.title'), 'subtitle' => __('app.subtitle'), 'description' => __('app.description'), 'content' => __('app.content')] as $field => $label)
                        <label>
                            <input type="checkbox" class="blog-ai-field" value="{{ $field }}" checked>
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>
                    <button type="button" class="button" id="blogAiRefine">{{ __('app.ai_refine') }}</button>
                    <p class="blog-ai-status" id="blogAiStatus"></p>
                </div>
            </div>
            @php
            $pics = array( __('app.image') => 'image', __('app.image2') => 'image2');
            @endphp
            @foreach($pics as $key => $i)
            @php
                if($i == 'image') $size =  '171x171px';
                else if($i == 'image2') $size = '1920x863px';
                $savedPath = is_string($item[$i] ?? null) ? $item[$i] : '';
                $aiPath = old('ai_'.$i);
                $previewPath = $savedPath;
                if (is_string($aiPath) && preg_match('/^blog\/ai\/[A-Za-z0-9\-]+\.jpg$/', $aiPath)) {
                    $previewPath = $aiPath;
                }
            @endphp
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ $key }} ({{ $size }}):
                    </label>
                </div>
                <div class="col-md-9">
                    <div class="row blog-ai-image-box" data-saved="{{ $savedPath }}" data-path="{{ $previewPath }}">
                        <input type="hidden" name="ai_{{ $i }}" id="ai_{{ $i }}" value="{{ is_string($aiPath) && preg_match('/^blog\/ai\/[A-Za-z0-9\-]+\.jpg$/', $aiPath) ? $aiPath : '' }}">
                        <div class="col-sm-6">
                            <div class="add-image">
                                <div class="add-image-wrap"  {!! $errors->has($i) ? 'style="border-color:red;"' : '' !!}>
                                    @if(!is_null($item[$i]) && $item[$i]!='')<a href="{{ url('cms/'.$route.'/imagedelete/'.$item->id.'?image='.$i) }}"><span class="add-image-btn"><i class="fas fa-times"></i></span></a>@endif
                                    @if($previewPath === '')
                                    <a href="{{ asset('cmsfiles/images/photo.png') }}" data-fancybox="photo">
                                        <img src="{{ asset('cmsfiles/images/photo.png') }}" alt="photo">
                                    </a>
                                    @else
                                    <a href="{{ asset('storage/'.$previewPath) }}" data-fancybox="photo">
                                        <img src="{{ asset('storage/'.$previewPath) }}" alt="photo">
                                    </a>
                                    @endif
                                </div>
                                <label for="my-file{{ $i }}" class="input-file-trigger{{ $i }}">
                                <input name="{{ $i }}" class="input-file{{ $i }}" id="my-file{{ $i }}" type="file">
                                    <span >{{ __('app.bildaktual') }}</span>
                                </label>
                                <script>
                                    var fileInput = document.querySelector(".input-file{{ $i }}"),
                                        button = document.querySelector(".input-file-trigger{{ $i }}");
            
                                    button.addEventListener("keydown", function (event) {
                                        if (event.keyCode == 13 || event.keyCode == 32) {
                                            fileInput.focus();
                                        }
                                    });
                                    button.addEventListener("click", function () {
                                        fileInput.focus();
                                        return false;
                                    });
                                </script>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <textarea id="blogAiImageNote-{{ $i }}" rows="5" class="textarea-default blog-ai-note" placeholder="{{ __('app.ai_image_help') }}"></textarea>
                            <button type="button" class="button blog-ai-image" data-target="{{ $i }}">{{ __('app.ai_generate_image') }}</button>
                            <p class="blog-ai-status" id="blogAiImageStatus-{{ $i }}"></p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            <div class="row form__submit">
                <div class="col-md-9 col-md-offset-3">
                    <button type="submit" class="button">@if($editing){{ __('app.edit') }}@else{{ __('app.add') }}@endif</button>
                </div>
            </div>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script type="text/javascript">
    $('input[type="file"]').change(function() {        
        readURL(this, $(this).parents('.add-image').children('.add-image-wrap').find('img'));
    });
    
    tinymce.init({
        selector : ".editor",
        language : "{{ str_replace('_', '-', app()->getLocale()) }}",
        plugins : ["advlist autolink lists link charmap print preview anchor", "searchreplace visualblocks code fullscreen", "insertdatetime media table paste "],
        toolbar : "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link code",
    });

    (function () {
        var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        var textUrl = @json(url('cms/blog/ai/text'));
        var imageUrl = @json(url('cms/blog/ai/image'));
        var workingText = @json(__('app.ai_working'));

        function fieldValue(name) {
            if ((name === 'description' || name === 'content') && window.tinymce) {
                var editor = tinymce.get(name);
                if (editor) return editor.getContent();
            }
            var el = document.querySelector('[name="' + name + '"]');
            return el ? el.value : '';
        }

        function setField(name, value) {
            if ((name === 'description' || name === 'content') && window.tinymce) {
                var editor = tinymce.get(name);
                if (editor) {
                    editor.setContent(value || '');
                    return;
                }
            }
            var el = document.querySelector('[name="' + name + '"]');
            if (el) el.value = value || '';
        }

        function post(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(body)
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
            });
        }

        var refineBtn = document.getElementById('blogAiRefine');
        if (refineBtn) {
            refineBtn.addEventListener('click', function () {
                var status = document.getElementById('blogAiStatus');
                var fields = Array.prototype.map.call(document.querySelectorAll('.blog-ai-field:checked'), function (el) {
                    return el.value;
                });
                refineBtn.disabled = true;
                status.className = 'blog-ai-status';
                status.textContent = workingText;
                post(textUrl, {
                    instruction: document.getElementById('blogAiInstruction').value,
                    fields: fields,
                    meta_title: fieldValue('meta_title'),
                    meta_description: fieldValue('meta_description'),
                    title: fieldValue('title'),
                    subtitle: fieldValue('subtitle'),
                    description: fieldValue('description'),
                    content: fieldValue('content')
                }).then(function (data) {
                    Object.keys(data.fields || {}).forEach(function (key) {
                        setField(key, data.fields[key]);
                    });
                    status.textContent = @json(__('app.ai_done'));
                }).catch(function (error) {
                    status.className = 'blog-ai-status is-error';
                    status.textContent = error.message;
                }).then(function () {
                    refineBtn.disabled = false;
                });
            });
        }

        Array.prototype.forEach.call(document.querySelectorAll('.blog-ai-image'), function (button) {
            button.addEventListener('click', function () {
                var target = button.getAttribute('data-target');
                var status = document.getElementById('blogAiImageStatus-' + target);
                var hidden = document.getElementById('ai_' + target);
                var wrap = button.closest('.blog-ai-image-box');
                var img = wrap.querySelector('img');
                var link = wrap.querySelector('a[data-fancybox]');
                button.disabled = true;
                status.className = 'blog-ai-status';
                status.textContent = workingText;
                post(imageUrl, {
                    target: target,
                    instruction: document.getElementById('blogAiImageNote-' + target).value,
                    title: fieldValue('title'),
                    subtitle: fieldValue('subtitle'),
                    description: fieldValue('description'),
                    content: fieldValue('content'),
                    current_path: wrap.getAttribute('data-path') || '',
                    previous_path: hidden.value || '',
                    saved_path: wrap.getAttribute('data-saved') || ''
                }).then(function (data) {
                    hidden.value = data.path;
                    wrap.setAttribute('data-path', data.path);
                    if (img) img.src = data.url;
                    if (link) link.href = data.url;
                    var file = document.getElementById('my-file' + target);
                    if (file) file.value = '';
                    status.textContent = @json(__('app.ai_image_done'));
                }).catch(function (error) {
                    status.className = 'blog-ai-status is-error';
                    status.textContent = error.message;
                }).then(function () {
                    button.disabled = false;
                });
            });
        });
    })();
</script>
<style>
    .blog-ai-fields { display: flex; flex-wrap: wrap; gap: 8px 16px; margin: 10px 0; }
    .blog-ai-fields label { font-weight: 400; margin: 0; }
    .blog-ai-help, .blog-ai-status { margin-top: 8px; color: #666; }
    .blog-ai-status.is-error { color: #c0392b; }
    .blog-ai-note { margin-top: 0; margin-bottom: 8px; min-height: 120px; resize: vertical; }
    .blog-ai-image { margin-top: 0; }
</style>  
@endsection
