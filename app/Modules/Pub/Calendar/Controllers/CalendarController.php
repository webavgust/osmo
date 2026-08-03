<?php

namespace App\Modules\Pub\Calendar\Controllers;

use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Calendar\Models\Calendar;
use App\Modules\Pub\Calendar\Repositories\CalendarRepository;
use App\Modules\Pub\Calendar\Services\CalendarService;
use App\Modules\Pub\Order\Services\OrderService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;

class CalendarController extends Controller
{
    use HasBreadcrumb;

    private $repo;
    private $service;

    public function __construct()
    {
        $this->repo = new CalendarRepository();
        $this->service = new CalendarService();
        $this->breadcrumb_add('', 'Календарь');
    }

    /**
     * Страница календаря
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('pub.calendar.index', [
            'breadcrumbs' => $this->breadcrumb,
            'events' => $this->service->convertToJson($this->repo->getForUser()),
            'further' => $this->service->convertToJson($this->repo->getFurther()),
            'undefined' => $this->service->decorate($this->repo->getUndefined())
        ]);
    }

    /**
     * Саоздание события
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function sidebar_add()
    {
        $template = View::make('pub.calendar.sidebars.add', ['title' => 'Создание события']);

        return $template;
    }

    /**
     * Просмотр события
     *
     * @param $id ID события
     * @return \Illuminate\Contracts\View\View
     */
    public function sidebar_show($id = null)
    {
        if (empty($id)) abort(404);
        $event = $this->repo->getOnceForUser($id);

        $template = View::make('pub.calendar.sidebars.view', [
            'title' => 'Просмотр события',
            'event' => $event,
            'reminder' => $event->reminder(),
        ]);

        return $template;
    }

    /**
     * Редактирование события
     *
     * @param $id ID события
     * @return \Illuminate\Contracts\View\View
     */
    public function sidebar_edit($id = null)
    {
        if (empty($id)) abort(404);
        $event = $this->repo->getOnceForUser($id);
        $template = View::make('pub.calendar.sidebars.edit', ['title' => 'Редактирование события', 'event' => $event]);

        return $template;
    }

    /**
     * Скачивание файла с событиями
     *
     * @param $user Пользователь
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function schedule_pdf($user = null)
    {
        $further = $this->repo->getFurtherGrouped($user);
        if (empty($further)) abort(404);
        if (empty($user)) $user = auth()->user();

        $file = $this->service->generate_pdf($user, $further);
        $headers = array(
            'Content-Type: application/pdf',
        );

        return response()->download($file, 'schedule.pdf', $headers);
    }
}
