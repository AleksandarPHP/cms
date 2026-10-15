<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class CmsLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $locale = Auth::user()->language;

        if($locale == "de") {
            app()->setLocale('de');
            setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'deu_deu');
        } else if($locale == "fr") { 
            app()->setLocale('fr');
            setlocale(LC_ALL, 'fr_FR.UTF-8'); 
        } else if($locale = "it") {
            app()->setLocale('it');
            setlocale(LC_ALL, 'it_IT.UTF-8');
        }
        
        return $next($request);
    }
}
