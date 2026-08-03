<?php

namespace App\Modules\Pub\OrderTask\Controllers;

use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\DocumentNumber\Models\DocumentNumber;
use App\Modules\Pub\DocumentNumber\Services\DocumentNumberService;
use App\Modules\Pub\Evaluation\Repository\EvaluationRepository;
use App\Modules\Pub\Evaluation\Services\EvaluationService;
use App\Modules\Pub\LabMeasure\Models\LabMeasure;
use App\Modules\Pub\LabMeasure\Repository\LabMeasureRepository;
use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\LabObject\Repository\LabObjectRepository;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Repositories\OrderRepository;
use App\Modules\Pub\Order\Services\OrderService;
use App\Modules\Pub\OrderTask\Events\OrderTaskUpdateEvent;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\OrderTask\Repositories\OrderTaskRepository;
use App\Modules\Pub\OrderTask\Requests\CreateRequest;
use App\Modules\Pub\OrderTask\Requests\CreateStep2Request;
use App\Modules\Pub\OrderTask\Requests\EditRequest;
use App\Modules\Pub\OrderTask\Services\OrderTaskListFilterService;
use App\Modules\Pub\OrderTask\Services\OrderTaskService;
use App\Modules\Pub\OrderTaskAddress\Models\OrderTaskAddress;
use App\Modules\Pub\OrderTaskMeasure\Models\OrderTaskMeasure;
use App\Modules\Pub\OrderTaskObject\Models\OrderTaskObject;
use App\Modules\Pub\OrderTaskPoint\Models\OrderTaskPoint;
use App\Modules\Pub\Service\Models\Service;
use App\Modules\Pub\SubContract\Models\SubContract;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use App\Services\Notificator\Notificator;
use App\Services\Portal\Events;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use phpDocumentor\Reflection\Location;

class OrderTaskController extends Controller
{
    use HasBreadcrumb;

    /*
     *  СОЗДАНИЕ
     */
    public function __construct()
    {
        $this->repo = new OrderTaskRepository();
        $this->service = new OrderTaskService();
        $this->breadcrumb_add(route('order_task.index'), 'Тех.задания');
    }

    // 1 шаг, форма создания
    public function index()
    {
        $filter_service = new OrderTaskListFilterService();
        $table_data = $this->repo->getTable();

        $arStatuses = [];
        foreach (OrderTask::STATUS_DATA as $status => $ar) {
            if(in_array($status, $table_data['filter']['status']))
                $arStatuses[$status] = [
                    'name' => OrderTask::STATUS_LANG[$status],
                    'color' => OrderTask::STATUS_COLOR[$status]['button'],
                ];
        }

        return view('pub::order_task.index', [
            'users' => [
                'creator' => User::whereIn('id', $table_data['filter']['creator'])->get(),
            ],
            'breadcrumbs' => $this->breadcrumb,
            'statuses' => $arStatuses,
            'filter' => $filter_service->getFilter(),
            'filter_count' => $filter_service->getFilterCount(),
            'user' => []
        ]);
    }


    public function create_from_evaluation(string $block_id)
    {
        $evaluation = EvaluationRepository::getByBlockID($block_id);
        if(!empty($evaluation) && !empty($evaluation->order_task)) {
            return Redirect::route('order_task.detail', $evaluation->order_task);
        }

        if(empty($evaluation) || !$evaluation->canTransform()) abort(404);
        $service = new EvaluationService();
        if($task = $service->transform($evaluation)) {


            return Redirect::route('order_task.detail', $task);
        } else {
            abort(404);
        }
    }
    public function step1_create_fast(int $contract_id, string $block_id)
    {
         return $this->step1_create($contract_id, 'nullable', $block_id);
    }

    public function step1_create(int $contract_id, string $contract_sub_id, string $block_id)
    {
        $order_task = OrderTask::select('id')->where(['block_id' => $block_id])->first();
        if(!empty($order_task))
        {
            return \Redirect::route('order_task.detail', $order_task);
            if($order_task->canCreate2()) {
                return \Redirect::route('order_task.create_step2', $order_task);
            } else {
            }
        }
        $this->breadcrumb_add('', 'Создание тех.задания', 1);



        return view("pub::order_task.create", [
           'contract_id' => $contract_id,
           'contract_sub_id' => $contract_sub_id,
           'block_id'   => $block_id,
           'lab_objects' => LabObject::where('is_last', 1)->get(),
           'breadcrumbs' => $this->breadcrumb
        ]);
    }




