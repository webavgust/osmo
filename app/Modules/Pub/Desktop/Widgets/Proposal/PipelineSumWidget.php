<?php

namespace App\Modules\Pub\Desktop\Widgets\Proposal;

use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Metrics\MetricRegistry;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Services\ProposalStatusService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Сумма КП в работе (patch v30): сколько денег лежит в незакрытых КП
 * и как эта сумма разложена по статусам или по менеджерам.
 *
 * Считается по последним редакциям групп (ProposalStatusService::latestIterations()),
 * статусы — три из v27. Сумма КП — его основной вариант (variants: is_main desc, id),
 * как в колонке «Стоимость» списка КП; настройкой можно взять самый дорогой вариант.
 * Пересчёт в валюту стола — MetricRegistry::mainSum(), то есть курсом на сегодня;
 * КП в валюте без курса в сумму не попадают и считаются отдельно.
 *
 * Дельта — к такому же прошлому отрезку (DesktopContext::previousRange()) и имеет
 * смысл только при отборе «отправленные в периоде»: иначе сравнивать не с чем.
 */
class PipelineSumWidget extends Widget
{
    /** Разбивка суммы */
    public const SPLITS = ['none' => 'Без разбивки', 'status' => 'По статусам', 'manager' => 'По менеджерам'];

    public static function id(): string { return 'pipeline_sum'; }

    public static function name(): string { return 'Сумма КП в работе'; }

    public static function category(): string { return 'proposal'; }

    public static function description(): string
    {
        return 'Сумма основных вариантов незакрытых КП в валюте стола с разбивкой по статусам или менеджерам';
    }

    public static function icon(): string { return 'fa-sack-dollar'; }

    public static function sizes(): array { return ['4x2', '4x4', '8x4']; }

    public static function defaultSize(): string { return '4x2'; }

    public static function order(): int { return 150; }

    public static function usesPeriod(): bool { return true; }

    public static function usesCurrency(): bool { return true; }

    public static function ttl(): int { return 300; }

