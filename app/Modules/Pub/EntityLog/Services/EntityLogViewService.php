<?php

namespace App\Modules\Pub\EntityLog\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Журнал изменений сущностей (patch v29): просмотр состояния на момент (?at=)
 * на детальных страницах КП, партнёра и компании.
 *
 * Ядро (EntityLogService::stateAt) здесь не меняется: сервис отвечает только
 * за право просмотра, 404 по несуществующему слепку и ссылки для баннера
 * в макете (layouts/layout.blade.php).
 */
class EntityLogViewService
{
    /** Месяцы в родительном падеже для даты словами */
    public const MONTHS = [
        1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
        'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',
    ];

    /**
     * Состояние корня на момент для детальной страницы.
     *
     * Без ?at= или без права entity_log_view — null: страница показывает живую
     * модель. Слепок не найден (чужой id, мусор вместо даты, пустой слепок) — 404.
     *
     * @param Model $root живая модель — корень агрегата (Proposal, Partner, Company)
     * @param string|null $at id слепка либо дата Y-m-d (см. EntityLogService::stateAt)
     * @return array|null результат stateAt + 'current_url' (живая карточка), 'timeline_url' (лента)
     */
    public static function state(Model $root, ?string $at): ?array
    {
        if ($at === null || trim($at) === '') return null;
        if (!auth()->user()?->can('entity_log_view')) return null;

        $state = EntityLogService::stateAt($root, trim($at));
        if (empty($state)) abort(404);

        return $state + [
            'current_url' => $root->logUrl(),
            'timeline_url' => route('entity_log.index', [$root::logType(), $root->logGroupKey()]),
        ];
    }

    /**
     * Дата словами: «14 сентября 2026»
     *
     * @param Carbon $date
     * @return string
     */
    public static function dateWords(Carbon $date): string
    {
        return $date->day . ' ' . (static::MONTHS[$date->month] ?? $date->month) . ' ' . $date->year;
    }
}
