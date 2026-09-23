<?php

namespace App\Modules\Pub\Desktop\Widgets\Partner;

use App\Modules\Pub\Analytics\Services\PartnerScoringService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Partner\Models\Partner;

/**
 * Активность партнёра (patch v30): динамика по годам одного партнёра либо
 * сравнение до трёх партнёров на одном графике.
 *
 * Считает по тем же выборкам, что и скоринг (PartnerScoringService), поэтому
 * цифры сходятся со страницей «Скоринг партнёров» и с расшифровкой партнёра
 * (PartnerStatsService). Год события определяется так же, как в скоринге:
 * КП — по дате отправки (иначе по дате спецификации), спецификация — по своей
 * дате (иначе по дате договора), оплата — по дате факта (иначе плана),
 * сделка — по дате создания в Битриксе.
 *
 * Партнёр без сопоставления с компанией Битрикс24 сделок не даёт вовсе —
 * это видно в виджете «Партнёры без Битрикс24».
 */
class PartnerActivityWidget extends Widget
{
    /** Сколько партнёров можно сравнить на одном графике */
    public const MAX_PARTNERS = 3;

    /**
     * Показатели: ключ => [подпись, подпись единицы]. «Платежи» — все платежи графика по дате
     * факта (иначе плана), как колонка «Платежи» в расшифровке партнёра, а не только оплаченные;
     * проекты дают балл скоринга с patch v26
     */
    public const METRICS = [
        'proposals' => ['КП', 'КП'],
        'specs' => ['Спецификации', 'спецификаций'],
        'payments' => ['Платежи', 'платежей'],
        'deals' => ['Сделки Битрикс24', 'сделок'],
        'projects' => ['Проекты', 'проектов'],
    ];

    /** Цвета Metronic по порядку партнёров */
    public const COLORS = ['primary', 'success', 'warning'];

    public static function id(): string { return 'partner_activity'; }

    public static function name(): string { return 'Активность партнёра'; }

    public static function category(): string { return 'partner'; }

    public static function description(): string
    {
        return 'Динамика по годам: КП, спецификации, платежи, сделки или проекты одного партнёра или сравнение до трёх';
    }

    public static function icon(): string { return 'fa-chart-column'; }