    public static function fields(): array
    {
        return [
            ['key' => 'status', 'type' => 'select', 'label' => 'Какие КП', 'default' => ProposalStatus::IN_WORK->value,
                'options' => fn() => ProposalsRecentWidget::statusOptions()],
            ['key' => 'scope', 'type' => 'select', 'label' => 'Отбор', 'default' => 'period',
                'options' => ['period' => 'Отправленные в периоде', 'all' => 'Все'],
                'hint' => 'Период — по дате отправки первой редакции; сравнение с прошлым отрезком работает только здесь'],
            ['key' => 'mine', 'type' => 'bool', 'label' => 'Только мои (менеджер — я)', 'default' => false],
            ['key' => 'variant', 'type' => 'select', 'label' => 'Вариант КП', 'default' => 'main',
                'options' => ['main' => 'Основной', 'max' => 'Самый дорогой']],
            ['key' => 'split', 'type' => 'select', 'label' => 'Разбивка', 'default' => 'manager', 'options' => static::SPLITS,
                'hint' => 'Видна в высоком или широком блоке; разбивка по статусам имеет смысл при выборе «Все»'],
            ['key' => 'compare', 'type' => 'bool', 'label' => 'Сравнение с прошлым отрезком', 'default' => true],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('proposal.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $period = $ctx->periodFor($settings);
        $split = (string) ($settings['split'] ?? 'manager');
        $total = 53800000.0;
        $items = [];

        if ($split === 'status') {
            foreach (ProposalStatus::cases() as $case) {
                $info = $case->data();
                $color = in_array($info['color'], ['secondary', 'light', 'white', ''], true) ? 'dark' : $info['color'];
                $items[] = [(string) $case->value, (string) $info['label'], $color];
            }
        } elseif ($split === 'manager') {
            // 12 менеджеров: высокому блоку есть чем заполниться
            $names = ['Анна Иванова', 'Пётр Смирнов', 'Ольга Крылова', 'Игорь Лебедев', 'Мария Кузнецова', 'Дмитрий Орлов',
                'Елена Соколова', 'Андрей Волков', 'Наталья Павлова', 'Сергей Морозов', 'Татьяна Новикова', 'Без менеджера'];
            foreach ($names as $i => $name) {
                $items[] = [(string) ($i + 1), $name, 'primary'];
            }
        }

        // доли убывают: каждая следующая строка примерно на пятую часть меньше предыдущей
        $weights = array_map(fn($i) => 0.8 ** $i, array_keys($items));
        $weight_sum = max(0.0001, array_sum($weights));
        $rows = [];

        foreach ($items as $i => [$key, $label, $color]) {
            $share = $weights[$i] / $weight_sum;
            $rows[] = [
                'key' => $key, 'label' => $label, 'color' => $color,
                'amount' => round($total * $share, -3),
                'count' => max(1, (int) round(40 * $share)),
                'share' => round($share * 100, 1),
            ];
        }

        return [
            'total' => $total,
            'previous' => 47200000.0,
            'delta_percent' => 14.0,
            'count' => empty($rows) ? 22 : array_sum(array_column($rows, 'count')),
            'symbol' => '₽',
            'label' => $period['label'],
            'dates' => $period['dates'],
            'status_label' => 'в работе',
            'scope_label' => 'отправленные в периоде',
            'split' => $split,
            'rows' => $rows,
            'skipped' => 0,
        ];
    }

    /**
     * Сумма незакрытых КП, сравнение с прошлым отрезком и разбивка
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['total', 'previous', 'delta_percent', 'count', 'symbol', 'label', 'dates',
     *     'status_label', 'scope_label', 'split', 'rows' => [['key', 'label', 'color', 'amount', 'count', 'share']], 'skipped']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $period = $ctx->periodFor($settings);
        $status = (string) $settings['status'];
        $variant = (string) $settings['variant'];
        $by_period = (string) $settings['scope'] === 'period';

        $all = ProposalsRecentWidget::applyStatus(ProposalStatusService::latestIterations(), $status);

        if (!empty($settings['mine'])) {
            $user_id = (int) ($ctx->user?->id ?? 0);
            $all = $all->filter(fn($row) => (int) $row->manager_id === $user_id)->values();
        }

        $rows = $by_period ? static::sentBetween($all, $period['from'], $period['to']) : $all;

        // сравнивать есть с чем только при отборе по периоду
        $previous = null;
        if ($by_period && !empty($settings['compare'])) {
            [$prev_from, $prev_to] = DesktopContext::previousRange($period['key']);
            $previous = static::sum(static::sentBetween($all, $prev_from, $prev_to), $variant, $currency);
        }

        $total = static::sum($rows, $variant, $currency);

        return [
            'total' => round($total, 2),
            'previous' => $previous === null ? null : round($previous, 2),
            'delta_percent' => $previous === null || $previous <= 0 ? null : round(($total - $previous) / $previous * 100, 1),
            'count' => $rows->count(),
            'symbol' => $ctx->symbol($currency),
            'label' => $by_period ? $period['label'] : 'все',
            'dates' => $period['dates'],
            'status_label' => static::statusLabel($status),
            'scope_label' => $by_period ? 'отправленные в периоде' : 'без отбора по периоду',
            'split' => (string) $settings['split'],
            'rows' => static::split($rows, (string) $settings['split'], $variant, $currency, $total),
            'skipped' => static::skipped($rows, $currency),
        ];
    }

    /**
     * Подпись выбранного статуса для заголовка виджета
     *
     * @param string $status код статуса или 'all'
     * @return string
     */
    protected static function statusLabel(string $status): string
    {
        $case = ProposalStatus::tryFrom($status);

        return $case === null ? 'все КП' : mb_strtolower($case->data()['label']);
    }

    /**
     * КП, первая редакция которых отправлена в отрезке
     *
     * @param Collection $rows последние редакции
     * @param Carbon $from
     * @param Carbon $to
     * @return Collection
     */
    protected static function sentBetween(Collection $rows, Carbon $from, Carbon $to): Collection
    {
        $groups = MetricRegistry::groupsSentBetween($from, $to)->flip();

        return $rows->filter(fn($row) => $groups->has((string) $row->group))->values();
    }

    /**
     * Сумма КП в валюте: основной вариант — через MetricRegistry::mainSum(),
     * самый дорогой — своим запросом с той же сборкой по валютам
     *
     * @param Collection $proposals редакции КП
     * @param string $variant main или max
     * @param string $currency код валюты результата
     * @return float
     */
    protected static function sum(Collection $proposals, string $variant, string $currency): float
    {
        if ($proposals->isEmpty()) return 0.0;
        if ($variant !== 'max') return MetricRegistry::mainSum($proposals, $currency);

        $max = DB::table('proposal_variants')
            ->whereIn('proposal_id', $proposals->pluck('id'))
            ->selectRaw('proposal_id, MAX(cost_total) as cost')
            ->groupBy('proposal_id')
            ->pluck('cost', 'proposal_id');

        // сначала складываем по валютам КП, потом один пересчёт каждой валюты
        $by_currency = [];
        foreach ($proposals as $proposal) {
            if (!$max->has($proposal->id)) continue;

            $slug = (string) $proposal->currency_slug;
            $by_currency[$slug] = ($by_currency[$slug] ?? 0.0) + (float) $max->get($proposal->id);
        }

        $total = 0.0;
        foreach ($by_currency as $slug => $amount) {
            $converted = CurrencyService::convertAmount($amount, $slug, $currency, now());
            if ($converted !== null) {
                $total += $converted;
            }
        }

        return $total;
    }

    /**
     * Разбивка суммы по статусам или по менеджерам
     *
     * @param Collection $proposals редакции КП
     * @param string $split код разбивки
     * @param string $variant main или max
     * @param string $currency
     * @param float $total общая сумма — от неё считается доля полоски
     * @return array [['key', 'label', 'color', 'amount', 'count', 'share']]
     */
    protected static function split(Collection $proposals, string $split, string $variant, string $currency, float $total): array
    {
        if ($split === 'none' || $proposals->isEmpty()) return [];

        if ($split === 'manager') {
            $proposals->load('manager');
            $groups = $proposals->groupBy(fn($row) => (string) ($row->manager_id ?? 0));
        } else {
            $groups = $proposals->groupBy(fn($row) => (string) ($row->status ?? ProposalStatus::IN_WORK->value));
        }

        $rows = [];

        foreach ($groups as $key => $group) {
            $amount = static::sum($group, $variant, $currency);

            if ($split === 'manager') {
                $first = $group->first();
                $label = (string) ($first->manager?->full_name ?? $first->manager?->name ?? 'Без менеджера');
                $color = 'primary';
            } else {
                $case = ProposalStatus::tryFrom((string) $key) ?? ProposalStatus::IN_WORK;
                $info = $case->data();
                $label = $info['label'];
                // secondary в Metronic почти не виден — как в остальных виджетах КП показываем dark
                $color = in_array($info['color'], ['secondary', 'light', 'white', ''], true) ? 'dark' : $info['color'];
            }

            $rows[] = [
                'key' => (string) $key,
                'label' => $label,
                'color' => $color,
                'amount' => round($amount, 2),
                'count' => $group->count(),
                'share' => $total > 0 ? round($amount / $total * 100, 1) : 0.0,
            ];
        }

        usort($rows, fn($a, $b) => $b['amount'] <=> $a['amount']);

        return $rows;
    }

    /**
     * Сколько КП выпало из суммы: их валюту не к чему пересчитать
     *
     * @param Collection $proposals
     * @param string $currency
     * @return int
     */
    protected static function skipped(Collection $proposals, string $currency): int
    {
        $known = [];
        $skipped = 0;

        foreach ($proposals as $proposal) {
            $slug = (string) $proposal->currency_slug;

            if (!array_key_exists($slug, $known)) {
                $known[$slug] = CurrencyService::getConvertRateForDate(now(), $slug, $currency) !== null;
            }

            if (!$known[$slug]) $skipped++;
        }

        return $skipped;
    }
}
