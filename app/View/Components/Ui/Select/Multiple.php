<?php

namespace App\View\Components\Ui\Select;


use Illuminate\View\Component;

class Multiple extends Component
{
    public $list;

    public function __construct($items, $selected = [], $id = null, $valueName = null, $blankId = null, $blankName = null, $blankIgnore = null, $keyAsValue = false)
    {
        $this->id = 'id';
        // BLANK
        if (empty($blankIgnore))
            $this->list[] = ['item_id' => $blankId ?? null, 'name' => $blankName ?? null];

        //
        if (count($items) > 0)
            foreach ($items as $i => $item) {
                $item_id = $item[$id] ?? $i;

                $name = !empty($valueName) ?
                    ($item->$valueName ?? $item[$valueName]) : ($item['name'] ?? $item);

                if($keyAsValue)
                    $item_id = $name;

                $selected_once = !empty($item_id) && in_array($item_id, $selected);
                $this->list[] = ['item_id' => $item_id, 'selected' => $selected_once, 'name' => $name];
            }
    }

    public function render()
    {
        return view('components.ui.select.multiple', ['list' => $this->list]);
    }
}
