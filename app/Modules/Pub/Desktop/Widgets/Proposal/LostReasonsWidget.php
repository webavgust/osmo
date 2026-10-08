<?php

namespace App\Modules\Pub\Desktop\Widgets\Proposal;

use App\Modules\Pub\Desktop\Metrics\MetricRegistry;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Models\ProposalLostReason;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Services\ProposalStatusService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Причины проигрыша (patch v30): из-за чего КП не дошли до сделки —
 * количество, доли и сумма по каждой причине.
 *
 * Считается по последним редакциям групп (ProposalStatusService::latestIterations()),
 * берутся КП в статусе «Проиграно» (v27). Причины — proposals.status_reasons (v44, несколько у КП;
 * до v44 — одна, status_reason)
 * (ProposalLostReason); «Заморожено» и «Отменено» с v27 живут здесь же, а не
 * в статусах. Дата решения — status_changed_at, без неё — дата отправки,
 * как у проигранных в MetricRegistry::decidedDates() (выигрыши датируются
 * иначе, но здесь их нет). Суммы — основные варианты
 * (MetricRegistry::mainSum()) в валюте стола.
 */
class LostReasonsWidget extends Widget
{
    /**
     * Цвета сегментов кольца. Цвет причины (ProposalLostReason::data()) годится
     * для плашки, но у половины причин он одинаковый — в кольце сегменты слились бы,
     * поэтому там цвета раздаются по порядку
     */
    public const PALETTE = ['primary', 'danger', 'warning', 'info', 'success', 'dark', 'gray-500', 'gray-700'];

    public static function id(): string { return 'lost_reasons'; }

    public static function name(): string { return 'Причины проигрыша'; }

    public static function category(): string { return 'proposal'; }

    public static function description(): string
    {
        return 'Проигранные КП по причинам: количество, доли и сумма';
    }

    public static function icon(): string { return 'fa-thumbs-down'; }

