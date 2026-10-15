<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="index, follow" />
    <meta name="googlebot" content="index, follow" />
    <meta name="author" content="symple.ch" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>symple.ch CMS PRO Administration</title>
    <link href="{{ asset('cmsfiles/images/favicon.png') }}" type="image/x-icon" rel="shortcut icon"/>
    <link href="{{ asset('cmsfiles/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('cmsfiles/css/fontawesome.5.5.0.min.css') }}" rel="stylesheet">
    <link href="{{ asset('cmsfiles/css/bootstrap-datetimepicker.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('cmsfiles/datatables/datatables.min.css') }}" rel="stylesheet"/>
    <link href="{{ asset('cmsfiles/fancybox/dist/jquery.fancybox.min.css') }}" rel="stylesheet">
    <link href="{{ asset('cmsfiles/css/style.css') }}" rel="stylesheet">
</head>
<body>
<header class="container header">
    <div class="row header__wrap">
        <div class="col-md-3 col-sm-3 col-xs-5 logo">
            <a href="{{ url('cms') }}"><img src="{{ asset('cmsfiles/images/logo.png') }}" alt="logo" class="img-responsive"></a>
        </div>
        <div class="col-md-9 col-sm-9 col-xs-7 header-right">
            <a
                href="{{ url('') }}"
                class="header__btn"
                target="_blank"
                data-toggle="tooltip"
                data-placement="bottom"
                title="Home page"
            >
                <i class="fas fa-desktop"></i>
            </a>
            <a href="http://support.symple.ch/" class="ticket visible-lg visible-md" target="_blank">{{ __('app.suppticket') }}</a>
            <a
                href="http://support.symple.ch/"
                target="_blank"
                class="header__btn hidden-lg hidden-md"
                data-toggle="tooltip"
                data-placement="bottom"
                title="{{ __('app.suppticket') }}"
            >
                <i class="far fa-question-circle"></i>
            </a>
            <a href="tel:+41(0)565609588" class="telephone visible-lg visible-md">Call us: +41 (0) 56 560 95 88</a>
            <a
                href="tel:+41(0)565609588"
                class="header__btn hidden-lg hidden-md"
                data-toggle="tooltip"
                data-placement="bottom"
                title="Call us"
            >
                <i class="fas fa-phone"></i>
            </a>
            @can('settings')
            <a
                href="{{url('/cms/settings/1/edit')}}"
                class="header__btn"
                data-toggle="tooltip"
                data-placement="bottom"
                title="{{ __('app.settings') }}"
            >
                <i class="far fa-edit"></i>
            </a>
            @endcan
            <a
                href="{{ route('logout') }}"
                class="header__btn hidden-lg hidden-md"
                data-toggle="tooltip"
                data-placement="bottom"
                onclick="event.preventDefault();if(document.getElementById('logout-form')){document.getElementById('logout-form').submit();document.getElementById('logout-form').id='logout-form-disabled';}"
                title="Logout"
            >
                <i class="fas fa-sign-out-alt"></i>
            </a>
            <div class="dropdown visible-lg visible-md">
              <button data-toggle="dropdown">
                {{ __('app.welcomeuser') }}
                <span>{{ Auth::user()->username }} <i class="fas fa-caret-down"></i></span>
              </button>
              <ul class="dropdown-menu">
                <li><a href="{{ route('logout') }}" onclick="event.preventDefault();if(document.getElementById('logout-form')){document.getElementById('logout-form').submit();document.getElementById('logout-form').id='logout-form-disabled';}">Logout</a></li>
              </ul>
            </div>
        </div>
    </div>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
</header>
<div class="container content">
    <div class="row content__wrap">