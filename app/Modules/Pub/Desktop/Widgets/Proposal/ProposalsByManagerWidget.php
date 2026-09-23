<?php

namespace App\Modules\Pub\Desktop\Widgets\Proposal;

use App\Modules\Pub\Desktop\Metrics\MetricRegistry;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Services\ProposalStatusService;
use Illuminate\Support\Collection;

/**
 * КП по менеджерам (patch v30): разрез КП по ответственным — количество, сумма
 * и выигранные, за период стола или за всю историю.
 *
 * Ответственный — manager_id последней редакции (страница списка КП раскладывает
 * КП по вкладкам менеджеров по этому же полю). Период — по дате отправки первой
 * редакции (MetricRegistry::groupsSentBetween), как в виджете «КП по статусам».
 * Сумма — основной вариант последней редакции, приведённый к валюте стола курсом
 * на сегодня (MetricRegistry::mainSum); суммы без курса в итог не попадают.
 */
class ProposalsByManagerWidget extends Widget
{
    public static function id(): string { return 'proposals_by_manager'; }

    public static function name(): string { return 'КП по менеджерам'; }

    public static function category(): string { return 'proposal'; }

    public static function description(): string
    {
        return 'Кто сколько ведёт КП: количество, сумма и выигранные по каждому менеджеру';
    }

    public static function icon(): string { return 'fa-users'; }