    public static function sizes(): array { return ['8x4', '16x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 160; }

    public static function ttl(): int { return 900; }

    public static function fields(): array
    {
        return [
            ['key' => 'partners', 'type' => 'list', 'label' => 'Партнёры', 'default' => [],
                'hint' => 'До трёх партнёров на одном графике; пусто — самый активный по выбранному показателю',
                'options' => fn() => Partner::orderBy('name')->pluck('name', 'id')
                    ->mapWithKeys(fn($name, $id) => [(string) $id => (string) $name])
                    ->all()],
            ['key' => 'metric', 'type' => 'select', 'label' => 'Показатель', 'default' => 'proposals',
                'options' => array_map(fn($metric) => $metric[0], static::METRICS)],
            ['key' => 'years', 'type' => 'number', 'label' => 'Лет', 'default' => 5, 'min' => 2, 'max' => 10],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('analytics.partners');
    }

    /**
     * Образцовые данные: как у живых — партнёр не выбран, значит один ряд (самый активный),
     * выбрано несколько — столько же рядов, до трёх; лет — по настройке
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function sample(array $settings, DesktopContext $ctx): array
    {
        $metric = static::metric($settings);
        $count = max(2, min(10, (int) ($settings['years'] ?? 5)));
        $years = range((int) now()->year - $count + 1, (int) now()->year);

        $chosen = count(array_filter((array) ($settings['partners'] ?? []), fn($id) => is_scalar($id) && ctype_digit((string) $id)));
        $take = max(1, min(static::MAX_PARTNERS, $chosen));

        $base = [
            ['ГК Восток', [4, 9, 7, 12, 10, 14, 11, 16, 13, 18]],
            ['Ташкент-Софт', [2, 5, 6, 4, 8, 7, 9, 6, 10, 9]],
            ['Луч', [1, 3, 2, 5, 4, 6, 3, 7, 5, 8]],
        ];

        $series = [];
        foreach (array_slice($base, 0, $take) as $i => [$name, $values]) {
            $data = array_slice($values, -$count);
            $series[] = [
                'id' => $i + 1, 'name' => $name, 'url' => null, 'color' => static::COLORS[$i],
                'data' => $data, 'total' => array_sum($data),
            ];
        }

        return [
            'metric' => $metric,
            'metric_label' => static::METRICS[$metric][0],
            'unit' => static::METRICS[$metric][1],
            'years' => $years,
            'series' => $series,
            'auto' => $chosen === 0,
            'total' => array_sum(array_column($series, 'total')),
        ];
    }

    /**
     * Динамика по годам для выбранных партнёров
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['metric', 'metric_label', 'unit', 'years', 'auto', 'total',
     *     'series' => [['id', 'name', 'url', 'color', 'data', 'total']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $metric = static::metric($settings);
        $counts = static::counts($metric);

        $ids = collect((array) ($settings['partners'] ?? []))
            ->filter(fn($id) => is_scalar($id) && ctype_digit((string) $id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->take(static::MAX_PARTNERS)
            ->values()
            ->all();

        // удалённого партнёра в настройках держать незачем — он бы дал пустой столбик
        $names = empty($ids) ? collect() : Partner::whereIn('id', $ids)->pluck('name', 'id');
        $ids = array_values(array_filter($ids, fn($id) => isset($names[$id])));

        // партнёр не выбран — берём самого активного по этому показателю за те же «Лет»,
        // что покажет график, а не за всю историю
        $auto = empty($ids);
        if ($auto) {
            $since = (int) now()->year - max(2, (int) $settings['years']) + 1;
            $totals = array_map(
                fn($by_year) => array_sum(array_filter($by_year, fn($year) => $year >= $since, ARRAY_FILTER_USE_KEY)),
                $counts
            );
            $totals = array_filter($totals);
            arsort($totals);
            $ids = array_slice(array_keys($totals), 0, 1);
            $names = empty($ids) ? collect() : Partner::whereIn('id', $ids)->pluck('name', 'id');
        }

        $years = static::years($counts, $ids, (int) $settings['years']);

        $series = [];
        foreach (array_values($ids) as $i => $id) {
            $data = array_map(fn($year) => (int) ($counts[$id][$year] ?? 0), $years);

            $series[] = [
                'id' => $id,
                'name' => (string) ($names[$id] ?? ('#' . $id)),
                'url' => route('partner.detail', $id),
                'color' => static::COLORS[$i] ?? 'info',
                'data' => $data,
                'total' => array_sum($data),
            ];
        }

        return [
            'metric' => $metric,
            'metric_label' => static::METRICS[$metric][0],
            'unit' => static::METRICS[$metric][1],
            'years' => $years,
            'series' => $series,
            'auto' => $auto && !empty($series),
            'total' => array_sum(array_column($series, 'total')),
        ];
    }

    /**
     * Показатель из настроек (неизвестный — КП)
     *
     * @param array $settings
     * @return string
     */
    public static function metric(array $settings): string
    {
        $metric = (string) ($settings['metric'] ?? 'proposals');

        return isset(static::METRICS[$metric]) ? $metric : 'proposals';
    }

    /**
     * Сколько событий показателя у каждого партнёра в каждом году:
     * partner_id => [год => количество]
     *
     * @param string $metric
     * @return array
     */
    public static function counts(string $metric): array
    {
        [$rows, $year_of] = match ($metric) {
            'specs' => [PartnerScoringService::specifications(), fn($row) => PartnerScoringService::specYear($row)],
            'payments' => [PartnerScoringService::payments(), fn($row) => ($row->date_fact ?? $row->date_plan)?->year],
            'deals' => [PartnerScoringService::deals(), fn($row) => $row->year_ref],
            // проекты — по дате начала, архивные тоже, как в скоринге (patch v26)
            'projects' => [PartnerScoringService::projects(), fn($row) => $row->year_ref],
            default => [PartnerScoringService::proposals(), fn($row) => $row->year_ref],
        };

        $out = [];

        foreach ($rows as $row) {
            $partner_id = (int) ($row->partner_id ?? 0);
            $year = $year_of($row);
            if (empty($partner_id) || empty($year)) continue;

            $out[$partner_id][(int) $year] = ($out[$partner_id][(int) $year] ?? 0) + 1;
        }

        return $out;
    }

    /**
     * Годы графика: $limit лет подряд по текущий (или позже, если есть плановые платежи),
     * но не раньше первого года, в котором у выбранных партнёров что-то было.
     *
     * Год без событий остаётся нулевым столбиком: раньше такие годы выпадали, и
     * 2023 → 2025 выглядело как два соседних года
     *
     * @param array $counts
     * @param array $ids
     * @param int $limit
     * @return array по возрастанию, без пропусков, не меньше двух лет
     */
    public static function years(array $counts, array $ids, int $limit): array
    {
        $limit = max(2, $limit);
        $now = (int) now()->year;

        $years = [];
        foreach ($ids as $id) {
            foreach (array_keys($counts[$id] ?? []) as $year) $years[] = (int) $year;
        }

        $last = empty($years) ? $now : max($now, max($years));
        $first = $last - $limit + 1;
        if (!empty($years)) {
            $first = min(max($first, min($years)), $last - 1);
        }

        return range($first, $last);
    }
}
