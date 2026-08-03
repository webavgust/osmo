<?php

namespace App\Modules\Pub\User\Controllers\Api;

use App\Jobs\AjaxProgress\UsersSync;
use App\Modules\Pub\AjaxProgress\Models\AjaxProgress;
use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\Order\Services\OrderService;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Request\SetSubUserRequest;
use App\Modules\Pub\User\Services\UserService;
use App\Modules\Pub\UserWorkCalendar\Models\UserWorkCalendar;
use App\Modules\Pub\WorkCalendar\Models\WorkCalendar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{

    public $service;

    public function __construct()
    {
        $this->service = new UserService();
    }

    /**
     * Таблица со списком
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list_table(Request $request)
    {
        $service = new UserService();
        $data = $service->tableDefault($request);

        return response()->json([
            "total" => $data['count_filter'],
            "totalNotFiltered" => $data['count'],
            "rows" => $data['rows']
        ]);
    }

    /**
     * Синхронизировать всех
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync_all()
    {
        $ajax = AjaxProgress::make()->fill([
            'finish_message' => 'Синхронизировано!',
            'target' => __METHOD__
        ]);
        $ajax->save();

        UsersSync::dispatch($ajax)->onQueue('database');

        return \Response::json(['uuid' => $ajax->uuid]);
    }

    /**
     * Установка руководителей
     *
     * @param SetSubUserRequest $request
     * @param User $user
     * @return \Illuminate\Contracts\View\View
     */
    public function parent_users_set(SetSubUserRequest $request, User $user)
    {
        $users = $request->input('user') ?? [];
        $user->parent_users()->sync($users);

        return \View::make('components.user.detail-sub-user-block', ['block' => 'parent', 'subUsers' => $user->parent_users]);
    }

    /**
     * Установка подчиненных
     *
     * @param SetSubUserRequest $request
     * @param User $user
     * @return \Illuminate\Contracts\View\View
     */
    public function sub_users_set(SetSubUserRequest $request, User $user)
    {
        $users = $request->input('user') ?? [];
        $user->sub_users()->sync($users);

        return \View::make('components.user.detail-sub-user-block', ['block' => 'sub', 'subUsers' => $user->sub_users]);
    }

    /**
     * Установка календаря
     *
     * @param Request $request
     * @param User $user
     * @param $year
     * @return array
     */
    public function work_calendar_set(Request $request, User $user, $year)
    {
        // сохраним настройки времени для пользователя
        $user_work_times = [];
        foreach ($request->time as $day_num => $ar) {
            $user_work_times[$day_num] = [
                'active' => $ar['active'] ?? 0
            ];
            if (!empty($ar['active'])) {
                $user_work_times[$day_num]['from'] = tools()->time_convert_back($ar['from']) ?? config('settings.working_time')[$day_num]['from'];
                $user_work_times[$day_num]['to'] = tools()->time_convert_back($ar['to']) ?? config('settings.working_time')[$day_num]['to'];
            }
        }
        $user->setting->set('user_work_times', $user_work_times);

        $user->work_calendar()->where('year', $year)->where('type', '!=', 'custom')->delete();
        $custom = $user->work_calendar()->where('year', $year)->where('type', 'custom')->get()->pluck('date')->toArray();

        foreach ($request->date as $date) {
            $carbon = Carbon::createFromFormat('Y-m-d', $date);
            if (in_array($date, $custom))
                $user->work_calendar()->where('date', $date)->delete();

            $row = new UserWorkCalendar();
            $row->fill([
                'type' => UserWorkCalendar::STATUS_HOLIDAY,
                'day' => $carbon->day,
                'month' => $carbon->month,
                'year' => $carbon->year,
                'date' => $date
            ])->user()->associate($user)->save();
        }

        return $request->all();
    }

    /**
     * Копирование календаря из рабочего календаря
     *
     * @param Request $request
     * @param User $user
     * @param $year
     * @return array
     */
    public function work_calendar_copy(Request $request, User $user, $year)
    {
        $user->work_calendar()->where('year', $year)->delete();

        $dates = WorkCalendar::select('date')->where('year', $year)->get()->pluck('date')->toArray();

        foreach ($dates as $date) {
            $carbon = Carbon::createFromFormat('Y-m-d', $date);
            $row = new UserWorkCalendar();
            $row->fill([
                'type' => UserWorkCalendar::STATUS_HOLIDAY,
                'day' => $carbon->day,
                'month' => $carbon->month,
                'year' => $carbon->year,
                'date' => $carbon->format('Y-m-d')
            ])->user()->associate($user)->save();
        }

        return $request->all();
    }

    /**
     * Рабочий календарь, установка времени
     *
     * @param Request $request
     * @param User $user
     * @param $date
     * @return string[]
     */
    public function work_calendar_set_time(Request $request, User $user, $date)
    {
        $carbon = Carbon::createFromFormat('Y-m-d', $date);
        $row = $user->work_calendar()->where('date', $date)->where('type', UserWorkCalendar::STATUS_CUSTOM)->first();

        if (empty($request->from) && empty($request->to)) {
            if (!empty($row)) {
                $row->delete();
                return ['status' => 'blank'];
            }
        } else {
            if (empty($row))
                $row = new UserWorkCalendar();


            $from = !empty($request->from) ? tools()->time_convert_back($request->from) : (config('settings.working_time')[$carbon->dayOfWeek]['from'] ?? config('settings.working_time')[1]['from']);
            $to = !empty($request->to) ? tools()->time_convert_back($request->to) : (config('settings.working_time')[$carbon->dayOfWeek]['to'] ?? config('settings.working_time')[1]['to']);


            $row->fill([
                'type' => UserWorkCalendar::STATUS_CUSTOM,
                'day' => $carbon->day,
                'month' => $carbon->month,
                'year' => $carbon->year,
                'date' => $carbon->format('Y-m-d'),
                'from' => $from,
                'to' => $to,
            ])->user()->associate($user)->save();
        }

        return ['status' => 'have'];
    }


    public function analytic_bind(User $user, Request $request)
    {
        if ($this->service->analytic_bind($user, $request)) {
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'error']);
        }
    }
}
