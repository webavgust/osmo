<?php

namespace App\Services\AjaxToken;

use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AjaxToken
{
    static public function generate()
    {
        if(!Auth::check()) return false;
        $user = auth()->user();
        $user->ajax_token = Str::random(60);
        $user->save();
    }

    static public function clear()
    {
        if(!Auth::check()) return false;
        $user = auth()->user();
        $user->ajax_token = '';
        $user->save();
    }

    static public function token($token)
    {
        if(!Auth::check()) return false;
        return auth()->user()->ajax_token;
    }

    static public function check($token)
    {
        if(!$token) return false;
        $user = User::where('ajax_token', $token)->first();
        return $user;
    }


}
