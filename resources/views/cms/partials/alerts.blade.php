@if(session('success') || $errors->any())
    <div class="row alert__wrap">
        @if(session('success'))
        <div class="alert alert-success">
            <span class="alert__icon"><i class="fas fa-check"></i></span>
            <p>{{ session('success') }}</p>
            <span class="alert__btn"><i class="fas fa-times"></i></span>
        </div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger">
            <span class="alert__icon"><i class="fas fa-exclamation-triangle"></i></span>
            <p>{{ __('app.error') }}</p>
            <ol>
                @foreach ($errors->all() as $error)
                   <li>{{ $error }}</li> 
                @endforeach
            </ol>
            <span class="alert__btn"><i class="fas fa-times"></i></span>
        </div>
        @endif
    </div>
@endif

