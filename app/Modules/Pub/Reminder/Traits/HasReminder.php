<?php

namespace App\Modules\Pub\Reminder\Traits;

use App\Modules\Pub\Files\Models\File;
use App\Modules\Pub\Reminder\Models\Reminder;

trait HasReminder
{
    public function reminders()
    {
        return $this->morphMany(Reminder::class, 'target');
    }

    public function reminder()
    {
        return [
            'module' => _module_name($this::class),
            'count' => count($this->reminders),
            'id' => $this->id
        ];
    }
}
