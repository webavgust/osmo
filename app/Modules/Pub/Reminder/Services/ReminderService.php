<?php

namespace App\Modules\Pub\Reminder\Services;

use App\Modules\Pub\Reminder\Repositories\ReminderRepository;
use App\Services\Notificator\Notificator;

class ReminderService
{

    // функция оповещения
    public static function remind(\App\Modules\Pub\ReminderTime\Models\ReminderTime $reminder_time)
    {
        $reminder = $reminder_time->reminder;
        $reminder_time->update(['notified' => 1]);

        Notificator::send($reminder->user, [
            'title' => $reminder->title,
            'message' => $reminder->message,
            'toastr' => 1,
            'mail' => \App\Mail\EventMail::class,
        ], $reminder_time->notificators);

        $reminder->refresh();
        $repo = new ReminderRepository();
        $repo->check($reminder->group);

        return true;
    }
}
