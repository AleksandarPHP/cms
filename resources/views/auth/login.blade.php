<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="index, follow" />
    <meta name="googlebot" content="index, follow" />
    <meta name="author" content="symple.ch" />
    <title>symple.ch CMS PRO Administration</title>
    <link href="{{ asset('cmsfiles/images/favicon.png') }}" type="image/x-icon" rel="shortcut icon"/>
    <link href="{{ asset('cmsfiles/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('cmsfiles/css/style.css') }}" rel="stylesheet">
</head>
<body>
<div class="login container">
    <div class="row">
        <div class="login__wrap">
            <div class="login__logo">
                <img src="{{ asset('cmsfiles/images/logo.png') }}" alt="logo" class="img-responsive">
            </div>
            <form method="post" action="{{ route('login') }}" class="login__form">
                @csrf
                @if($errors->any())
                <div class="alert alert-danger">
                    <span class="alert__icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <p><b>Falscher Benutzername/E-mail oder falsches Passwort</b></p>
                    <span class="alert__btn"><i class="fas fa-times"></i></span>
                </div>
                @endif
                <h4>Benutzername oder E-mail</h4>
                <label class="login__input">
                    <i class="fas fa-user"></i>
                    <input type="text" class="form_default_input @error('login') error @enderror" name="login" value="{{ old('login') }}" required autocomplete="login" autofocus>
                </label>
                <h4>Passwort</h4>
                <label class="login__input">
                    <i class="fas fa-unlock-alt"></i>
                    <input id="password" type="password" name="password" {!! $errors->has('password') ? 'style="border-color:red;"' : '' !!}>
                </label>
                <label class="login__check">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Angemeldet bleiben
                </label>
                <button type=submit>Login</button>
            </form>
        </div>
    </div>
</div>
    <!-- SCRIPTS -->
    <script src="{{ asset('cmsfiles/js/jquery.1.12.4.min.js') }}"></script>
    <script src="{{ asset('cmsfiles/js/jquery-ui.1.12.1.min.js') }}"></script>
    <script src="{{ asset('cmsfiles/js/bootstrap.min.js') }}"></script>
</body>

</html>
