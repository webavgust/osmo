<?php

namespace App\Modules\Pub\Order\Repositories;

use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Services\OrderListFilterService;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserDepartment\Models\UserDepartment;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    public function search($q)
    {
        return Order::where('id', 'like', "%{$q}%");
    }

    private function as($arParams)
    {
        $users = $arParams['users'] ?? null;

        if(!empty($arParams['as'])) {
            switch ($arParams['as']) {
                case 'manager':
                    $builder = Order::asManager($users);
                    break;
                case 'curator':
                    $builder = Order::asCurator($users);
                    break;
                default:
                    $builder = Order::asManager($users);
            }
        } else {
            $builder = Order::asManager($users);
        }
        return $builder;
    }
    public function getTable($params = [])
    {
        $filterService = new OrderListFilterService($params['_token'] ?? null);
        $builder = Order::where('orders.id', '>', 0);


        # User restrict
        $user = auth()->user();
        if($user->isAdmin()) {

        } elseif($user->hasGroup(UserGroup::GROUP_SUPERVISOR)) {
            # получим всех людей, которые входят во все подразделения, к которым принадлежит пользователь
            $managers = DB::table('users', 'u')
                ->leftJoin('user_user_department as uud', 'uud.user_id', '=', 'u.id')
                ->leftJoin('user_user_department as uud2', 'uud2.user_department_id', '=', 'uud.user_department_id')
                ->leftJoin('user_user_group', 'uud2.user_id', '=', 'user_user_group.user_id')
                ->leftJoin('users as u2', 'uud2.user_id', '=', 'u2.id')
                ->select('u2.id')
                ->where('u.id', $user->id)
                ->where('user_user_group.user_group_id', UserGroup::GROUP_MANAGER)
                ->get()
                ->pluck('id');
            $builder->where(function ($query) use ($managers) {
                $query->whereIn('manager_id', $managers);
            });
        } elseif($user->hasGroup(UserGroup::GROUP_CURATOR)) {
            $builder->where(function ($query) use ($user) {
                $query->where('manager_id', $user->id)
                ->orWhere('curator_id', $user->id);
            });
        } elseif($user->hasGroup(UserGroup::GROUP_MANAGER)) {
            $builder->where('manager_id', $user->id);
        } else {
            $builder->where(function($query) use ($user) {
                $query->where('author_id', $user->id)
                    ->orWhere('manager_id', $user->id)
                    ->orWhere('curator_id', $user->id);
            });
        }
        $builder_full = clone $builder;

        # Filter
        $builder = $filterService->filter($builder);
        $count = $count_filtered = $builder->count();

        # Search
        if(!empty($params['search']))
        {
            $builder->search($params['search']);
            $count_filtered = $builder->count();
        }


        if(!empty($params['sort']) && !empty($params['order']))
        {
            switch($params['sort'])
            {
                case 'comments':
                    // тут начинаются извращения
                    $builder->fromRaw('
                        orders
                        left join (
                                SELECT text, order_id, created_at
                                FROM order_comments
                                WHERE id IN (
                                    SELECT MAX(id)
                                    FROM order_comments
                                    GROUP BY order_id
                                )
                            ) as last_comment
                         ON orders.id = last_comment.order_id
                    ');
                    $builder->orderBy(DB::raw('order_comments.created_at'), $params['order']);
                    break;
                default:
                    $builder->orderBy($params['sort'], $params['order']);
            }
        } else {
            $builder->orderBy('order_sent_to_techdep', 'desc');
        }

        $builder
        ->with([
                'author' => function($query) {
                    $query->select(User::getShowFields());
                },
                'manager' => function($query) {
                    $query->select(User::getShowFields());
                },
                'curator' => function($query) {
                    $query->select(User::getShowFields());
                },
                'order_task',
                'comments' => function($query) {
                    $query->orderBy('created_at', 'desc');
                    $query->with('user', function($query) {
                        $query->select(['id', 'last_name', 'name']);
                    });
                }
        ]);



        # Paginate
        if(!empty($params['limit']))
            $builder->limit($params['limit']);

        if(!empty($params['offset']))
            $builder->skip($params['offset']);



        return [
            'count' => $count,
            'count_filter' => $count_filtered,
            'filter' => [
                'authors' => $builder_full->pluck('author_id')->unique()->toArray(),
                'managers' => $builder_full->pluck('manager_id')->unique()->toArray(),
                'curators' => $builder_full->pluck('curator_id')->unique()->toArray(),
                'id' => $builder_full->pluck('id')->unique()->toArray(),
            ],
            'rows' => $builder->get()
        ];
    }


    // данные для РС:Менеджер:таблица статусов
    public function getCountData($arParams = [])
    {
        $builder = $this->as($arParams);
        $builder->select('id', 'is_finished', 'is_archived');

        if(!empty($arParams['dates'])) $builder->betweenDates($arParams['dates']);

        $statuses = [];
        $data = collect($builder->get());
        $data->map(function($item, $index) use (&$statuses) {
            if($item->is_archived) {
                $item->status = "archived";
            } else {
                if($item->is_finished) {
                    $item->status = "finished";
                } else {
                    $item->status = "active";
                }
            }
            if(empty($statuses[$item->status]))
                $statuses[$item->status] = 0;
            $statuses[$item->status]++;
        });

        return $statuses;
    }


    public function getDangerCount($arParams = [])
    {
        $builder = $this->as($arParams);
        return $builder->whereNotNull('md_specify_finaldate')->where('is_finished', 0)->where('is_archived', 0)->whereDate('md_specify_finaldate', '>', now()->year(2020)->startOfYear())->whereDate('md_specify_finaldate', '<', now()->addDays(1))->count();
    }

    public function getWarningCount($arParams = [])
    {
        $builder = $this->as($arParams);
        return $builder->whereNotNull('md_specify_finaldate')->where('is_finished', 0)->where('is_archived', 0)->whereDate('md_specify_finaldate', '>', now()->addDays(1))->whereDate('md_specify_finaldate', '<', now()->addDays(3))->count();
    }

    public function getControlCount($arParams = [])
    {
        $builder = $this->as($arParams);
        return $builder->whereNotNull('md_specify_finaldate')->where('is_finished', 0)->where('is_archived', 0)->whereDate('md_specify_finaldate', '>', now()->addDays(3))->whereDate('md_specify_finaldate', '<', now()->addDays(14))->count();
    }

}
