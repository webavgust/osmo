<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\EntityLog\Models\EntityLog;
use App\Modules\Pub\EntityLog\Services\EntityLogService;
use App\Modules\Pub\User\Models\User;
use Carbon\CarbonInterface;

/**
 * Журнал изменений (patch v30): последние события журнала сущностей (patch v29) —
 * кто, что и когда поменял, со ссылкой на ленту объекта.
 *
 * Строки — entity_logs по всем корням (КП, партнёр, компания) или по одному объекту;
 * название объекта берётся из title события (оно записано в момент изменения), адрес —
 * маршрут ленты entity_log.index. Виден только с правом entity_log_view — тем же,
 * что охраняет страницу ленты.
 */
class ChangesWidget extends Widget
{
    /** Цвет плашки события */
    public const COLORS = [
        EntityLog::EVENT_BASELINE => 'secondary',
        EntityLog::EVENT_CREATED => 'success',
        EntityLog::EVENT_UPDATED => 'primary',
        EntityLog::EVENT_DELETED => 'danger',
    ];

    /** Иконка события */
    public const ICONS = [
        EntityLog::EVENT_BASELINE => 'fa-flag',
        EntityLog::EVENT_CREATED => 'fa-plus',
        EntityLog::EVENT_UPDATED => 'fa-pen',
        EntityLog::EVENT_DELETED => 'fa-trash',
    ];

    public static function id(): string { return 'changes'; }

    public static function name(): string { return 'Журнал изменений'; }

    public static function category(): string { return 'common'; }

    public static function description(): string
    {
        return 'Последние изменения КП, партнёров и компаний: кто, что и когда поменял';
    }

    public static function icon(): string { return 'fa-timeline'; }

    public static function sizes(): array { return ['8x8', '8x4', '16x8']; }

    public static function defaultSize(): string { return '8x8'; }

    public static function order(): int { return 330; }

    public static function ttl(): int { return 120; }

    /** Право страницы-источника: лента объекта закрыта тем же entity_log_view */
    public static function available(User $user): bool
    {
        return $user->can('entity_log_view');
    }

