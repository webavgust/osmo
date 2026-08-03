<?php

namespace App\Modules\Pub\OrderTask\Events;

use App\Modules\Pub\EducationTask\Models\EducationTask;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderTaskStartWorking
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order_task;

    public function __construct(OrderTask $orderTask)
    {
        $this->order_task = $orderTask;
    }
}
