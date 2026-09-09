<?php

namespace App\View\Components\Order;

use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Status extends Component
{
    public $status_color;
    public $status_name;
    public $font;

    /**
     * Create a new component instance.
     *
     * @return void
     */

    public function __construct($status, $font = 14)
    {
        $this->status_color = Order::STATUS[$status]['color']['badge'];
        $this->status_name = Order::STATUS[$status]['name'];
        $this->font = $font;
    }

    public function render(): View
    {
        return view('components.order.status');
    }
}
