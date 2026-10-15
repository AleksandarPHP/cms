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
                        {{ __('app.name') }}
                    </label>
                </div>
                <div class="col-md-9">
                    <input type="text" name="name" class="input-default" placeholder="{{ __('app.name') }}" value="{{ old('name', $item->name) }}" {!! $errors->has('name') ? 'style="border-color:red;"' : '' !!}>
                    
                </div>
            </div>
            <div class="row form__title">
                <h3> {{ __('app.description')}}</h3>
            </div>
            <textarea name="description" id="description" rows="5" class="textarea-default editor" placeholder="{{ __('app.description')}}" {!! $errors->has('description') ? 'style="border-color:red;"' : '' !!}>
                {{ old('description', $item->description) }}
            </textarea>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.street')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <input type="text" name="street" class="input-default" placeholder="{{ __('app.street') }}" value="{{ old('street', $item->street) }}" {!! $errors->has('street') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.plz')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <input type="text" name="plz" class="input-default" placeholder="{{ __('app.plz') }}" value="{{ old('plz', $item->plz) }}" {!! $errors->has('plz') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.ort')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <input type="text" name="ort" class="input-default" placeholder="{{ __('app.ort') }}" value="{{ old('ort', $item->ort) }}" {!! $errors->has('ort') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.telefon')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <input type="text" name="telefon" class="input-default" placeholder="{{ __('app.telefon') }}" value="{{ old('telefon', $item->telefon) }}" {!! $errors->has('telefon') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.email')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <input type="email" name="email" class="input-default" placeholder="{{ __('app.email') }}" value="{{ old('email', $item->email) }}" {!! $errors->has('email') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.facebook')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <input type="text" name="facebook" class="input-default" placeholder="{{ __('app.facebook') }}" value="{{ old('facebook', $item->facebook) }}" {!! $errors->has('facebook') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.instagram')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <input type="text" name="instagram" class="input-default" placeholder="{{ __('app.instagram') }}" value="{{ old('instagram', $item->instagram) }}" {!! $errors->has('instagram') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
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
    tinymce.init({
        selector : ".editor",
        language : "{{ str_replace('_', '-', app()->getLocale()) }}",
        plugins : ["advlist autolink lists link charmap print preview anchor", "searchreplace visualblocks code fullscreen", "insertdatetime media table paste "],
        toolbar : "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link code",
    });
</script>  
@endsection
