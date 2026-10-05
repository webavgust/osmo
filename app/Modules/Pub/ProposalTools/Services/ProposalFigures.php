<?php

namespace App\Modules\Pub\ProposalTools\Services;

/**
 * Числа позиций варианта КП для документов: оба шаблона PDF и Excel.
 *
 * Итоги варианта хранятся округлёнными по блокам (ProposalRepository::create_variants_new):
 * скидка партнёра на платформу и нейросервисы снимается с суммы блока и
 * округляется один раз, а цена сценария за единицу хранится в целых рублях.
 * Если выводить строки из хранимых цен, сумма строк расходится с ИТОГО —
 * на рубль, а у нейросервисов с большим числом потоков и на сотни.
 *
 * Поэтому каждая строка считается точно, по формулам карточки, а в целые
 * единицы валюты переводится методом наибольшего остатка: строки активных
 * позиций блока в сумме дают ровно итог блока из карточки. Неактивные
 * (жёлтые) позиции ни в один итог не входят и округляются сами по себе.
 */
class ProposalFigures
{
    /** Блоки в порядке PDF: связь варианта, общий процент партнёра, хранимые итоги */
    public const BLOCKS = [
        'soft' => ['relation' => 'proposal_software', 'final' => 'soft_cost_total', 'nds' => 'soft_nds_cost_total'],
        'platform' => ['relation' => 'proposal_platforms', 'partner_p' => 'platform_discount_partner_p', 'final' => 'platform_cost_total', 'nds' => 'platform_nds_cost_total'],
        'neuro' => ['relation' => 'proposal_scenarios', 'partner_p' => 'neuro_discount_partner_p', 'final' => 'neuro_cost_total', 'nds' => 'neuro_nds_cost_total'],
        'work' => ['relation' => 'proposal_works', 'final' => 'work_cost_total', 'nds' => 'work_nds_cost_total'],
    ];

    /**
     * Числа варианта: строки по ключу «блок:id» и итоги блоков.
     *
     * В строке точные суммы (list — по прайсу, client — со скидкой заказчика,
     * final — после всех скидок, nds — НДС с final, client_nds — НДС с client)
     * и они же в целых единицах валюты с суффиксом _out — их и выводить.
     * В итогах блока — суммы _out активных строк; final и nds совпадают
     * с хранимыми итогами варианта.
     *
     * @param \App\Modules\Pub\ProposalVariant\Models\ProposalVariant $variant
     * @return array{rows: array<string, array>, blocks: array<string, array>}
     */
    public static function variant($variant): array
    {
        $rate = (float) ($variant->proposal->nds ?? 0);
        $rows = [];
        $blocks = [];

        foreach (static::BLOCKS as $code => $block) {
            $pp = isset($block['partner_p']) ? (float) $variant->{$block['partner_p']} : 0.0;
            $items = [];

            foreach ($variant->{$block['relation']} ?? [] as $item) {
                $count = (float) $item->count;
                if ($count <= 0) continue;

                $cost = (float) $item->cost;
                $list = $cost * $count;

                if (isset($block['partner_p'])) {
                    // платформа и нейросервисы: скидка заказчику округляется на строке, процент партнёра общий на вариант
                    $client = $list - round($list / 100 * (int) $item->discount);
                    $final = $client * (1 - $pp / 100);
                    $processed = (bool) $item->cb_process;
                } else {
                    // ПО и работы: итог позиции хранится, скидка заказчику — процент от прайса
                    $percent = (float) $item->discount_customer;
                    $client = $percent > 0 ? $list - $list / 100 * $percent : $list;
                    $final = (float) $item->total;
                    $reference = $code === 'soft' ? $item->proposal_software : $item->proposal_work;
                    $processed = (bool) ($reference->cb_process ?? true);
                }

                $items[$code . ':' . $item->id] = [
                    'block' => $code,
                    'id' => $item->id,
                    'processed' => $processed,
                    'count' => $count,
                    'cost' => $cost,
                    'list' => $list,
                    'client' => $client,
                    'final' => $final,
                    'nds' => $item->cb_nds ? $final / 100 * $rate : 0.0,
                    'client_nds' => $item->cb_nds ? $client / 100 * $rate : 0.0,
                    'stored_nds' => (float) $item->nds,
                ];
            }

            $active = array_filter($items, fn($row) => $row['processed']);
            $out = [
                'list' => static::apportion(static::column($active, 'list'), array_sum(array_column($active, 'list'))),
                'client' => static::apportion(static::column($active, 'client'), array_sum(array_column($active, 'client'))),
                'final' => static::apportion(static::column($active, 'final'), (float) $variant->{$block['final']}),
                'client_nds' => static::apportion(static::column($active, 'client_nds'), array_sum(array_column($active, 'client_nds'))),
            ];
            // НДС: если хранимый итог блока недостижим округлением строк — выводим НДС позиций как в карточке
            $nds = static::column($active, 'nds');
            $out['nds'] = static::reachable($nds, (float) $variant->{$block['nds']})
                ? static::apportion($nds, (float) $variant->{$block['nds']})
                : array_map(fn($row) => (int) round($row['stored_nds']), $active);

            $totals = ['list' => 0, 'client' => 0, 'final' => 0, 'nds' => 0, 'client_nds' => 0, 'active' => count($active), 'rows' => count($items)];
            foreach ($items as $key => $row) {
                foreach (['list', 'client', 'final', 'nds', 'client_nds'] as $field) {
                    // неактивная позиция: своё округление, в итог блока не идёт
                    $row[$field . '_out'] = $row['processed'] ? $out[$field][$key] : (int) round($field === 'nds' ? $row['stored_nds'] : $row[$field]);
                    if ($row['processed']) $totals[$field] += $row[$field . '_out'];
                }
                $rows[$key] = $row;
            }
            $blocks[$code] = $totals;
        }

        return ['rows' => $rows, 'blocks' => $blocks];
    }

