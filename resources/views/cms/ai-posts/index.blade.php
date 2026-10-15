@extends('cms.layout.container')

@php
$counts = array_fill_keys(array_keys($statuses), 0);
foreach ($posts as $post) {
    if (isset($counts[$post['status']])) {
        $counts[$post['status']]++;
    }
}
@endphp

@section('content')
<div class="col-sm-9 main sm-module" id="sm-app" data-page="index">
    @include('cms.ai-posts.partials.head', [
        'heading' => 'Social Media',
        'description' => 'Manage, review and schedule your social media content.',
    ])

    <div class="sm-toolbar">
        <div class="sm-filters" id="smFilters">
            <button type="button" class="button button-white sm-filter is-active" data-status="all">All <em>{{ count($posts) }}</em></button>
            @foreach($statuses as $key => $label)
            <button type="button" class="button button-white sm-filter" data-status="{{ $key }}">{{ $label }} <em>{{ $counts[$key] }}</em></button>
            @endforeach
        </div>
        <input type="search" class="input-default sm-search" id="smSearch" placeholder="Search posts...">
    </div>

    <div class="main__list">
        <div class="row form__title">
            <h3>Posts</h3>
        </div>
        <div class="sm-table-wrap" id="smTableWrap">
            <div class="table-responsive">
                <table class="sm-table">
                    <thead>
                        <tr>
                            <th>Post</th>
                            <th>Platforms</th>
                            <th>Status</th>
                            <th>Scheduled</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="smBody">
                        @foreach($posts as $post)
                        @php
                            $when = !empty($post['scheduled_at']) ? \Illuminate\Support\Carbon::parse($post['scheduled_at'])->format('j M Y, H:i') : '—';
                            $created = \Illuminate\Support\Carbon::parse($post['created_at'])->format('j M Y');
                            $search = strtolower($post['title'].' '.$post['excerpt'].' '.$post['status'].' '.implode(' ', $post['platforms']));
                        @endphp
                        <tr data-id="{{ $post['id'] }}" data-status="{{ $post['status'] }}" data-search="{{ $search }}" data-title="{{ $post['title'] }}">
                            <td class="sm-post" data-label="Post">
                                <div class="sm-post__main">
                                    <img class="sm-thumb" src="{{ $post['image_url'] }}" alt="">
                                    <div>
                                        <a class="sm-post__title" href="{{ url('cms/ai-posts/'.$post['id']) }}">{{ $post['title'] }}</a>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Platforms">
                                @if(in_array('facebook', $post['platforms']))<i class="fab fa-facebook-f sm-ico sm-ico--fb" title="Facebook"></i>@endif
                                @if(in_array('instagram', $post['platforms']))<i class="fab fa-instagram sm-ico sm-ico--ig" title="Instagram"></i>@endif
                            </td>
                            <td data-label="Status"><span class="sm-badge sm-badge--{{ $post['status'] }}">{{ $statuses[$post['status']] }}</span></td>
                            <td data-label="Scheduled">{{ $when }}</td>
                            <td data-label="Created">{{ $created }}</td>
                            <td class="td_action" data-label="Actions">
                                <div class="sm-actions">
                                    <a href="{{ url('cms/ai-posts/'.$post['id']) }}" class="btn_action" title="View"><img src="{{ asset('cmsfiles/images/btn_show.jpg') }}" alt="View"></a>
                                    <a href="{{ url('cms/ai-posts/'.$post['id'].'/edit') }}" class="btn_action" title="Edit"><img src="{{ asset('cmsfiles/images/btn_edit.jpg') }}" alt="Edit"></a>
                                    <a href="{{ url('cms/ai-posts/create?duplicate='.$post['id']) }}" class="btn_action" title="Duplicate"><img src="{{ asset('cmsfiles/images/btn_copy.jpg') }}" alt="Duplicate"></a>
                                    <a href="#" class="btn_action js-delete" data-id="{{ $post['id'] }}" data-title="{{ $post['title'] }}" title="Delete"><img src="{{ asset('cmsfiles/images/btn_delete.jpg') }}" alt="Delete"></a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="sm-table-foot" id="smShowing">Showing {{ count($posts) }} posts</div>
        </div>
        <div class="sm-empty is-hidden" id="smEmptyAll">
            <i class="fas fa-robot"></i>
            <h3>No posts yet.</h3>
            <p>Create your first social media post and start automating your content.</p>
            <a href="{{ url('cms/ai-posts/create') }}" class="button">Create Post</a>
        </div>
        <div class="sm-empty is-hidden" id="smEmptyFilter">
            <i class="fas fa-search"></i>
            <h3>No posts match your filters.</h3>
            <p>Try another status or clear the search.</p>
            <button type="button" class="button button-white" id="smClearFilters">Show all posts</button>
        </div>
    </div>
    @include('cms.ai-posts.partials.modals')
</div>
@endsection

@section('scripts')
@include('cms.ai-posts.partials.scripts')
@endsection
