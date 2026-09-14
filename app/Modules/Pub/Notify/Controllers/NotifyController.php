<?php

namespace App\Modules\Pub\Notify\Controllers;

use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Notify\Models\Notify;
use App\Modules\Pub\Notify\Repositories\NotifyRepository;
use App\Modules\Pub\Notify\Services\NotifyService;
use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        // период истории уведомлений (patch v30: перенесено из удалённого Pub/Dashboard, ключ сессии прежний)
        if(empty(session('dashboard_date')))
            $this->storeDates(
                Carbon::now()->day > 25 ? Carbon::now()->startOfMonth() : Carbon::now()->previous('month')->startOfMonth(),
                Carbon::now()->endOfDay()
            );

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

    /**
     * Сохранить период истории уведомлений (daterangepicker на странице списка)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function set_dates(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        $this->storeDates(
            Carbon::createFromTimestamp(strtotime($request->start)),
            Carbon::createFromTimestamp(strtotime($request->end))->endOfDay()
        );

        return \Response::json(['status' => 'success']);
    }

    /**
     * Период в сессии (ключ dashboard_date — как у прежнего рабочего стола)
     */
    private function storeDates(Carbon $start, Carbon $end): void
    {
        session()->put('dashboard_date', [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ]);
        session()->save();
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
