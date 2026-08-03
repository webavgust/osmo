<?php

namespace App\Modules\Pub\Work\Repositories;

use App\Modules\Pub\Course\Models\Course;
use App\Modules\Pub\EducationApplication\Works\EducationApplicationListFilterWork;
use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use App\Modules\Pub\Work\Models\Contractor;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Works\OrderListFilterWork;
use App\Modules\Pub\Work\Services\WorkListFilterService;
use App\Modules\Pub\Sector\Models\Sector;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use App\Modules\Pub\Work\Models\Work;
use Illuminate\Support\Facades\DB;

class WorkRepository
{

    public static function getAll()
    {
        return Work::all()->keyBy('id');
    }

    public static function create(\Illuminate\Http\Request $request)
    {
        $data = $request->all();

        $work = Work::create([
            'name' => $data['name'],
            'group' => $data['group'],
            'extended' => $data['extended'],
            'notice' => $data['notice'],
            'count' => $data['count'],
            'cost' => $data['cost'],
            'lang' => $data['lang'],
        ]);

        return $work;
    }

    public static function update(Work $work, \Illuminate\Http\Request $request)
    {
        $data = $request->all();
        $work->update([
            'name' => $data['name'],
            'group' => $data['group'],
            'extended' => $data['extended'],
            'notice' => $data['notice'],
            'count' => $data['count'],
            'cost' => $data['cost'],
            'lang' => $data['lang'],
        ]);
    }

    public static function delete(Work $work)
    {
        $work->delete();
    }

    /**
     * Получение данных для таблицы
     *
     * @param $params
     * @return array
     */
    public function getTable($params = [])
    {
        $filterWork = new WorkListFilterService($params['_token'] ?? null);
        $builder = Work::where('id', '>', 0);

        $builder_full = clone $builder;

        # Filter
        $builder = $filterWork->filter($builder);
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
