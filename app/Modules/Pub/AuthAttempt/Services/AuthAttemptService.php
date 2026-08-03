<?php

namespace App\Modules\Pub\AuthAttempt\Services;

use App\Modules\Pub\AuthAttempt\Models\AuthAttempt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class AuthAttemptService
{
    private $uuid;
    private $attempt;
    public function __construct()
    {
        if(empty(Session::get('user_beacon')))
            Session::put('user_beacon', Str::uuid()->toString());


        $this->uuid = Session::get('user_beacon');
    }

    public function init(\App\Modules\Pub\User\Request\AuthRequest $request)
    {
        $this->attempt = new AuthAttempt([
            'login' => $request->input('login'),
            'ip' => $request->ip(),
            'unique_token' => $this->uuid,
            'user_agent' => $request->userAgent(),
            'attempted_at' => Carbon::now()
        ]);
    }

    public function failed()
    {
        $this->attempt->success = false;
        $this->attempt->save();
    }

    public function success($user_id)
    {
        $this->attempt->user_id = $user_id;
        $this->attempt->success = true;
        $this->attempt->save();
    }

}