    // 1 шаг, сохранение после создания
    public function step1_save(CreateRequest $request)
    {
        // если ТЗ уже создано, мы не можем быть тут
        if(OrderTask::select('id')->where(['block_id' => $request->input('block_id')])->first())
            return \Redirect::route('order_task.create_step2', $order_task);

        // создадим договор
        $contract = Contract::find($request->contract_id);
        if(empty($contract)) {
            $contract = new Contract(['id' => $request->contract_id]);
            $contract->save();
        }

        // создадим субдоговор
        $sub_contract = SubContract::where('slug', $request->contract_sub_id)->where('contract_id', $contract->id)->first();

        if(empty($sub_contract)) {
            $sub_contract = new SubContract(['slug' => $request->contract_sub_id]);
            $sub_contract->contract()->associate($contract)->save();
        }

        // создадим задание и привяжем номер документа
        $number = DocumentNumberService::generate(OrderTask::class);
        $task = new OrderTask([
            'block_id' => $request->block_id
        ]);
        $task->creator()->associate(auth()->user());
        $task->sub_contract()->associate($sub_contract)->save();
        $this->costTotalRecalc($task);

        $task->number()->save($number);

        // привяжем объекты
        $o_sort = 0;
        foreach($request->object as $o_uid => $object_data) {
            $object = OrderTaskObject::make([
                'name' => $object_data['name'],
                'sort' => $o_sort
            ]);
            $task->objects()->save($object);

            // добавим услуги
            $service = Service::find(1);
            $object->services()->attach([$service->id => ['count' => 1, 'cost' => $service->cost, 'cost_total' => $service->cost]]);
            $service = Service::find(2);
            $object->services()->attach([$service->id => ['count' => 1, 'cost' => $service->cost, 'cost_total' => $service->cost]]);


            $object->lab_object()->associate(LabObject::find($object_data['type']))->save();




            $a_sort = 0;
            foreach($request->address[$o_uid] as $a_uid => $address_data) {
                $address = OrderTaskAddress::make([
                    'address' => $address_data['address'],
                    'expanses' => $address_data['expanses'],
                    'transport' => $address_data['transport'],
                    'sort' =>  $a_sort
                ]);
                $object->addresses()->save($address);

                $p_sort = 0;
                foreach($request->point[$a_uid] as $p_uid => $point_data) {
                    $point = OrderTaskPoint::make([
                        'name' => $point_data['name'],
                        'number' => $point_data['number'],
                        'sort' => $p_sort
                    ]);
                    $p_sort += 100;
                    $address->points()->save($point);
                }

                $a_sort += 100;
            }
        }

        event(new OrderTaskUpdateEvent($task, 'create_step1'));

        return \Redirect::route('order_task.create_step2', $task->id);
    }




    // 2 шаг, форма создания
    public function step2_create(OrderTask $order_task)
    {
        if(!$order_task->canEdit()) abort(404);
        if(!$order_task->canCreate2()) return redirect()->route('order_task.edit_step1', $order_task);

        $this->breadcrumb_add(route('order_task.detail', $order_task), 'ТЗ №' . $order_task->id);
        $this->breadcrumb_add('', 'Создание объектов');

        $order_task->load('objects.addresses.points');

        $measure_collect = [];
        $costs = [];
        foreach($order_task->objects as $object)
        {
            if(!empty($object->lab_object)) {
                $measures = LabMeasureRepository::getForLabObjectWithParents($object->lab_object);
                $measure_collect[$object->id] = LabMeasure::getTree($measures->pluck('id')->toArray() ?? []);
//                dd($measure_collect[$object->id]);
            } else {
                $measure_collect[$object->id] = LabMeasure::getTree([0]);
            }


            //dd($measure_collect);

            foreach($measure_collect[$object->id] as $measure) {
                $costs[$measure->id] = $measure->cost;// + rand(1, 1000);
            }
        }

        $measures = LabMeasureRepository::getLast();
        $costs = $measures->pluck('cost', 'id');

        $services = Service::all();
        return view("pub.order_task.create_2", [
           'lab_objects' => LabObject::where('is_last', 1)->get(),
           'order_task' => $order_task,
           'costs' => $costs,
           'services' => $services,
           'measure_collect' => $measure_collect,
           'breadcrumbs' => $this->breadcrumb
        ]);
    }


