<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;

class isActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::user() && Auth::user()->is_active === 0) {
            Auth::logout();
            return redirect()->route('login')->withErrors('Dein Konto wurde deaktiviert.');
        }

        return $next($request);
    }
}
