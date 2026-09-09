<?php

namespace App\View\Components\Evaluation\Create;

use App\Modules\Pub\Course\Repositories\CourseRepository;
use App\Modules\Pub\EducationApplicationCourse\Models\EducationApplicationCourse;
use App\Modules\Pub\EducationCenter\Repositories\EducationCenterRepository;
use App\Modules\Pub\EvaluationAddress\Models\EvaluationAddress;
use App\Modules\Pub\EvaluationPoint\Models\EvaluationPoint;
use App\Modules\Pub\LabMeasure\Models\LabMeasure;
use App\Modules\Pub\LabMeasure\Repository\LabMeasureRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class PointRow extends Component
{
    public $uid;
    public $num;
    public $point;

    public function __construct($parent = false, EvaluationPoint $point = null, $num = 1)
    {
        $this->uid = $point->id ?? Str::uuid()->toString();
        $this->parent = $parent;
        $this->point = $point;
        $this->num = $num;
    }

    public function render(): View
    {
        return view('components.evaluation.create.point_row', [
            'point' => $this->point,
            'uid' => $this->uid,
            'parent' => $this->parent,
            'num' => $this->num,
        ]);
    }
}