    // 2 шаг, сохранение после создания
    public function step2_save(OrderTask $order_task, CreateStep2Request $request)
    {
        if(!$order_task->canEdit()) abort(404);

        if(!empty($request->input('point_new'))) {
            foreach($request->input('point_new') as $uid => $ar)
            {
                $address = OrderTaskAddress::findOrFail($ar['address_id']);
                $sort = $address->points->count() * 100;
                $point = (new OrderTaskPoint())->fill(['name' => $ar['name'], 'number' => $ar['number'] ?? null,  'sort' => $sort]);
                $address->points()->save($point);
                $arPointReplace[$uid] = $point->id;
            }
        }
        foreach($request->point as $point_id => $measures)
        {
            if(!empty($arPointReplace[$point_id]))
                $point_id = $arPointReplace[$point_id];


            $point = OrderTaskPoint::findOrFail($point_id);

            foreach($measures as $measure)
            {
                $lab_measure = LabMeasure::findOrFail($measure['measure_id']);
                $otm = new OrderTaskMeasure();
                $otm->fill($measure);
                $otm->cost_total = $otm->cost * $otm->count;
                $otm->bonus = $lab_measure->bonus;
                $otm->bonus_total = $otm->bonus * $otm->count;

                $otm->point()->associate($point);
                $otm->measure()->associate($lab_measure);
                $otm->save();
            }
        }

        if(!empty($request->service)) {
            foreach($request->service as $object_id => $services) {
                $object = OrderTaskObject::findOrFail($object_id);
                foreach($services as $service_id => $service) {
                    if(!empty($service['link_object_id'])) {
                        $object->services()->attach([
                            $service['service_id'] => [
                                'comment' => $service['comment'],
                                'link_object_id' => $service['link_object_id'] > 0 ? $service['link_object_id'] : null
                            ],
                        ]);
                    } else {
                        $service_db = Service::findOrFail($service['service_id']);
                        $object->services()->attach([
                            $service['service_id'] => [
                                'count' => $service['count'],
                                'cost' => $service['cost'],
                                'cost_total' => $service['count'] * $service['cost'],
                                'bonus' => $service_db->bonus,
                                'bonus_total' => $service['count'] * $service_db->bonus,
                                'comment' => $service['comment']
                            ],
                        ]);
                    }
                }
            }
        }


        $this->service->updateStatus($order_task, OrderTask::STATUS_CREATED);

        event(new OrderTaskUpdateEvent($order_task, 'create_step2'));

        return \Redirect::route('order_task.detail', $order_task);
    }


    /*
     *  РЕДАКТИРОВАНИЕ
     */

    // 1 шаг, форма редактирования
    public function step1_edit(OrderTask $order_task)
    {
        if(!$order_task->canEdit()) abort(404);

        if(!empty($order_task->order)) $this->breadcrumb_add(route('order.detail', $order_task->order), 'Заявка ' . $order_task->order->id );
        $this->breadcrumb_add(route('order_task.detail', $order_task), 'ТЗ №' . $order_task->id);
        $this->breadcrumb_add('', 'Редактирование объектов', 1);

        $order_task = OrderTaskService::decorate($order_task);
        return view("pub.order_task.edit", [
            'lab_objects' => LabObjectRepository::getActive(),
            'order_task' => $order_task,
            'breadcrumbs' => $this->breadcrumb
        ]);
    }



