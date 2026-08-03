<?php

namespace App\View\Components\Ui\Select;


use Illuminate\View\Component;

class Single extends Component
{
    public $list;
    public $id = 'id';  // поле идентификатора
    public $blank;
    public $blankId;
    public $blankName;
    public $blank_ignore;
    public $value;

    public function __construct($items, $value = null, $id = null, $valueName = null, $blankId = null, $blankName = null, $blankIgnore = null)
    {
        $this->id = 'id';
        // BLANK
        if (empty($blankIgnore))
            $this->list[] = ['item_id' => $blankId ?? null, 'name' => $blankName ?? null];

        //
        if (count($items) > 0)
            foreach ($items as $i => $item) {
                $item_id = !empty($id) ? $item[$id] ?? $item->$id ?? $i : $i;

                if(!empty($valueName)) {
                    $name =
                        $item->$valueName
                        ?? $item[$valueName]
                        ?? $item['name']
                        ?? $item;
                } else {
                    $name = $item['name']
                            ?? $item;
                }
                $selected = !empty($value) && $value == $item_id;
                $this->list[] = ['item_id' => $item_id, 'selected' => $selected, 'name' => $name];
            }
    }

    public function render()
    {
        return view('components.ui.select.single', ['list' => $this->list]);
    }
}
