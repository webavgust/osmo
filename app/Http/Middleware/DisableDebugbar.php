<?php

namespace App\Http\Middleware;

use Closure;
use Debugbar;

class DisableDebugbar
{
    public function handle($request, Closure $next)
    {
        if (class_exists('\Barryvdh\Debugbar\Facades\Debugbar')) {
            \Barryvdh\Debugbar\Facades\Debugbar::disable();
        }
        return $next($request);
    }
}
