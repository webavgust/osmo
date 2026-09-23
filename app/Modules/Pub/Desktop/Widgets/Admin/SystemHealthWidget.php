<?php

namespace App\Modules\Pub\Desktop\Widgets\Admin;

use App\Modules\Pub\Constant\ConstantService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\EntityLog\Models\EntityLog;
use App\Modules\Pub\ExternalProposal\Models\ExternalProposal;
use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Состояние портала (patch v30) — светофор по подсистемам.
 *
 * Показывается только то, что портал действительно хранит:
 *  - очередь: таблицы jobs и failed_jobs (сколько задач ждёт, есть ли упавшие);
 *  - Битрикс24: отметки времени из системной константы bitrix_update_timestamps
 *    (ConstantService::getBitrixSyncTime(), их пишет страница синхронизации);
 *  - OSMOVIEW CP: последняя выгрузка КП — external_proposals.fetched_at;
 *  - курсы валют: последняя дата в currency_rates (правило «старше двух дней»
 *    взято у виджета «Курсы валют»);
 *  - журнал изменений: записи entity_logs (patch v29) за сутки и всего;
 *  - журнал ошибок: файл storage/logs/laravel.log — размер и дата последней записи.
 *
 * Цвет: красный — упавшие задачи или курсы старше двух дней, жёлтый —
 * синхронизация давно не проходила или очередь стоит, серый — данных нет вовсе
 * (подсистемой не пользуются). Ничего, чего нет в базе, виджет не выдумывает:
 * например, даты изменения констант портал не хранит.
 *
 * Только для администратора панели.
 */
class SystemHealthWidget extends Widget
{
    /** Подсистемы: код => [название, иконка] */
    public const PARTS = [
        'queue' => ['Очередь задач', 'fa-list-check'],
        'bitrix' => ['Битрикс24', 'fa-arrows-rotate'], // коротко: полное «Синхронизация Битрикс24» не влезало в плитку и строку
        'osmoview' => ['OSMOVIEW CP', 'fa-cloud-arrow-down'],
        'rates' => ['Курсы валют', 'fa-coins'],
        'changes' => ['Журнал изменений', 'fa-timeline'],
        'errors' => ['Журнал ошибок', 'fa-triangle-exclamation'],
    ];

    /** Светофор: статус => цвет Metronic */
    public const COLORS = ['ok' => 'success', 'warn' => 'warning', 'bad' => 'danger', 'none' => 'gray-400'];

    /** Порядок строк: сначала проблемы — в низком блоке обрезаются только здоровые подсистемы */
    public const RANKS = ['bad' => 0, 'warn' => 1, 'ok' => 2, 'none' => 3];

    /** Синхронизация старше стольких часов — жёлтый */
    public const SYNC_STALE_HOURS = 24;

    /** Выгрузка OSMOVIEW CP идёт по запросу, а не по расписанию: жёлтый только через неделю */
    public const OSMOVIEW_STALE_DAYS = 7;

    /** Курсы старше стольких дней — красный (правило виджета «Курсы валют») */
    public const RATES_STALE_DAYS = 2;

    /** Задача ждёт в очереди дольше стольких минут — жёлтый */
    public const QUEUE_STALE_MINUTES = 60;

    public static function id(): string
    {
        return 'system_health';
    }

    public static function name(): string
    {
        return 'Состояние портала';
    }

    public static function category(): string
    {
        return 'admin';
    }

    public static function description(): string
    {
        return 'Служебная сводка: очередь, синхронизации, курсы, журналы';
    }

    public static function icon(): string
    {
        return 'fa-heart-pulse';
    }

    public static function sizes(): array
    {
        return ['8x4', '8x8', '16x4'];
    }

    public static function defaultSize(): string
    {
        return '8x4';
    }

    public static function order(): int
    {
        return 400;
    }

    public static function ttl(): int
    {
        return 120;
    }

    /** Служебная сводка портала — только администратору панели */
    public static function available(User $user): bool
    {
        return $user->isPanelAdmin();
    }

