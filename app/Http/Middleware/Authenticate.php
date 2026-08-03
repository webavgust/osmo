<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use App\Services\AjaxToken\AjaxToken;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */

    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('auth.form', ['back' => $request->fullUrl()]);
        }
    }


}
