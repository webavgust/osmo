<?php

namespace App\View\Components\Evaluation\Create;

use App\Modules\Pub\Course\Repositories\CourseRepository;
use App\Modules\Pub\EducationApplicationCourse\Models\EducationApplicationCourse;
use App\Modules\Pub\EducationCenter\Repositories\EducationCenterRepository;
use App\Modules\Pub\EvaluationMeasure\Models\EvaluationMeasure;
use App\Modules\Pub\LabMeasure\Models\LabMeasure;
use App\Modules\Pub\LabMeasure\Repository\LabMeasureRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class MeasureRow extends Component
{
    public $address;
    public $uid_measure;
    public $measure;
    public $num;
    public $measures;
    public $data;

    public function __construct($parent, EvaluationMeasure $measure = null, $num = 1, $data = [])
    {
        $this->measure = $measure;
        $this->parent = $parent;
        $this->uid = $uid ?? Str::uuid()->toString();
        $this->num = $num;
        $this->measures = LabMeasureRepository::getLast();
        $this->data = $data;
    }

    public function render(string $check = null): View
    {
        return view('components.evaluation.create.measure_row', [
            'measures' => $this->measures,
            'measure' => $this->measure,
            'uid' => $this->uid,
            'num' => $this->num,
            'uid_measure' => $this->uid_measure,
            'parent' => $this->parent,
            'data' => $this->data,
        ]);
    }
}
