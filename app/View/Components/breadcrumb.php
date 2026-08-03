<?php

namespace App\View\Components;

use Illuminate\View\Component;

class breadcrumb extends Component
{
    private $data;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.breadcrumb')->with(['data' => $this->data]);
    }
}
