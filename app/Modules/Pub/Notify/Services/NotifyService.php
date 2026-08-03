<?php

namespace App\Modules\Pub\Notify\Services;

use App\Modules\Pub\Notify\Models\Notify;
use App\Modules\Pub\User\Models\User;

class NotifyService
{
    public static function post(User $user, $arParams)
    {
        $notify = new Notify([
            'user_id' => $user->id,
            'link' => !empty($arParams['link']) ? $arParams['link'] : null,
            'icon' => $arParams['icon'] ?? null,
            'title' => $arParams['title'],
            'message' => $arParams['message'] ?? null,
            'toastr' => !empty($arParams['toastr']) && $arParams['toastr']
        ]);
        $notify->save();
    }

    public function truncate()
    {

    }
}
