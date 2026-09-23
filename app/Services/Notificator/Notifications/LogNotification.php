<?php

namespace App\Services\Notificator\Notifications;

use App\Modules\Pub\User\Models\User;
use App\Services\Notificator\Interfaces;
use App\Services\Notificator\NotificationTrait;
use Illuminate\Support\Facades\Log;
use function dispatch;

class LogNotification implements Interfaces\NotificationInterface
{
    use NotificationTrait;
    public static array $info = [
       'type' => 'log',
       'name' => 'Лог',
       'icon_family' => 'fa-light',
       'icon' => 'fa-file',
       'color' => 'secondary',
       'hide' => true,
    ];

    public function send(User $user)
    {
        // заголовок и текст лежат в arParams (NotificationTrait); свойств title/message у класса нет —
        // задание падало в воркере «Undefined property» (найдено при запуске воркера 23.09.2026)
        dispatch(function () {
            Log::info('TELEGRAM TITLE ' . ($this->arParams['title'] ?? ''));
            Log::info('TELEGRAM MESSAGE ' . ($this->arParams['message'] ?? ''));
        })->delay($this->seconds);
    }

}
