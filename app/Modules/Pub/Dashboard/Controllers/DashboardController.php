<?php

namespace App\Modules\Pub\Dashboard\Controllers;

use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Dashboard\Models\Dashboard;
use App\Http\Controllers\Controller;
use App\Modules\Pub\Dashboard\Services\DashboardService;
use App\Modules\Pub\Graph\Services\GraphService;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Repositories\OrderRepository;
use App\Modules\Pub\Order\Services\OrderService;
use App\Modules\Pub\Order\Services\OrderStatService;
use App\Modules\Pub\OrderTask\Services\OrderTaskStatService;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\OrderTask\Repositories\OrderTaskRepository;
use App\Modules\Pub\OrderTask\Services\OrderTaskService;
use App\Modules\Pub\OrderTaskObject\Models\OrderTaskObject;
use App\Modules\Pub\PlanVisit\Repositories\PlanVisitRepository;
use App\Modules\Pub\Report\Models\Report;
use App\Modules\Pub\Report\Repositories\ReportRepository;
use App\Modules\Pub\Salary\Repositories\SalaryRepository;
use App\Modules\Pub\Stat\Services\StatService;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\User\Services\UserService;
use App\Modules\Pub\Visit\Repository\VisitRepository;
use App\Services\Tools\Tools;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use stdClass;

class DashboardController extends Controller
{
    use HasBreadcrumb;
    private $service;
    private $dates;
    private $user_mode;
    private $sub_user;


    public function __construct()
    {
        $this->service = new DashboardService();

        // ДАТЫ
        if(empty(session('dashboard_date')))
            $this->service->initDate();

        $dates = session('dashboard_date');
        $this->dates = [
            'start' => Carbon::createFromTimestamp(strtotime($dates['start'])),
            'end' => Carbon::createFromTimestamp(strtotime($dates['end'])),
        ];


        // РЕЖИМ
        if(!_can('users_have_sub') || empty(session('dashboard_user_mode')))
            $this->service ->setMode('self');

        $this->user_mode = session('dashboard_user_mode');


        $this->sub_user = User::find(session('dashboard_sub_user', auth()->id()));

        if(auth()->check() && $this->sub_user->id != auth()->id() && (!_can('users_have_sub') || auth()->user()->sub_users->doesntContain($this->sub_user->id))) {
            $this->service->setSubUser(auth()->id());
            $this->sub_user = User::find(session('dashboard_sub_user', auth()->id()));
        }


        $this->breadcrumb_add('', 'Рабочий стол');
    }





    public function index($mode = null)
    {
        $service = new DashboardService();

        // выбор рабочего стола
        $available = $service->getAvailable()->keyBy('chr');

        if($available->isEmpty()) abort(404);
        if(!empty($mode) && $available->pluck('chr')->doesntContain($mode)) abort(404);

        if(empty($mode))
        {
            if(!session('dashboard_mode') || $available->pluck('chr')->doesntContain(session('dashboard_mode')))
            {
                $mode = $available->pluck('chr')->first();
            } else {
                $mode = session('dashboard_mode');
            }
        }
        session()->put('dashboard_mode', $mode);

        // даты выборки



        $data = new StdClass();
        switch($mode) {
            case 'ann':

                break;
        }


        return view($available[$mode]->template)->with([
            'dates' => $this->dates,
            'user_mode' => $this->user_mode,
            'sub_user' => $this->sub_user,
            'mode' => $mode,
            'breadcrumbs' => $this->breadcrumb,
            'available' => $available,
            'data' => $data
        ]);

    }



    public function sidebar_sub_user_select()
    {
        $users = Auth()->user()->sub_users;
        $user_selected = $this->sub_user;
        $template = View::make('pub.dashboard.sidebars.sub_user_select', ['title' => 'Кого показывать', 'sub_users' => $users, 'user_selected' => $user_selected]);
        return $template;
    }



    public function sidebar_user_select()
    {
        $repo = new UserRepository();
        $service = new UserService();
        $users = $repo->getSubUsers();
        $users_grouped = $service->groupByDepartment($users);
        $users_grouped_json = Tools::select2_optgroup($users_grouped, 'users', $this->user_mode['mode'] == 'select' ? $this->user_mode['users']->pluck('id')->toArray() ?? [] : []);

        $template = View::make('pub.dashboard.sidebars.user_select', ['title' => 'Кого показывать', 'users_grouped_json' => $users_grouped_json, 'user_mode' => $this->user_mode]);
        return $template;
    }




    public function set_dates(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        $this->service->setDate(
            Carbon::createFromTimestamp(strtotime($request->start)),
            Carbon::createFromTimestamp(strtotime($request->end))->endOfDay()
        );

        return Response::json(['status' => 'success']);
    }






}
