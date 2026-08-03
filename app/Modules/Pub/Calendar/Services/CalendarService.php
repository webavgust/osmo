<?php

namespace App\Modules\Pub\Calendar\Services;

use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;

class CalendarService
{
    /**
     * Конвертер в JSON
     *
     * @param $events
     * @return mixed[]
     */
    public static function convertToJson($events)
    {
        $result = collect();
        foreach ($events as $event) {
            $result->push([
                'id' => $event->id,
                'allDay' => $event->all_day,
                'title_icon' => $event->title_icon,
                'title' => $event->title,
                'reminders_count' => $event->reminders()->count(),
                'start' => $event->start,
                'end' => !empty($event->end) ? $event->end->addSecond() : null,
                'classNames' => ['bg-' . $event->color, 'border-0', 'cursor-pointer']
            ]);
        }

        return $result->toArray();
    }

    /**
     * Декоратор
     *
     * @param \Illuminate\Database\Eloquent\Collection $undefined
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function decorate(\Illuminate\Database\Eloquent\Collection $undefined)
    {
        $undefined->map(function ($item) {
            if (!empty($item->duration))
                $item->duration_str = Carbon::today()->addMinutes($item->duration)->format('H:i:s');
        });

        return $undefined;
    }

    /**
     * Генератор PDF
     *
     * @param User $user
     * @param $further
     * @return string
     */
    public function generate_pdf(User $user, $further)
    {
        $filename = 'temp/calendar.pdf';
        $pdf = \PDF::loadView('templates.pdf.calendar_events', ['user' => $user, 'further' => $further], [],
            [
                'format' => 'A4-P',
                'orientation' => 'P'
            ]);
        $pdf->save(storage_path('app/public/' . $filename));

        return storage_path('app/public/' . $filename);
    }
}
