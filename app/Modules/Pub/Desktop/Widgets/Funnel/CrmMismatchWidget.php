<?php

namespace App\Modules\Pub\Desktop\Widgets\Funnel;

use App\Modules\Pub\CrmMonitor\Services\CrmMismatchService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Models\ProposalStatus;

/**
 * Расхождения с Битрикс24 (patch v30): сводка страницы «Расхождения с Битрикс24».
 *
 * Данные — CrmMismatchService::rows() / counters() / money(), тот же отбор, что у страницы:
 * сверяется последний созданный вариант последней редакции КП. Валюты КП и сделок
 * НЕ пересчитываются (разная валюта — это само по себе расхождение), поэтому сумма
 * расхождения показывается без символа валюты, как на странице.
 *
 * Допуск суммы берётся из константы crm_amount_tolerance (CrmMismatchService::amountTolerance()),
 * отдельной настройки для него нет — он общий для портала.
 */
class CrmMismatchWidget extends Widget
{
    public static function id(): string { return 'crm_mismatch'; }

    public static function name(): string { return 'Расхождения с Битрикс24'; }

    public static function category(): string { return 'funnel'; }

    public static function description(): string
    {
        return 'КП, у которых сумма, валюта или статус не сходятся со сделкой CRM';
    }

    public static function icon(): string { return 'fa-scale-unbalanced'; }

