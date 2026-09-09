<?php

namespace App\View\Components\Evaluation\Create;

use App\Modules\Pub\Course\Repositories\CourseRepository;
use App\Modules\Pub\EducationApplicationCourse\Models\EducationApplicationCourse;
use App\Modules\Pub\EducationCenter\Repositories\EducationCenterRepository;
use App\Modules\Pub\EvaluationAddress\Models\EvaluationAddress;
use App\Modules\Pub\EvaluationObject\Models\EvaluationObject;
use App\Modules\Pub\LabMeasure\Models\LabMeasure;
use App\Modules\Pub\LabMeasure\Repository\LabMeasureRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class AddressRow extends Component
{
    public $uid;
    public $num;
    public $address;

    public function __construct($parent = false, EvaluationAddress $address = null, $num = 1)
    {
        $this->uid = $address->id ?? Str::uuid()->toString();
        $this->num = $num;
        $this->parent = $parent;
        $this->address = $address;
    }

    public function render(): View
    {
        return view('components.evaluation.create.address_row', [
            'address' => $this->address,
            'uid' => $this->uid,
            'num' => $this->num,
            'parent' => $this->parent,
        ]);
    }
}