    // 1 шаг, сохранение после редактирования
    public function step1_update(OrderTask $order_task, EditRequest $request)
    {
        if(!$order_task->canEdit()) abort(404);

        $order_task->update([
            'contacts' => $request->contacts
        ]);
        // привяжем объекты
        $o_sort = 0;
        $arObjHaveID = $order_task->objects->pluck('id')->flip();
        foreach($request->object as $o_uid => $object_data) {
            $arObjHaveID->forget($o_uid);
            $object = OrderTaskObject::find($o_uid);

            // новый объект
            if(empty($object))
            {
                $object = OrderTaskObject::make([
                    'name' => $object_data['name'],
                    'sort' => $o_sort
                ]);

                $order_task->objects()->save($object);
                $service = Service::find(1);
                $object->services()->attach([$service->id => ['count' => 1, 'cost' => $service->cost, 'cost_total' => $service->cost]]);
                $service = Service::find(2);
                $object->services()->attach([$service->id => ['count' => 1, 'cost' => $service->cost, 'cost_total' => $service->cost]]);


                // старый объект
            } else {
                $object->update([
                    'name' => $object_data['name'],
                    'sort' => $o_sort
                ]);
            }
            $object->lab_object()->associate(LabObject::find($object_data['type']))->save();

            $a_sort = 0;
            $arAddressHaveID = $object->addresses->pluck('id')->flip();
            foreach($request->address[$o_uid] as $a_uid => $address_data) {
                $arAddressHaveID->forget($a_uid);
                $address = OrderTaskAddress::find($a_uid);
                if(empty($address))
                {
                    $address = OrderTaskAddress::make([
                        'address' => $address_data['address'],
                        'expanses' => $address_data['expanses'] ?? 0,
                        'transport' => $address_data['transport'] ?? 0,
                        'sort' => $o_sort
                    ]);
                    $object->addresses()->save($address);
                } else {
                    $address->update([
                        'address' => $address_data['address'],
                        'expanses' => $address_data['expanses'] ?? 0,
                        'transport' => $address_data['transport'] ?? 0,
                        'sort' =>  $a_sort
                    ]);
                }


                $p_sort = 0;
                $arPointHaveID = $address->points->pluck('id')->flip();
                foreach($request->point[$a_uid] as $p_uid => $point_data) {
                    $arPointHaveID->forget($p_uid);
                    $point = OrderTaskPoint::find($p_uid);

                    if(empty($point)) {
                        $point = OrderTaskPoint::make([
                            'name' => $point_data['name'],
                            'number' => $point_data['number'],
                            'sort' => $p_sort
                        ]);
                    } else {
                        $point->update([
                            'name' => $point_data['name'],
                            'number' => $point_data['number'],
                            'sort' => $p_sort
                        ]);
                    }
                    $address->points()->save($point);
                }
            }
        }


        if($arObjHaveID->count() > 0)
        {
            $arObjHaveID->each(function($value, $key) {
                OrderTaskObject::find($key)->delete();
            });
        }
        if($arAddressHaveID->count() > 0)
        {
            $arAddressHaveID->each(function($value, $key) {
                OrderTaskAddress::find($key)->delete();
            });
        }

        if($arPointHaveID->count() > 0)
        {
            $arPointHaveID->each(function($value, $key) {
                OrderTaskPoint::find($key)->delete();
            });
        }

//        if($order_task->status == OrderTask::STATUS_STARTED)
//            $order_task->update(['status' => OrderTask::STATUS_CREATED]);


        event(new OrderTaskUpdateEvent($order_task, 'update_step1'));
        return \Redirect::route('order_task.edit_step2', $order_task);
    }