    /**
     * Числа одной позиции из результата variant()
     *
     * @param array $figures результат variant()
     * @param string $block soft|platform|neuro|work
     * @param mixed $item позиция варианта
     * @return array
     */
    public static function row(array $figures, string $block, $item): array
    {
        return $figures['rows'][$block . ':' . $item->id];
    }

    /**
     * Перевести точные суммы строк в целые так, чтобы они давали заданный итог.
     *
     * Каждая строка округляется вниз или вверх; вверх идут строки с большей
     * дробной частью. Если итог так не получить (хранимый итог устарел и
     * не сходится с позициями), строки просто округляются.
     *
     * @param array<string, float> $values
     * @param float $target
     * @return array<string, int>
     */
    public static function apportion(array $values, float $target): array
    {
        if (!static::reachable($values, $target)) {
            return array_map(fn($value) => (int) round($value), $values);
        }

        $values = array_map(fn($value) => round($value, 4), $values);
        $result = array_map(fn($value) => (int) floor($value), $values);
        $rest = (int) round($target) - array_sum($result);

        $fractions = [];
        foreach ($values as $key => $value) {
            if ($value - $result[$key] > 0) $fractions[$key] = $value - $result[$key];
        }
        arsort($fractions);
        foreach (array_slice(array_keys($fractions), 0, $rest) as $key) {
            $result[$key]++;
        }

        return $result;
    }

    /**
     * Можно ли получить итог, округляя строки вниз или вверх
     *
     * @param array<string, float> $values
     * @param float $target
     * @return bool
     */
    protected static function reachable(array $values, float $target): bool
    {
        $values = array_map(fn($value) => round($value, 4), $values);
        $target = (int) round($target);

        return $target >= array_sum(array_map('floor', $values)) && $target <= array_sum(array_map('ceil', $values));
    }

    /**
     * Поле строк с сохранением ключей
     *
     * @param array $rows
     * @param string $field
     * @return array<string, float>
     */
    protected static function column(array $rows, string $field): array
    {
        return array_map(fn($row) => (float) $row[$field], $rows);
    }
}
