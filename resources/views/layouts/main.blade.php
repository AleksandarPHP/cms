<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="index, follow" />
    <meta name="googlebot" content="index, follow" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <meta name="theme-color" content="#ffffff">
    <title>@yield('title', 'Website Title')</title>
    <link href="{{ asset('assets/images/favicon.png') }}" rel="shortcut icon" type="image/x-icon" />
    {{-- stylesheets --}}
    <link href="{{ asset('assets/vendor/bootstrap-4.3.1/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/vendor/fontawesome-free-5.13.1/css/all.min.css') }}" rel="stylesheet" type="text/css">
    @yield('styles')
    <link href="{{ asset('assets/stylesheets/main.css') }}" rel="stylesheet" type="text/css">
  </head>
  <body>
    @yield('content')
    {{-- javascripts --}}
    <script src="{{ asset('assets/vendor/jquery/jquery-3.5.1.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap-4.3.1/js/bootstrap.bundle.min.js') }}"></script>
    @yield('scripts')
    <script src="{{ asset('assets/javascripts/main.js') }}"></script>
    @yield('page_script')
  </body>
</html>
