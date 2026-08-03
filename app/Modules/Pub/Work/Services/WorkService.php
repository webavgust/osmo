<?php

namespace App\Modules\Pub\Work\Services;

use App\Modules\Pub\CalculationLesson\Models\CalculationLesson;
use App\Modules\Pub\CalculationLesson\Repositories\CalculationLessonRepository;
use App\Modules\Pub\Work\Models\WorkGrade;
use App\Modules\Pub\Work\Models\WorkType;
use App\Modules\Pub\StudyLesson\Models\StudyLesson;
use App\Modules\Pub\Work\Models\Work;
use App\Modules\Pub\Work\Repositories\ContractorRepository;
use App\Modules\Pub\Work\Repositories\WorkRepository;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserWorkCalendar\Models\UserWorkCalendar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WorkService
{
    private $repo;
    private $repo_calc_lessons;

    public function __construct()
    {
        $this->repo = new WorkRepository();
    }

    /**
     * Данные для таблицы
     *
     * @param $params
     * @return array
     */
    public function tableDefault($params)
    {
        $data = $this->repo->getTable($params);

        // Преобразование
        // TODO: переделать на Resource
        $data['rows']->map(function ($item) {
            $item->created_at = _datetime($item->created_at);
            $item->updated_at = _datetime($item->updated_at);
        });

        return $data;
    }


}

