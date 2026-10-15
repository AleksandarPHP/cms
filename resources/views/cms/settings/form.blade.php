@extends('cms.layout.container')

@section('content')
<div class="col-sm-9 main">
    <div class="row main__title">
        <div class="col-sm-8">
            <h2><i class="fas fa-list"></i>{{ $title }}</h2>
            <p>{{ __('app.edit') }}</p>
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
    <form method="post" action="{{ url('cms/'.$route.'/'.$item->id) }}"enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form__wrap">
            <div class="row form__title">
                <h3>{{ $title }}</h3>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.webtitle') }}
                    </label>
                </div>
                <div class="col-md-9">
                    <input type="text" name="webtitle" class="input-default" placeholder="{{ __('app.webtitle') }}" value="{{ old('webtitle', $item->webtitle) }}" {!! $errors->has('webtitle') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.webdescription')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <textarea class="textarea-default" name="webdescription" rows="6" placeholder="{{ __('app.webdescription')}}" {!! $errors->has('webdescription') ? 'style="border-color:red;"' : '' !!}>{{ old('webdescription', $item->webdescription) }}</textarea>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.webkeys')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <textarea class="textarea-default" name="webkeys" rows="6" placeholder="{{ __('app.webkeys')}}" {!! $errors->has('webkeys') ? 'style="border-color:red;"' : '' !!}>{{ old('webkeys', $item->webkeys) }}</textarea>
                </div>
            </div>
            @if(($brandColors ?? []) !== [])
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">{{ __('app.brandcolors') }}</label>
                </div>
                <div class="col-md-9">
                    @foreach($brandColors as $color)
                    <span style="display:inline-block;width:36px;height:36px;margin:0 8px 8px 0;vertical-align:middle;border:1px solid #ddd;background:{{ $color }};"></span>
                    <span style="margin-right:16px;vertical-align:middle;">{{ $color }}</span>
                    @endforeach
                    <p>{{ __('app.brandcolorshelp') }}</p>
                </div>
            </div>
            @endif
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">{{ __('app.brandfontprimary') }}</label>
                </div>
                <div class="col-md-9">
                    <input type="text" name="brand_font_primary" class="input-default" placeholder="Montserrat" value="{{ old('brand_font_primary', $item->brand_font_primary) }}" {!! $errors->has('brand_font_primary') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">{{ __('app.brandfontsecondary') }}</label>
                </div>
                <div class="col-md-9">
                    <input type="text" name="brand_font_secondary" class="input-default" placeholder="Open Sans" value="{{ old('brand_font_secondary', $item->brand_font_secondary) }}" {!! $errors->has('brand_font_secondary') ? 'style="border-color:red;"' : '' !!}>
                    <p>{{ __('app.brandfontshelp') }}</p>
                </div>
            </div>
            @php
                $pics = array('Logo' => 'logo', 'Favicon' => 'favicon', 'Map' => 'map');
            @endphp
            @foreach($pics as $key => $i)
            @php
                if($key == 'Logo') $size = '150x50px';
                else if($key == 'Favicon') $size = '50x50px';
                else if($key == 'Map') $size = '960x680px';
            @endphp
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ $key }} ({{ $size }}):
                    </label>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="add-image">
                                <div class="add-image-wrap"  {!! $errors->has($i) ? 'style="border-color:red;"' : '' !!}>
                                    @if(is_null($item[$i]) || $item[$i]=='')
                                    <a href="{{ asset('cmsfiles/images/photo.png') }}" data-fancybox="photo">
                                        <img src="{{ asset('cmsfiles/images/photo.png') }}" alt="photo">
                                    </a>
                                    @else
                                    <a href="{{ asset('storage/'.$item[$i]) }}" data-fancybox="photo">
                                        <img src="{{ asset('storage/'.$item[$i]) }}" alt="photo">
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
</script> 
@endsection