    public static function fields(): array
    {
        return [
            ['key' => 'parts', 'type' => 'list', 'label' => 'Подсистемы', 'default' => [],
                'hint' => 'Пусто — все подсистемы',
                'options' => fn() => collect(self::PARTS)->map(fn($part) => $part[0])->all()],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('admin.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // даты — от сегодняшнего дня, подписи — те же, что пишет data()
        $bitrix = now()->subDays(2)->setTime(10, 41);
        $osmoview = now()->subHours(3);
        $changes = now()->subMinutes(40);
        $errors = now()->subHours(1);

        $rows = [
            ['queue', 'ok', '0 в очереди', 'упавших нет', null],
            ['bitrix', 'warn', $bitrix->diffForHumans(), 'синхронизация, таблиц: 4', $bitrix->format('d.m.Y H:i')],
            ['osmoview', 'ok', $osmoview->diffForHumans(), 'выгружено КП: 27', $osmoview->format('d.m.Y H:i')],
            ['rates', 'ok', now()->format('d.m.Y'), 'валют в последний день: 5', now()->format('d.m.Y')],
            ['changes', 'ok', '18 за сутки', 'всего записей: 663', $changes->format('d.m.Y H:i')],
            ['errors', 'warn', '4,9 МБ', 'есть записи за сутки', $errors->format('d.m.Y H:i')],
        ];

        return static::summary(array_map(fn($row) => static::row($row[0], $row[1], $row[2], $row[3], $row[4]), $rows));
    }

    /**
     * Сводка по выбранным подсистемам
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [['key', 'name', 'icon', 'status', 'color', 'value', 'note', 'when']], 'bad', 'warn']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $chosen = collect((array) ($settings['parts'] ?? []))
            ->filter('is_scalar')
            ->map(fn($part) => (string) $part)
            ->filter(fn($part) => isset(self::PARTS[$part]))
            ->values();

        $keys = $chosen->isNotEmpty() ? $chosen->all() : array_keys(self::PARTS);
        $rows = [];

        foreach ($keys as $key) {
            $rows[] = match ($key) {
                'queue' => $this->queue(),
                'bitrix' => $this->bitrix(),
                'osmoview' => $this->osmoview(),
                'rates' => $this->rates(),
                'changes' => $this->changes(),
                'errors' => $this->errors(),
            };
        }

        return static::summary($rows);
    }

    /**
     * Сводка: строки проблемами вверх и счётчики светофора
     *
     * @param array $rows
     * @return array
     */
    protected static function summary(array $rows): array
    {
        usort($rows, fn($a, $b) => (self::RANKS[$a['status']] ?? 9) <=> (self::RANKS[$b['status']] ?? 9));

        return [
            'rows' => $rows,
            'bad' => count(array_filter($rows, fn($row) => $row['status'] === 'bad')),
            'warn' => count(array_filter($rows, fn($row) => $row['status'] === 'warn')),
        ];
    }

    /**
     * Строка подсистемы
     *
     * @param string $key
     * @param string $status ok|warn|bad|none
     * @param string $value что показать крупно
     * @param string $note пояснение
     * @param string|null $when дата и время события
     * @return array
     */
    protected static function row(string $key, string $status, string $value, string $note = '', ?string $when = null): array
    {
        [$name, $icon] = self::PARTS[$key];

        return [
            'key' => $key,
            'name' => $name,
            'icon' => $icon,
            'status' => $status,
            'color' => self::COLORS[$status] ?? 'gray-400',
            'value' => $value,
            'note' => $note,
            'when' => $when,
        ];
    }

    /**
     * Очередь задач: сколько ждёт (jobs) и сколько упало (failed_jobs)
     *
     * @return array
     */
    protected function queue(): array
    {
        $waiting = (int) DB::table('jobs')->count();
        $failed = (int) DB::table('failed_jobs')->count();
        $oldest = DB::table('jobs')->min('created_at');
        $stale = $oldest && now()->timestamp - (int) $oldest > self::QUEUE_STALE_MINUTES * 60;

        $status = match (true) {
            $failed > 0 => 'bad',
            $stale => 'warn',
            default => 'ok',
        };

        $note = $failed > 0
            ? 'упавших задач: ' . $failed
            : ($stale ? 'самая старая ждёт с ' . Carbon::createFromTimestamp((int) $oldest)->format('d.m.Y H:i') : 'упавших нет');

        return static::row('queue', $status, $waiting . ' в очереди', $note,
            $oldest ? Carbon::createFromTimestamp((int) $oldest)->format('d.m.Y H:i') : null);
    }

    /**
     * Синхронизация Битрикс24: отметки времени по таблицам (bitrix_update_timestamps)
     *
     * @return array
     */
    protected function bitrix(): array
    {
        $times = collect((array) ConstantService::getBitrixSyncTime())
            ->map(fn($time) => static::moment($time))
            ->filter()
            ->values();

        if ($times->isEmpty()) {
            return static::row('bitrix', 'none', 'нет отметок', 'синхронизация ещё не проходила');
        }

        $last = $times->max();
        $stale = $last->diffInHours(now()) >= self::SYNC_STALE_HOURS;

        return static::row('bitrix', $stale ? 'warn' : 'ok', $last->diffForHumans(),
            'синхронизация, таблиц: ' . $times->count(), $last->format('d.m.Y H:i'));
    }

    /**
     * OSMOVIEW CP: последняя выгрузка КП (external_proposals.fetched_at)
     *
     * @return array
     */
    protected function osmoview(): array
    {
        $count = (int) ExternalProposal::query()->count();
        $last = static::moment(ExternalProposal::query()->max('fetched_at'));

        if (!$last) {
            return static::row('osmoview', 'none', 'нет выгрузок', 'КП из OSMOVIEW CP не забирали');
        }

        $stale = $last->diffInDays(now()) >= self::OSMOVIEW_STALE_DAYS;

        return static::row('osmoview', $stale ? 'warn' : 'ok', $last->diffForHumans(),
            'выгружено КП: ' . $count, $last->format('d.m.Y H:i'));
    }

    /**
     * Курсы валют: последняя дата в currency_rates
     *
     * @return array
     */
    protected function rates(): array
    {
        $last_date = DB::table('currency_rates')->max('date');

        if (!$last_date) {
            return static::row('rates', 'none', 'нет курсов', 'таблица курсов пуста');
        }

        $date = Carbon::parse($last_date);
        $days = $date->diffInDays(now());
        $slugs = (int) DB::table('currency_rates')->where('date', $last_date)->distinct()->count('slug');

        $status = match (true) {
            $days > self::RATES_STALE_DAYS => 'bad',
            $days >= 1 => 'warn',
            default => 'ok',
        };

        return static::row('rates', $status, $date->format('d.m.Y'), 'валют в последний день: ' . $slugs, $date->format('d.m.Y'));
    }

    /**
     * Журнал изменений (patch v29): записей за сутки и всего
     *
     * @return array
     */
    protected function changes(): array
    {
        $total = (int) EntityLog::query()->count();
        $today = (int) EntityLog::query()->where('created_at', '>=', now()->subDay())->count();
        $last = static::moment(EntityLog::query()->max('created_at'));

        return static::row('changes', $total > 0 ? 'ok' : 'none', $today . ' за сутки',
            'всего записей: ' . $total, $last?->format('d.m.Y H:i'));
    }

    /**
     * Журнал ошибок: файл storage/logs/laravel.log
     *
     * @return array
     */
    protected function errors(): array
    {
        $file = storage_path('logs/laravel.log');

        if (!is_file($file)) {
            return static::row('errors', 'none', 'нет файла', 'storage/logs/laravel.log не заведён');
        }

        $size = (int) @filesize($file);
        $changed = Carbon::createFromTimestamp((int) @filemtime($file));
        $today = $changed->diffInHours(now()) < 24;

        return static::row('errors', $today ? 'warn' : 'ok', static::bytes($size),
            $today ? 'есть записи за сутки' : 'последняя запись ' . $changed->format('d.m.Y'),
            $changed->format('d.m.Y H:i'));
    }

    /**
     * Дата и время из строки или объекта в часовом поясе портала; пусто — null
     *
     * @param mixed $value
     * @return Carbon|null
     */
    protected static function moment($value): ?Carbon
    {
        if (empty($value)) return null;

        try {
            return Carbon::parse($value instanceof \DateTimeInterface ? $value : (string) $value)
                ->setTimezone(config('app.timezone'));
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Размер файла по-человечески: 5 124 922 → «4,9 МБ»
     *
     * @param int $bytes
     * @return string
     */
    protected static function bytes(int $bytes): string
    {
        [$divider, $suffix] = match (true) {
            $bytes >= 1073741824 => [1073741824, ' ГБ'],
            $bytes >= 1048576 => [1048576, ' МБ'],
            $bytes >= 1024 => [1024, ' КБ'],
            default => [1, ' Б'],
        };

        return number_format($bytes / $divider, $divider === 1 ? 0 : 1, ',', ' ') . $suffix;
    }
}
