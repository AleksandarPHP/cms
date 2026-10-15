@extends('layouts.main')

@section('title', $item->meta_title ?: $item->title)

@section('content')
<main class="container" style="max-width:960px;padding:48px 16px 72px;">
    <p style="margin-bottom:24px;"><a href="{{ url('/') }}">&larr;</a></p>
    @if($image)
    <img src="{{ $image }}" alt="{{ $item->title }}" style="width:100%;height:auto;margin-bottom:24px;">
    @endif
    <h1>{{ $item->title }}</h1>
    @if($item->subtitle)
    <p style="font-size:1.25rem;color:#444;">{{ $item->subtitle }}</p>
    @endif
    @if($item->content)
    <div>{!! $item->content !!}</div>
    @elseif($item->description)
    <div>{!! $item->description !!}</div>
    @endif
</main>
@endsection
