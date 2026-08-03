<?php

namespace App\Modules\Pub\OrderTask\FileGenerators\Interfaces;

use App\Modules\Pub\OrderTask\Models\OrderTask;

interface OrderTaskFileGeneratorInterface
{
    public function generate(OrderTask $order_task): string;
}
