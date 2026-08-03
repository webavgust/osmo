<?php

namespace App\Modules\Pub\Calendar\Repositories;

use App\Modules\Pub\Calendar\Models\Calendar;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Collection;

class CalendarRepository
{
    /**
     * Создание события
     *
     * @param $data Данные
     * @return Calendar
     */
    public function add($data)
    {
        $user = auth()->user();
        if (!empty($data['user_id']))
            $user = User::findOrFail($data['user_id']);

        $event = new Calendar();
        $event->fill([
            'start' => $data['start'],
            'end' => $data['end'],
            'mode' => $data['mode'] ?? 'day',
            'all_day' => $data['all_day'] ?? 1,
            'title_icon' => $data['title_icon'] ?? '',
            'title' => $data['title'] ?? '',
            'text' => $data['text'] ?? '',
            'color' => $data['color'] ?? 'info',
            'editable' => ((empty($data['editable']) || $data['editable']) ? true : false),
            'target_sub' => $data['target_sub'] ?? '',
        ])->user()->associate($user)->save();

        return $event;
    }

    /**
     * Редактирование события
     *
     * @param Calendar $event Событие
     * @param Collection $data Данные
     * @return void
     */
    public function edit(Calendar $event, Collection $data)
    {
        $event->fill($data->all())->save();
    }

    /**
     * Получения событий для пользователя
     *
     * @param User|null $user Пользователь
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\LaravelIdea\Helper\App\Modules\Pub\Calendar\Models\_IH_Calendar_QB
     */
    public function forUser(User $user = null)
    {
        if (empty($user)) $user = auth()->user();

        return $user->calendar_events();
    }

    /**
     * Получения событий для пользователя
     *
     * @param User|null $user Пользователь
     * @return Calendar[]|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Modules\Pub\Calendar\Models\_IH_Calendar_C
     */
    public function getForUser(User $user = null)
    {
        return $this->forUser($user)->get();
    }

    /**
     * События без даты
     *
     * @param User|null $user Пользователь
     * @return Calendar[]|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Modules\Pub\Calendar\Models\_IH_Calendar_C
     */
    public function getUndefined(User $user = null)
    {
        if (empty($user)) $user = auth()->user();

        return $user->calendar_events()->whereNull('start')->get();
    }

    /**
     * Получить первое событие
     *
     * @param int $id ID События
     * @param User|null $user Пользователь
     * @return \App\Models\ModuleModel|Calendar
     */
    public function getOnceForUser(int $id, User $user = null)
    {
        if (empty($user)) $user = auth()->user();

        return Calendar::where('user_id', $user->id)->where('id', $id)->firstOrFail();
    }

    /**
     * Будущие события
     *
     * @param User|null $user Пользователь
     * @return Calendar[]|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Modules\Pub\Calendar\Models\_IH_Calendar_C
     */
    public function getFurther(User $user = null)
    {
        return $this->forUser($user)->where('end', '>', now()->format('Y-m-d'))->orderBy('start', 'ASC')->get();
    }

    /**
     * Будущие события, сгруппированные по месяцу
     *
     * @param User|null $user Пользователь
     * @return Collection
     */
    public function getFurtherGrouped(User $user = null)
    {
        $ret = collect();
        $rows = $this->getFurther();
        foreach ($rows as $row) {
            if (empty($ret[$row->start->format('m.Y')])) $ret[$row->start->format('m.Y')] = collect();
            $ret[$row->start->format('m.Y')]->push($row);
        }

        return $ret;
    }
}
