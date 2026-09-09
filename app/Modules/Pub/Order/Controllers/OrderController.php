<?php

namespace App\Modules\Pub\Order\Controllers;

use App\Jobs\Portal\Orders\SyncAll;
use App\Jobs\Portal\Orders\SyncOne;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Repositories\OrderRepository;
use App\Modules\Pub\Order\Services\OrderListFilterService;
use App\Modules\Pub\Order\Services\OrderService;
use App\Modules\Pub\Order\Services\OrderSyncPortalService;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class OrderController extends Controller
{
    use HasBreadcrumb;
    private $repo;
    private $service;
    public function __construct()
    {
        $this->repo = new OrderRepository();
        $this->service = new OrderService();
        $this->breadcrumb_add(route('order.index'), 'Заявки');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $filter_service = new OrderListFilterService();
        if(!empty($request->get('preset'))) {
            $preset = $filter_service->getPreset($request->get('preset'));
            if(!empty($preset)) $filter_service->setFilter($preset['filter']);
            return \Redirect::route('order.index');
        }
        $table_data = $this->repo->getTable();



        return view('pub::order.index', [
            'users' => [
                'author' => User::whereIn('id', $table_data['filter']['authors'])->get(),
                'manager' => User::whereIn('id', $table_data['filter']['managers'])->get(),
                'curator' => User::whereIn('id', $table_data['filter']['curators'])->get(),
            ],
            'presets' => $filter_service->getPresets(),
            'is_admin' => auth()->user()->isAdmin(),
            'is_curator' => auth()->user()->isSupervisor(),
            'filter' => $filter_service->getFilter(),
            'filter_count' => $filter_service->getFilterCount(),
            'breadcrumbs' => $this->breadcrumb
        ]);
    }

    public function detail(?Order $order)
    {
        if(empty($order) || !$order->canView()) abort(404);
        /*
         *  ORDER TREE
         */

        $order->task_tree = $this->service->getTree($order);

        $this->breadcrumb_add('', 'Заявка #' . $order->id, 1);
        return view('pub::order.detail', [
            'reminder' => $order->reminder(),
            'breadcrumbs' => $this->breadcrumb,
            'order' => $order,
            'period_data' => $order->daysData(),
            'curators' => UserGroup::find(UserGroup::GROUP_CURATOR)->users
        ]);

    }


    public function attach_form(Order $order, Request $request)
    {
        if(!$order->canView()) abort(404);
        if(!empty($order->order_task)) abort(404);

        $table_data = (new OrderRepository())->getTable()['filter']['id'] ?? [];
        //$orders = Order::where('id', '!=', $order->id)->doesntHave('order_task')->orderBy('id', 'desc')->pluck('id')->intersect($table_data);

        $template = \Illuminate\Support\Facades\View::make('pub.order.sidebars.attach', ['title' => 'Привязка ТЗ', 'order' => $order]);
        return $template;
    }
}
