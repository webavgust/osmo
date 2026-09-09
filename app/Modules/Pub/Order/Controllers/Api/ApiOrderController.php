<?php

namespace App\Modules\Pub\Order\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AjaxProgress\OrdersSync;
use App\Jobs\AjaxProgress\UsersSync;
use App\Modules\Pub\AjaxProgress\Models\AjaxProgress;
use App\Modules\Pub\Menu\Models\Menu;
use App\Modules\Pub\Menu\Services\MenuService;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Requests\DaysCalcRequest;
use App\Modules\Pub\Order\Requests\ListFilterRequest;
use App\Modules\Pub\Order\Requests\SetPresetRequest;
use App\Modules\Pub\Order\Services\OrderListFilterService;
use App\Modules\Pub\Order\Services\OrderService;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use App\Services\AjaxToken\AjaxToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class ApiOrderController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new OrderService();
    }

    public function list_table(Request $request)
    {
        $service = new OrderService();
        $data = $service->tableDefault($request->only(['_token', 'sort', 'order', 'search', 'limit', 'offset']));


        return response()->json([
            "total" => $data['count_filter'],
            "totalNotFiltered" => $data['count'],
            "rows" => $data['rows']
        ]);
    }

    public function filter(ListFilterRequest $request)
    {
        $service = new OrderListFilterService($request->_token);
        $filter = [];
        foreach($request->validated() as $field => $value) {
            if(!empty($value)) $filter[$field] = $value;
        }
        $rules_count = $service->setFilter($filter);

        return response()->json([
           'result' => 'success',
           'rules_count' => $rules_count
        ]);
    }
    public function preset(SetPresetRequest $request)
    {
        $service = new OrderListFilterService($request->_token);
        $preset = $service->getPreset($request->validated('preset'));
        $service->setFilter($preset['filter']);

        return response()->json([
           'result' => 'success'
        ]);
    }

    public function filterRemove(ListFilterRequest $request)
    {
        $service = new OrderListFilterService($request->_token);
        $service->clearFilter();
        return response()->json([
           'result' => 'success'
        ]);
    }

    public function daysCalc(Order $order, DaysCalcRequest $request)
    {
        $service = new OrderService();
        if($request->type == 'days') {
            $data = $request->only(['type', 'days']);
            $data['order_date'] = $order->order_sent_to_techdep;
            $return = $service->daysCalc($data);
        } else {
            $data = $request->only(['type', 'date']);
            $data['order_date'] = $order->order_sent_to_techdep;
            $return = $service->daysCalc($data);
        }

        return response()->json([
            'result' => 'success',
            'data' => $return
        ]);
    }

    public function setCurator(Order $order, Request $request)
    {
        $request->validate(['curator' => 'int|required']);

        $curator = User::findOrFail($request->input('curator'));
        if(!$curator->hasGroup(UserGroup::GROUP_CURATOR)) abort(500);

        if($order->log(['curator_id' => [$order->curator_id, $curator->id]]))
        {
            $order->curator_id = $curator->id;
            $order->update();
        }

        $this->service->syncEvent($order);

        return response()->json([
            'result' => 'success',
            'user' => [
                'id' => $curator->id,
                'name' => $curator->fullName,
                'avatar' => $curator->avatar(),
                'a_wrap' => auth()->user()->can_do('users_view_profile')
            ]
        ]);
    }

    public function setStatus(Order $order, Request $request)
    {
        $request->validate(['is_finished' => 'required|bool', 'is_archived' => 'nullable|bool']);


        if($order->log([
            'is_finished' => [$order->is_finished, $request->input('is_finished')],
            'is_archived' => [$order->is_archived, $request->input('is_archived')]
        ]))
        {
            $order->is_finished = $request->input('is_finished');
            $order->is_archived = $request->input('is_archived');
            $order->update();
        }

        return View::make('components.order.detail.status_badge', ['order' => $order]);
    }

    public function sync_all()
    {
        $ajax = AjaxProgress::make()->fill([
            'finish_message' => 'Синхронизировано!',
            'target' => __METHOD__
        ]);
        $ajax->save();

        OrdersSync::dispatch($ajax)->onQueue('database');

        return \Response::json(['uuid' => $ajax->uuid]);
    }
}
