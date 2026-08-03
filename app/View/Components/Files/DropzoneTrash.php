<?php

namespace App\View\Components\Files;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\EducationApplication\Models\EducationApplication;
use App\Modules\Pub\EducationTask\Models\EducationTask;
use App\Modules\Pub\Evaluation\Models\Evaluation;
use App\Modules\Pub\Files\Models\File;
use App\Modules\Pub\Files\Services\FileService;
use App\Modules\Pub\Report\Models\Report;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class DropzoneTrash extends Component
{

    public $block;
    public $preset;
    public $files;
    public $name;
    public $ignore_header;
    public $uid;
    public $denyDelete = false;
    public $box;
    public $callback;

    public function __construct($block, $box = null, $ignoreHeader = false, $callback = '')
    {
        $this->block = $block;
        $this->preset = File::PRESETS['trash'][$block] ?? [];
        $this->box = $box;
        $this->ignore_header = $ignoreHeader;
        $this->uid = 'dz' . Str::random(8);
        $this->callback = $callback;
    }


    public function render()
    {
        return view('components.files.dropzone_trash', [
            'block' => $this->block,
            'preset' => $this->preset,
            'box' => $this->box,
            'callback' => $this->callback,
        ]);
    }
}