    public static function fields(): array
    {
        return [
            ['key' => 'target', 'type' => 'entity', 'label' => 'Объект', 'entities' => ['proposal', 'partner', 'company'],
                'default' => null, 'hint' => 'Пусто — события по всем объектам'],
            ['key' => 'type', 'type' => 'select', 'label' => 'Тип объекта', 'default' => 'all',
                'hint' => 'Учитывается, когда объект не выбран', 'options' => fn() => static::typeOptions()],
            ['key' => 'events', 'type' => 'list', 'label' => 'Типы событий', 'default' => [],
                'hint' => 'Пусто — все события', 'options' => EntityLog::EVENTS],
            ['key' => 'mine', 'type' => 'bool', 'label' => 'Только мои изменения', 'default' => false],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько событий', 'default' => 40, 'min' => 3, 'max' => 50,
                'hint' => 'Верхняя граница: сколько строк влезет в блок — решает высота'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        $target = static::target($settings);

        return $target ? route('entity_log.index', [$target['type'], $target['id']]) : null;
    }

    /**
     * Типы объектов для выбора: слаг => подпись класса (logLabel)
     *
     * @return array
     */
    public static function typeOptions(): array
    {
        $options = ['all' => 'Все'];

        foreach (EntityLogService::types() as $slug => $class) {
            $options[$slug] = method_exists($class, 'logLabel') ? $class::logLabel() : class_basename($class);
        }

        return $options;
    }

    /**
     * Выбранный объект: ['type', 'id'] или null, если объект не задан
     *
     * @param array $settings
     * @return array|null
     */
    protected static function target(array $settings): ?array
    {
        $target = $settings['target'] ?? null;
        $id = is_array($target) ? trim((string) ($target['id'] ?? '')) : '';
        $type = is_array($target) ? (string) ($target['type'] ?? '') : '';

        return $id !== '' && EntityLogService::classOf($type) ? ['type' => $type, 'id' => $id] : null;
    }

    /**
     * Последние события журнала
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [['id', 'type', 'type_label', 'title', 'event', 'event_label', 'color', 'icon', 'user', 'when', 'ago', 'ago_short', 'changes', 'url']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $labels = static::typeOptions();
        $query = EntityLog::query()->with('user')->orderByDesc('created_at')->orderByDesc('id');

        if ($target = static::target($settings)) {
            $query->timeline($target['type'], $target['id']);
        } elseif (($settings['type'] ?? 'all') !== 'all' && EntityLogService::classOf((string) $settings['type'])) {
            $query->where('type', (string) $settings['type']);
        }

        $events = array_values(array_intersect(
            array_map('strval', array_filter((array) ($settings['events'] ?? []), 'is_scalar')),
            array_keys(EntityLog::EVENTS)
        ));

        if ($events) {
            $query->whereIn('event', $events);
        }

        if (!empty($settings['mine']) && $ctx->user) {
            $query->where('user_id', (int) $ctx->user->id);
        }

        $logs = $query->limit((int) $settings['limit'])->get();

        // у удалённого объекта ленты нет (страница ленты отвечает 404) — его строки без ссылки;
        // корень ищется так же, как на странице ленты, по одному запросу на объект
        $alive = [];
        foreach ($logs as $log) {
            $key = $log->type . ':' . $log->group_key;
            $alive[$key] ??= EntityLogService::rootByKey((string) $log->type, (string) $log->group_key) !== null;
        }

        $rows = $logs->map(fn(EntityLog $log) => [
            'id' => (int) $log->id,
            'type' => (string) $log->type,
            'type_label' => (string) ($labels[$log->type] ?? $log->type),
            'title' => (string) $log->title,
            'event' => (string) $log->event,
            'event_label' => (string) $log->event_label,
            'color' => static::COLORS[$log->event] ?? 'secondary',
            'icon' => static::ICONS[$log->event] ?? 'fa-pen',
            'user' => (string) ($log->user?->full_name ?: ($log->user?->name ?: 'Система')),
            'when' => $log->created_at->format('d.m.Y H:i'),
            'ago' => $log->created_at->diffForHumans(),
            // коротко («12 мин.») — для узкого списка, где полное «12 минут назад» съедает название
            'ago_short' => $log->created_at->diffForHumans(null, CarbonInterface::DIFF_ABSOLUTE, true),
            'changes' => (int) $log->changes_count,
            'url' => $alive[$log->type . ':' . $log->group_key] ? route('entity_log.index', [$log->type, $log->group_key]) : null,
        ])->all();

        return ['rows' => $rows];
    }

    /**
     * Образцовые данные для превью
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function sample(array $settings, DesktopContext $ctx): array
    {
        $sample = [
            ['proposal', 'КП', 'КП № AA-794 (ред. 2)', EntityLog::EVENT_UPDATED, 'Анна Соколова', 6],
            ['company', 'Компания', 'ООО «Альфа»', EntityLog::EVENT_CREATED, 'Алексей Гордеев', 0],
            ['partner', 'Партнёр', 'ГК Восток', EntityLog::EVENT_UPDATED, 'Анна Соколова', 2],
            ['proposal', 'КП', 'КП № AA-788 (ред. 1)', EntityLog::EVENT_BASELINE, 'Система', 0],
            ['proposal', 'КП', 'КП № AA-781 (ред. 3)', EntityLog::EVENT_UPDATED, 'Игорь Лебедев', 4],
            ['company', 'Компания', 'АО «Северный терминал»', EntityLog::EVENT_UPDATED, 'Мария Кузнецова', 1],
            ['partner', 'Партнёр', 'Интегратор Плюс', EntityLog::EVENT_CREATED, 'Алексей Гордеев', 0],
            ['proposal', 'КП', 'КП № AA-775 (ред. 1)', EntityLog::EVENT_DELETED, 'Игорь Лебедев', 0],
        ];

        // 40 строк: образец повторяется со сдвигом во времени — высокий блок есть чем заполнить
        $rows = array_map(function ($index) use ($sample) {
            $row = $sample[$index % count($sample)];
            $when = now()->subMinutes(12 + $index * $index * 9);

            return [
                'id' => 0,
                'type' => $row[0],
                'type_label' => $row[1],
                'title' => $row[2],
                'event' => $row[3],
                'event_label' => EntityLog::EVENTS[$row[3]] ?? $row[3],
                'color' => static::COLORS[$row[3]] ?? 'secondary',
                'icon' => static::ICONS[$row[3]] ?? 'fa-pen',
                'user' => $row[4],
                'when' => $when->format('d.m.Y H:i'),
                'ago' => $when->diffForHumans(),
                'ago_short' => $when->diffForHumans(null, CarbonInterface::DIFF_ABSOLUTE, true),
                'changes' => $row[5],
                'url' => null,
            ];
        }, range(0, 39));

        return ['rows' => $rows];
    }
}
