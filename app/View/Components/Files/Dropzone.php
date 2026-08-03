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

class Dropzone extends Component
{

    public $mode;
    public $id;
    public $block;
    public $block_id;
    public $preset;
    public $files;
    public $can_add;
    public $name;
    public $ignore_header;
    public $uid;
    public $denyDelete = false;
    public $box;

    public function __construct($mode, $id, $block, $name, $files = null, $blockid = null, $box = null, $ignoreHeader = false)
    {
        if (empty($files))
            $files = collect();

        $preset_transform = [
            'evaluation' => Evaluation::class,

        ];
        $this->mode = $mode;
        $this->id = $id;
        $this->block = $block;
        $this->block_id = $blockid;
        $this->preset = File::PRESETS[$preset_transform[$mode]][$block] ?? [];
        $this->name = $name;
        $this->box = $box;

        if (empty($this->block_id)) {
            $this->files = collect(array_merge($files[$this->block] ?? [], FileService::temporaryDBFiles($this->mode, $this->id, $this->block)->toArray()));
        } else {
            $this->files = collect(array_merge($files[$this->block][$this->block_id] ?? [], FileService::temporaryDBFiles($this->mode, $this->id, $this->block, $this->block_id)->toArray()));
        }
        $this->can_add = $this->preset['count'] == 0 || $this->files->count() < $this->preset['count'];
        $this->ignore_header = $ignoreHeader;
        $this->uid = 'dz' . Str::random(8);
    }


    public function render()
    {
        return view('components.files.dropzone', [
            'mode' => $this->mode,
            'id' => $this->id,
            'block' => $this->block,
            'block_id' => $this->block_id,
            'preset' => $this->preset,
            'files' => $this->files,
            'can_add' => $this->can_add,
            'box' => $this->box,
        ]);
    }
}
