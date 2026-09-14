<?php

namespace App\Modules\Pub\EntityLog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\EntityLog\Models\EntityLog;
use App\Modules\Pub\EntityLog\Services\EntityLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * Журнал изменений сущностей (patch v29): страница ленты объекта
 */
class EntityLogController extends Controller
{
    use HasBreadcrumb;

    /** Разделы для крошек: слаг корня => [имя маршрута списка, подпись] */
    protected const SECTIONS = [
        'proposal' => ['proposal.index', 'КП'],
        'partner' => ['partner.index', 'Партнёры'],
        'company' => ['company.index', 'Компании'],
        'deal_project' => ['crm-deal.index', 'Реестр сделок Битрикс24'],
    ];

    /** Месяцы в родительном падеже для заголовка дня */
    protected const MONTHS = [
        1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
        'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',
    ];

    /**
     * Лента изменений объекта: события по дням, фильтр по полю / пользователю / событию
     *
     * @param string $type слаг корня (proposal, partner, company)
     * @param string $key ключ ленты (у КП — group)
     * @return View
     */
    public function index(string $type, string $key): View
    {
        $root = EntityLogService::rootByKey($type, $key);
        if (!$root) abort(404);

        // крошки: раздел → объект → журнал
        if (isset(static::SECTIONS[$type])) {
            [$route, $label] = static::SECTIONS[$type];
            $this->breadcrumb_add(route($route), $label);
        }
        $this->breadcrumb_add($root->logUrl(), $root->logTitle());
        $this->breadcrumb_add(null, 'Журнал изменений');

        $filter = request()->only(['field', 'user', 'event']);
        $rules_count = count(array_filter($filter, fn($value) => $value !== null && $value !== ''));

        $logs = EntityLogService::timeline($type, $key, $filter, 30);

        // живые строки корня (у КП — редакции) для ссылок на состояние; удалённых строк нет — ссылки не будет
        $class = EntityLogService::classOf($type);
        $rows = $class::query()
            ->whereIn('id', $logs->getCollection()->pluck('model_id')->unique()->all())
            ->get()
            ->keyBy('id');

        // ссылка «состояние на этот момент» — у событий со слепком (deleted слепка не имеет)
        $state_urls = [];
        $with_state = $class::logStateView();
        foreach ($logs as $log) {
            $row = $rows->get($log->model_id);
            if ($with_state && $row && $log->data !== null) {
                $state_urls[$log->id] = $row->logUrl() . '?at=' . $log->id;
            }
        }

        // события по дням; лента идёт по убыванию времени, поэтому первое событие дня — последнее по времени
        $days = $logs->getCollection()
            ->groupBy(fn(EntityLog $log) => $log->created_at->format('Y-m-d'))
            ->map(function (Collection $day_logs, string $date) use ($rows, $with_state) {
                $state_url = null;
                foreach ($day_logs as $log) {
                    $row = $rows->get($log->model_id);
                    if ($with_state && $row) {
                        $state_url = $row->logUrl() . '?at=' . $date;
                        break;
                    }
                }

                return [
                    'label' => $this->dayLabel($date),
                    'state_url' => $state_url,
                    'logs' => $day_logs,
                ];
            })
            ->values()
            ->all();

        return view('pub.entity_log.index', [
            'breadcrumbs' => $this->breadcrumb,
            'type' => $type,
            'key' => $key,
            'root' => $root,
            'logs' => $logs,
            'days' => $days,
            'filter' => $filter,
            'field_options' => EntityLogService::fieldOptions($type, $key),
            'user_options' => EntityLogService::userOptions($type, $key),
            'events' => EntityLog::EVENTS,
            'rules_count' => $rules_count,
            'state_urls' => $state_urls,
        ]);
    }

    /**
     * Дата дня словами: «14 сентября 2026»
     *
     * @param string $date Y-m-d
     * @return string
     */
    protected function dayLabel(string $date): string
    {
        [$year, $month, $day] = array_map('intval', explode('-', $date));

        return $day . ' ' . (static::MONTHS[$month] ?? $month) . ' ' . $year;
    }
}
