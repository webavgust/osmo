<?php

namespace App\Modules\Pub\OrderTask\Services;

use App\Events\OrderTask\Agreement\OrderTaskAgreementAccepted;
use App\Events\OrderTask\Agreement\OrderTaskAgreementDeclined;
use App\Modules\Pub\Constant\Models\Constant;
use App\Modules\Pub\DocumentNumber\Services\DocumentNumberService;
use App\Modules\Pub\Evaluation\Models\Evaluation;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\OrderTask\Events\OrderTaskChangeStatus;
use App\Modules\Pub\OrderTask\Events\OrderTaskStartWorking;
use App\Modules\Pub\OrderTask\Events\OrderTaskUpdateEvent;
use App\Modules\Pub\OrderTask\FileGenerators\Interfaces\OrderTaskFileGeneratorInterface;
use App\Modules\Pub\OrderTask\Jobs\CloneJob;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\OrderTask\Repositories\OrderTaskRepository;
use App\Modules\Pub\OrderTaskAddress\Models\OrderTaskAddress;
use App\Modules\Pub\OrderTaskAgreement\Models\OrderTaskAgreement;
use App\Modules\Pub\OrderTaskAgreement\Services\OrderTaskAgreementService;
use App\Modules\Pub\OrderTaskMeasure\Models\OrderTaskMeasure;
use App\Modules\Pub\OrderTaskObject\Models\OrderTaskObject;
use App\Modules\Pub\OrderTaskPoint\Models\OrderTaskPoint;
use App\Modules\Pub\PlanEducationTaskCourseSalary\Repositories\PlanEducationTaskCourseSalaryRepository;
use App\Modules\Pub\Sampler\Models\Sampler;
use App\Modules\Pub\SubContract\Models\SubContract;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Services\Portal\Events;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Mpdf\Tag\Samp;

class OrderTaskService
{
    private $repo;

    public function __construct()
    {
        $this->repo = new OrderTaskRepository();
    }

    public static function generateFile(OrderTask $order_task, OrderTaskFileGeneratorInterface $generator)
    {
        return $generator->generate($order_task);
    }


    public static function decorate($rows)
    {
        if (!empty($rows->id)) {
            $rows->created_time = _date($rows->created_at);
            $rows->status_decorate = [
                "chr" => $rows->status,
                "name" => OrderTask::STATUS_LANG[$rows->status],
                "color" => OrderTask::STATUS_COLOR[$rows->status]['badge'],
                "class" => OrderTask::STATUS_COLOR[$rows->status]['button'],
            ];
        } else {
            $rows = $rows->map(function ($item) {
                $item->created_time = _date($item->created_at);
                $item->status_decorate = [
                    "chr" => $item->status,
                    "name" => OrderTask::STATUS_LANG[$item->status],
                    "color" => OrderTask::STATUS_COLOR[$item->status]['badge'],
                    "class" => OrderTask::STATUS_COLOR[$item->status]['button'],
                ];

                return $item;
            });
        }

        return $rows;
    }


    // копирование ТЗ из заявки в заявку
    public function copyOrder($from, $to)
    {
        // скопировать объект
        if (empty($from->order_task) || !empty($to->order_task)) return false;

        $task = $from->order_task;
        $task_to = $task->replicate();
        $task_to->order()->associate($to)->save();
        $task_to->push();

        $number = DocumentNumberService::generate(OrderTask::class);
        $task_to->number()->save($number);

        foreach ($task->objects as $object) {
            $object_to = $object->replicate();
            $object_to->task()->associate($task_to)->save();

            foreach ($object->addresses as $address) {
                $address_to = $address->replicate();
                $address_to->object()->associate($object_to)->save();

                foreach ($address->points as $point) {
                    $point_to = $point->replicate();
                    $point_to->address()->associate($address_to)->save();

                    foreach ($point->measures as $measure) {
                        $measure_to = $measure->replicate();
                        $measure_to->point()->associate($point_to)->save();
                    }
                }
            }


            foreach ($object->services as $service) {
                $object_to->services()->attach([$service->id => [
                    'count' => $service->pivot->count,
                    'bonus' => $service->pivot->bonus,
                    'bonus_total' => $service->pivot->bonus_total,
                    'cost' => $service->pivot->cost,
                    'cost_total' => $service->pivot->cost_total,
                    'comment' => $service->pivot->comment,
                ]]);
            }
        }

        return true;
    }

