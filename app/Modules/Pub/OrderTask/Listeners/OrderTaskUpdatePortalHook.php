<?php

namespace App\Modules\Pub\OrderTask\Listeners;

use App\Modules\Pub\OrderTask\Controllers\OrderTaskController;
use App\Services\Portal\Events;
use Illuminate\Support\Facades\Log;

class OrderTaskUpdatePortalHook
{
    public function handle($event)
    {
        $orderTask = $event->orderTask;
        $events = new Events();
        $events->hook('annex_hook', [
            'source' => env('APP_NAME'),
            'contract' => $orderTask->sub_contract->contract_id,
            'src_doc_key' => $orderTask->sub_contract->slug,
            'annex_key' => $orderTask->block_id,
            'event' => $event->event,
        ]);
    }
}
