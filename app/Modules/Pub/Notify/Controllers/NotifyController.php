<?php

namespace App\Modules\Pub\Notify\Controllers;

use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Dashboard\Services\DashboardService;
use App\Modules\Pub\Notify\Models\Notify;
use App\Modules\Pub\Notify\Repositories\NotifyRepository;
use App\Modules\Pub\Notify\Services\NotifyService;
use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class NotifyController
{
    use HasBreadcrumb;
    private $repo;
    private $dates;

    public function __construct()
    {
        $this->repo = new NotifyRepository();
        $dashboard_service = new DashboardService();
        if(empty(session('dashboard_date')))
            $dashboard_service->initDate();

        $dates = session('dashboard_date');
        $this->dates = [
            'start' => Carbon::createFromTimestamp(strtotime($dates['start'])),
            'end' => Carbon::createFromTimestamp(strtotime($dates['end'])),
        ];
        $this->breadcrumb_add(null, 'Уведомления');
    }

    public function toast(Notify $notify = null)
    {
        if(empty($notify) || !$notify->isOwner()) abort(404);

        return View::make('components.notify.toastr', compact('notify'));
    }

    public function header()
    {
        $notifies = $this->repo->getUnreadedWithUpdate();
        return View::make('components.layout.notifies.shell', compact('notifies'));
    }

    public function delete(Notify $notify = null)
    {
        if(empty($notify) || !$notify->isOwner()) abort(404);
        $notify->delete();

        return \Response::json(['result' => 'success']);
    }
    public function clear()
    {
        $this->repo->getForUser()->delete();
        return \Response::json(['result' => 'success']);
    }

    public function list()
    {
        $this->breadcrumb_add(null, 'Список');
        $actual = $this->repo->getActual();
        $trashed = $this->repo->getTrashed($this->dates);


        return view('pub.notify.list', [
            'dates' => $this->dates,
            'actual' => $actual,
            'trashed' => $trashed,
            'breadcrumbs' => $this->breadcrumb
        ]);

    }
}
