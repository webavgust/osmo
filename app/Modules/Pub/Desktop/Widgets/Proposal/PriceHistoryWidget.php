<?php

namespace App\Modules\Pub\Desktop\Widgets\Proposal;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\ProposalTools\Services\ProposalPriceHistoryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * История цен (patch v30): как двигалась цена выбранного КП — по редакциям
 * или по позициям последнего изменения.
 *
 * Считает ProposalPriceHistoryService — тот же сервис, что рисует страницу
 * «История цен», поэтому суммы и отклонения сходятся с ней. Цена редакции —
 * её основной вариант; валюту выбирает сам сервис (mode()): все редакции в одной
 * валюте — считаем в ней, разные — приводим к рублям. Поэтому валюта стола тут
 * не участвует: иначе цифры разошлись бы со страницей.
 *
 * КП выбирается в настройках поиском. Если не выбрано — берём последнее
 * изменённое КП, у которого больше одной редакции: без этого виджет на свежем
 * столе пустой.
 */
class PriceHistoryWidget extends Widget
{
    /** Что показывать в списке */
    public const MODES = ['iterations' => 'Редакции КП', 'positions' => 'Позиции последнего изменения'];

    public static function id(): string { return 'price_history'; }

    public static function name(): string { return 'История цен'; }

    public static function category(): string { return 'proposal'; }

    public static function description(): string
    {
        return 'Как менялась цена КП: суммы по редакциям с датами или позиции последнего изменения';
    }

    public static function icon(): string { return 'fa-timeline-arrow'; }

