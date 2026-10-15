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
                        {{ __('app.username')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <input type="text" name="username" class="input-default" placeholder="{{ __('app.username')}}" value="{{ old('username', $item->username) }}" {!! $errors->has('username') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.vorname')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <input type="text" name="name" class="input-default" placeholder="{{ __('app.vorname')}}" value="{{ old('name', $item->name) }}" {!! $errors->has('name') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.email')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <input type="email" name="email" class="input-default" placeholder="{{ __('app.email')}}" value="{{ old('email', $item->email) }}" {!! $errors->has('email') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.password')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <input name="password" autocomplete="off" type="password" class="input-default" id="password" placeholder="{{ __('app.password')}}" {!! $errors->has('password') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.password_confirmation')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <input name="password_confirmation" autocomplete="off" type="password" class="input-default" id="password_confirmation" placeholder="{{ __('app.password_confirmation')}}" {!! $errors->has('password_confirmation') ? 'style="border-color:red;"' : '' !!}>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.sprache') }}
                    </label>
                </div>
                <div class="col-md-9">
                    <select class="select-default" name="cmsLang" {!! $errors->has('cmsLang') ? 'style="border-color:red;"' : '' !!}>
                        <option value="de" @if(old('cmsLang', $item->language) == "de") selected @endif >DEUTSCH</option>
                        <option value="fr" @if(old('cmsLang', $item->language) == "fr") selected @endif >FRANÇAIS</option>
                        <option value="it" @if(old('cmsLang', $item->language) == "it") selected @endif >ITALIANO</option>
                    </select>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.active')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <div class="onof" {!! $errors->has('is_active') ? 'style="border-color:red;"' : '' !!}>
                        <label>
                            <input type="radio" name="is_active" value="1" @if(($errors->any() && old('is_active') == "1") || (!$errors->any() && (!$editing || $item->is_active == "1"))) checked @endif> <span>On</span>
                        </label>
                        <label>
                            <input type="radio" name="is_active" value="0" @if(($errors->any() && old('is_active') == "0") || (!$errors->any() && $item->is_active == "0")) checked @endif> <span>Off</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="row form__block">
                <div class="col-md-3">
                    <label class="label-left">
                        {{ __('app.notification')}}
                    </label>
                </div>
                <div class="col-md-9">
                    <div class="onof" {!! $errors->has('notification') ? 'style="border-color:red;"' : '' !!}>
                        <label>
                            <input type="radio" name="notification" value="1" @if(($errors->any() && old('notification') == "1") || (!$errors->any() && (!$editing || $item->notification == "1"))) checked @endif> <span>On</span>
                        </label>
                        <label>
                            <input type="radio" name="notification" value="0" @if(($errors->any() && old('notification') == "0") || (!$errors->any() && $item->notification == "0")) checked @endif> <span>Off</span>
                        </label>
                    </div>
                </div>
            </div><br>
            <div class="row form__title">
                <h3>{{ __('app.modul') }}</h3>
            </div>
            <div class="row default__table">
                <div class="table-responsive">
                    <table id="users" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th width="30">{{ __('app.active') }}</th>
                                <th><b>{{ __('app.module') }}</b></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(config('modules') as $modul)
                            @php
                            $modId = $modul[0];
                            $modTitle = $modul[2];
                            @endphp
                            <tr>
                                <td class="text-center"><input type="checkbox" name="{{ $modId }}" value="1" {{ (($errors->any() && old($modId) || (!$errors->any() && $item[$modId]))) ? 'checked' : '' }} ></td>
                                <td><b>{{ __($modTitle) }}</b></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
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
