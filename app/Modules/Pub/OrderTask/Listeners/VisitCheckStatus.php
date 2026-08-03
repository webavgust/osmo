<?php

namespace App\Modules\Pub\OrderTask\Listeners;

use App\Modules\Pub\OrderTask\Controllers\OrderTaskController;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\OrderTask\Services\OrderTaskService;
use App\Services\Portal\Events;
use Illuminate\Support\Facades\Log;

class VisitCheckStatus
{
    public function handle($event)
    {
        $visit = $event->visit;
        $task = $visit->order_task_address->object->task;
        if($task->status !== OrderTask::STATUS_WORKING)
            return false;

        $check = $task->objects->every(function($obj) {
            return $obj->isFinished();
        });

        if($check) {
            OrderTaskService::updateStatus($task, OrderTask::STATUS_ALL_WORKS_FINISHED);
        }
    }
}
