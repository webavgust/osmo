<?php

namespace App\Modules\Pub\OrderTask\Listeners;

use App\Services\Portal\Events;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class OrderTaskChangeStatusPortalAvgHook
{
    public function handle($event)
    {
        $order_task = $event->order_task;
        $events = new Events();
        $response = $events->avg_hook('order_task/status', [], [
            'id' => $order_task->id,
            'status' => $order_task->status,
        ]);
    }
}
