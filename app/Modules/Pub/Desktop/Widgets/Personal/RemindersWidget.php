<?php

namespace App\Modules\Pub\Desktop\Widgets\Personal;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Reminder\Models\Reminder;
use App\Modules\Pub\ReminderTime\Models\ReminderTime;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Напоминания (patch v30): ближайшие неисполненные времена напоминаний пользователя.
 *
 * Владение — как на странице reminder.index (Reminder::hasAccess: адресованные
 * пользователю или созданные им), одна строка на группу и время. Просроченные
 * (время прошло, уведомление не отправлено) — сверху.
 */
class RemindersWidget extends Widget
{
    public const HORIZONS = ['today' => 'Сегодня', '3days' => '3 дня', 'week' => 'Неделя', 'all' => 'Все будущие'];

    public static function id(): string { return 'reminders'; }

    public static function name(): string { return 'Напоминания'; }

    public static function category(): string { return 'personal'; }

    public static function description(): string
    {
        return 'Ближайшие напоминания и просроченные, со ссылкой на объект';
    }

    public static function icon(): string { return 'fa-alarm-clock'; }

    public static function sizes(): array { return ['8x4', '4x4', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 200; }

    public static function ttl(): int { return 60; }

    public static function fields(): array
    {
        return [
            ['key' => 'horizon', 'type' => 'select', 'label' => 'Горизонт', 'default' => 'week', 'options' => static::HORIZONS],
            ['key' => 'overdue', 'type' => 'bool', 'label' => 'Показывать просроченные', 'default' => true],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько показывать', 'default' => 8, 'min' => 1, 'max' => 30],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('reminder.index');
    }

    /**
     * Строки: ['time', 'text', 'object', 'overdue', 'url', 'sidebar'] и счётчики для сводки
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [...], 'overdue' => int|null (null — просроченные выключены), 'ahead' => int]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $user = $ctx->user;
        if (!$user) {
            return ['rows' => [], 'overdue' => null, 'ahead' => 0];
        }

        $now = now();
        $to = match ($settings['horizon']) {
            'today' => $now->copy()->endOfDay(),
            '3days' => $now->copy()->addDays(2)->endOfDay(),
            'all' => null,
            default => $now->copy()->addDays(6)->endOfDay(),
        };
        $limit = (int) $settings['limit'];

        $times = ReminderTime::query()
            ->where('notified', false)
            ->whereHas('reminder', fn($query) => $query->hasAccess($user))
            ->where(function ($query) use ($now, $to, $settings) {
                $query->where(function ($query) use ($now, $to) {
                    $query->where('notify_at', '>=', $now);
                    if ($to) $query->where('notify_at', '<=', $to);
                });
                if ($settings['overdue']) $query->orWhere('notify_at', '<', $now);
            })
            ->with('reminder')
            ->orderBy('notify_at')
            ->limit($limit * 4)
            ->get()
            ->unique(fn($time) => $time->reminder->group . '|' . $time->notify_at)
            ->take($limit);

        $rows = [];
        foreach ($times as $time) {
            [$object, $url, $sidebar] = $this->object($time->reminder);
            $rows[] = [
                // год — только у времени не из текущего года (старая просрочка)
                'time' => $time->notify_at->format($time->notify_at->isCurrentYear() ? 'd.m H:i' : 'd.m.y H:i'),
                'text' => Str::limit((string) ($time->reminder->title ?: $time->reminder->message), 80),
                'object' => $object,
                'overdue' => $time->notify_at->lt($now),
                'url' => $url ?? route('reminder.index'),
                // объект, который открывается сайдбаром (событие календаря), — как на странице напоминаний
                'sidebar' => $sidebar,
            ];
        }

        // сводка — полные числа, а не строки списка (их режет «Сколько показывать»)
        $count = fn($filter) => (int) ReminderTime::query()
            ->join('reminders', 'reminders.id', '=', 'reminder_times.reminder_id')
            ->whereNull('reminders.deleted_at')
            ->where('reminder_times.notified', false)
            ->where(fn($query) => $query->where('reminders.user_id', $user->id)->orWhere('reminders.created_by', $user->id))
            ->where($filter)
            // одна строка на группу и время — как в списке
            ->selectRaw('count(distinct reminders.`group`, reminder_times.notify_at) as aggregate')
            ->value('aggregate');

        return [
            'rows' => $rows,
            'overdue' => $settings['overdue'] ? $count(fn($query) => $query->where('reminder_times.notify_at', '<', $now)) : null,
            'ahead' => $count(function ($query) use ($now, $to) {
                $query->where('reminder_times.notify_at', '>=', $now);
                if ($to) $query->where('reminder_times.notify_at', '<=', $to);
            }),
        ];
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
        // столько напоминаний, сколько разрешено настройкой (до 40); первые два — просроченные
        $pool = [
            ['Выставить счёт по договору № 118', 'КП № AA-794'],
            ['Позвонить партнёру «Инфосистемы»', 'Событие: Созвон'],
            ['Продлить ключи ООО «Восток»', null],
            ['Отправить КП «Русалу»', 'КП № AA-801'],
            ['Проверить оплату по счёту № 57', 'Договор № 121'],
            ['Подготовить спецификацию', 'Договор № 121'],
            ['Напомнить о продлении лицензий', 'Компания: ООО «Восток»'],
            ['Созвон с Пекином', null],
        ];

        $rows = [];
        for ($i = 0, $n = min(40, max(1, (int) $settings['limit'])); $i < $n; $i++) {
            [$text, $object] = $pool[$i % count($pool)];
            $at = now()->startOfHour()->addHours(($i - 2) * 13 + 1);
            $rows[] = ['time' => $at->format('d.m H:i'), 'text' => $text, 'object' => $object, 'overdue' => $at->lt(now()), 'url' => null, 'sidebar' => null];
        }

        $overdue = count(array_filter($rows, fn($row) => $row['overdue']));

        return [
            'rows' => $settings['overdue'] ? $rows : array_values(array_filter($rows, fn($row) => !$row['overdue'])),
            'overdue' => $settings['overdue'] ? $overdue : null,
            'ahead' => count($rows) - $overdue,
        ];
    }

    /**
     * Подпись и ссылка привязанного объекта: страница объекта или его сайдбар
     * (detail_route вида «sidebar:маршрут», как у события календаря); неизвестный маршрут — без ссылки
     *
     * @param Reminder $reminder
     * @return array [подпись|null, url страницы|null, url сайдбара|null]
     */
    protected function object(Reminder $reminder): array
    {
        $class = (string) $reminder->target_type;
        if ($class === '' || !class_exists($class)) {
            return [null, null, null];
        }

        $object = $reminder->target;
        $label = (string) ($class::$module_name ?? '');
        if (!$object) {
            return [$label !== '' ? $label : null, null, null];
        }

        $name = trim((string) ($object->title ?? $object->name ?? ''));
        $label = $name !== '' ? Str::limit(($label !== '' ? $label . ': ' : '') . $name, 40) : $label;

        $route = (string) ($class::$detail_route ?? '');
        $sidebar = Str::startsWith($route, 'sidebar:');
        if ($sidebar) $route = Str::after($route, 'sidebar:');
        $link = $route !== '' && Route::has($route) ? route($route, $object) : null;

        return [$label !== '' ? $label : null, $sidebar ? null : $link, $sidebar ? $link : null];
    }
}
