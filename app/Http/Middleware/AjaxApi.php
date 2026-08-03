<?php

namespace App\Http\Middleware;

use App\Services\AjaxToken\AjaxToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AjaxApi
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
        if(empty($request->input('_token')))
            abort(403);

        $user = $request->user();
        if(empty($user)) {
            $user = UserRepository::getByToken($request->input('_token'));
            Auth::loginUsingId($user->id);
            $user = auth()->user();
        }

        if($user->ajax_token !== $request->input('_token'))
            return response()->json(['error' => 'auth']);

        return $next($request);
    }
}
