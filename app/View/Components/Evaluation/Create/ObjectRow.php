<?php

namespace App\View\Components\Evaluation\Create;

use App\Modules\Pub\EvaluationObject\Models\EvaluationObject;
use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\LabObject\Repository\LabObjectRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class ObjectRow extends Component
{
    public $uid;
    public $lab_objects;
    public $object;
    public $num;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(EvaluationObject $object = null, $num = 1)
    {
        $this->uid = $object->id ?? Str::uuid()->toString();
        $this->object = $object;
        $this->num = $num;
        $this->lab_objects = LabObjectRepository::getAll();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.evaluation.create.object_row', [
            'object' => $this->object,
            'num' => $this->num,
            'uid' => $this->uid,
            'lab_objects' => $this->lab_objects,
        ]);
    }
}
