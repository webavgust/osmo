<?php

namespace App\Modules\Pub\OrderTask\Events;

use App\Modules\Pub\OrderTask\Models\OrderTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderTaskUpdateEvent implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $orderTask;
    public $event;


    public function __construct(OrderTask $orderTask, string $event)
    {
        $this->orderTask = $orderTask;
        $this->event = $event;
    }
}
