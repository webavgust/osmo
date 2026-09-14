<?php

namespace App\Modules\Pub\Desktop\Widgets\Funnel;

use App\Modules\Pub\Constant\ConstantService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Синхронизация Битрикс24 (patch v30): когда в последний раз обновлялось зеркало CRM
 * и сколько в нём записей.
 *
 * Время берётся оттуда же, откуда его читает страница «Настройка CRM», — из константы
 * bitrix_update_timestamps (ConstantService::getBitrixSyncTime()), её пишет
 * ApiSyncController::refresh после переноса дампа. Количество записей — по таблицам
 * соединения bitrix. Дополнительно показывается косвенный признак свежести выгрузки —
 * самая поздняя дата изменения сделки (crm_deal.date_modify): это дата правки в Битриксе,
 * а не время переноса, поэтому подписана отдельно.
 *
 * Кнопки «Синхронизировать» на столе нет: перенос делается вставкой SQL-дампа в окне
 * страницы «Настройка CRM» (SyncBoxController::refresh), одной кнопкой он не запускается.
 */
class SyncWidget extends Widget
{
    /** Возраст синхронизации, часов: до — зелёный, дальше — жёлтый, потом красный */
    public const FRESH_HOURS = 24;
    public const STALE_HOURS = 72;

    public static function id(): string { return 'sync'; }

    public static function name(): string { return 'Синхронизация Битрикс24'; }

    public static function category(): string { return 'funnel'; }

    public static function description(): string
    {
        return 'Когда последний раз обновлялось зеркало CRM и сколько в нём записей';
    }

    public static function icon(): string { return 'fa-arrows-rotate'; }

    public static function sizes(): array { return ['4x2', '8x2', '8x4']; }

    public static function defaultSize(): string { return '4x2'; }

    public static function order(): int { return 1200; }

    public static function ttl(): int { return 300; }

    public static function fields(): array
    {
        return [
            ['key' => 'show_tables', 'type' => 'bool', 'label' => 'Список таблиц', 'default' => true,
                'hint' => 'Таблицы зеркала с числом записей и датой переноса — если в блоке есть место'],
            ['key' => 'show_modified', 'type' => 'bool', 'label' => 'Последняя правка сделки', 'default' => true,
                'hint' => 'Самая поздняя дата изменения сделки в выгрузке — косвенный признак свежести данных'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('sync.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $now = now();
        $rows = [['crm_company', 383, 26], ['crm_company_uf', 345, 26], ['crm_deal', 406, 25], ['crm_deal_uf', 405, 25]];

        $rows = array_map(fn($row) => static::row($row[0], $row[1], $now->copy()->subHours($row[2]), $now), $rows);

        return static::pack($rows, $now->copy()->subHours(25), $now->copy()->subHours(26), $now,
            $now->copy()->subHours(24)->format('d.m.Y H:i'), false);
    }

    /**
     * Время последнего переноса, возраст и таблицы зеркала
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['ok', 'last', 'oldest', 'total', 'tables', 'never', 'modified', 'rows']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $now = now();

        try {
            $times = (array) (ConstantService::getBitrixSyncTime() ?: []);
            $names = collect(DB::connection('bitrix')->select('SHOW TABLES'))
                ->map(fn($table) => (string) array_values((array) $table)[0])
                ->all();

            $rows = [];
            foreach ($names as $name) {
                $synced = empty($times[$name]) ? null : Carbon::parse($times[$name])->timezone(config('app.timezone'));
                $rows[] = static::row($name, (int) DB::connection('bitrix')->table($name)->count(), $synced, $now);
            }

            // косвенный признак: когда в Битриксе последний раз правили сделку из выгрузки
            $modified = null;
            if ($settings['show_modified'] && in_array('crm_deal', $names, true)) {
                $raw = DB::connection('bitrix')->table('crm_deal')->max('date_modify');
                $modified = $raw ? Carbon::parse($raw)->format('d.m.Y H:i') : null;
            }
        } catch (\Throwable $e) {
            report($e);

            return static::pack([], null, null, $now, null, true);
        }

        $dates = array_filter(array_column($rows, 'synced_at'));
        $last = $dates ? Carbon::parse(max($dates)) : null;
        $oldest = count($dates) === count($rows) && $dates ? Carbon::parse(min($dates)) : null;

        return static::pack($rows, $last, $oldest, $now, $modified, false);
    }

    /**
     * Строка таблицы зеркала
     *
     * @param string $name
     * @param int $count
     * @param Carbon|null $synced
     * @param Carbon $now
     * @return array
     */
    protected static function row(string $name, int $count, ?Carbon $synced, Carbon $now): array
    {
        $hours = $synced ? max(0, $synced->diffInHours($now)) : null;

        return [
            'table' => $name,
            'count' => $count,
            'synced_at' => $synced?->format('Y-m-d H:i:s'),
            'date' => $synced?->format('d.m.Y H:i'),
            'hours' => $hours,
            'age' => static::age($hours),
            'color' => static::color($hours),
        ];
    }

    /**
     * Сборка данных виджета
     *
     * @param array $rows таблицы зеркала
     * @param Carbon|null $last время последнего переноса
     * @param Carbon|null $oldest время самого давнего переноса (если перенесены все таблицы)
     * @param Carbon $now
     * @param string|null $modified последняя правка сделки в выгрузке
     * @param bool $failed зеркало недоступно
     * @return array
     */
    protected static function pack(array $rows, ?Carbon $last, ?Carbon $oldest, Carbon $now, ?string $modified, bool $failed): array
    {
        $hours = $last ? max(0, $last->diffInHours($now)) : null;
        $old_hours = $oldest ? max(0, $oldest->diffInHours($now)) : null;

        return [
            'ok' => !$failed,
            'last' => [
                'date' => $last?->format('d.m.Y H:i'),
                'hours' => $hours,
                'age' => static::age($hours),
                'color' => static::color($hours),
            ],
            'oldest' => [
                'date' => $oldest?->format('d.m.Y H:i'),
                'hours' => $old_hours,
                'age' => static::age($old_hours),
                'color' => static::color($old_hours),
            ],
            'total' => (int) array_sum(array_column($rows, 'count')),
            'tables' => count($rows),
            'never' => count(array_filter($rows, fn($row) => $row['synced_at'] === null)),
            'modified' => $modified,
            'rows' => $rows,
        ];
    }

    /**
     * Возраст словами: «3 ч.», «5 дн.», «только что»
     *
     * @param int|null $hours
     * @return string
     */
    protected static function age(?int $hours): string
    {
        if ($hours === null) return 'не переносилось';
        if ($hours < 1) return 'только что';
        if ($hours < 24) return $hours . ' ч.';

        return intdiv($hours, 24) . ' дн.';
    }

    /**
     * Цвет по возрасту: сутки — зелёный, трое суток — жёлтый, дальше красный
     *
     * @param int|null $hours
     * @return string
     */
    protected static function color(?int $hours): string
    {
        return match (true) {
            $hours === null => 'secondary',
            $hours <= static::FRESH_HOURS => 'success',
            $hours <= static::STALE_HOURS => 'warning',
            default => 'danger',
        };
    }
}
