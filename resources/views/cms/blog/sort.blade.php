@extends('cms.layout.container')

@section('content')
<div class="col-sm-9 main">
    <div class="row main__title">
        <div class="col-sm-8">
            <h2><i class="fas fa-list"></i>{{ $title }}</h2>
            <p>{{ __('app.sort') }}</p>
        </div>
        <div class="col-sm-4 shortcuts">
            <a
                href="{{ url('cms') }}"
                class="shortcut"
                data-toggle="tooltip"
                data-placement="bottom"
                title="{{ __('app.daschlow') }}"
            >
                <i class="fas fa-desktop"></i>
            </a>
            <a
                href="{{ url('cms/'.$route) }}"
                class="shortcut"
                data-toggle="tooltip"
                data-placement="bottom"
                title="{{ __('app.list') }}"
            >
                <i class="far fa-clone"></i>
            </a>
            <a
                href="{{ url('cms/'.$route.'/create') }}"
                class="shortcut"
                data-toggle="tooltip"
                data-placement="bottom"
                title="{{ __('app.add') }}"
            >
                <i class="fas fa-plus"></i>
            </a>
            <a
                href="https://symple.kayako.com/"
                target="_blank"
                class="shortcut"
                data-toggle="tooltip"
                data-placement="bottom"
                title="Support"
            >
                <i class="fas fa-question"></i>
            </a>
        </div>
    </div>
    @include('cms.partials.alerts')
    @php
    $sortarray = array();
    @endphp    
        <div class="row main__sort">
            <ul id="sortable">
                @foreach ($items as $item)
                    @php
                    array_push($sortarray, $item->id);
                    $sortTitle = ($item->title!="") ? $item->title.'(ID: '.$item->id.')' : $title.'(ID: '.$item->id.')';
                    @endphp
                    <li alt="{{ $item->id }}">{{ $sortTitle }}</li>
                @endforeach
            </ul>
            <form method="post">
                @csrf
                <input type="hidden" name="sortarray" id="sortarray" value="{{ implode("|",$sortarray) }}">
                <button type="submit" class="button">{{ __('app.sort') }}</button>
            </form>
        </div>
</div>

@endsection
