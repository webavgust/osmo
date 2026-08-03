<?php

namespace App\View\Components;

use Illuminate\View\Component;

class breadcrumb_item extends Component
{
    private $item;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($item)
    {
        $this->item = $item;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.breadcrumb_item')->with(['item' => $this->item]);
    }
}
