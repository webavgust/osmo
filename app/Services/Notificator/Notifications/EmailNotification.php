<?php

namespace App\Services\Notificator\Notifications;

use App\Modules\Pub\Notify\Services\NotifyService;
use App\Modules\Pub\User\Models\User;
use App\Services\Notificator\Interfaces;
use App\Services\Notificator\NotificationTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use function dispatch;

class EmailNotification implements Interfaces\NotificationInterface
{
    use NotificationTrait;

    public static array $info = [
        'type' => 'email',
        'name' => 'На почту',
        'icon_family' => 'fa-light',
        'icon' => 'fa-at',
        'color' => 'primary',
        'sort' => 30
    ];

    /**
     * Отправка сообщения
     *
     * @param User $user
     * @return void
     */
    public function send(User $user)
    {
        unset($this->arParams['template']);
        $this->arParams['ignore_template'] = true;

        $email = $user->email;
        if (empty($email)) return false;


        Log::channel('email')->info($email);
        Log::channel('email')->info($this->arParams['title']);
        Log::channel('email')->info($this->arParams['message']);


        if(env('APP_ENV') == 'development')
            $email = 'avg.den@yandex.ru';

        dispatch(function () use ($email) {
            $this->arParams['email'] = $email;
            $d = Mail::to($email)->send((new $this->arParams['mail']($this->arParams)));
        })->delay($this->seconds)->onQueue('database');
    }
}