    public static function sizes(): array { return ['8x4', '16x8', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 210; }

    public static function usesPeriod(): bool { return true; }

    public static function usesCurrency(): bool { return true; }

    public static function ttl(): int { return 600; }

    public static function fields(): array
    {
        return [
            ['key' => 'scope', 'type' => 'select', 'label' => 'Какие КП', 'default' => 'period',
                'options' => ['period' => 'Отправленные в периоде', 'all' => 'Все'],
                'hint' => 'Период — по дате отправки первой редакции'],
            ['key' => 'metric', 'type' => 'select', 'label' => 'Показатель', 'default' => 'count',
                'options' => ['count' => 'Количество КП', 'amount' => 'Сумма КП', 'won' => 'Выиграно, шт.']],
            ['key' => 'status', 'type' => 'select', 'label' => 'Статус', 'default' => 'all',
                'options' => fn() => ProposalsRecentWidget::statusOptions()],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько менеджеров', 'default' => 10, 'min' => 3, 'max' => 50],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('proposal.index');
    }

    /**
     * Образцовые данные для превью библиотеки (без запросов к базе)
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function sample(array $settings, DesktopContext $ctx): array
    {
        // 40 менеджеров: высокому блоку есть чем заполниться; имена — сочетания без повторов
        $first = [['Анна', 'Марина', 'Ольга', 'Елена', 'Наталья'], ['Павел', 'Сергей', 'Игорь', 'Дмитрий', 'Алексей']];
        $last = [
            ['Иванова', 'Волкова', 'Смирнова', 'Попова', 'Морозова', 'Орлова', 'Зайцева', 'Громова'],
            ['Головин', 'Панов', 'Лебедев', 'Кузнецов', 'Соколов', 'Орлов', 'Зайцев', 'Громов'],
        ];
        $sample = [];

        for ($i = 0; $i < 40; $i++) {
            $gender = $i % 2;
            $count = max(2, (int) round(34 * 0.93 ** $i));
            $sample[] = [
                $first[$gender][$i % 5] . ' ' . $last[$gender][intdiv($i, 2) % 8],
                $count, intdiv($count, 3), intdiv($count, 6), $count * 1400000.0 + ($i * 37 % 11) * 100000.0,
            ];
        }

        $rows = [];

        foreach ($sample as $i => [$name, $count, $won, $lost, $amount]) {
            $rows[] = [
                'id' => $i + 1, 'name' => $name, 'count' => $count, 'won' => $won, 'lost' => $lost,
                'in_work' => $count - $won - $lost, 'amount' => $amount, 'url' => null,
            ];
        }

        return static::pack($rows, (string) $settings['metric'], $ctx->symbol($ctx->currencyFor($settings)), 'Текущий квартал');
    }

    /**
     * Разрез КП по менеджерам
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [['id', 'name', 'count', 'won', 'lost', 'in_work', 'amount',
     *     'value', 'share', 'url']], 'total', 'total_amount', 'total_won', 'symbol', 'metric',
     *     'period_label', 'managers']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $metric = (string) $settings['metric'];
        $currency = $ctx->currencyFor($settings);
        $period_label = null;

        $proposals = ProposalStatusService::latestIterations();

        if ((string) $settings['scope'] === 'period') {
            $period = $ctx->periodFor($settings);
            $groups = MetricRegistry::groupsSentBetween($period['from'], $period['to'])->flip();
            $proposals = $proposals->filter(fn($row) => $groups->has($row->group));
            $period_label = $period['label'];
        }

        $proposals = ProposalsRecentWidget::applyStatus($proposals, (string) $settings['status']);

        if ($proposals->isEmpty()) {
            return static::pack([], $metric, $ctx->symbol($currency), $period_label);
        }

        // имена менеджеров одним заходом (withTrashed в связи — имя не пропадает у удалённого)
        $proposals->load('manager');

        $rows = $proposals->groupBy(fn($row) => (int) $row->manager_id)
            ->map(function (Collection $group, $manager_id) use ($currency, $metric) {
                $first = $group->first();
                $counters = ProposalStatusService::counters($group);

                return [
                    'id' => (int) $manager_id,
                    'name' => (string) ($first->manager?->full_name ?: $first->manager?->name ?: 'Без менеджера'),
                    'count' => $group->count(),
                    'won' => (int) ($counters[ProposalStatus::WON->value] ?? 0),
                    'lost' => (int) ($counters[ProposalStatus::LOST->value] ?? 0),
                    'in_work' => (int) ($counters[ProposalStatus::IN_WORK->value] ?? 0),
                    // сумма нужна только показателю «Сумма КП» — лишних пересчётов курса не делаем
                    'amount' => $metric === 'amount' ? MetricRegistry::mainSum($group, $currency) : 0.0,
                    'url' => route('proposal.index'),
                ];
            })
            ->values()
            ->all();

        return static::pack($rows, $metric, $ctx->symbol($currency), $period_label, (int) $settings['limit']);
    }

    /**
     * Отсортировать по показателю, посчитать доли и итоги
     *
     * @param array $rows строки менеджеров
     * @param string $metric count | amount | won
     * @param string $symbol символ валюты
     * @param string|null $period_label подпись периода
     * @param int|null $limit сколько менеджеров оставить
     * @return array
     */
    protected static function pack(array $rows, string $metric, string $symbol, ?string $period_label, ?int $limit = null): array
    {
        $value = fn(array $row) => match ($metric) {
            'amount' => (float) $row['amount'],
            'won' => (float) $row['won'],
            default => (float) $row['count'],
        };

        $totals = [
            'total' => array_sum(array_column($rows, 'count')),
            'total_won' => array_sum(array_column($rows, 'won')),
            'total_amount' => array_sum(array_column($rows, 'amount')),
            'managers' => count($rows),
        ];

        usort($rows, fn($a, $b) => $value($b) <=> $value($a) ?: strcmp($a['name'], $b['name']));

        // доля — от лучшего менеджера: полоска показывает разрыв, а не вклад в итог
        $max = empty($rows) ? 0.0 : $value($rows[0]);

        foreach ($rows as $i => $row) {
            $rows[$i]['value'] = $value($row);
            $rows[$i]['share'] = $max > 0 ? round($value($row) / $max * 100, 1) : 0.0;
        }

        if ($limit !== null) {
            $rows = array_slice($rows, 0, max(1, $limit));
        }

        return $totals + [
            'rows' => $rows,
            'symbol' => $symbol,
            'metric' => $metric,
            'period_label' => $period_label,
        ];
    }
}