    // 2 шаг, форма редактирования
    public function step2_edit(OrderTask $order_task)
    {
        if(!$order_task->canEdit()) abort(404);
        if(!$order_task->canEdit2()) return redirect()->route('order_task.create_step2', $order_task);

        if(empty($order_task))
            return \Redirect::route('order_task.create_step1', $order_task);

        if($order_task->status == OrderTask::STATUS_STARTED)
            return \Redirect::route('order_task.create_step2', $order_task);

        $this->breadcrumb_add(route('order_task.detail', $order_task), 'ТЗ №' . $order_task->id);
        $this->breadcrumb_add(route('order_task.edit_step1', $order_task), 'Редактирование объектов');
        $this->breadcrumb_add('', 'Редактирование измерений', 1);

        $order_task->load('objects.addresses.points');

        $measure_collect = [];
        $costs = [];
        foreach($order_task->objects as $object)
        {
            if(!empty($object->lab_object)) {
                $measures = LabMeasureRepository::getForLabObjectWithParents($object->lab_object);
                $measure_collect[$object->id] = LabMeasure::getTree($measures->pluck('id')->toArray() ?? []);

                foreach($measure_collect[$object->id] as $measure) {
                    $costs[$measure->id] = $measure->cost;// + rand(1, 1000);
                }
            } else {

            }
        }
        $order_task = OrderTaskService::decorate($order_task);
        $services = Service::all();


        return view("pub.order_task.edit_2", [
            'lab_objects' => LabObject::where('is_last', 1)->get(),
            'order_task' => $order_task,
            'costs' => $costs,
            'services' => $services,
            'measure_collect' => $measure_collect,
            'breadcrumbs' => $this->breadcrumb
        ]);
    }


    // 2 шаг, сохранение после редактирования
    public function step2_update(OrderTask $order_task, CreateStep2Request $request)
    {
        if(!$order_task->canEdit()) abort(404);

        // новые точки
        if(!empty($request->input('point_new'))) {
            foreach($request->input('point_new') as $uid => $ar)
            {
                $address = OrderTaskAddress::findOrFail($ar['address_id']);
                $sort = $address->points->count() * 100;
                $point = (new OrderTaskPoint())->fill(['name' => $ar['name'], 'number' => $ar['number'] ?? null, 'sort' => $sort]);
                $address->points()->save($point);
                $arPointReplace[$uid] = $point->id;
            }
        }


        foreach($request->point as $point_id => $measures)
        {
            if(!empty($arPointReplace[$point_id]))
                $point_id = $arPointReplace[$point_id];


            $point = OrderTaskPoint::findOrFail($point_id);
            $arMeasureHaveID = $point->measures()->pluck('id')->flip();
            foreach($measures as $measure_id => $ar_measure)
            {
                $arMeasureHaveID->forget($measure_id);
                $lab_measure = LabMeasure::findOrFail($ar_measure['measure_id']);
                $otm = OrderTaskMeasure::find($measure_id);
                if(empty($otm)) {
                    $otm = OrderTaskMeasure::make([
                        'cost' => $ar_measure['cost'],
                        'count' => $ar_measure['count'],
                        'cost_total' => $ar_measure['cost'] * $ar_measure['count'],
                        'bonus' => $lab_measure->bonus,
                        'bonus_total' => $lab_measure->bonus * $ar_measure['count'],
                        'comment' => $ar_measure['comment']
                    ]);
                    $otm->point()->associate($point);
                    $otm->measure()->associate($lab_measure);
                    $otm->save();
                } else {
                    $otm->count = $ar_measure['count'];
                    $otm->cost_total = $otm->cost * $otm->count;
                    $otm->bonus = $lab_measure->bonus;
                    $otm->bonus_total = $otm->bonus * $otm->count;
                    $otm->measure()->associate($lab_measure);
                    $otm->update($ar_measure);
                }
            }
             // удалим лишние
              if($arMeasureHaveID->count() > 0)
                  $arMeasureHaveID->each(fn($value, $key) => OrderTaskMeasure::find($key)->delete());

        }

        foreach($order_task->objects as $object)
        {
            $object->services()->sync([]);
        }
        if(!empty($request->service)) {
            foreach($request->service as $object_id => $services) {
                foreach($services as $service_id => $service) {
                    if(!empty($service['link_object_id'])) {
                        $object->services()->attach([
                            $service['service_id'] => [
                                'comment' => $service['comment'],
                                'link_object_id' => $service['link_object_id'] > 0 ? $service['link_object_id'] : null
                            ],
                        ]);
                    } else {
                        $service_db = Service::findOrFail($service['service_id']);
                        $object->services()->attach([
                            $service['service_id'] => [
                                'count' => $service['count'],
                                'cost' => $service['cost'],
                                'cost_total' => $service['count'] * $service['cost'],
                                'bonus' => $service_db->bonus,
                                'bonus_total' => $service['count'] * $service_db->bonus,
                                'comment' => $service['comment']
                            ],
                        ]);
                    }
                }
            }
        }




        event(new OrderTaskUpdateEvent($order_task, 'update_step2'));
        if($order_task->status == OrderTask::STATUS_STARTED) {
            $this->service->updateStatus($order_task, OrderTask::STATUS_CREATED);
        }


        return \Redirect::route('order_task.detail', $order_task);
    }


