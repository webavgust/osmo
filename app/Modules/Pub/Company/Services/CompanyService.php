<?php

namespace App\Modules\Pub\Company\Services;

use App\Modules\Pub\CalculationLesson\Models\CalculationLesson;
use App\Modules\Pub\CalculationLesson\Repositories\CalculationLessonRepository;
use App\Modules\Pub\Company\Models\CompanyGrade;
use App\Modules\Pub\Company\Models\CompanyType;
use App\Modules\Pub\Partner\Models\PartnerGrade;
use App\Modules\Pub\StudyLesson\Models\StudyLesson;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Company\Repositories\ContractorRepository;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserWorkCalendar\Models\UserWorkCalendar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CompanyService
{
    private $repo;
    private $repo_calc_lessons;

    public function __construct()
    {
        $this->repo = new CompanyRepository();
    }

    public static function getProposalsGrouped(Company $company)
    {
        $ret = [];
        $groups = $company->proposals->groupBy('group');
        foreach($groups as $group) {
            $ret[] = [
                'name' => $group[0]['name'],
                'rows' => $group
            ];
        }

        return $ret;
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
            $item->url = route('company.detail', $item);
            $item->country_name = $item->country?->name ?? null;

            $item->partner['grade_decorate'] = PartnerGrade::from($item->partner['grade'])->data();
        });

        return $data;
    }


}

