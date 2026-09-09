<?php

namespace App\View\Components\Calculation;

use Illuminate\View\Component;

class Block extends Component
{
    public $name;
    public $type;
    public $data;
    public $amounts;
    public $corrections;
    public $ignore_header;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($name, $type, $data, $ignoreHeader = 0)
    {
        $this->name = $name;
        $this->type = $type;
        $this->data = $data;
        $this->ignore_header = $ignoreHeader;

        $amounts = $corrections = 0;
        foreach ($data as $rows) {
            foreach ($rows as $row) {
                if ($row->is_correction) {
                    $corrections += $row->amount;
                } else {
                    $amounts += $row->amount;
                }
            }
            $this->amounts = $amounts;
            $this->corrections = $corrections;
        }
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.calculation.block', [
            'name' => $this->name,
            'type' => $this->type,
            'data' => $this->data,
            'amounts' => $this->amounts,
            'corrections' => $this->corrections,
        ]);
    }
}
