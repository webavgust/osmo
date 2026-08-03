<?php

namespace App\Modules\Pub\Partner\Repositories;

use App\Modules\Pub\Course\Models\Course;
use App\Modules\Pub\EducationApplication\Services\EducationApplicationListFilterService;
use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use App\Modules\Pub\Partner\Models\Contractor;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Services\OrderListFilterService;
use App\Modules\Pub\Partner\Services\PartnerListFilterService;
use App\Modules\Pub\Sector\Models\Sector;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use App\Modules\Pub\Partner\Models\Partner;
use Illuminate\Support\Facades\DB;

class PartnerRepository
{

    public static function getAll()
    {
        return Partner::all()->keyBy('id');
    }

    public static function create(\Illuminate\Http\Request $request)
    {
        $data = $request->all();
        $partner = Partner::create([
            'active' => $data['active'] ?? false,
            'name' => $data['name'],
            'type' => $data['type'],
            'grade' => $data['grade'],
            'region' => $data['region'],
            'contact' => $data['contact'],
            'phone' => $data['phone'],
        ]);

        return $partner;
    }

    public static function update(Partner $partner, \Illuminate\Http\Request $request)
    {
        $data = $request->all();
        $partner->update([
            'active' => $data['active'] ?? false,
            'name' => $data['name'],
            'type' => $data['type'],
            'grade' => $data['grade'],
            'region' => $data['region'],
            'contact' => $data['contact'],
            'phone' => $data['phone'],
        ]);
    }

    public static function delete(Partner $partner)
    {
        $partner->delete();
    }

    /**
     * Получение данных для таблицы
     *
     * @param $params
     * @return array
     */
    public function getTable($params = [])
    {
        $filterService = new PartnerListFilterService($params['_token'] ?? null);
        $builder = Partner::where('partners.id', '>', 0);

        $builder_full = clone $builder;

        # Filter
        $builder = $filterService->filter($builder);
        $count = $count_filtered = $builder->count();

        # Search
        if (!empty($params['search'])) {
            $builder->search($params['search']);
            $count_filtered = $builder->count();
        }

        if (!empty($params['sort']) && !empty($params['order'])) {
            $builder->orderBy($params['sort'], $params['order']);
        } else {
            $builder->orderBy('active','desc')->orderBy('name');
        }

//        $builder
//            ->with([
//                'user' => function ($query) {
//                    $query->select(User::getShowFields());
//                }
//            ])
//            ->with([
//                'course' => function ($query) {
//                    $query->select(Course::getShowFields());
//                }
//            ]);

        $builder->withCount('companies');
        # Paginate
        if (!empty($params['limit']))
            $builder->limit($params['limit']);

        if (!empty($params['offset']))
            $builder->skip($params['offset']);

        return [
            'count' => $count,
            'count_filter' => $count_filtered,
            'rows' => $builder->get(),
            'filter' => [
                'grade' => $builder_full->pluck('grade')->unique()->toArray(),
            ]
        ];
    }

}
