<?php

namespace App\Modules\Pub\Software\Repositories;

use App\Modules\Pub\Course\Models\Course;
use App\Modules\Pub\EducationApplication\Softwares\EducationApplicationListFilterSoftware;
use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use App\Modules\Pub\Software\Models\Contractor;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Softwares\OrderListFilterSoftware;
use App\Modules\Pub\Software\Services\SoftwareListFilterService;
use App\Modules\Pub\Sector\Models\Sector;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use App\Modules\Pub\Software\Models\Software;
use Illuminate\Support\Facades\DB;

class SoftwareRepository
{

    public static function getAll()
    {
        return Software::all()->keyBy('id');
    }

    public static function create(\Illuminate\Http\Request $request)
    {
        $data = $request->all();

        $software = Software::create([
            'name' => $data['name'],
            'extended' => $data['extended'],
            'notice' => $data['notice'],
            'count' => $data['count'],
            'cost' => $data['cost'],
            'cb_nds' => !empty($data['cb_nds']) ? (bool)$data['cb_nds'] : false,
        ]);

        return $software;
    }

    public static function update(Software $software, \Illuminate\Http\Request $request)
    {
        $data = $request->all();
        $software->update([
            'name' => $data['name'],
            'extended' => $data['extended'],
            'notice' => $data['notice'],
            'count' => $data['count'],
            'cost' => $data['cost'],
            'cb_nds' => !empty($data['cb_nds']) ? (bool)$data['cb_nds'] : false,
        ]);
    }

    public static function delete(Software $software)
    {
        $software->delete();
    }

    /**
     * Получение данных для таблицы
     *
     * @param $params
     * @return array
     */
    public function getTable($params = [])
    {
        $filterSoftware = new SoftwareListFilterService($params['_token'] ?? null);
        $builder = Software::where('id', '>', 0);

        $builder_full = clone $builder;

        # Filter
        $builder = $filterSoftware->filter($builder);
        $count = $count_filtered = $builder->count();

        # Search
        if (!empty($params['search'])) {
            $builder->search($params['search']);
            $count_filtered = $builder->count();
        }

        if (!empty($params['sort']) && !empty($params['order'])) {
            $builder->orderBy($params['sort'], $params['order']);
        } else {
            $builder->orderBy('name');
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
//                'grade' => $builder_full->pluck('grade')->unique()->toArray(),
            ]
        ];
    }

}
