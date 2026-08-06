<?php

namespace App\Modules\Pub\ProposalTools\Services;

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
     * Итерации КП со суммами и отклонениями от предыдущей
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

        $prev = null;

        return $proposals->map(function ($proposal) use (&$prev) {
            $variant = static::mainVariant($proposal);

            $row = [
                'proposal' => $proposal,
                'iteration' => (int) $proposal->iteration,
                'sended_at' => $proposal->sended_at,
                'manager' => $proposal->manager?->name,
                'currency' => $proposal->currency_slug ?: 'RUB',
                'variant' => $variant,
                'period' => static::period($variant),
                'blocks' => [],
                'nds' => (float) ($variant->nds_cost_total ?? 0),
                'total' => (float) ($variant->cost_total ?? 0),
                'diff' => null,
                'diff_p' => null,
            ];

            foreach (static::blocks() as $code => $block) {
                $row['blocks'][$code] = (float) ($variant->{$block['total']} ?? 0);
            }

            if ($prev !== null) {
                $row['diff'] = $row['total'] - $prev['total'];
                $row['diff_p'] = $prev['total'] > 0
                    ? round(($row['total'] - $prev['total']) / $prev['total'] * 100, 1)
                    : null;
            }

            $prev = $row;

            return $row;
        })->values();
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
     * а название — то, что видит заказчик.
     *
     * @param Proposal $from
     * @param Proposal $to
     * @return Collection
     */
    public static function diff(Proposal $from, Proposal $to): Collection
    {
        $left = static::positions($from)->keyBy('key');
        $right = static::positions($to)->keyBy('key');

        $keys = $left->keys()->merge($right->keys())->unique();

        return $keys->map(function ($key) use ($left, $right) {
            $a = $left->get($key);
            $b = $right->get($key);

            $state = match (true) {
                empty($a) => 'added',
                empty($b) => 'removed',
                abs($a['total'] - $b['total']) > 1 => 'changed',
                abs($a['cost'] - $b['cost']) > 1 => 'changed',
                (float) $a['count'] !== (float) $b['count'] => 'changed',
                default => 'same',
            };

            return [
                'block' => $a['block'] ?? $b['block'],
                'label' => $a['label'] ?? $b['label'],
                'state' => $state,
                'from' => $a,
                'to' => $b,
                'diff' => ($b['total'] ?? 0) - ($a['total'] ?? 0),
                'diff_p' => !empty($a['total'])
                    ? round((($b['total'] ?? 0) - $a['total']) / $a['total'] * 100, 1)
                    : null,
            ];
        })
        ->sortBy([['block', 'asc'], ['label', 'asc']])
        ->values();
    }
}
