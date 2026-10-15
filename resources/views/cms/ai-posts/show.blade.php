@extends('cms.layout.container')

@section('content')
<div class="col-sm-9 main sm-module" id="sm-app" data-page="show" data-post-id="{{ $post['id'] }}">
    @include('cms.ai-posts.partials.head', [
        'heading' => 'Social Media Post #'.$post['id'],
        'description' => $post['title'] ?: 'Review the brief, the copy, and the publishing history.',
    ])
    <div id="sm-detail-mount">
        @include('cms.ai-posts.partials.detail', ['post' => $post])
    </div>
    @include('cms.ai-posts.partials.modals')
</div>
@endsection

@section('scripts')
@include('cms.ai-posts.partials.scripts')
@endsection