    // создание копии при отказе
    public function recreate(OrderTask $order_task)
    {
        $task_new = $order_task->replicate();
        $task_new->iteration = $this->getLastIteration($order_task) + 1;
        $task_new->status = OrderTask::STATUS_CREATED;
        $task_new->push();

        $number = DocumentNumberService::generate(OrderTask::class);
        $task_new->number()->save($number);

        foreach ($order_task->objects as $object) {
            $object_to = $object->replicate();
            $object_to->task()->associate($task_new)->save();

            foreach ($object->addresses as $address) {
                $address_to = $address->replicate();
                $address_to->object()->associate($object_to)->save();

                foreach ($address->points as $point) {
                    $point_to = $point->replicate();
                    $point_to->address()->associate($address_to)->save();

                    foreach ($point->measures as $measure) {
                        $measure_to = $measure->replicate();
                        $measure_to->point()->associate($point_to)->save();
                    }
                }
            }


            foreach ($object->services as $service) {
                $object_to->services()->attach([$service->id => [
                    'count' => $service->pivot->count,
                    'cost' => $service->pivot->cost,
                    'cost_total' => $service->pivot->cost_total,
                    'comment' => $service->pivot->comment,
                ]]);
            }
        }

        event(new OrderTaskUpdateEvent($task_new, 'recreate'));

        return $task_new;
    }


    // присоединение ТЗ к Заявке
    public function attach(OrderTask $order_task, Order $order)
    {
        $order_task->order()->associate($order)->save();
        event(new OrderTaskUpdateEvent($order_task, 'attach'));
        return true;
    }


    // работа с таблицей
    public function tableDefault($params)
    {
        $data = $this->repo->getTable($params);
        // Преобразование
        // TODO: переделать на Resource
        $data['rows'] = $this->decorate($data['rows']);

        // проверка прав
        foreach ($data['rows'] as $i => $row) {
            $row['portal'] = $row->evaluation->portal;
            $row['can'] = [
                'edit' => $row->canEdit(),
                'copy' => $row->canCopy(),
                'agree' => $row->canAgree(),
                'agree_view' => $row->canAgreeView(),
                'attach' => $row->canAttach()
            ];
            $row['client'] = [
                'id' => $row->evaluation->portal?->client_id,
                'name' => $row->evaluation->portal?->client_name,
                'link' => env('PORTAL_URL') . "/projects/clients/" . $row->evaluation->portal?->client_id . "/",
            ];
            $row['agreement'] = [
                'name' => $row->evaluation->portal?->contract_name,
                'link' => env('PORTAL_URL') . "/projects/contracts/" . $row->evaluation->sub_contract->contract_id . "/",
            ];
            $row['annex'] = [
                'name' => $row->evaluation->portal?->annex_name ?? $row->evaluation->block_id ?? null,
                'date' => !empty($row->evaluation->portal?->annex_date) ? date("d.m.Y", $row->evaluation->portal?->annex_date) : null
            ];
            $data['rows'][$i] = $row;
        }
        return $data;
    }


    public function notReady($arParams = [])
    {
        $rows = $this->repo->notReady($arParams);
        $rows = $this->decorate($rows);
        return $rows;
    }


    public function stepAgreement($arParams = [])
    {
        $rows = $this->repo->stepAgreement($arParams);
        $rows = $this->decorate($rows);
        return $rows;
    }


    public function notAgreemented($arParams = [])
    {
        $rows = $this->repo->notAgreemented($arParams);
        $rows = $this->decorate($rows);
        return $rows;
    }


    public function agree(OrderTask $order_task, mixed $users)
    {
        $service = new OrderTaskAgreementService();
        $agreement = $service->create($order_task, $users);
        $this->updateStatus($order_task, OrderTask::STATUS_AGREEMENT);
        event(new OrderTaskUpdateEvent($order_task, 'status_agreement'));

        return true;
    }

