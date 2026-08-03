<?php

namespace App\Modules\Pub\OrderTask\Controllers\Api;

use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Evaluation\Models\Evaluation;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Repositories\OrderRepository;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\OrderTask\Repositories\OrderTaskRepository;
use App\Modules\Pub\OrderTask\Requests\AgreeDecisionRequest;
use App\Modules\Pub\OrderTask\Requests\AgreeRequest;
use App\Modules\Pub\OrderTask\Requests\ListFilterRequest;
use App\Modules\Pub\OrderTask\Requests\ListRequest;
use App\Modules\Pub\OrderTask\Services\OrderTaskListFilterService;
use App\Modules\Pub\OrderTask\Services\OrderTaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ApiOrderTaskController
{
    private $service;

    public function __construct()
    {
        $this->service = new OrderTaskService();
    }


    /**
     * Перевод ТЗ в статус STATUS_IN_WORK
     *
     * @param Order $order
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function status_work(Order $order, Request $request)
    {
        if (!_can('order_task_submit') || !$order->canView() || empty($order->order_task) || $order->order_task->status != OrderTask::STATUS_CREATED) abort(404);
        $order->order_task()->update(['status' => OrderTask::STATUS_CREATED]);
        $order->refresh();
        return View::make('components.order.detail.order_task_control', ['order' => $order]);
    }


    /**
     * Отправка ТЗ на согласование
     *
     * @param Order $order
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function agree(OrderTask $order_task, AgreeRequest $request)
    {
        if (!$order_task->canAgree()) abort(404);


        $service = new OrderTaskService();;
        if ($service->agree($order_task, $request->input('user'))) {
            $order_task->refresh();

            return View::make('components.dashboard.order_task_agree.tr_row', ['task' => OrderTaskService::decorate($order_task)]);

        } else {
            abort(404);
        }
    }


    /**
     * Согласование от ответственного лица по ТЗ
     *
     * @param Order $order
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function agree_decision(OrderTask $order_task, AgreeDecisionRequest $request)
    {
        if (!$order_task->canMakeDecision()) abort(404);

        $service = new OrderTaskService();;
        if ($service->agreeMakeDecision($order_task, $request->all())) {
            return ['status' => 'OK'];
        } else {
            abort(404);
        }
    }

    /**
     * Копирование ТЗ из одной заявки в другую
     *
     * @param Order $order
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function copy(Order $order, Request $request)
    {
        $order_to = Order::findOrFail($request->copy_to);
        if (!_can('order_task_copy') || !$order->canView() || !$order_to->canView() || empty($order->order_task)) abort(404);

        $service = new OrderTaskService();
        if ($service->copyOrder($order, $order_to)) {
            return \Response::json([
                'url' => route('order.detail', $order_to)
            ]);
        } else {
            abort(404);
        }
    }


    /**
     * Присоединение ТЗ к заявке
     *
     * @param OrderTask $order_task
     * @param Request $request
     */
    public function attach(OrderTask $order_task, Request $request)
    {
        $order_to = Order::findOrFail($request->attach_to);
        if (!_can('order_task_attach') || !$order_to->canView() || !empty($order_to->order_task)) abort(404);

        $service = new OrderTaskService();
        if ($service->attach($order_task, $order_to)) {
            return \Response::json(['number' => $order_to->id]);
        } else {
            abort(404);
        }
    }

    /**
     * Присоединение ТЗ к заявке
     *
     * @param OrderTask $order_task
     * @param Request $request
     */
    public function attach_order(Order $order, Request $request)
    {
        if (!_can('order_task_attach') || !$order->canView() || !empty($order->order_task)) abort(404);

        $order_task = OrderTask::findOrFail($request->input('attach_to'));
        $service = new OrderTaskService();
        if ($service->attach($order_task, $order)) {
            return \Response::json(['number' => $order->id]);
        } else {
            abort(404);
        }
    }



    //

    /**
     * Cписок доступных заявок без ТЗ
     *
     * @param ListRequest $request
     * @return array
     */
    public function list(ListRequest $request)
    {
        $arReturn = [];
        if (strlen($request->get('q')) < 3) return $arReturn;
        $repo = new OrderRepository();
        $table_data = array_flip($repo->getTable()['filter']['id'] ?? []);

        $result = $repo->search($request->get('q'))->doesntHave('order_task')->pluck('id')->each(function ($item) use (&$arReturn, $table_data) {
            // проверка на доступные
            if (!$table_data[$item]) return;
            $arReturn[] = [
                'id' => $item,
                'text' => $item
            ];
        });
        return $arReturn;
    }


    public function list_free(ListRequest $request)
    {
        $arReturn = [];
//        if(strlen($request->get('q')) < 3) return $arReturn;
        $repo = new OrderTaskRepository();

        $tasks = OrderTask::where('contract_id', 'like', "%{$request->get('q')}%")
            ->orWhere('contract_sub_id', 'like', "%{$request->get('q')}%")
            ->doesntHave('order');

        $result = $tasks->each(function ($item) use (&$arReturn) {
            // проверка на доступные
            $arReturn[] = [
                'id' => $item->id,
                'text' => $item->contract_id . ' / ' . $item->contract_sub_id
            ];
        });
        return $arReturn;
    }


    /**
     * Данные для обслуживания таблицы с ТЗ
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list_table(Request $request)
    {
        $service = new OrderTaskService();
        $data = $service->tableDefault($request->only(['_token', 'sort', 'order', 'search', 'limit', 'offset']));


        return response()->json([
            "total" => $data['count_filter'],
            "totalNotFiltered" => $data['count'],
            "rows" => $data['rows']
        ]);
    }


    /**
     * Фильтрация по таблице ТЗ
     *
     * @param ListFilterRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function filter(ListFilterRequest $request)
    {
        $service = new OrderTaskListFilterService($request->_token);
        $rules_count = $service->setFilter($request->validated());

        return response()->json([
            'result' => 'success',
            'rules_count' => $rules_count
        ]);

    }

    /**
     * Удаление фильтра ТЗ
     *
     * @param ListFilterRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function filterRemove(ListFilterRequest $request)
    {
        $service = new OrderTaskListFilterService($request->_token);
        $service->clearFilter();
        return response()->json([
            'result' => 'success'
        ]);
    }

    public function portal_check(int $contract_id, string $contract_sub_id, string $order_task_slug, $iteration = null)
    {
//        $contract = Contract::findOrFail($contract_id);
//        $sub_contracts_builder = $contract->sub_contracts()->where('slug', $contract_sub_id);
//        $sub_contracts = $sub_contracts_builder->get();
//
//        $tasks = collect();
//        $sub_contracts->each(function ($item) use (&$tasks) {
//            $tasks = $tasks->merge($item->getActualOrderTasks(), $tasks);
//        });
//
//        if (!$tasks->count()) {
//        } else {
//            $rows = [];

            $builder = Evaluation::where('block_id', $order_task_slug)
            ->whereHas('sub_contract', function($query) use ($contract_sub_id, $contract_id) {
                  $query->where('sub_contracts.slug', $contract_sub_id);
                  $query->whereHas('contract', function($query) use ($contract_id) {
                      $query->where('id', $contract_id);
                  });
            });
            if(!empty($iteration))
                $builder->where('iteration', $iteration);

            $builder->orderBy('iteration', 'desc')->first();
            $evaluation = $builder->first();

            if(empty($evaluation))
                return response()->json(['status' => 'not_found']);

            $evaluation->sub_contract_id = $evaluation->sub_contract['slug'];
            $evaluation->contract_id = $evaluation->sub_contract['contract_id'];


            switch ($evaluation->status) {
                case Evaluation::STATUS_FINAL_CHECK:
                case Evaluation::STATUS_READY_TO_TRANSFORM:
                    $agreement = $evaluation->final_agreement;

                    foreach ($agreement->users as $user) {
                        $arAgreementUsers[] = [
                            'user_id' => $user->id,
                            'agreed' => $user->pivot->agreed,
                            'comment' => $user->pivot->comment,
                            'updated_at' => $user->pivot->updated_at,
                        ];
                    }

                    unset($agreement->users);
                    $agreement->users = $arAgreementUsers;
                    $files = [];
                    foreach ( $evaluation->files->groupBy('extension') as $ext => $docs) {
                        foreach ($docs as $document) {
                            $files[$ext][] = [
                                'path' => download_path($document->path),
                                'filesize' => $document->size
                            ];
                        }
                    }

                    unset($evaluation->final_agreement);
                $evaluation->final_agreement = [
                        'status' => $agreement->status,
                        'created_at' => $agreement->created_at,
                        'updated_at' => $agreement->updated_at,
                        'files' => $files,
                        'users' => $agreement->users
                    ];
                    break;
            }
            unset($evaluation->sub_contract);


            return response()->json(['status' => 'found', 'task' => $evaluation]);
    }

    // отмена при отказе
    public function cancel(OrderTask $order_task, Request $request)
    {
        if (!$order_task->hasAccess() || $order_task->status != OrderTask::STATUS_DECLINED) abort(404);

        $service = new OrderTaskService();
        if ($service->updateStatus($order_task, OrderTask::STATUS_ARCHIVE)) {
            return response()->json(['status' => 'success']);
        } else {
            abort(404);
        }

    }


    // пересоздание при отказе
    public function recreate(OrderTask $order_task, Request $request)
    {
        if (!$order_task->canRemake()) abort(404);
        $service = new OrderTaskService();
        if ($new_task = $service->recreate($order_task)) {
            return response()->json(['status' => 'success', 'url' => route('order_task.detail', $new_task)]);
        } else {
            abort(404);
        }

        return response()->json(['status' => 'success']);
    }


    public function start_working(OrderTask $order_task)
    {
        if(!$order_task->canStartWorking()) {
            abort(404);
        }

        return $this->service->startWorking($order_task);
    }

    public function finish(OrderTask $order_task)
    {
        if(!$order_task->canFinish()) {
            abort(404);
        }

        return $this->service->finish($order_task);
    }


    public function set_samplers(Request $request, OrderTask $order)
    {
        if(!$order->canSetSamplers())
            abort(404);

        return $this->service->set_samplers($order, $request);
    }
}
