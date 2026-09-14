<?php

namespace App\Modules\Pub\Desktop\Widgets\Admin;

use App\Modules\Pub\Constant\Models\Constant;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Str;

/**
 * Константы портала (patch v30) — выбранные настройки с их значениями.
 *
 * Источник — таблица consts (модель Constant), та же, что правит админ-панель
 * /admin/consts. Порядок строк — порядок выбора в настройках; ничего не выбрано —
 * все константы, кроме системных (system = 1: их значение пишет сам код, например
 * время синхронизации с Битрикс24).
 *
 * Даты изменения константы виджет не показывает: в таблице consts нет ни
 * timestamps, ни записей в журнале изменений (patch v29) — показывать нечего.
 *
 * Значение-JSON сжимается в одну строку; полное значение — в подсказке. Строка
 * ведёт в админ-панель констант с поиском по коду. Только для администратора панели.
 */
class ConstsWatchWidget extends Widget
{
    /** Сколько символов значения показывать в строке */
    public const VALUE_LIMIT = 60;

    public static function id(): string
    {
        return 'consts_watch';
    }

    public static function name(): string
    {
        return 'Константы портала';
    }

    public static function category(): string
    {
        return 'admin';
    }

    public static function description(): string
    {
        return 'Значения выбранных констант портала — ключевые настройки на столе';
    }

    public static function icon(): string
    {
        return 'fa-sliders';
    }

    public static function sizes(): array
    {
        return ['8x4', '8x8', '16x8'];
    }

    public static function defaultSize(): string
    {
        return '8x4';
    }

    public static function order(): int
    {
        return 300;
    }

    public static function ttl(): int
    {
        return 300;
    }

    /** Право страницы-источника: константы правит только админ-панель */
    public static function available(User $user): bool
    {
        return $user->isPanelAdmin();
    }

    public static function fields(): array
    {
        return [
            ['key' => 'keys', 'type' => 'list', 'label' => 'Константы', 'default' => [],
                'hint' => 'Пусто — все константы портала, кроме системных',
                'options' => fn() => Constant::query()->orderBy('key')->get()->mapWithKeys(
                    fn(Constant $constant) => [(string) $constant->key => (string) ($constant->name ?: $constant->key)]
                )->all()],
            ['key' => 'note', 'type' => 'bool', 'label' => 'Показывать примечание', 'default' => false],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('admin.consts.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $base = [
            ['nds_rate', 'Ставка НДС', '22', 'Процент НДС в расчётах'],
            ['work_hour_rate', 'Ставка за 1 час работы', '6000', ''],
            ['license_horizons', 'Лицензии: горизонты истечения', '[30,60,90]', 'Дни для реестра лицензий'],
            ['scoring_weight_specs', 'Скоринг: вес суммы подписанных спецификаций', '35', ''],
            ['payment_soon_days', 'Платежи: «скоро», дней', '30', ''],
            ['license_expired_tail_days', 'Лицензии: хвост истёкших, дней', '14', ''],
            ['proposal_currency', 'КП: валюта по умолчанию', 'rub', ''],
            ['bitrix_webhook_url', 'Битрикс24: адрес вебхука', 'https://osmo.bitrix24.ru/rest/1/xxxxxxxxxxxx/', 'Входящий вебхук портала'],
            ['desk_widgets_limit', 'Рабочий стол: виджетов на столе', '80', ''],
            ['mail_notify_to', 'Почта уведомлений', 'sales@osmoview.ru', ''],
        ];

        // 40 констант: высокому блоку должно быть чем заполниться
        $sample = [];
        for ($i = 0; $i < 40; $i++) {
            [$key, $name, $value, $note] = $base[$i % 10];
            $n = intdiv($i, 10);
            $sample[] = [$key . ($n ? '_' . $n : ''), $name . ($n ? ' (' . ($n + 1) . ')' : ''), $value, $note];
        }

        $rows = array_map(fn($row) => [
            'key' => $row[0],
            'name' => $row[1],
            'value' => $row[2],
            'full' => $row[2],
            'note' => $row[3],
            'system' => false,
            'url' => null,
        ], $sample);

        return ['total' => count($rows), 'rows' => $rows];
    }

    /**
     * Выбранные константы с их значениями
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['total', 'rows' => [['key', 'name', 'value', 'full', 'note', 'system', 'url']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $keys = collect((array) ($settings['keys'] ?? []))
            ->filter('is_scalar')
            ->map(fn($key) => (string) $key)
            ->unique()
            ->values();

        $query = Constant::query()->orderBy('key');

        if ($keys->isNotEmpty()) {
            $query->whereIn('key', $keys->all());
        } else {
            $query->where('system', 0);
        }

        $found = $query->get();

        // выбранные константы идут в порядке выбора, остальные — по коду
        if ($keys->isNotEmpty()) {
            $order = $keys->flip();
            $found = $found->sortBy(fn(Constant $constant) => $order[$constant->key] ?? PHP_INT_MAX)->values();
        }

        $rows = $found->map(function (Constant $constant) {
            $full = static::plain((string) $constant->value);

            return [
                'key' => (string) $constant->key,
                'name' => (string) ($constant->name ?: $constant->key),
                'value' => Str::limit($full, self::VALUE_LIMIT),
                'full' => Str::limit($full, 500),
                'note' => Str::limit((string) $constant->note, 200),
                'system' => (bool) $constant->system,
                'url' => route('admin.consts.index', ['q' => $constant->key]),
            ];
        })->all();

        return ['total' => count($rows), 'rows' => $rows];
    }

    /**
     * Значение одной строкой: JSON сжимается, переводы строк и отступы схлопываются
     *
     * @param string $value
     * @return string
     */
    protected static function plain(string $value): string
    {
        $value = trim($value);

        if ($value !== '' && in_array($value[0], ['{', '['], true)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                $value = (string) json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
            }
        }

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}