    public function agreeMakeDecision(OrderTask $order_task, mixed $all)
    {
        if (!auth()->user()->isAdmin() && $all['user_id'] != auth()->user()->id) abort(404);
        $user = $order_task->agreement->users()->where('id', $all['user_id'])->first();

        if (empty($user) || $user->pivot['agreed'] !== 0) abort(404);

        switch ($all['decision']) {
            case 'confirm':
                $order_task->agreement->users()->updateExistingPivot($user, ['agreed' => 1, 'comment' => $all['comment'] ?? null], false);

                // проверим на все решения
                $count_other = 0;
                foreach ($order_task->agreement->users as $user) {
                    if ($user->pivot['agreed'] === 0)
                        $count_other++;
                }
                if (!$count_other) {
                    // не осталось согласовантов
                    $this->updateStatus($order_task, OrderTask::STATUS_ACCEPTED);


                }

                break;
            case 'decline':
                $order_task->agreement->users()->updateExistingPivot($user, ['agreed' => -1, 'comment' => $all['comment'] ?? null], false);
                $order_task->agreement()->update([
                    'status' => OrderTaskAgreement::STATUS_DECLINED
                ]);

                $this->updateStatus($order_task, OrderTask::STATUS_DECLINED);

                break;
            default:
                abort(404);

        }

        event(new OrderTaskUpdateEvent($order_task, 'agree_decision'));

        return ['success' => 'OK'];
    }

    public static function updateStatus(OrderTask $order_task, string $STATUS)
    {
        $order_task->update(['status' => $STATUS]);
        event(new OrderTaskChangeStatus($order_task));
//        event(new OrderTaskUpdateEvent($order_task, 'status_update'));
        return true;
    }


    public function getLastIteration(OrderTask $order_task)
    {
        return OrderTask::where('block_id', $order_task->block_id)->max('iteration');
    }

    public static function cloneCheck(int $contract_id, string $sub_contract_id, string $block_id, string $clone_sub_contract_id, string $clone_block_id)
    {
        //  найдём целевое ТЗ
        $order_task = OrderTask::where('block_id', $block_id)->first();
        if (empty($order_task) || empty($order_task->sub_contract) || $order_task->sub_contract->slug !== $sub_contract_id || $order_task->sub_contract->contract_id !== $contract_id)
            return ['status' => 'error', 'error' => 'Not found', 'debug' => [

            ]];

        if ($order_task_have = OrderTask::where('block_id', $clone_block_id)->whereHas('sub_contract', function ($sub_contract) use ($clone_sub_contract_id) {
                $sub_contract->where('slug', $clone_sub_contract_id);
            })->count() > 0)
            return ['status' => 'error', 'error' => 'Already exist'];

        return ['status' => 'success', 'order_task' => $order_task];
    }

    public static function clone(int $contract_id, string $sub_contract_id, string $block_id, string $clone_sub_contract_id, string $clone_block_id)
    {
        $check = self::cloneCheck($contract_id, $sub_contract_id, $block_id, $clone_sub_contract_id, $clone_block_id);
        if ($check['status'] == 'success') {
            // DO WORK
            CloneJob::dispatch($check['order_task'], $clone_sub_contract_id, $clone_block_id)->delay(10);
        }

        return $check;
    }

