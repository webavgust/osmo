<?php

namespace App\Modules\Pub\Calendar\Controllers\Api;

use App\Modules\Pub\Calendar\Models\Calendar;
use App\Modules\Pub\Calendar\Repositories\CalendarRepository;
use App\Modules\Pub\Calendar\Requests\AddEventRequest;
use App\Modules\Pub\Calendar\Requests\EditEventRequest;
use App\Modules\Pub\Calendar\Requests\SetEventRequest;
use Carbon\Carbon;
use Illuminate\Http\Response;

class ApiCalendarController
{
    private $repo;

    public function __construct()
    {
        $this->repo = new CalendarRepository();
    }

    /**
     * Добавление записи в календарь
     *
     * @param AddEventRequest $request Request
     * @return \Illuminate\Http\JsonResponse
     *
     * TODO: [REF] перенести логику в сервис
     */
    public function add(AddEventRequest $request)
    {
        switch ($request->validated('mode')) {
            case 'day':
                $from = Carbon::createFromTimestamp(strtotime($request->validated('date')))->startOfDay();
                $to = Carbon::createFromTimestamp(strtotime($request->validated('date')))->endOfDay();
                $all_day = true;
                break;
            case 'dates':
                list($from, $to) = explode(" - ", $request->validated('dates'));
                $from = Carbon::createFromTimestamp(strtotime($from))->startOfDay();
                $to = Carbon::createFromTimestamp(strtotime($to))->endOfDay();
                $all_day = false;
                break;
            case 'time':
                $from = Carbon::createFromTimestamp(strtotime($request->validated('datetime.date1') . ' ' . $request->validated('datetime.time1')));
                $to = Carbon::createFromTimestamp(strtotime($request->validated('datetime.date2') . ' ' . $request->validated('datetime.time2')));
                $all_day = false;
                break;
        }

        $data = collect([
            'start' => $from,
            'end' => $to,
            'all_day' => $all_day,
            'mode' => $request->validated('mode'),
            'title' => $request->validated('caption'),
            'text' => $request->validated('text'),
            'color' => $request->validated('color')
        ]);
        $this->repo->add($data);

        return \Response::json(['status' => 'success']);
    }

    /**
     * Редактирование записи
     *
     * @param EditEventRequest $request Request
     * @param Calendar $event Событие
     * @return \Illuminate\Http\JsonResponse
     *
     * TODO: [REF] Перенести логику в сервис
     */
    public function edit(EditEventRequest $request, Calendar $event)
    {

        if (!$event->canEdit()) abort(404);

        switch ($request->validated('mode')) {
            case 'day':
                $from = Carbon::createFromTimestamp(strtotime($request->validated('date')))->startOfDay();
                $to = Carbon::createFromTimestamp(strtotime($request->validated('date')))->endOfDay();
                $all_day = true;
                break;
            case 'dates':
                list($from, $to) = explode(" - ", $request->validated('dates'));
                $from = Carbon::createFromTimestamp(strtotime($from))->startOfDay();
                $to = Carbon::createFromTimestamp(strtotime($to))->endOfDay();
                $all_day = true;
                break;
            case 'time':
                $from = Carbon::createFromTimestamp(strtotime($request->validated('datetime.date1') . ' ' . $request->validated('datetime.time1')));
                $to = Carbon::createFromTimestamp(strtotime($request->validated('datetime.date2') . ' ' . $request->validated('datetime.time2')));
                $all_day = false;
                break;
            case 'future':
                $from = null;
                $to = null;
                $all_day = false;
                break;
        }

        $data = collect([
            'start' => $from,
            'end' => $to,
            'all_day' => $all_day,
            'mode' => $request->validated('mode'),
            'title' => $request->validated('caption'),
            'text' => $request->validated('text'),
            'color' => $request->validated('color')
        ]);

        $this->repo->edit($event, $data);

        return \Response::json(['status' => 'success']);
    }

    /**
     *  Обновление записи
     *
     * @param SetEventRequest $request Request
     * @param Calendar $event Событие
     * @return \Illuminate\Http\JsonResponse
     *
     * TODO: [REF] Перенести логику в сервис
     */
    public function set(SetEventRequest $request, Calendar $event)
    {
        if (!$event->canEdit()) abort(404);

        if ((boolean)$request->validated('data.allDay')) {
            $all_day = true;
            $duration = 0;
            if (!empty($request->validated('data.set_date'))) {
                $mode = 'day';
                $from = Carbon::createFromTimestamp(strtotime($request->validated('data.set_date')))->startOfDay();
                $to = Carbon::createFromTimestamp(strtotime($request->validated('data.set_date')))->endOfDay();
            } else {
                $from = Carbon::createFromTimestamp(strtotime($request->validated('data.start')))->subHours(3)->startOfDay();
                $to = Carbon::createFromTimestamp(strtotime($request->validated('data.end')))->subHours(3)->startOfDay()->subSecond();
                $mode = $from->diffInDays($to) == 1 ? 'day' : 'dates';
            }
        } else {
            $all_day = false;
            $mode = 'time';
            // SET
            if (!empty($request->validated('data.set_date'))) {
                $from = Carbon::createFromTimestamp(strtotime($request->validated('data.set_date')));
                $duration = $event->duration ?? 120;
                $to = $from->copy();
                $to->addMinutes($duration);
            } else {
                // TODO: вручную вычитаем часовой пояс
                $from = Carbon::createFromTimestamp(strtotime($request->validated('data.start')))->subHours(3);
                $to = Carbon::createFromTimestamp(strtotime($request->validated('data.end')))->subHours(3);
                $duration = $from->diffInMinutes($to);
            }
        }


        $data = collect([
            'start' => $from,
            'end' => $to,
            'all_day' => $all_day,
            'duration' => $duration,
            'mode' => $mode
        ]);

        $this->repo->edit($event, $data);

        return \Response::json(['status' => 'success']);
    }
}
