<?php

namespace App\Modules\Pub\OrderTask\Listeners;

use App\Modules\Pub\OrderTask\Controllers\OrderTaskController;
use Illuminate\Support\Facades\Log;

class OrderTaskUpdateListener
{
    public function handle($event)
    {
        $controller = new OrderTaskController();
        $controller->costTotalRecalc($event->orderTask);
    }
}