    public static function cloneProcess(OrderTask $order_task, string $clone_sub_contract_id, string $clone_block_id)
    {
        // проверим, есть ли блок
        $sub_contract = SubContract::where('slug', $clone_sub_contract_id)->whereHas('contract', function ($contract) use ($order_task) {
            $contract->where('id', $order_task->sub_contract->contract_id);
        })->first();

        // проверяем, если ли версия договора
        if (empty($sub_contract)) {
            $sub_contract = new SubContract();
            $sub_contract->fill([
                'contract_id' => $order_task->sub_contract->contract_id,
                'slug' => $clone_sub_contract_id
            ])->save();
        }

        // клонируем ТЗ
        $task_new = $order_task->replicate();
        $task_new->iteration = 1;
        $task_new->status = OrderTask::STATUS_CREATED;
        $task_new->block_id = $clone_block_id;
        $task_new->sub_contract_id = $sub_contract->id;
        $task_new->push();

        $number = DocumentNumberService::generate(OrderTask::class);
        $task_new->number()->save($number);

        foreach ($order_task->objects as $object) {
            $object_to = $object->replicate();
            $object_to->task()->associate($task_new)->save();

            foreach ($object->addresses as $address) {
                $address_to = $address->replicate();
                $address_to->object()->associate($object_to)->save();

                foreach ($address->points as $point) {
                    $point_to = $point->replicate();
                    $point_to->address()->associate($address_to)->save();

                    foreach ($point->measures as $measure) {
                        $measure_to = $measure->replicate();
                        $measure_to->point()->associate($point_to)->save();
                    }
                }
            }


            foreach ($object->services as $service) {
                $object_to->services()->attach([$service->id => [
                    'count' => $service->pivot->count,
                    'cost' => $service->pivot->cost,
                    'cost_total' => $service->pivot->cost_total,
                    'bonus' => $service->pivot->bonus,
                    'bonus_total' => $service->pivot->bonus_total,
                    'comment' => $service->pivot->comment,
                ]]);
            }
        }

        event(new OrderTaskUpdateEvent($task_new, 'cloned'));
        return true;
    }

    public function makeFromEvaluation(Evaluation $evaluation)
    {
        if (!$evaluation->canTransform())
            return false;

        $annex = Str::lower(Str::random(8));

        $task = new OrderTask();

        $task->fill([
            'status' => OrderTask::STATUS_CREATED,
            'discount' => $evaluation->discount,
            'block_id' => $annex,
            'supervisor_rate' => $evaluation->supervisor_rate,
            'minus_rate' => $evaluation->minus_rate,
        ]);
        $task->creator()->associate(auth()->user() ?? $evaluation->creator);
        $task->sub_contract()->associate($evaluation->sub_contract);
        $task->evaluation()->associate($evaluation);
        $task->number()->save(DocumentNumberService::generate(OrderTask::class));
        $task->save();

        // OBJECT
        foreach ($evaluation->objects as $object) {
            $obj = new OrderTaskObject();
            $obj->fill([
                'name' => $object->name,
                'sort' => $object->sort
            ]);
            $obj->lab_object()->associate($object->lab_object);
            $task->objects()->save($obj);

            // ADDRESS
            foreach ($object->addresses as $address) {
                $adr = new OrderTaskAddress();
                $adr->fill([
                    'address' => $address->address,
                    'expanses' => $address->expanses,
                    'transport' => $address->transport,
                    'specialist' => $address->specialist,
                    'sort' => $address->sort
                ]);
                $obj->addresses()->save($adr);

                // POINTS
                foreach ($address->points as $point) {
                    $pt = new OrderTaskPoint();
                    $pt->fill([
                        'name' => $point->name,
                        'number' => $point->number,
                        'sort' => $point->sort
                    ]);
                    $adr->points()->save($pt);

                    foreach ($point->measures as $measure) {
                        $ms = new OrderTaskMeasure();
                        $bonus = $measure->measure->bonus;
                        $ms->fill([
                            'count' => $measure->count,
                            'cost' => $measure->cost,
                            'cost_total' => $measure->cost_total,
                            'bonus' => $bonus ,
                            'bonus_total' => $measure->count * $bonus,
                            'sort' => $measure->sort
                        ]);
                        $ms->measure()->associate($measure->measure);
                        $pt->measures()->save($ms);
                    }
                }

            }
        }


        // отправим плановую стоимость
        $this->send_plan($task);


        return $task;
    }