    public function copy_form(Order $order, Request $request)
    {
        if(!$order->canView()) abort(404);
        if(empty($order->order_task)) abort(404);


        $template = View::make('pub.order_task.sidebars.copy', ['title' => 'Копирование ТЗ', 'order' => $order]);
        return $template;
    }


    /**
     * Форма привязки ТЗ к заявке
     *
     * @param OrderTask $order_task
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     *
     */

    public function attach_form(OrderTask $order_task, Request $request)
    {
        if(empty($order_task)) abort(404);

        $template = View::make('pub.order_task.sidebars.attach', ['title' => 'Привязка заказа', 'order_task' => $order_task]);
        return $template;
    }

    /**
     * Форма согласования ТЗ
     *
     * @param OrderTask $order_task
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     *
     */

    public function agreement_form(OrderTask $order_task, Request $request)
    {
        if(empty($order_task) || !_can('order_task_agree') || !$order_task->canAgree()) abort(404);
        $users = UserGroup::find(UserGroup::GROUP_AGREEMENT)->users;

        $order_task = OrderTaskService::decorate($order_task);
        $template = View::make('pub.order_task.sidebars.agreement', ['title' => 'Отправка на согласование', 'users' => $users, 'order_task' => $order_task]);
        return $template;
    }

    /**
     * Форма просмотра согласования ТЗ
     *
     * @param OrderTask $order_task
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     *
     */

    public function agreement_view(OrderTask $order_task, Request $request)
    {
        if(empty($order_task) || !_can('order_task_agree')) abort(404);
        $agreement = $order_task->agreement()->get();

        $template = View::make('pub.order_task.sidebars.agreement_view', [
            'title' => 'Просмотр согласования',
            'order_task' => $order_task,
            'agreement' => $agreement,
            'files' => $agreement->getDocuments()
        ]);
        return $template;
    }

    /**
     * Информация о заявке в сайдбаре
     *
     * @param OrderTask $order_task
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     *
     */

    public function sidebar_view(OrderTask $order_task, Request $request)
    {
        if(empty($order_task)) abort(404);

        $service = new OrderService();

        $task_tree = $service->getTree($order_task->order);

        $template = View::make(view: 'pub.order_task.sidebars.detail', data: ['title' => 'Техническое задание', 'order_task' => $order_task, 'task_tree' => $task_tree]);
        return $template;
    }

    public function detail(Request $request, OrderTask $order_task = null)
    {
        if(empty($order_task) || !$order_task->hasAccess())
            abort(404);

        $this->breadcrumb_add('', 'ТЗ №' . $order_task->id);

        $arViewModes = collect(['finance' => ['name' => 'Финансы', 'icon' => 'fa-ruble-sign']]);
        if($order_task->hasWorkMode()) $arViewModes['working'] = ['name' => 'Работа', 'icon' => 'fa-briefcase'];

        $view_mode = $request->input('mode');
        if(empty($view_mode) || empty($arViewModes[$view_mode])) {
            $view_mode = $arViewModes->keys()->last();
        }

        return view('pub.order_task.detail', [
            'reminder' => $order_task->reminder(),
            'breadcrumbs' => $this->breadcrumb,
            'order_task' => $order_task,
            'view_modes' => $arViewModes,
            'view_mode' => $view_mode,
        ]);
    }


