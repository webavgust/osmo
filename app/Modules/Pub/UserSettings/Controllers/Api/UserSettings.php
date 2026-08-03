<?php

namespace App\Modules\Pub\UserSettings\Controllers\Api;

use App\Modules\Pub\UserSettings\Requests\SetRequest;

class UserSettings
{
    public function set(SetRequest $request)
    {
        if(!empty(auth()->user()->setting))
            auth()->user()->setting->set($request->validated());
    }
}
