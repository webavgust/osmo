<?php

namespace App\View\Components\Logger;


use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class Block extends Component
{

    public $loop;
    public $log;
    public $fields;
    public $search;

    /**
     * Create a new component instance.
     *
     * @return void
     */

    public function __construct($loop, $log)
    {
        $this->loop = $loop;
        $this->log = $log;
        $this->fields = collect($log['output'])->pluck('name', 'field');
        $this->search = "|" . implode("|", $this->fields->keys()->toArray()) . "|";
    }

    public function render(): View
    {
        return view('components.logger.block', [
            'loop' => $this->loop,
            'log' => $this->log,
            'fields' => $this->fields,
            'search' => $this->search,
        ]);
    }
}