    public static function sizes(): array { return ['8x4', '4x2', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 180; }

    public static function ttl(): int { return 600; }

    public static function fields(): array
    {
        return [
            ['key' => 'target', 'type' => 'entity', 'label' => 'КП', 'entities' => ['proposal'], 'default' => null,
                'hint' => 'Поиск по номеру и названию; пусто — последнее изменённое КП с несколькими редакциями'],
            ['key' => 'mode', 'type' => 'select', 'label' => 'Список', 'default' => 'iterations', 'options' => static::MODES],
            ['key' => 'block', 'type' => 'select', 'label' => 'Блок расчёта', 'default' => 'total',
                'options' => fn() => ['total' => 'Итого'] + collect(ProposalPriceHistoryService::blocks())
                    ->map(fn($block) => $block['label'])
                    ->all()],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько строк', 'default' => 10, 'min' => 3, 'max' => 30],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        $group = static::group($settings);

        return $group === '' ? route('proposal.index') : route('proposal_tools.price_history', $group);
    }

    /**
     * Группа выбранного КП; пусто — последнее изменённое КП с несколькими редакциями
     *
     * @param array $settings
     * @return string
     */
    public static function group(array $settings): string
    {
        $group = (string) ($settings['target']['type'] ?? '') === 'proposal'
            ? trim((string) ($settings['target']['id'] ?? ''))
            : '';

        if ($group !== '') return $group;

        return (string) (DB::table('proposals')
            ->selectRaw('`group`, MAX(updated_at) as changed')
            ->groupBy('group')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('changed')
            ->value('group') ?? '');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $mode = (string) ($settings['mode'] ?? 'iterations');
        $limit = max(1, (int) ($settings['limit'] ?? 10));

        // 12 редакций: цена то падает, то растёт — высокому блоку и графику есть что показать
        $values = [3100000.0, 2870000.0, 2950000.0, 2640000.0, 2710000.0, 2580000.0,
            2800000.0, 2760000.0, 2690000.0, 2720000.0, 2610000.0, 2640000.0];
        $count = count($values);
        $managers = ['Анна Иванова', 'Пётр Смирнов'];
        $rows = [];

        foreach ($values as $i => $value) {
            $diff = $i === 0 ? null : $value - $values[$i - 1];

            $rows[] = [
                'label' => 'ред. ' . ($i + 1),
                'sub' => now()->subDays(($count - $i) * 9)->format('d.m.Y'),
                'note' => $managers[$i % 2],
                'value' => $value,
                'diff' => $diff,
                'diff_p' => $diff === null ? null : round($diff / $values[$i - 1] * 100, 1),
                'delta' => match (true) { $diff === null => 'flat', $diff > 0 => 'up', default => 'down' },
            ];
        }

        $last = $rows[$count - 1];
        $note = '';

        if ($mode === 'positions') {
            $names = ['Лицензия OSMOVIEW Detect, 50 камер', 'Сервер видеоаналитики 2U', 'Пусконаладочные работы на объекте', 'Техническая поддержка, 1 год'];
            $blocks = ['Лицензии', 'Оборудование', 'Внедрение', 'Поддержка'];
            $notes = ['', 'добавлено', 'убрано'];
            $list = [];

            for ($i = 0; $i < 12; $i++) {
                $diff = ($i % 3 === 2 ? -1 : 1) * 15000.0 * ($i + 1);
                $list[] = [
                    'label' => $names[$i % 4] . ($i >= 4 ? ' (' . ($i + 1) . ')' : ''),
                    'sub' => $blocks[$i % 4],
                    'note' => $notes[$i % 3],
                    'value' => 120000.0 * ($i + 1),
                    'diff' => $diff,
                    'diff_p' => round($diff / (120000.0 * ($i + 1)) * 100, 1),
                    'delta' => $diff > 0 ? 'up' : 'down',
                ];
            }

            $note = 'редакции ' . ($count - 1) . ' → ' . $count . ', отличий: 12';
        } else {
            $list = array_reverse($rows);
        }

        return [
            'found' => true,
            'name' => 'Видеоаналитика на складе',
            'number' => '1024',
            'group' => '',
            'url' => null,
            'symbol' => '₽',
            'converted' => false,
            'rate_unknown' => false,
            'block_label' => 'Итого',
            'mode' => $mode,
            'iterations' => $count,
            'current' => $last['value'],
            'diff' => $last['diff'],
            'diff_p' => $last['diff_p'],
            'date' => $last['sub'],
            // как в data(): строк не больше, чем задано в настройке
            'rows' => array_slice($list, 0, $limit),
            'note' => $note,
        ];
    }

    /**
     * История цен выбранного КП
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['found', 'name', 'number', 'group', 'url', 'symbol', 'converted', 'rate_unknown',
     *     'block_label', 'mode', 'iterations', 'current', 'diff', 'diff_p', 'date', 'note',
     *     'rows' => [['label', 'sub', 'note', 'value', 'diff', 'diff_p', 'delta']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $block = (string) $settings['block'];
        $blocks = ProposalPriceHistoryService::blocks();

        $empty = [
            'found' => false, 'name' => '', 'number' => '', 'group' => '', 'url' => null,
            'symbol' => '₽', 'converted' => false, 'rate_unknown' => false,
            'block_label' => (string) ($blocks[$block]['label'] ?? 'Итого'),
            'mode' => (string) $settings['mode'], 'iterations' => 0,
            'current' => null, 'diff' => null, 'diff_p' => null, 'date' => null,
            'rows' => [], 'note' => '',
        ];

        $group = static::group($settings);
        if ($group === '') return $empty;

        $rows = ProposalPriceHistoryService::iterations($group);
        if ($rows->isEmpty()) return $empty;

        // валюты редакций совпадают — считаем в них, иначе приводим к рублям (как на странице)
        $mode = ProposalPriceHistoryService::mode($rows);
        $rows = ProposalPriceHistoryService::apply($rows, $mode['convert']);
        $rows = static::forBlock($rows, $block);

        $last = $rows->last();
        $proposal = $last['proposal'];
        $currencies = DesktopContext::currencies();

        $card = array_merge($empty, [
            'found' => true,
            'name' => (string) $proposal->name,
            'number' => trim((string) $proposal->number),
            'group' => (string) $proposal->group,
            'url' => route('proposal_tools.price_history', $proposal->group),
            'symbol' => (string) ($currencies[$mode['currency']]->symbol ?? $mode['currency']),
            'converted' => (bool) $mode['convert'],
            'rate_unknown' => (bool) $mode['rate_unknown'],
            'iterations' => $rows->count(),
            'current' => round((float) $last['value'], 2),
            'diff' => $last['diff'] === null ? null : round((float) $last['diff'], 2),
            'diff_p' => $last['diff_p'],
            'date' => $last['sended_at']?->format('d.m.Y'),
        ]);

        return (string) $settings['mode'] === 'positions'
            ? array_merge($card, static::positions($rows, $block, (int) $settings['limit']))
            : array_merge($card, ['rows' => static::iterations($rows, (int) $settings['limit'])]);
    }

    /**
     * Подставить в 'value' сумму выбранного блока и пересчитать отклонения.
     * Сервис считает diff по итогу, а блок мы выбираем сами
     *
     * @param Collection $rows строки ProposalPriceHistoryService::apply()
     * @param string $block код блока или 'total'
     * @return Collection
     */
    protected static function forBlock(Collection $rows, string $block): Collection
    {
        if ($block === 'total' || !array_key_exists($block, ProposalPriceHistoryService::blocks())) {
            return $rows;
        }

        $prev = null;

        return $rows->map(function ($row) use ($block, &$prev) {
            $row['value'] = (float) ($row['blocks_value'][$block] ?? 0);
            $row['diff'] = $prev === null ? null : $row['value'] - $prev;
            $row['diff_p'] = $prev === null || $prev <= 0 ? null : round(($row['value'] - $prev) / $prev * 100, 1);
            $prev = $row['value'];

            return $row;
        })->values();
    }

    /**
     * Строки списка: редакции КП, свежие сверху
     *
     * @param Collection $rows
     * @param int $limit
     * @return array
     */
    protected static function iterations(Collection $rows, int $limit): array
    {
        return $rows->reverse()
            ->take($limit)
            ->map(fn($row) => [
                'label' => 'ред. ' . $row['iteration'],
                'sub' => $row['sended_at']?->format('d.m.Y') ?? '—',
                'note' => (string) ($row['manager'] ?? ''),
                'value' => round((float) $row['value'], 2),
                'diff' => $row['diff'] === null ? null : round((float) $row['diff'], 2),
                'diff_p' => $row['diff_p'],
                'delta' => static::delta($row['diff']),
            ])
            ->values()
            ->all();
    }

    /**
     * Строки списка: позиции, которыми отличаются две последние редакции
     *
     * @param Collection $rows строки редакций
     * @param string $block код блока или 'total'
     * @param int $limit
     * @return array ['rows', 'note']
     */
    protected static function positions(Collection $rows, string $block, int $limit): array
    {
        if ($rows->count() < 2) {
            return ['rows' => [], 'note' => 'У КП одна редакция — сравнивать не с чем'];
        }

        $to_row = $rows->last();
        $from_row = $rows->get($rows->count() - 2);

        $from = ProposalRepository::getOnce($to_row['proposal']->group, (int) $from_row['iteration']);
        $to = ProposalRepository::getOnce($to_row['proposal']->group, (int) $to_row['iteration']);

        if (!$from instanceof Proposal || !$to instanceof Proposal) {
            return ['rows' => [], 'note' => 'Редакции для сравнения не нашлись'];
        }

        // две редакции в одной валюте сравниваются в ней, разные — приводятся к рублю
        $convert = ($from_row['currency'] ?? null) !== ($to_row['currency'] ?? null);
        $diff = ProposalPriceHistoryService::diff(
            from: $from,
            to: $to,
            convert: $convert,
            rate_from: (float) ($from_row['rate'] ?? 1),
            rate_to: (float) ($to_row['rate'] ?? 1)
        );

        $blocks = ProposalPriceHistoryService::blocks();

        if (array_key_exists($block, $blocks)) {
            $diff = $diff->where('block', $block)->values();
        }

        $changed = $diff->whereIn('state', ['added', 'removed', 'changed'])->values();

        if ($changed->isEmpty()) {
            return ['rows' => [], 'note' => 'Редакции ' . $from_row['iteration'] . ' и ' . $to_row['iteration'] . ' по позициям не отличаются'];
        }

        $rows = $changed->take($limit)->map(fn($row) => [
            // названия позиций приходят из расчёта КП — в них попадают переносы строк
            'label' => trim((string) preg_replace('/\s+/u', ' ', (string) $row['label'])),
            'sub' => (string) ($blocks[$row['block']]['label'] ?? ''),
            'note' => match ($row['state']) {
                'added' => 'добавлено',
                'removed' => 'убрано',
                default => '',
            },
            'value' => round((float) ($row['to']['value'] ?? 0), 2),
            'diff' => round((float) $row['diff'], 2),
            'diff_p' => $row['diff_p'],
            'delta' => static::delta($row['diff']),
        ])->values()->all();

        return [
            'rows' => $rows,
            'note' => 'редакции ' . $from_row['iteration'] . ' → ' . $to_row['iteration'] . ', отличий: ' . $changed->count(),
        ];
    }

    /**
     * Направление отклонения для класса .desk-delta
     *
     * @param float|null $diff
     * @return string up, down или flat
     */
    protected static function delta(?float $diff): string
    {
        return match (true) {
            $diff === null, abs($diff) < 1 => 'flat',
            $diff > 0 => 'up',
            default => 'down',
        };
    }
}
