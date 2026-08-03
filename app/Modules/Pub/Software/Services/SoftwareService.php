<?php

namespace App\Modules\Pub\Software\Services;

use App\Modules\Pub\CalculationLesson\Models\CalculationLesson;
use App\Modules\Pub\CalculationLesson\Repositories\CalculationLessonRepository;
use App\Modules\Pub\Software\Models\SoftwareGrade;
use App\Modules\Pub\Software\Models\SoftwareType;
use App\Modules\Pub\StudyLesson\Models\StudyLesson;
use App\Modules\Pub\Software\Models\Software;
use App\Modules\Pub\Software\Repositories\ContractorRepository;
use App\Modules\Pub\Software\Repositories\SoftwareRepository;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserSoftwareCalendar\Models\UserSoftwareCalendar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SoftwareService
{
    private $repo;
    private $repo_calc_lessons;

    public function __construct()
    {
        $this->repo = new SoftwareRepository();
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

