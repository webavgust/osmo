<?php

namespace App\Services\Notificator\Notifications;

use App\Modules\Pub\User\Models\User;
use App\Services\Notificator\Interfaces;
use App\Services\Notificator\NotificationTrait;
use App\Services\Portal\Telegram;
use function dispatch;

class TelegramNotification implements Interfaces\NotificationInterface
{
    use NotificationTrait;

    public static array $info = [
        'type' => 'telegram',
        'name' => 'Телеграм',
        'default' => true,
        'icon_family' => 'fa-brands',
        'icon' => 'fa-telegram',
        'color' => 'info',
        'sort' => 5
    ];

    public function send(User $user)
    {
        dispatch(function () use ($user) {
            $message = Telegram::prepare($this->arParams);
            Telegram::send($user, $message);
            return true;
        })->delay($this->seconds)->onQueue('database');
    }
}