    public static function sizes(): array { return ['4x2', '8x4', '16x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 1100; }

    public static function ttl(): int { return 900; }

    public static function fields(): array
    {
        return [
            ['key' => 'issue', 'type' => 'select', 'label' => 'Вид расхождения', 'default' => 'all',
                'hint' => 'Счётчики по видам считаются всегда, отбор влияет на крупное число и список',
                'options' => ['all' => 'Любое'] + array_map(fn($issue) => $issue['label'], CrmMismatchService::issues())],
            // по умолчанию — максимум: сколько строк влезет, решает высота блока (подгон .desk-fit)
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько КП в списке', 'default' => 30, 'min' => 1, 'max' => 30,
                'hint' => 'Не больше, чем влезает по высоте блока'],
            ['key' => 'show_money', 'type' => 'bool', 'label' => 'Сумма расхождений', 'default' => true],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        $issue = (string) ($settings['issue'] ?? 'all');

        return route('crm_monitor.index', $issue !== 'all' ? ['issue' => $issue] : []);
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $issues = CrmMismatchService::issues();
        $counts = ['amount' => 12, 'currency' => 3, 'missing' => 4, 'no_deal' => 9, 'stage' => 3, 'no_variant' => 0];

        $base = [
            ['Поставка лицензий, ГК «Восток»', 'ГК «Восток»', 'won', ['amount'], 420000.0, 1],
            ['Пилот OSMO, «Ташкент-Софт»', 'Ташкент-Софт', 'in_work', ['no_deal'], 0.0, 0],
            ['Продление, ООО «Гранит»', 'ООО «Гранит»', 'in_work', ['currency', 'amount'], -85000.0, 1],
            ['Внедрение, АО «Вектор»', 'АО «Вектор»', 'won', ['stage'], 0.0, 2],
            ['Техподдержка, «Балтика-Сервис»', 'Балтика-Сервис', 'in_work', ['amount'], 1250000.0, 1],
            ['Расширение, ТОО «Алтын»', 'ТОО «Алтын»', 'in_work', ['no_deal'], 0.0, 0],
            ['Модуль аналитики, «Минск-Трейд»', 'Минск-Трейд', 'won', ['missing'], 0.0, 1],
            ['Лицензии на год, ООО «Норд»', 'ООО «Норд»', 'in_work', ['amount'], -310000.0, 1],
            ['Обучение, АО «Каспий»', 'АО «Каспий»', 'in_work', ['no_deal'], 0.0, 0],
            ['Миграция данных, «Ереван-Тех»', 'Ереван-Тех', 'won', ['amount', 'stage'], 64000.0, 1],
        ];

        // до 30 КП (максимум настройки), список режется настройкой — как в data()
        $rows = [];
        for ($i = 0; $i < 30; $i++) {
            $row = $base[$i % count($base)];
            // номер КП — как на портале: инициалы менеджера и номер («OD588»)
            array_splice($row, 1, 0, [['OD', 'AA', 'PG', 'AK', 'DS'][$i % 5] . (620 - $i * 3)]);
            $rows[] = $row;
        }
        $rows = array_slice($rows, 0, max(1, (int) $settings['limit']));

        return static::pack($issues, $counts, array_map(fn($row) => [
            'name' => $row[0],
            'number' => $row[1],
            'company' => $row[2],
            'status_label' => ProposalStatus::tryFrom($row[3])?->data()['label'] ?? '—',
            'status_color' => ProposalStatus::tryFrom($row[3])?->data()['color'] ?? 'secondary',
            'codes' => $row[4],
            'labels' => array_map(fn($code) => $issues[$code]['label'], $row[4]),
            'reasons' => array_map(fn($code) => $issues[$code]['hint'], $row[4]),
            'color' => $issues[$row[4][0]]['color'],
            'diff' => $row[5],
            'deals' => $row[6],
            'url' => null,
        ], $rows), [
            'count' => 5, 'proposal_total' => 12400000.0, 'deals_total' => 12735000.0, 'diff' => 335000.0,
        ], (float) CrmMismatchService::AMOUNT_TOLERANCE, (string) ($settings['issue'] ?? 'all'));
    }

    /**
     * Счётчики по видам расхождений, деньги и последние КП
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['total', 'shown', 'issue', 'issue_label', 'tolerance', 'money',
     *                'issues' => [code => ['label', 'color', 'icon', 'hint', 'count']],
     *                'rows' => [['name', 'number', 'company', 'status_label', 'status_color',
     *                            'codes', 'labels', 'reasons', 'color', 'diff', 'deals', 'url']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $catalog = CrmMismatchService::issues();
        $issue = (string) ($settings['issue'] ?? 'all');

        // счётчики — по всем расхождениям, список — по выбранному виду (как на странице)
        $all = CrmMismatchService::rows();
        $rows = $issue !== 'all' && isset($catalog[$issue])
            ? $all->filter(fn($row) => in_array($issue, $row['issue_codes'], true))->values()
            : $all;

        $list = $rows->take((int) $settings['limit'])->map(function ($row) use ($catalog) {
            $proposal = $row['proposal'];
            $codes = $row['issue_codes'];

            return [
                'name' => (string) $proposal->name,
                'number' => (string) ($proposal->number ?? ''),
                'company' => (string) ($proposal->company?->name ?? ''),
                'status_label' => $row['status']?->data()['label'] ?? '—',
                'status_color' => $row['status']?->data()['color'] ?? 'secondary',
                'codes' => $codes,
                'labels' => array_map(fn($code) => $catalog[$code]['label'] ?? $code, $codes),
                'reasons' => array_values($row['issues']),
                'color' => $catalog[$codes[0] ?? '']['color'] ?? 'warning',
                'diff' => in_array('amount', $codes, true) ? round((float) $row['diff'], 2) : 0.0,
                'deals' => $row['links']->count(),
                'url' => route('deal_card.index', $proposal),
            ];
        })->all();

        return static::pack(
            $catalog,
            CrmMismatchService::counters($all),
            $list,
            CrmMismatchService::money($all),
            CrmMismatchService::amountTolerance(),
            $issue,
            $all->count(),
            $rows->count()
        );
    }

    /**
     * Общая сборка данных для живых и образцовых
     *
     * @param array $catalog виды расхождений
     * @param array $counters код => сколько КП
     * @param array $list строки списка
     * @param array $money сводка по деньгам
     * @param float $tolerance допуск суммы
     * @param string $issue выбранный вид расхождения
     * @param int|null $total всего КП с расхождениями
     * @param int|null $shown сколько попало в отбор
     * @return array
     */
    protected static function pack(array $catalog, array $counters, array $list, array $money,
                                   float $tolerance, string $issue, ?int $total = null, ?int $shown = null): array
    {
        $issues = [];
        foreach ($catalog as $code => $row) {
            $issues[$code] = $row + ['count' => (int) ($counters[$code] ?? 0)];
        }

        $known = isset($catalog[$issue]);
        $total ??= max($counters ? max($counters) : 0, count($list));
        $shown ??= $known ? (int) ($counters[$issue] ?? 0) : $total;

        return [
            'total' => (int) $total,
            'shown' => (int) $shown,
            'issue' => $known ? $issue : null,
            'issue_label' => $known ? $catalog[$issue]['label'] : 'расхождений',
            'tolerance' => $tolerance,
            'money' => [
                'count' => (int) ($money['count'] ?? 0),
                'proposal_total' => (float) ($money['proposal_total'] ?? 0),
                'deals_total' => (float) ($money['deals_total'] ?? 0),
                'diff' => (float) ($money['diff'] ?? 0),
            ],
            'issues' => $issues,
            'rows' => array_values($list),
        ];
    }
}
