@extends('cms.layout.container')

@section('content')
<div class="col-sm-9 main sm-module" id="sm-app" data-page="accounts">
    @include('cms.ai-posts.partials.head', [
        'heading' => 'Accounts',
        'description' => 'Facebook and Instagram profiles used for publishing.',
    ])
    <div class="form__wrap">
        <div class="sm-empty is-hidden" id="smAccountsEmpty">
            <i class="fas fa-share-alt"></i>
            <h3>No connected accounts.</h3>
            <p>Connect Facebook and Instagram to publish and schedule from the CMS.</p>
        </div>
        @foreach($accounts as $key => $account)
        <div class="sm-account" data-network="{{ $key }}">
            <div class="row form__title">
                <h3>
                    @if($key === 'facebook')<i class="fab fa-facebook-f"></i>@else<i class="fab fa-instagram"></i>@endif
                    {{ $account['name'] }}
                </h3>
            </div>
            <div class="sm-account__body">
                <div class="sm-account__on">
                    <p><span class="sm-badge sm-badge--published">Connected</span></p>
                    <p class="sm-kv"><span>{{ $account['subtitle'] }}</span> <strong class="js-acc-title">{{ $account['title'] }}</strong></p>
                    <p class="sm-kv"><span>Account ID</span> <span class="js-acc-id">{{ $account['account_id'] }}</span></p>
                    <p class="sm-kv"><span>Last synchronized</span> <span class="js-acc-sync">{{ $account['synced_at'] }}</span></p>
                    <button type="button" class="button button-white js-manage" data-network="{{ $key }}">Manage</button>
                </div>
                <div class="sm-account__off is-hidden">
                    <p>This {{ $account['name'] }} account is not connected.</p>
                    <button type="button" class="button js-connect" data-network="{{ $key }}">Connect {{ $account['name'] }}</button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @include('cms.ai-posts.partials.modals')
</div>
@endsection

@section('scripts')
@include('cms.ai-posts.partials.scripts')
@endsection