    public function costTotalRecalc(OrderTask $orderTask)
    {
        $cost_total = 0;
        $bonus_total = 0;
        foreach($orderTask->objects as $object) {
            foreach($object->addresses as $address) {

                $cost_total += $address->expanses;
                $cost_total += $address->transport;

                foreach($address->points as $point) {
                    foreach($point->measures as $measure) {
                        $cost_total += $measure['cost_total'];
                        $bonus_total += $measure['bonus_total'];
                    }
                }
            }

            foreach($object->services as $service)
            {
                $cost_total += $service->pivot['cost_total'];
                $bonus_total += $service->pivot['bonus_total'];
            }
        }

        $cost_total -= $orderTask->discount;

        $orderTask->cost_total = $cost_total;
        $orderTask->bonus_total = $bonus_total;
        $orderTask->save();
    }

    /**
     * Вывод поп-апа с назначением пробоотборщиков
     *
     * @param OrderTask $orderTask ТЗ
     * @return \Illuminate\Contracts\View\View
     */
    public function box_set_samplers(Request $request, OrderTask $orderTask)
    {
        if (!$orderTask->hasAccess() || !$orderTask->canSetSamplers())
            abort(404);

        $objects_source = [];
        if(_can('direction_A') && $request->input('type') == 'A')
            $objects_source['A'] = [
                'objects' => $orderTask->objectsA,
                'name' => 'Направление А',
                'users' => $orderTask->samplersA()->get()->keyBy('user_id')->keys() ?? [],
            ];

        if(_can('direction_B') && $request->input('type') == 'B')
            $objects_source['B'] = [
                'objects' => $orderTask->objectsB,
                'name' => 'Направление Б',
                'users' => $orderTask->samplersB()->get()->keyBy('user_id')->keys() ?? [],
            ];

        $template = View::make('pub.order_task.boxes.set_samplers', [
            'title' => 'Назначение пробоотборщиков',
            'type' => $request->input('type'),
            'orderTask' => $orderTask,
            'objects_source' => $objects_source,
        ]);

        return $template;
    }

    public function box_summary(OrderTask $orderTask)
    {
        $template = View::make('pub.order_task.box.summary', [
            'title' => 'Сводная таблица для ТЗ <mark>#' . $orderTask->id . '</mark>',
            'task' => $orderTask,
        ]);
        return $template;
    }

    public function box_visits(OrderTask $orderTask)
    {
        $visits = $orderTask->objects->flatMap->addresses->flatMap->visits;

        $template = View::make('pub.order_task.box.visits', [
            'title' => 'Акты ТЗ <mark>#' . $orderTask->id . '</mark>',
            'task' => $orderTask,
            'visits' => $visits,
        ]);
        return $template;
    }


    /**
     * Вывод поп-апа с назначением пробоотборщиков
     *
     * @param OrderTask $orderTask ТЗ
     * @return \Illuminate\Contracts\View\View
     */
    public function sidebar_set_samplers(Request $request, string $target_type, int $target_id)
    {
        switch($target_type) {
            case 'order_task':
                $orderTask = OrderTask::find($target_id);
                if($request->input('type') == 'A') {
                    $outType = 'A';
                    $selector = "globalA";
                } else {
                    $outType = 'B';
                    $selector = "globalB";
                }

                break;
            case 'object':
                $object = OrderTaskObject::findOrFail($target_id);
                $orderTask = $object->task;
                $outType = $object->isA() ? 'A' : 'B';
                $selector = "object_" . $object->id;
                break;
            case 'address':
                $address = OrderTaskAddress::findOrFail($target_id);
                $orderTask = $address->object->task;
                $outType = $address->object->isA() ? 'A' : 'B';
                $selector = "address_" . $address->id;
                break;
            case 'point':
                $point = OrderTaskPoint::findOrFail($target_id);
                $orderTask = $point->address->object->task;
                $outType = $point->address->object->isA() ? 'A' : 'B';
                $selector = "point_" . $point->id;
                break;
            default: abort(404);
        }

        $users = $outType == 'A' ? UserRepository::getSamplersA() : UserRepository::getSamplersB();

        if (!$orderTask->hasAccess() || !$orderTask->canSetSamplers())
            abort(404);
        $selected = $request->input('selected') ? explode(",", $request->input('selected')) : [];
        $template = View::make('pub.order_task.sidebars.set_samplers', [
            'title' => 'Выбор пробоотборщиков',
            'users' => $users,
            'selector' => $selector,
            'selected' => $selected,
        ]);
        return $template;
    }
}
