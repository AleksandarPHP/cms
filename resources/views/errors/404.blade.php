@extends('layouts.main')

@section('content')
@include('partials.header')
<section class="not_found">
  <div class="container">
    <div class="row">
      <div class="col-12 text-center">
        <h1>404</h1>
        <h2>Page Not Found</h2>
        <a href="{{ url('/') }}" class="button">HOME</a>
      </div>
    </div>
  </div>
</section>
@include('partials.footer')
@endsection