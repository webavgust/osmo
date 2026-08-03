<?php

namespace App\Services\Notificator\Notifications;

use App\Modules\Pub\Notify\Services\NotifyService;
use App\Modules\Pub\User\Models\User;
use App\Services\Notificator\Interfaces;
use App\Services\Notificator\NotificationTrait;
use function dispatch;

class SiteNotification implements Interfaces\NotificationInterface
{
    use NotificationTrait;
    public static array $info = [
        'type' => 'site',
        'name' => 'Уведомление',
        'default' => true,
        'icon_family' => 'fa-light',
        'icon' => 'fa-bell',
        'sort' => 0,
        'color' => 'primary',
        'hide' => true
    ];

    public function send(User $user)
    {
        dispatch(function () use ($user) {
            NotifyService::post($user, $this->arParams);
            return true;
        })->delay($this->seconds)->onQueue('database');
    }
}