    public function send_plan(OrderTask $order_task)
    {
        $evaluation = $order_task->evaluation;


        $url_params = [
            'source' => env('APP_NAME'),
            'target_type' => 'order_task',
            'target_id' => $order_task->id,
            'target_application' => $order_task->evaluation->block_id,
        ];


        /*
        *  ADDRESSES
        */
        $addresses = collect();
        $evaluation->objects->each(function ($object, $obj_index) use ($evaluation, &$addresses) {
            $object->addresses->each(function ($address, $adr_index) use ($evaluation, &$addresses, $object, $obj_index) {
                $addresses->push([
                    'inner_id' => $address->id,
                    'name' => "Объект №" . ($obj_index + 1) . " [{$object->name}], адрес №" . ($adr_index + 1) . " [{$address->address}]",
                    'cost' => $address->cost_raw,
                    'count' => 1,
                    'user_id' => $evaluation->creator->id,
                ]);
            });
        });


        /*
         *  SALARIES
         */
        $salaries = collect();
        $salaries->push([
            'inner_id' => $order_task->id,
            'name' => "Зарплата руководителя лаборатории",
            'cost' => $evaluation->plan_supervisor_salary,
            'count' => 1,
            'user_id' => 798
        ]);


        $data = [
            'addresses' => [
                'rows' => $addresses->toArray(),
                'cost' => $addresses->sum(fn($item) => $item['cost']),
            ],
            'salaries' => [
                'rows' => $salaries->toArray(),
                'cost' => $salaries->sum(fn($item) => $item['cost']),
            ],
            'total' => [
                'cost' => $evaluation->plan_cost_total,
            ],
        ];


        $events = new Events();
        return $events->avg_hook('order_task/set_plan', $url_params, $data);
    }

    // Запускаем ТЗ в работу
    public function startWorking(OrderTask $orderTask)
    {
        if(!$orderTask->canStartWorking())
            return false;


        event(new OrderTaskStartWorking($orderTask));

        return $this->updateStatus($orderTask, OrderTask::STATUS_WORKING);
    }

    // Завершаем ТЗ
    public function finish(OrderTask $orderTask)
    {
        if(!$orderTask->canFinish())
            return false;

        return $this->updateStatus($orderTask, OrderTask::STATUS_FINISHED);
    }

    public function set_samplers(OrderTask $order, Request $request)
    {
        // очистим все записи

//        $order->objects->each(function($object) {
//            $object->samplers()->truncate();
//            $object->addresses->each(function($address) {
//                $address->samplers()->truncate();
//                $address->points->each(function($point) {
//                    $point->samplers()->truncate();
//                });
//            });
//        });

        $input = $request->input('samplers');
        $type = $request->input('type');

        switch($request->input('type')) {
            case 'A':
                $order->samplersA()->delete();
                $users = UserRepository::getById(explode(",", trim($input['global'][$type] ?? "")));
                foreach ($users as $user) {
                    $sampler = (new Sampler())->fill(['extra' => $type])->user()->associate($user);
                    $order->samplers()->save($sampler);
                }
                break;
            case 'B':
                $order->samplersB()->delete();
                $users = UserRepository::getById(explode(",", trim($input['global'][$type] ?? "")));
                foreach ($users as $user) {
                    $sampler = (new Sampler())->fill(['extra' => $type])->user()->associate($user);
                    $order->samplers()->save($sampler);
                }
                break;
        }

        $order->objects->each(function($object) use ($type, $input) {
            if(
                ($type == 'A' && !$object->isA())
                ||
                ($type == 'B' && !$object->isB())
            )
            return;
            $object->samplers()->delete();
            $users = UserRepository::getById(explode(",", trim($input['object'][$object->id] ?? "")));

            foreach ($users as $user) {
                $sampler = (new Sampler())->user()->associate($user);
                $object->samplers()->save($sampler);
            }


            $object->addresses->each(function($address) use ($input) {
                $address->samplers()->delete();
                $users = UserRepository::getById(explode(",", trim($input['address'][$address->id] ?? "")));

                foreach ($users as $user) {
                    $sampler = (new Sampler())->user()->associate($user);
                    $address->samplers()->save($sampler);
                }

                $address->points->each(function($point) use ($input) {
                    $point->samplers()->delete();
                    $users = UserRepository::getById(explode(",", trim($input['point'][$point->id] ?? "")));

                    foreach ($users as $user) {
                        $sampler = (new Sampler())->user()->associate($user);
                        $point->samplers()->save($sampler);
                    }
                });
            });
        });


        return true;
    }
}

