<?php

namespace App\Modules\Pub\Partner\Services;

use App\Modules\Pub\CalculationLesson\Models\CalculationLesson;
use App\Modules\Pub\CalculationLesson\Repositories\CalculationLessonRepository;
use App\Modules\Pub\Partner\Models\PartnerGrade;
use App\Modules\Pub\Partner\Models\PartnerType;
use App\Modules\Pub\StudyLesson\Models\StudyLesson;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Repositories\ContractorRepository;
use App\Modules\Pub\Partner\Repositories\PartnerRepository;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserWorkCalendar\Models\UserWorkCalendar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PartnerService
{
    private $repo;
    private $repo_calc_lessons;

    public function __construct()
    {
        $this->repo = new PartnerRepository();
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
            $item->grade = PartnerGrade::from($item->grade)->data();
            $item->type = PartnerType::from($item->type)->data();
        });

        return $data;
    }


}

