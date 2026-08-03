<?php

namespace App\Jobs\Reminders;

use App\Modules\Pub\Reminder\Services\ReminderService;
use App\Modules\Pub\ReminderTime\Models\ReminderTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class Remind implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $reminder_time;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ReminderTime $reminder_time)
    {
        $this->reminder_time = $reminder_time;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ReminderService::remind($this->reminder_time);
    }
}
