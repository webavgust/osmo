<?php

namespace App\Modules\Pub\ProposalTools\Services;

use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * История изменения цен по итерациям КП.
 *
 * Итерации уже хранят полный расчёт: у каждой свой набор вариантов,
 * сценариев, платформы, ПО и работ. Сервис читает основной вариант каждой
 * итерации и показывает, как двигались суммы и отдельные позиции.
 *
 * Про валюту. Редакции одного КП бывают в разных валютах, и вычитать рубли
 * из долларов нельзя. Правило такое:
 *   — все сравниваемые редакции в одной валюте → считаем в ней, ничего
 *     не пересчитываем (даже если это не рубль);
 *   — валюты разные → приводим всё к рублям по курсу на сегодня и показываем
 *     исходную сумму с курсом рядом.
 *
 * Новых таблиц не требуется — считаем по тому, что есть.
 */
class ProposalPriceHistoryService
{
    /**
     * Блоки расчёта: колонки в таблице и группы в сравнении позиций
     *
     * @return array
     */
    public static function blocks(): array
    {
        return [
            'platform' => ['label' => 'Платформа', 'total' => 'platform_cost_total'],
            'neuro' => ['label' => 'Нейросервисы', 'total' => 'neuro_cost_total'],
            'soft' => ['label' => 'ПО', 'total' => 'soft_cost_total'],
            'work' => ['label' => 'Работы', 'total' => 'work_cost_total'],
        ];
    }

    /**
     * Итерации КП: суммы в своей валюте и в рублях
     *
     * @param string $group
     * @return Collection
     */
    public static function iterations(string $group): Collection
    {
        $proposals = Proposal::where('group', $group)
            ->with(['variants', 'manager'])
            ->orderBy('iteration')
            ->get();

        return $proposals->map(function ($proposal) {
            $variant = static::mainVariant($proposal);
            $currency = static::currency($variant, $proposal);
            $rate = static::rate($currency);

            $row = [
                'proposal' => $proposal,
                'iteration' => (int) $proposal->iteration,
                'sended_at' => $proposal->sended_at,
                'manager' => $proposal->manager?->name,
                'currency' => $currency,
                'rate' => $rate['value'],
                'rate_unknown' => $rate['unknown'],
                'variant' => $variant,
                'period' => static::period($variant),
                'blocks' => [],
                'blocks_rub' => [],
                'nds' => (float) ($variant->nds_cost_total ?? 0),
                'total' => (float) ($variant->cost_total ?? 0),
            ];

            $row['nds_rub'] = $row['nds'] * $row['rate'];
            $row['total_rub'] = $row['total'] * $row['rate'];

            foreach (static::blocks() as $code => $block) {
                $sum = (float) ($variant->{$block['total']} ?? 0);
                $row['blocks'][$code] = $sum;
                $row['blocks_rub'][$code] = $sum * $row['rate'];
            }

            return $row;
        })->values();
    }

    /**
     * В какой валюте показывать историю
     *
     * @param Collection $rows
     * @return array ['convert' => bool, 'currency' => 'RUB', 'currencies' => [...]]
     */
    public static function mode(Collection $rows): array
    {
        $currencies = $rows->pluck('currency')->filter()->unique()->values();
        $convert = $currencies->count() > 1;

        return [
            'convert' => $convert,
            'currency' => $convert ? Currency::CURRENCY_DEFAULT : ($currencies->first() ?: Currency::CURRENCY_DEFAULT),
            'currencies' => $currencies->all(),
            'rate_unknown' => $convert && $rows->where('rate_unknown', true)->isNotEmpty(),
        ];
    }

    /**
     * Отображаемые суммы и отклонение от предыдущей редакции
     *
     * @param Collection $rows
     * @param bool $convert Приводить к рублям
     * @return Collection
     */
    public static function apply(Collection $rows, bool $convert): Collection
    {
        $prev = null;

        return $rows->map(function ($row) use ($convert, &$prev) {
            $row['value'] = $convert ? $row['total_rub'] : $row['total'];
            $row['nds_value'] = $convert ? $row['nds_rub'] : $row['nds'];
            $row['blocks_value'] = $convert ? $row['blocks_rub'] : $row['blocks'];
            // пересчитали — показываем, из чего получилась сумма
            $row['show_source'] = $convert && $row['currency'] !== Currency::CURRENCY_DEFAULT;

            $row['diff'] = $prev === null ? null : $row['value'] - $prev;
            $row['diff_p'] = $prev === null || $prev <= 0
                ? null
                : round(($row['value'] - $prev) / $prev * 100, 1);

            $prev = $row['value'];

            return $row;
        })->values();
    }

    /**
     * Курс валюты к рублю на сегодня
     *
     * @param string $currency
     * @return array ['value' => float, 'unknown' => bool]
     */
    public static function rate(string $currency): array
    {
        $rate = CurrencyService::getConvertRateForDate(now(), $currency, Currency::CURRENCY_DEFAULT);

        // курса нет — считаем один к одному и помечаем, чтобы сумма
        // не исчезла из истории молча
        return ['value' => $rate ?? 1.0, 'unknown' => $rate === null];
    }

    /**
     * Основной вариант итерации (в нём и живёт цена, которую видит заказчик)
     *
     * @param Proposal $proposal
     * @return mixed
     */
    public static function mainVariant(Proposal $proposal)
    {
        return $proposal->variants->firstWhere('is_main', true) ?? $proposal->variants->first();
    }

