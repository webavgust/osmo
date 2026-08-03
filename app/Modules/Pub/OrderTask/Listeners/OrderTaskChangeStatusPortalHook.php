<?php

namespace App\Modules\Pub\OrderTask\Listeners;

use App\Services\Portal\Events;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class OrderTaskChangeStatusPortalHook
{
    public function handle($event)
    {
        $orderTask = $event->order_task;
        $events = new Events();
        $events->hook('annex_hook', [
            'source' => env('APP_NAME'),
            'contract' => $orderTask->sub_contract->contract_id,
            'src_doc_key' => $orderTask->sub_contract->slug,
            'annex_key' => $orderTask->block_id,
            'status' => $orderTask->status,
            'event' => 'status_changed'
        ]);
    }
}
