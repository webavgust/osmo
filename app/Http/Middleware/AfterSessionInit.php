<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AfterSessionInit
{
    public function handle($request, Closure $next)
    {
        if(Auth::check()) Auth::user()->hit();
//        dump(\Session::getId());
        return $next($request);
    }
}
