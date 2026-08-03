<?php

namespace App\Modules\Pub\ReminderTime\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Reminder\Models\Reminder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Facades\Queue;

class ReminderTime extends ModuleModel
{
    protected $fillable = ['notificators', 'notify_at', 'job_id', 'notified'];
    protected $casts = [
        'notify_at' => 'datetime',
        'notified' => 'boolean',
        'notificators' => 'json'
    ];

    protected static function booted()
    {
        static::deleting(function ($item) {
            Queue::deleteReserved('database', $item->job_id);
        });
    }

    public function reminder()
    {
        return $this->belongsTo(Reminder::class);
    }
}
