<a href="#" class="mobile-btn visible-xs"><i class="fas fa-bars"></i>MENU</a>
<aside class="col-sm-3 menu">
    <div class="row language">
        <span>{{ __('app.sprache') }}:</span>
        <select id="cmsLang" class="cmsLang" name="cmsLang">
            <option @if(app()->getLocale() == 'de') selected @endif value="de" selected>DEUTSCH</option>
            <option @if(app()->getLocale() == 'fr') selected @endif value="fr">FRANÇAIS</option>
            <option @if(app()->getLocale() == 'it') selected @endif value="it">ITALIANO</option>
        </select>
        <a href="#" class="mobile-close-btn visible-xs"><i class="fas fa-times"></i></a>
    </div>
    <nav class="row">
        <ul>
            <li @if(request()->is('cms')) class="active" @endif><a href="{{ url('cms') }}"><i class="fas fa-tachometer-alt"></i>{{ __('app.daschlow') }}</a></li>
            @foreach(config('modules') as $modul)
            @can($modul[0])
            @if($modul[5])
            @php
            $id = $modul[0];
            $url = $modul[1];
            $title = $modul[2];
            $icon = $modul[3];
            $active = request()->is($url.'*') ? 'active' : '';
            $submenu = $modul[4];
            @endphp
            @if(count($submenu))
            <li class="dropdown-li {{ $active }}">
                <a href="#" class="dropdown-btn"><i class="fas {{ $icon }}"></i>{{ __($title) }}</a>
                <ul class="dropdown-ul">
                    @foreach ($submenu as $key => $value)
                    <li><a href="{{ url($url.$value) }}">{{ __($key) }}</a></li>
                    @endforeach
                </ul>
            </li>
            @else 
            <li class="{{ $active }}" ><a href="{{ url($url) }}"><i class="fas {{ $icon }}"></i>{{ __($title) }}</a></li>
            @endif
            @endif
            @endcan
            @endforeach
        </ul>
    </nav>
</aside>