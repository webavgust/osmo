<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Portal
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->get('token');
        if($token != env('API_TOKEN')) {
            return response()->json(['error' => 'auth']);
        }

        return $next($request);
    }
}