    public static function sizes(): array { return ['8x4', '4x4', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 160; }

    public static function usesPeriod(): bool { return true; }

    public static function usesCurrency(): bool { return true; }

    public static function ttl(): int { return 600; }

    public static function fields(): array
    {
        return [
            ['key' => 'scope', 'type' => 'select', 'label' => 'Отбор', 'default' => 'period',
                'options' => ['period' => 'Решённые в периоде', 'all' => 'За всё время'],
                'hint' => 'Дата решения — смена статуса, а у старых записей дата отправки'],
            ['key' => 'mine', 'type' => 'bool', 'label' => 'Только мои (менеджер — я)', 'default' => false],
            ['key' => 'show_sum', 'type' => 'bool', 'label' => 'Показывать суммы', 'default' => true],
            ['key' => 'chart', 'type' => 'bool', 'label' => 'Кольцо долей', 'default' => false,
                'hint' => 'Вместо списка причин; видно в высоком блоке'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('proposal.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $period = $ctx->periodFor($settings);

        // все причины и «не указана»: высокому блоку есть чем заполниться
        $sample = [
            [ProposalLostReason::CANCELED, 9, 24600000.0],
            [ProposalLostReason::PRICE, 7, 18200000.0],
            [ProposalLostReason::COMPETITOR, 6, 11400000.0],
            [ProposalLostReason::BUDGET, 5, 9700000.0],
            [ProposalLostReason::FROZEN, 4, 6300000.0],
            [ProposalLostReason::TIMELINE, 3, 5100000.0],
            [ProposalLostReason::FUNCTIONAL, 3, 4200000.0],
            [ProposalLostReason::NO_RESPONSE, 2, 2900000.0],
            [ProposalLostReason::NO_NEED, 2, 2400000.0],
            [ProposalLostReason::INTERNAL, 1, 1800000.0],
            [ProposalLostReason::OTHER, 1, 900000.0],
            [null, 1, 600000.0],
        ];

        $total = array_sum(array_column($sample, 1));
        $rows = [];

        foreach ($sample as [$case, $count, $amount]) {
            $info = $case?->data() ?? ['label' => 'Причина не указана', 'hint' => 'Статус поставлен без причины', 'color' => 'secondary'];
            $rows[] = static::row((string) $case?->value, $info, $count, $amount, $total);
        }

        return [
            'total' => $total,
            'multi' => 0,
            'amount' => array_sum(array_column($sample, 2)),
            'symbol' => '₽',
            'label' => $period['label'],
            'dates' => $period['dates'],
            'scope_label' => 'решённые в периоде',
            'top' => $rows[0]['label'],
            'rows' => static::palette($rows),
        ];
    }

    /**
     * Проигранные КП по причинам
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['total', 'multi', 'amount', 'symbol', 'label', 'dates', 'scope_label', 'top',
     *     'rows' => [['key', 'label', 'hint', 'color', 'count', 'share', 'amount']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $period = $ctx->periodFor($settings);
        $by_period = (string) $settings['scope'] === 'period';

        $lost = MetricRegistry::latestWithStatus(ProposalStatus::LOST);

        if (!empty($settings['mine'])) {
            $user_id = (int) ($ctx->user?->id ?? 0);
            $lost = $lost->filter(fn($row) => (int) $row->manager_id === $user_id)->values();
        }

        if ($by_period) {
            $lost = static::resolvedBetween($lost, $period['from'], $period['to']);
        }

        // итог — по уникальным КП
        $total = $lost->count();
        $sums = !empty($settings['show_sum']);
        $rows = [];

        // patch v44: у КП может быть несколько причин — КП входит в каждую свою причину
        // (число КП по причине честное, доли в сумме дают больше 100 %), а сумма КП
        // делится между его причинами поровну: суммы по причинам складываются в итог.
        // Корзины [код причины][число причин у КП] — чтобы делить сумму одним запросом на корзину
        $buckets = [];
        foreach ($lost as $row) {
            $codes = array_map(fn(ProposalLostReason $case) => $case->value, $row->reasons_enum) ?: [''];
            foreach ($codes as $code) {
                $buckets[$code][count($codes)][] = $row;
            }
        }

        foreach ($buckets as $key => $by_split) {
            $case = ProposalLostReason::tryFrom((string) $key);
            $info = $case?->data() ?? ['label' => 'Причина не указана', 'hint' => 'Статус поставлен без причины', 'color' => 'secondary'];
            $count = 0;
            $amount = 0.0;

            foreach ($by_split as $split => $group) {
                $count += count($group);
                if ($sums) {
                    $amount += MetricRegistry::mainSum(collect($group), $currency) / $split;
                }
            }

            $rows[] = static::row((string) $key, $info, $count, $amount, $total);
        }

        usort($rows, fn($a, $b) => [$b['count'], $b['amount']] <=> [$a['count'], $a['amount']]);

        return [
            'total' => $total,
            // КП с несколькими причинами — для подписи о делении суммы
            'multi' => $lost->filter(fn($row) => count($row->reasons_enum) > 1)->count(),
            'amount' => $sums ? round(MetricRegistry::mainSum($lost, $currency), 2) : 0.0,
            'symbol' => $ctx->symbol($currency),
            'label' => $by_period ? $period['label'] : 'за всё время',
            'dates' => $period['dates'],
            'scope_label' => $by_period ? 'решённые в периоде' : 'за всё время',
            'top' => $rows[0]['label'] ?? '',
            'rows' => static::palette($rows),
        ];
    }

    /**
     * Раздать сегментам кольца цвета по порядку (см. PALETTE)
     *
     * @param array $rows строки причин, уже отсортированные
     * @return array
     */
    protected static function palette(array $rows): array
    {
        foreach ($rows as $i => $row) {
            $rows[$i]['chart_color'] = static::PALETTE[$i % count(static::PALETTE)];
        }

        return $rows;
    }

    /**
     * Строка причины для вьюхи
     *
     * @param string $key код причины ('' — причина не указана)
     * @param array $info описание из ProposalLostReason::data()
     * @param int $count
     * @param float $amount
     * @param int $total всего проигранных — от него считается доля
     * @return array
     */
    protected static function row(string $key, array $info, int $count, float $amount, int $total): array
    {
        $color = (string) ($info['color'] ?? '');
        // secondary в Metronic почти не виден — как в остальных виджетах КП показываем dark
        $color = in_array($color, ['secondary', 'light', 'white', ''], true) ? 'dark' : $color;

        return [
            'key' => $key,
            'label' => (string) ($info['label'] ?? 'Причина не указана'),
            'hint' => (string) ($info['hint'] ?? ''),
            'color' => $color,
            'count' => $count,
            'share' => $total > 0 ? round($count / $total * 100, 1) : 0.0,
            'amount' => round($amount, 2),
        ];
    }

    /**
     * КП, решение по которым принято в отрезке: по дате смены статуса,
     * а у старых записей — по дате отправки
     *
     * @param Collection $rows
     * @param Carbon $from
     * @param Carbon $to
     * @return Collection
     */
    protected static function resolvedBetween(Collection $rows, Carbon $from, Carbon $to): Collection
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        return $rows->filter(function ($row) use ($from, $to) {
            $date = $row->status_changed_at ?? $row->sended_at;

            return $date !== null && $date->between($from, $to);
        })->values();
    }
}
