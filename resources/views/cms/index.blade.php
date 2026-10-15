@extends('cms.layout.container')

@section('content')

<div class="col-sm-9 main">
    <div class="row main__title">
        <div class="col-md-12">
            <h2>{{ __('app.daschupp') }}</h2>
            <p>{{ __('app.hallo') }} <b>{{ Auth::user()->username }}</b>, {{ __('app.welcomebei') }} SYMPLE.CMS PRO.</p>
        </div>
    </div>
    <div class="row alert__wrap">
        <div class="alert alert-default">
          <span class="alert__icon"><i class="fas fa-info-circle"></i></span>
          <p>{{ __('app.warrningindex') }}</p>
          <span class="alert__btn"><i class="fas fa-times"></i></span>
        </div>
    </div>
    <div class="row main__banner">
        <img src="{{ asset('cmsfiles/images/headerpic_cms.png') }}" alt="Symple" class="img-responsive headerimg">
    </div>
    <div class="row dashboard">
        <div class="col-sm-3 dashboard__item">
            <a href="{{ url('cms') }}" data-toggle="tooltip" data-placement="top" title="{{ __('app.daschlow') }}">
                <i class="fas fa-list"></i>
                <h3>{{ __('app.daschlow') }}</h3>
            </a>
        </div>
        @foreach(config('modules') as $modul)
        {{-- @can($modul[0]) --}}
        @if($modul[5])
        @php
        $url = $modul[1];
        $title = $modul[2];
        $icon = $modul[3];
        @endphp
        <div class="col-sm-3 dashboard__item">
            <a href="{{ url($url) }}" data-toggle="tooltip" data-placement="top" title="{{ __($title) }}">
                <i class="fas {{ $icon }}"></i>
                <h3>{{ __($title) }}</h3>
            </a>
        </div>
        @endif
        {{-- @endcan --}}
        @endforeach
    </div>
</div>
@endsection