    /**
     * Валюта расчёта
     *
     * @param mixed $variant
     * @param Proposal|null $proposal
     * @return string
     */
    public static function currency($variant, Proposal $proposal = null): string
    {
        return CurrencyService::slug($variant->currency_slug ?? $proposal?->currency_slug);
    }

    /**
     * Период варианта человеческим языком
     *
     * @param mixed $variant
     * @return string
     */
    public static function period($variant): string
    {
        if (empty($variant)) return '—';

        return match ($variant->period_type) {
            'year' => ($variant->period_value ?: 1) . ' год(а)',
            'pilot' => 'пилот ' . ($variant->period_value ?: 1) . ' мес',
            'unlimited' => 'бессрочно',
            default => (string) $variant->period_type,
        };
    }

    /**
     * Позиции основного варианта: ключ → строка расчёта
     *
     * @param Proposal $proposal
     * @return Collection
     */
    public static function positions(Proposal $proposal): Collection
    {
        $variant = static::mainVariant($proposal);
        if (empty($variant)) return collect();

        $ret = collect();

        foreach ($variant->proposal_platforms as $row) {
            $ret->push(static::position('platform', $row->description ?: 'Платформа', $row->count, $row->cost, $row->discount, (float) $row->cost_discount * (float) $row->count));
        }

        foreach ($variant->proposal_scenarios as $row) {
            $label = $row->real_name ?: ($row->mnemonic_name ?: 'Сценарий #' . $row->scenario_id);
            $ret->push(static::position('neuro', $label, $row->count, $row->cost, $row->discount, (float) $row->cost_discount * (float) $row->count));
        }

        foreach ($variant->proposal_software as $row) {
            $label = $row->proposal_software?->description ?: 'ПО';
            $ret->push(static::position('soft', $label, $row->count, $row->cost, $row->discount_customer ?? 0, (float) $row->total));
        }

        foreach ($variant->proposal_works as $row) {
            $label = $row->proposal_work?->description ?: 'Работы';
            $ret->push(static::position('work', $label, $row->count, $row->cost, $row->discount_customer ?? 0, (float) $row->total));
        }

        return $ret;
    }

    /**
     * Строка позиции
     *
     * @return array
     */
    protected static function position(string $block, string $label, $count, $cost, $discount, $total): array
    {
        $label = trim(Str::limit(strip_tags(str_replace(['<br>', '<br />', '</p>'], ' ', (string) $label)), 90));

        return [
            'block' => $block,
            'label' => $label !== '' ? $label : '—',
            'key' => $block . '|' . mb_strtolower($label),
            'count' => (float) $count,
            'cost' => (float) $cost,
            'discount' => (float) $discount,
            'total' => (float) $total,
        ];
    }

    /**
     * Сравнение двух итераций по позициям
     *
     * Позиции сопоставляем по названию внутри блока: id у новой итерации свои,
     * а название — то, что видит заказчик. Если валюты редакций различаются,
     * суммы приводятся к рублям — иначе Δ считалась бы между рублями и валютой.
     *
     * @param Proposal $from
     * @param Proposal $to
     * @param bool $convert Приводить к рублям
     * @param float $rate_from Курс валюты редакции «из»
     * @param float $rate_to Курс валюты редакции «в»
     * @return Collection
     */
    public static function diff(Proposal $from, Proposal $to, bool $convert = false, float $rate_from = 1.0, float $rate_to = 1.0): Collection
    {
        $left = static::positions($from)->keyBy('key');
        $right = static::positions($to)->keyBy('key');

        $keys = $left->keys()->merge($right->keys())->unique();

        return $keys->map(function ($key) use ($left, $right, $convert, $rate_from, $rate_to) {
            $a = $left->get($key);
            $b = $right->get($key);

            if ($a) $a['value'] = $convert ? $a['total'] * $rate_from : $a['total'];
            if ($b) $b['value'] = $convert ? $b['total'] * $rate_to : $b['total'];

            $from_value = (float) ($a['value'] ?? 0);
            $to_value = (float) ($b['value'] ?? 0);

            $state = match (true) {
                empty($a) => 'added',
                empty($b) => 'removed',
                abs($from_value - $to_value) > 1 => 'changed',
                (float) $a['count'] !== (float) $b['count'] => 'changed',
                default => 'same',
            };

            return [
                'block' => $a['block'] ?? $b['block'],
                'label' => $a['label'] ?? $b['label'],
                'state' => $state,
                'from' => $a,
                'to' => $b,
                'diff' => $to_value - $from_value,
                'diff_p' => $from_value > 0 ? round(($to_value - $from_value) / $from_value * 100, 1) : null,
            ];
        })
        ->sortBy([['block', 'asc'], ['label', 'asc']])
        ->values();
    }

    /**
     * Итого по сравнению позиций
     *
     * @param Collection $diff
     * @return array
     */
    public static function diffTotal(Collection $diff): array
    {
        $from = (float) $diff->sum(fn($item) => (float) ($item['from']['value'] ?? 0));
        $to = (float) $diff->sum(fn($item) => (float) ($item['to']['value'] ?? 0));

        return [
            'from' => $from,
            'to' => $to,
            'diff' => $to - $from,
            'diff_p' => $from > 0 ? round(($to - $from) / $from * 100, 1) : null,
            'added' => $diff->where('state', 'added')->count(),
            'removed' => $diff->where('state', 'removed')->count(),
            'changed' => $diff->where('state', 'changed')->count(),
        ];
    }
}
