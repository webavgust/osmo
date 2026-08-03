<?php

namespace App\Services\Notificator;

use App\Modules\Pub\User\Models\User;
use App\Services\Notificator\Interfaces\NotificationInterface;
use App\Services\Notificator\Notifications\AnyEmailNotification;
use App\Services\Notificator\Notifications\EmailNotification;
use App\Services\Notificator\Notifications\LogNotification;
use App\Services\Notificator\Notifications\SiteNotification;
use App\Services\Notificator\Notifications\TelegramNotification;
use App\Services\Tools\Tools;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

class Notificator
{
    const NOTIFICATORS = [
        'email' => EmailNotification::class,
        'email_any' => AnyEmailNotification::class,
        'site' => SiteNotification::class,
        'telegram' => TelegramNotification::class,
        'log' => LogNotification::class
    ];

    public static function send(User $user, array $arParams, $notificators = [])
    {
        if(empty($notificators))
            $notificators = array_keys(self::NOTIFICATORS);

        foreach($notificators as $notify_id) {
            if (class_exists($notify_id) && (new $notify_id($arParams)) instanceof NotificationInterface) {
                $notifier = new ($notify_id)($arParams);
                $notifier->send($user);
            } elseif (!empty(self::NOTIFICATORS[$notify_id])) {
                $notifier = new (self::NOTIFICATORS[$notify_id])($arParams);
                $notifier->send($user);
            }
        }
    }

    public static function getAvailableNotificators()
    {
        $find = [];
        foreach (File::allFiles(__DIR__ . '/Notifications') as $file) {
            $class = Tools::filenameToClass($file->getRealPath());
            if ($class) {
                $temp = new $class([]);
                if ($temp instanceof NotificationInterface)
                    if (!empty($temp::$hide) && !auth()->user()->isAdmin()) continue;

                $find[$temp::$info['type']] = $temp::$info + ['class' => $class];
            }
        }

        $find = collect($find)->sortBy(fn($item) => $item['sort'] ?? 1000)->toArray();

        return $find;
    }

}
