<?php

namespace App\Modules\Pub\OrderTask\Repositories;

use App\Modules\Pub\EducationTask\Models\EducationTask;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Services\OrderListFilterService;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\OrderTask\Services\OrderTaskListFilterService;
use App\Modules\Pub\OrderTask\Services\OrderTaskService;
use App\Modules\Pub\OrderTaskAgreement\Models\OrderTaskAgreement;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\UserDepartment\Models\UserDepartment;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderTaskRepository
{
    // ТЗ с выполненными и проверенными работи
    public static function getWithCheckedWorks()
    {
        return OrderTask::whereHas('objects.addresses.visits.visit_order_task_measures.samples.sample_works', function ($query) {
            $query->whereNotNull('checked_at');
        })->get();
    }

    public function search($q)
    {
        return OrderTask::where('id', 'like', "%{$q}%");
    }

    public function notReady($arParams = [])
    {
        $users = $arParams['users'] ?? null;
        $builder = OrderTask::forUsers($users)->where('status', OrderTask::STATUS_STARTED);
        if(!empty($arParams['dates'])) $builder->betweenDates($arParams['dates']);

        if(!auth()->user()->isAdmin()) {
            switch($arParams['as']) {
                case 'manager':
                    $builder->where('creator',  auth()->user()->id);
                    break;
                case 'curator':
                    $builder->where('curator',  auth()->user()->id);
                    break;
                default:
                    $builder->where('creator',  auth()->user()->id);
            }
        }
        return $builder->get();
    }


    public function stepAgreement($arParams = [])
    {
        $users = $arParams['users'] ?? null;
        $builder = OrderTask::forUsers($users)->whereIn('status', [OrderTask::STATUS_AGREEMENT, OrderTask::STATUS_CANCELLED, OrderTask::STATUS_CREATED, OrderTask::STATUS_ACCEPTED ]);
        if(!empty($arParams['dates'])) $builder->betweenDates($arParams['dates']);


        return $builder->orderBy('updated_at', 'desc')->get();
    }

    public function notAgreemented($arParams = [])
    {
        $users = $arParams['users'] ?? null;
        $builder = OrderTask::forUsers($users)->whereIn('status', [OrderTask::STATUS_DECLINED ]);
        if(!empty($arParams['dates'])) $builder->betweenDates($arParams['dates']);

        return $builder->orderBy('updated_at', 'desc')->get();
    }

    public function getTable($params = [])
    {
        $filterService = new OrderTaskListFilterService($params['_token'] ?? null);
        $builder = OrderTask::where('id', '>', 0);


        # User restrict
        $user = auth()->user();
        if($user->isAdmin()) {

        } else {
            $builder->where(function($builder) {
                $builder->where('id', '=', 0);
                // Руководитель
                if(auth()->user()->isSupervisor()) {
                    $builder->orWhereIn('status', [OrderTask::STATUS_ACCEPTED, OrderTask::STATUS_WORKING, OrderTask::STATUS_FINISHED]);
                // Начальник лаборатории
                } elseif(auth()->user()->isLabSupervisor()) {
                    $builder->orWhereIn('status', [OrderTask::STATUS_WORKING, OrderTask::STATUS_ALL_WORKS_FINISHED, OrderTask::STATUS_FINISHED]);
                // Куратор по направлению
                } elseif(auth()->user()->isCuratorByDirection()) {
                    $builder->orWhere(function($builder) {
                        $builder->whereIn('status', [OrderTask::STATUS_WORKING]);
                        $builder->where(function ($builder) {
                            $builder->where('id', '=', 0);
                            if (_can('direction_A'))
                                $builder->orWhereHas('objectsA');

                            if (_can('direction_B'))
                                $builder->orWhereHas('objectsB');
                        });
                    });
                // Исполнитель по направлению
                } elseif(auth()->user()->isExecutorByDirection()) {
                    $builder->orWhere(function ($builder) {
                        $builder->whereIn('status', [OrderTask::STATUS_WORKING]);
                        $builder->where(function ($builder) {
                            $builder->where('id', '=', 0);
                            if (_can('direction_A'))
                                $builder->orWhereHas('objectsA');

                            if (_can('direction_B'))
                                $builder->orWhereHas('objectsB');
                        });
                    });
                // Пробоотборщик
                } elseif(auth()->user()->isSampler()) {
                    $rows = collect();
                    $data = $this->getSamplerTaskTableData();
                    foreach ($data as $tab) {
                        $rows = $rows->merge($tab['data']);
                    }

                    $builder->orWhere(function ($builder) use ($rows) {
                        $builder->whereIn('id', $rows->pluck('id'));
                    });
                } else {
                    $builder->orWhere(function($builder) {
                        $builder->where('created_by', auth()->user()->id)
                            // согласовант
                            ->orWhere(function($builder) {
                                      $builder->whereHas('agreement', function($builder) {
                                          $builder->whereHas('users', function($builder) {
                                              $builder->where('id', auth()->id());
                                          });
                                      })
                                      ->whereIn('status', [
                                          OrderTask::STATUS_AGREEMENT,
                                          OrderTask::STATUS_DECLINED,
                                          OrderTask::STATUS_ACCEPTED,
                                          OrderTask::STATUS_ARCHIVE,
                                      ]);
                            });
                            // запуск в работу
                            if(_can('order_task_submit'))
                                $builder->orWhere(function($builder) {
                                    $builder->where('status', OrderTask::STATUS_ACCEPTED);
                                });
                    });
                }
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
            switch($params['sort']) {
                case 'created_time':
                    $builder->orderBy('created_at', $params['order']);
                    break;
                default:
                    $builder->orderBy($params['sort'], $params['order']);
            }
        } else {
            $builder->orderBy('created_at', 'desc');
        }

        $builder
        ->with([
                'creator' => function($query) {
                    $query->select(User::getShowFields());
                },
                'sub_contract' => function($query) {
                    $query->select(['id', 'slug', 'contract_id']);
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
                'creator' => $builder_full->pluck('created_by')->unique()->toArray(),
                'status' => $builder_full->pluck('status')->unique()->toArray(),
                'id' => $builder_full->pluck('id')->unique()->toArray(),
            ],
            'rows' => $builder->get()
        ];
    }



    // данные для РС:Менеджер:таблица статусов
    public function getTaskCountData($arParams = [])
    {
        $users = $arParams['users'] ?? null;
        $builder = OrderTask::forUsers($users)->select('id', 'status', \DB::raw("count(id) as count"));
        if(!empty($arParams['dates'])) $builder->betweenDates($arParams['dates']);

        $data = collect($builder->groupBy('status')->get()->keyBy('status'));
        return $data->sortBy(fn($value, $status) => OrderTask::STATUS_DATA[$status]['sort']);
    }


    // данные для РС:Менеджер:таблица заявок
    public function getTaskTableData($arParams = [])
    {
        $task_table = [];

        // не законченные
        $temp = $this->notReady($arParams);
        if($temp->isNotEmpty()) {
            $task_table[] = [
                "name" => "Незаконченные ТЗ",
                "icon" => "fa-pen-to-square",
                "data" => $this->decorate($temp),
                "template" => "components.order_task.dashboard.order_task_table"
            ];
        }

        // на согласовании
        $temp = $this->stepAgreement($arParams);
        if($temp->isNotEmpty()) {
            $task_table[] = [
                "name" => "Согласование",
                "icon" => "fa-users",
                "data" => $this->decorate($temp),
                "template" => "components.order_task.dashboard.order_task_table"
            ];
        }

        // не согласованные
        $temp = $this->notAgreemented($arParams);
        if($temp->isNotEmpty()) {
            $task_table[] = [
                "name" => "Не согласованные",
                "icon" => "fa-users-slash",
                "data" => $this->decorate($temp),
                "template" => "components.order_task.dashboard.order_task_table"
            ];
        }


        //$data->task_agreement = $order_task_service->stepAgreement();

//        $data = collect(OrderTask::available($user)->select('id', 'status', \DB::raw("count(id) as count"))->groupBy('status')->get()->keyBy('status'));

        return $task_table;
    }


    public function forAgreementers($arParams = [])
    {
        if(auth()->user()->isAdmin()) {
            $users = $arParams['users'] ?? null;
            $builder = OrderTask::forUsers($users)->whereIn('status', [OrderTask::STATUS_AGREEMENT, OrderTask::STATUS_DECLINED, OrderTask::STATUS_ACCEPTED ]);
            if(!empty($arParams['dates'])) $builder->betweenDates($arParams['dates']);

            return $builder->orderBy('updated_at', 'desc')->get();
        } else {
              $arOrderTaskID = collect();
              $agreements = auth()->user()->order_task_agreements()->with('order_task')->get()
                  ->each(function($item) use ($arOrderTaskID) {
                      if(!$arOrderTaskID->contains($item->order_task->id))
                          $arOrderTaskID->push($item->order_task->id);
              });

            $builder = OrderTask::whereIn('id', $arOrderTaskID)->whereIn('status', [OrderTask::STATUS_AGREEMENT, OrderTask::STATUS_DECLINED, OrderTask::STATUS_ACCEPTED ]);
            if(!empty($arParams['dates'])) $builder->betweenDates($arParams['dates']);

            return $builder->orderBy('updated_at', 'desc')->get();

        }
    }
    // данные для РС:Согласовант:таблица заявок
    public function getAgreementTaskTableData($arParams = [])
    {
        $task_table = [];
        $temp = $this->forAgreementers($arParams);
        $arOrderTaskStatuses = collect();
        foreach($temp as $order_task) {
            if(empty($arOrderTaskStatuses[$order_task->status]))
                $arOrderTaskStatuses[$order_task->status] = collect();
            $arOrderTaskStatuses[$order_task->status]->push($order_task);
        }


        // на согласовании
        if(!empty($arOrderTaskStatuses[OrderTask::STATUS_AGREEMENT]))
            $task_table[] = [
                "name" => "Нужно согласовать",
                "icon" => "fa-users",
                "data" => $this->decorate($arOrderTaskStatuses[OrderTask::STATUS_AGREEMENT]),
                "template" => "components.order_task.dashboard.order_task_table_wo_control"
            ];

        // Согласовано
        if(!empty($arOrderTaskStatuses[OrderTask::STATUS_ACCEPTED]))
            $task_table[] = [
                "name" => "Согласовано",
                "icon" => "fa-users",
                "data" => $this->decorate($arOrderTaskStatuses[OrderTask::STATUS_ACCEPTED]),
                "template" => "components.order_task.dashboard.order_task_table_agreementer"
            ];

        // Не согласованные
        if(!empty($arOrderTaskStatuses[OrderTask::STATUS_DECLINED]))
            $task_table[] = [
                "name" => "Не согласованные",
                "icon" => "fa-users-slash",
                "data" => $this->decorate($arOrderTaskStatuses[OrderTask::STATUS_DECLINED]),
                "template" => "components.order_task.dashboard.order_task_table_agreementer"
            ];


        return $task_table;
    }

    public function forExecutorDashboard($mode = false)
    {
        if(!empty($mode)) {
            switch($mode) {
                case 'A':
                    return OrderTask::forA()->get();
                    break;
                case 'B':
                    return OrderTask::forB()->get();
                    break;
            }
        }
        $rows = collect();
        if(_can('order_group_A')) $rows['A'] = $this->decorate($this->forExecutorDashboard('A'));
        if(_can('order_group_B')) $rows['B'] = $this->decorate($this->forExecutorDashboard('B'));

        return $rows;
    }



    private function decorate($rows)
    {
        return OrderTaskService::decorate($rows);
    }


    /**
     * Срез для руководителей
     *
     */
    public function forSupervisors($arParams = [])
    {
        $builder = OrderTask::whereIn('status', [OrderTask::STATUS_ACCEPTED, OrderTask::STATUS_WORKING])
            ->with('evaluation');

        return $builder->orderBy('id', 'desc')->get();
    }


    /**
     * Срез для руководителей лаборатории
     *
     */
    public function forLabSupervisors($arParams = [])
    {
        $builder = OrderTask::whereIn('status', [OrderTask::STATUS_WORKING, OrderTask::STATUS_ALL_WORKS_FINISHED])
            ->with('evaluation');

        return $builder->orderBy('id', 'desc')->get();
    }


    /**
     * Данные для таблицы руководителя
     *
     * @param $arParams
     * @return array
     */
    public function getSupervisorTaskTableData($arParams = [])
    {

        $task_table = [];
        $temp = $this->forSupervisors($arParams);
        $users_id = $temp->pluck('evaluation.created_by')->merge($temp->pluck('created_by'))->unique();
        $users = UserRepository::getById($users_id->toArray())->keyBy('id');
        $arOrderTaskStatuses = collect();

        foreach ($temp as $order_task) {
            if (empty($arOrderTaskStatuses[$order_task->status]))
                $arOrderTaskStatuses[$order_task->status] = collect();
            $arOrderTaskStatuses[$order_task->status]->push($order_task);
        }


        // Согласовано, можно отправить в работу
        if (!empty($arOrderTaskStatuses[OrderTask::STATUS_ACCEPTED]))
            $task_table[] = [
                "name" => "Нужно отправить в работу",
                "icon" => "fa-play",
                "data" => $this->decorate($arOrderTaskStatuses[OrderTask::STATUS_ACCEPTED]),
                "users" => $users,
                "template" => "components.dashboard.supervisor.order_task.agreed_tbl"
            ];

        // В работе
        if (!empty($arOrderTaskStatuses[OrderTask::STATUS_WORKING]))
            $task_table[] = [
                "name" => "В работе",
                "icon" => "fa-briefcase",
                "data" => $this->decorate($arOrderTaskStatuses[OrderTask::STATUS_WORKING]),
                "users" => $users,
                "template" => "components.dashboard.supervisor.order_task.working_tbl"
            ];

        return $task_table;
    }


    /**
     * Данные для таблицы руководителя лаборатории
     *
     * @param $arParams
     * @return array
     */
    public function getLabSupervisorTaskTableData($arParams = [])
    {

        $task_table = collect();
        $temp = $this->forLabSupervisors($arParams);
        $users_id = $temp->pluck('evaluation.created_by')->merge($temp->pluck('created_by'))->unique();
        $users = UserRepository::getById($users_id->toArray())->keyBy('id');
        $arOrderTaskStatuses = collect();

        foreach ($temp as $order_task) {
            if (empty($arOrderTaskStatuses[$order_task->status]))
                $arOrderTaskStatuses[$order_task->status] = collect();
            $arOrderTaskStatuses[$order_task->status]->push($order_task);
        }

        // В работе
        if (!empty($arOrderTaskStatuses[OrderTask::STATUS_WORKING]))
            $task_table[] = [
                "name" => "В работе",
                "icon" => "fa-briefcase",
                "data" => $this->decorate($arOrderTaskStatuses[OrderTask::STATUS_WORKING]),
                "users" => $users,
                "template" => "components.dashboard.lab_supervisor.order_task.working_tbl"
            ];

        return $task_table;
    }

    /**
     * Срез для руководителей лаборатории
     *
     */
    public function forCuratorDirection($arParams = [])
    {
        $rows = OrderTask::whereIn('status', [OrderTask::STATUS_WORKING])
            ->with('evaluation')->orderBy('id', 'desc')->get();


        if(!_can('direction_A'))
            $rows = $rows->filter(fn($task) => $task->objectsB->count() !== 0);

        if(!_can('direction_B'))
            $rows = $rows->filter(fn($task) => $task->objectsA->count() !== 0);



        return $rows;
    }


    /**
     * Данные для таблицы руководителя
     *
     * @param $arParams
     * @return array
     */
    public function getCuratorDirectionTaskTableData($arParams = [])
    {

        $task_table = [];
        $temp = $this->forCuratorDirection($arParams);
        $users_id = $temp->pluck('evaluation.created_by')->merge($temp->pluck('created_by'))->unique();
        $users = UserRepository::getById($users_id->toArray())->keyBy('id');
        $arOrderTaskStatuses = collect();

        foreach ($temp as $order_task) {
            if (empty($arOrderTaskStatuses[$order_task->status]))
                $arOrderTaskStatuses[$order_task->status] = collect();
            $arOrderTaskStatuses[$order_task->status]->push($order_task);
        }

        // В работе
        if (!empty($arOrderTaskStatuses[OrderTask::STATUS_WORKING]))
            $task_table[] = [
                "name" => "В работе",
                "icon" => "fa-briefcase",
                "data" => $this->decorate($arOrderTaskStatuses[OrderTask::STATUS_WORKING]),
                "users" => $users,
                "template" => "components.dashboard.curator_direction.order_task.working_tbl"
            ];

        return $task_table;
    }

    /**
     * Срез для руководителей лаборатории
     *
     */
    public function forExecutorDirection($arParams = [])
    {
        $rows = OrderTask::whereIn('status', [OrderTask::STATUS_WORKING])
            ->with('evaluation')->orderBy('id', 'desc')->get();

        if(!_can('direction_A'))
            $rows = $rows->filter(fn($task) => $task->objectsB->count() !== 0);

        if(!_can('direction_B'))
            $rows = $rows->filter(fn($task) => $task->objectsA->count() !== 0);


        return $rows;
    }


    /**
     * Данные для таблицы руководителя
     *
     * @param $arParams
     * @return array
     */
    public function getExecutorDirectionTaskTableData($arParams = [])
    {

        $task_table = [];
        $temp = $this->forExecutorDirection($arParams);
        $users_id = $temp->pluck('evaluation.created_by')->merge($temp->pluck('created_by'))->unique();
        $users = UserRepository::getById($users_id->toArray())->keyBy('id');
        $arOrderTaskStatuses = collect();
        $arOrderWithoutSamplers = collect();

        $arOrderWithoutSamplers = collect();
        foreach ($temp as $order_task) {
            if (empty($arOrderTaskStatuses[$order_task->status]))
                $arOrderTaskStatuses[$order_task->status] = collect();
            $arOrderTaskStatuses[$order_task->status]->push($order_task);

            $order_task->objects->flatMap->addresses->flatMap->points->each(function ($point) use (&$arOrderWithoutSamplers) {
                if (!$point->hasSamplers()) {
                    $arOrderWithoutSamplers->push($point->address->object->task);
                    return false;
                }
            });
        }


        if($arOrderWithoutSamplers->isNotEmpty()) {
            $arOrderTaskStatuses[OrderTask::STATUS_WORKING] = $arOrderTaskStatuses[OrderTask::STATUS_WORKING]->reject(fn($model) => $arOrderWithoutSamplers->contains('id', $model->id));

            $task_table[] = [
                "name" => "Нужно назначить пробоотборщиков",
                "icon" => "fa-briefcase",
                "data" => $this->decorate($arOrderWithoutSamplers),
                "users" => $users,
                "template" => "components.dashboard.executor_direction.order_task.working_tbl"
            ];
        }

        // В работе
        if (!empty($arOrderTaskStatuses[OrderTask::STATUS_WORKING]))
            $task_table[] = [
                "name" => "В работе",
                "icon" => "fa-briefcase",
                "data" => $this->decorate($arOrderTaskStatuses[OrderTask::STATUS_WORKING]),
                "users" => $users,
                "template" => "components.dashboard.executor_direction.order_task.working_tbl"
            ];
        return $task_table;
    }

    /**
     * Срез для руководителей лаборатории
     *
     */
    public function forSampler($arParams = [])
    {
        $rows = OrderTask::whereIn('status', [OrderTask::STATUS_WORKING])
            ->with('evaluation')->orderBy('id', 'desc')->get();

        if(!_can('direction_A'))
            $rows = $rows->filter(fn($task) => $task->objectsB->count() !== 0);

        if(!_can('direction_B'))
            $rows = $rows->filter(fn($task) => $task->objectsA->count() !== 0);


        return $rows;
    }


    /**
     * Данные для таблицы руководителя
     *
     * @param $arParams
     * @return array
     */
    public function getSamplerTaskTableData($arParams = [])
    {
        $task_table = [];
        $temp = $this->forSampler($arParams);
        $users_id = $temp->pluck('evaluation.created_by')->merge($temp->pluck('created_by'))->unique();
        $users = UserRepository::getById($users_id->toArray())->keyBy('id');

        $inWork = collect();
        $canJoinA = collect();
        $canJoinB = collect();

        foreach($temp as $task) {
            if($task->getSamplers()->pluck('user_id')->contains(auth()->id()))
                $inWork[] = $task;


            // направление А
            if(_can('direction_A')) {
//                $check = true;
//                $task->objectsA->each(function ($object) use (&$check) {
//                    if (!$object->hasSamplers()) {
//                        $check = false;
//                        return false;
//                    }
//                });
//                if(!$check)
                    $canJoinA[] = $task;
            }

            // направление Б
            if(_can('direction_B')) {

//                $task->objectsB->each(function ($object) use (&$check) {
//                    if (!$object->hasSamplers()) {
//                        $check = false;
//                        return false;
//                    }
//                });
//                if(!$check)
                    $canJoinB[] = $task;
            }

        }

        if($inWork->isNotEmpty())
            $task_table[] = [
                "name" => "Назначен пробоотборщиком",
                "icon" => "fa-briefcase",
                "data" => $this->decorate($inWork),
                "users" => $users,
                "template" => "components.dashboard.curator.order_task.working_tbl"
            ];

        if($canJoinA->isNotEmpty())
            $task_table[] = [
                "name" => "Можно взять в работу (А)",
                "icon" => "fa-briefcase",
                "data" => $this->decorate($canJoinA),
                "users" => $users,
                "template" => "components.dashboard.curator.order_task.working_tbl"
            ];

        if($canJoinB->isNotEmpty())
            $task_table[] = [
                "name" => "Можно взять в работу (Б)",
                "icon" => "fa-briefcase",
                "data" => $this->decorate($canJoinB),
                "users" => $users,
                "template" => "components.dashboard.curator.order_task.working_tbl"
            ];

        // В работе
        if (!empty($arOrderTaskStatuses[OrderTask::STATUS_WORKING]))
            $task_table[] = [
                "name" => "В работе",
                "icon" => "fa-briefcase",
                "data" => $this->decorate($arOrderTaskStatuses[OrderTask::STATUS_WORKING]),
                "users" => $users,
                "template" => "components.dashboard.executor_direction.order_task.working_tbl"
            ];
        return $task_table;
    }

}
