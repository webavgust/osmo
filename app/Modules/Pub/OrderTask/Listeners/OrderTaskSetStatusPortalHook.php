<?php

namespace App\Modules\Pub\OrderTask\Listeners;

use App\Modules\Pub\OrderTask\Controllers\OrderTaskController;
use App\Services\Portal\Events;
use Illuminate\Support\Facades\Log;

class OrderTaskSetStatusPortalHook
{
    public function handle($event)
    {

        $order_task = $event->orderTask;
        $events = new Events();
        $response = $events->avg_hook('order_task/recreate', [], [
            'target_app' => $order_task->evaluation->block_id,
            'mktime' => strtotime($order_task->created_at),
            'status' => $order_task->status,
            'new_id' => $order_task->id,
        ]);

        return $response;

    }
}
