<?php

namespace App\Modules\Bitrix\Dashboard\Services;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\Desktop\Widgets\Funnel\FunnelTableWidget;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Отчёт «Движение сделок за год» (patch v50, решения владельца 16.11.2026).
 *
 * Источник — зеркало Битрикс24: crm_deal + crm_deal_stage_history. Группы стадий:
 * в работе — semantic P, кроме Suspended; отвалились — F и Suspended («пауза = отвалились»);
 * закончились — S. Сделки менеджеров DashboardDataService::EXCLUDED_MANAGERS не учитываются.
 * Данных мало (~430 сделок, ~1500 записей истории), поэтому всё читается двумя запросами
 * и разбирается в PHP.
 */
class DealMovementService
{
    public const GROUP_WORK = 'work';
    public const GROUP_LOST = 'lost';
    public const GROUP_DONE = 'done';

    /** Стадия «на паузе»: семантика P, но для отчёта — «отвалились» */
    public const STAGE_SUSPENDED = 'Suspended';

    /** Срез «сейчас» — по текущей стадии сделки */
    public const AT_NOW = 'now';

    /** Срез «на конец года» — по последней записи истории до 31.12 (для текущего года — до сегодня) */
    public const AT_YEAR_END = 'year_end';

    /** Стадии после FunnelTableWidget::STAGES: хвост воронки P, затем итог S, пауза и отказ */
    public const GANTT_STAGES_TAIL = ['Closing documents', 'Completed', 'Suspended', 'Canceled'];

    /** Цвета стадий диаграммы Ганта (одинаковые в светлой и тёмной теме) */
    public const GANTT_COLOR_SUSPENDED = '#f5a524';
    public const GANTT_COLOR_FAIL = '#e5484d';
    public const GANTT_COLOR_SUCCESS = '#2fb36b';
    public const GANTT_COLOR_UNKNOWN = '#a1a5b7';

    /**
     * Цвета стадий P по месту в воронке (Lead … Closing documents). Не градиент: соседние стадии
     * контрастны по тону и светлоте (тёмный / светлый вперемешку, начиная с чёрного Lead), чтобы стыки читались на полосе.
     * Красный, зелёный и янтарный не используются — они за отказом, успехом и паузой.
     */
    public const GANTT_FUNNEL_COLORS = [
        '#1a1a1a', // Lead — чёрный (решение владельца 16.11)
        '#93c5fd', // Research — светло-голубой
        '#7c3aed', // Presentation — фиолетовый
        '#2dd4bf', // Pilot project — бирюзовый
        '#1e3a8a', // Competition/tender — тёмно-синий
        '#f9a8d4', // TCP — розовый
        '#0f766e', // Contracting — тёмная бирюза
        '#c4b5fd', // Invoice + Specification — лавандовый
        '#92400e', // Execution (PRE-PAYMENT) — коричневый
        '#38bdf8', // Execution (POST-PAYMENT) — небесный
        '#be185d', // Acceptance tests — малиновый
        '#818cf8', // Closing documents — светлый индиго
    ];

    /** @var Collection|null сделки (без исключённых менеджеров), ключ — id */
    private ?Collection $deals = null;

    /** @var array<int, array>|null история по сделкам, по возрастанию даты */
    private ?array $history = null;

    /**
     * Годы для выбора: от года первой сделки до текущего.
     *
     * @return int[]
     */
    public function years(): array
    {
        $current = (int) Carbon::now()->year;
        $first = $this->deals()->min(fn($deal) => (int) substr((string) $deal->date_create, 0, 4)) ?: $current;

        return range((int) $first, $current);
    }

    /**
     * Когорта года: сделки, созданные в году, и в какой они группе на момент среза.
     *
     * @param int $year год создания сделок
     * @param string $at момент среза: self::AT_NOW или self::AT_YEAR_END
     * @param bool $by_manager считать ли разбивку по менеджерам
     * @return array{total: array, managers: array} total — итог; managers — строки по менеджерам (пусто без $by_manager)
     */
    public function cohort(int $year, string $at, bool $by_manager): array
    {
        $cutoff = $this->cutoff($year);
        $total = $this->emptyCohortRow();
        $managers = [];

        foreach ($this->deals() as $deal) {
            if ($this->yearOf($deal->date_create) !== $year) continue;

            $group = $at === self::AT_YEAR_END
                ? $this->groupAt((int) $deal->id, $cutoff) ?? $this->groupOf($deal->stage_semantic_id, $deal->stage_name)
                : $this->groupOf($deal->stage_semantic_id, $deal->stage_name);

            $total['total']++;
            $total[$group]++;

            if ($by_manager) {
                $name = $this->managerName($deal->assigned_by);
                $managers[$name] ??= $this->emptyCohortRow();
                $managers[$name]['total']++;
                $managers[$name][$group]++;
            }
        }

        return [
            'total' => $this->withPercents($total),
            'managers' => $this->sortManagers(array_map(fn($row) => $this->withPercents($row), $managers)),
        ];
    }

    /**
     * Движения за год по всем сделкам: сколько перешли в «отвалились» и в «закончились».
     * Сделка считается один раз на группу — по первому переходу в эту группу в течение года.
     * Переход — запись истории, группа которой отличается от группы предыдущей записи.
     *
     * @param int $year год переходов
     * @param bool $by_manager считать ли разбивку по менеджерам
     * @return array{total: array, managers: array} строки вида [lost => [all, this, past], done => [...]]
     */
    public function movements(int $year, bool $by_manager): array
    {
        $total = $this->emptyMovementRow();
        $managers = [];

        foreach ($this->deals() as $id => $deal) {
            $source = $this->yearOf($deal->date_create) === $year ? 'this' : 'past';
            $counted = [];
            $previous = null;

            foreach ($this->history()[$id] ?? [] as $record) {
                $group = $record['group'];

                if ($group !== $previous && $group !== self::GROUP_WORK
                    && !isset($counted[$group]) && $this->yearOf($record['date']) === $year) {
                    $counted[$group] = true;
                    $total[$group]['all']++;
                    $total[$group][$source]++;

                    if ($by_manager) {
                        $name = $this->managerName($deal->assigned_by);
                        $managers[$name] ??= $this->emptyMovementRow();
                        $managers[$name][$group]['all']++;
                        $managers[$name][$group][$source]++;
                    }
                }

                $previous = $group;
            }
        }

        return ['total' => $total, 'managers' => $managers];
    }

    /**
     * Данные диаграммы Ганта: сделки, у которых в году был переход (запись истории) или хотя бы день
     * в группе «в работе». Простоявшие весь год на паузе (Suspended) или в итоге S/F не попадают.
     *
     * Отрезок стадии — от записи истории до следующей записи (подряд идущие записи одной стадии
     * склеиваются). Последняя запись: S/F — нулевой отрезок («метка конца», final = true),
     * иначе — до сегодня. Сделка без истории — один отрезок от даты создания по текущей стадии.
     * Сделки отсортированы по дате создания.
     *
     * @param int $year год диаграммы
     * @return array{year: int, today: string, deals: array, stages: array}
     *     deals — [id, title, company, manager, created, changed, is_new, group, group_ye, segments[{stage, from, to, final}]];
     *     group — группа сейчас, group_ye — на конец года (как срез self::AT_YEAR_END);
     *     stages — встретившиеся стадии в порядке воронки: [name, semantic, group, color, pause, funnel]; pause — Suspended, рисуется штриховкой;
     *     funnel — место в воронке (FunnelTableWidget::STAGES + Closing documents), -1 — вне воронки
     */
    public function gantt(int $year): array
    {
        $start = sprintf('%04d-01-01', $year);
        $end = sprintf('%04d-12-31', $year);
        $today = Carbon::today()->format('Y-m-d');
        $cutoff = $this->cutoff($year);

        $deals = [];
        $used = [];

        $sorted = $this->deals()->sortBy(fn($deal) => (string) $deal->date_create . sprintf('#%010d', $deal->id));

        foreach ($sorted as $id => $deal) {
            $segments = $this->ganttSegments((int) $id, $deal, $today);

            // в году был переход (начало отрезка) или хотя бы день «в работе»;
            // сделка, весь год простоявшая на паузе / в итоге, не показывается («пауза = отвалились»)
            $visible = false;
            foreach ($segments as $segment) {
                $moved = $segment['from'] >= $start && $segment['from'] <= $end;
                $working = !$segment['final'] && $segment['from'] <= $end && $segment['to'] >= $start
                    && $this->groupOf($segment['semantic'], $segment['stage']) === self::GROUP_WORK;
                if ($moved || $working) {
                    $visible = true;
                    break;
                }
            }
            if (!$visible) continue;

            foreach ($segments as $segment) {
                $used[$segment['stage']] ??= $segment['semantic'];
            }

            $group = $this->groupOf($deal->stage_semantic_id, $deal->stage_name);
            $title = trim((string) $deal->title);
            $company = trim((string) $deal->company_name);

            $deals[] = [
                'id' => (int) $id,
                'title' => $title !== '' ? $title : 'Сделка ' . $id,
                'company' => $company !== '' ? $company : null,
                'manager' => $this->managerName($deal->assigned_by),
                'created' => substr((string) $deal->date_create, 0, 10),
                // последнее изменение — дата последней записи истории (без истории — создание)
                'changed' => substr((string) ($this->history()[$id][count($this->history()[$id] ?? []) - 1]['date'] ?? $deal->date_create), 0, 10),
                'is_new' => $this->yearOf($deal->date_create) === $year,
                'group' => $group,
                'group_ye' => $this->groupAt((int) $id, $cutoff) ?? $group,
                'segments' => array_map(fn($segment) => [
                    'stage' => $segment['stage'],
                    'from' => $segment['from'],
                    'to' => $segment['to'],
                    'final' => $segment['final'],
                ], $segments),
            ];
        }

        return [
            'year' => $year,
            'today' => $today,
            'deals' => $deals,
            'stages' => $this->ganttStages($used),
        ];
    }

    /**
     * Группа отчёта по семантике и названию стадии.
     *
     * @param string|null $semantic P / S / F
     * @param string|null $stage_name название стадии
     * @return string self::GROUP_*
     */
    public function groupOf(?string $semantic, ?string $stage_name): string
    {
        if ($semantic === 'S') return self::GROUP_DONE;
        if ($semantic === 'F' || $stage_name === self::STAGE_SUSPENDED) return self::GROUP_LOST;

        return self::GROUP_WORK;
    }

    /**
     * Сделки без исключённых менеджеров (один запрос, кешируется в экземпляре).
     */
    private function deals(): Collection
    {
        return $this->deals ??= CrmDeal::query()
            ->select(['id', 'date_create', 'title', 'company_name', 'stage_id', 'stage_name', 'stage_semantic_id', 'assigned_by'])
            ->get()
            ->reject(fn($deal) => DashboardDataService::isExcluded($deal->assigned_by))
            ->keyBy('id');
    }

    /**
     * История стадий одним запросом, сгруппированная по сделке (по возрастанию даты и id).
     * Пустое stage_name (бывает у удалённых стадий) восстанавливается по stage_id.
     *
     * @return array<int, array<int, array{date: string, group: string, stage_id: ?string, stage: ?string, semantic: ?string}>>
     */
    private function history(): array
    {
        if ($this->history !== null) return $this->history;

        $rows = CrmDeal::query()->getConnection()
            ->table('crm_deal_stage_history')
            ->select(['id', 'deal_id', 'date_create', 'stage_id', 'stage_name', 'stage_semantic_id'])
            ->whereNotNull('deal_id')
            ->orderBy('date_create')
            ->orderBy('id')
            ->get();

        // справочник «код стадии → название» для записей без названия
        $names = [];
        foreach ($this->deals() as $deal) {
            if ($deal->stage_id && $deal->stage_name) $names[$deal->stage_id] = $deal->stage_name;
        }
        foreach ($rows as $row) {
            if ($row->stage_id && $row->stage_name) $names[$row->stage_id] = $row->stage_name;
        }

        $this->history = [];
        foreach ($rows as $row) {
            $name = $row->stage_name ?: ($names[$row->stage_id] ?? null);
            $this->history[(int) $row->deal_id][] = [
                'date' => (string) $row->date_create,
                'group' => $this->groupOf($row->stage_semantic_id, $name),
                'stage_id' => $row->stage_id,
                'stage' => $name,
                'semantic' => $row->stage_semantic_id,
            ];
        }

        return $this->history;
    }

    /**
     * Отрезки стадий одной сделки для диаграммы Ганта (даты — Y-m-d).
     *
     * @return array<int, array{stage: string, semantic: ?string, from: string, to: string, final: bool}>
     */
    private function ganttSegments(int $deal_id, $deal, string $today): array
    {
        $records = $this->history()[$deal_id] ?? [[
            'date' => (string) $deal->date_create,
            'stage_id' => $deal->stage_id,
            'stage' => $deal->stage_name,
            'semantic' => $deal->stage_semantic_id,
        ]];

        // подряд идущие записи одной стадии склеиваются в один отрезок
        $points = [];
        foreach ($records as $record) {
            $stage = $record['stage'] ?: 'Удалённая стадия (' . ($record['stage_id'] ?: '?') . ')';
            if ($points && end($points)['stage'] === $stage) continue;

            $points[] = [
                'stage' => $stage,
                'semantic' => $record['semantic'],
                'from' => substr((string) $record['date'], 0, 10),
            ];
        }

        $segments = [];
        $last = count($points) - 1;
        foreach ($points as $i => $point) {
            $final = $i === $last && in_array($point['semantic'], ['S', 'F'], true);
            $to = $i < $last ? $points[$i + 1]['from'] : ($final ? $point['from'] : max($today, $point['from']));

            $segments[] = $point + ['to' => $to, 'final' => $final];
        }

        return $segments;
    }

    /**
     * Встретившиеся стадии в порядке воронки и с цветами.
     * Порядок — FunnelTableWidget::STAGES + GANTT_STAGES_TAIL, неизвестные — в конце по алфавиту.
     * P — контрастный набор GANTT_FUNNEL_COLORS по месту в воронке,
     * Suspended — янтарный, F — красный, S — зелёный, неизвестные — серый.
     *
     * @param array<string, ?string> $used название стадии → семантика
     * @return array<int, array{name: string, semantic: ?string, group: string, color: string, pause: bool, funnel: int}>
     */
    private function ganttStages(array $used): array
    {
        $order = array_merge(FunnelTableWidget::STAGES, self::GANTT_STAGES_TAIL);
        $funnel = array_values(array_diff($order, ['Completed', self::STAGE_SUSPENDED, 'Canceled']));

        $rank = fn($name) => ($index = array_search($name, $order, true)) === false ? [1, 0, $name] : [0, $index, $name];
        $names = array_keys($used);
        usort($names, fn($a, $b) => $rank($a) <=> $rank($b));

        $stages = [];
        foreach ($names as $name) {
            $semantic = $used[$name];
            $position = array_search($name, $funnel, true);

            $color = match (true) {
                $semantic === 'S' => self::GANTT_COLOR_SUCCESS,
                $semantic === 'F' => self::GANTT_COLOR_FAIL,
                $name === self::STAGE_SUSPENDED => self::GANTT_COLOR_SUSPENDED,
                $position !== false => $this->funnelColor($position, count($funnel)),
                default => self::GANTT_COLOR_UNKNOWN,
            };

            $stages[] = [
                'name' => $name,
                'semantic' => $semantic,
                'group' => $this->groupOf($semantic, $name),
                'color' => $color,
                'pause' => $name === self::STAGE_SUSPENDED,
                'funnel' => $position === false ? -1 : $position,
            ];
        }

        return $stages;
    }

    /**
     * Цвет стадии P по месту в воронке — из контрастного набора GANTT_FUNNEL_COLORS (по кругу, если стадий больше).
     */
    private function funnelColor(int $position, int $count): string
    {
        return self::GANTT_FUNNEL_COLORS[$position % count(self::GANTT_FUNNEL_COLORS)];
    }

    /**
     * Группа сделки по последней записи истории не позже $cutoff; null — записей нет.
     */
    private function groupAt(int $deal_id, string $cutoff): ?string
    {
        $group = null;

        foreach ($this->history()[$deal_id] ?? [] as $record) {
            if ($record['date'] > $cutoff) break;
            $group = $record['group'];
        }

        return $group;
    }

    /**
     * Момент среза «на конец года»: 31.12 23:59:59, для текущего года — сейчас.
     */
    private function cutoff(int $year): string
    {
        $end = Carbon::create($year, 12, 31, 23, 59, 59);

        return ($end->isFuture() ? Carbon::now() : $end)->format('Y-m-d H:i:s');
    }

    /** Год из даты вида «2025-03-01 10:00:00» */
    private function yearOf($date): int
    {
        return (int) substr((string) $date, 0, 4);
    }

    /** Имя менеджера из assigned_by «[83] Анна Август.» */
    private function managerName(?string $assigned_by): string
    {
        $name = trim(preg_replace('/^\[\d+\]\s*/', '', (string) $assigned_by));

        return $name !== '' ? $name : 'Без менеджера';
    }

    private function emptyCohortRow(): array
    {
        return ['total' => 0, self::GROUP_WORK => 0, self::GROUP_LOST => 0, self::GROUP_DONE => 0];
    }

    private function emptyMovementRow(): array
    {
        $cell = ['all' => 0, 'this' => 0, 'past' => 0];

        return [self::GROUP_LOST => $cell, self::GROUP_DONE => $cell];
    }

    /** Добавить доли групп в процентах (целые, от «всего») */
    private function withPercents(array $row): array
    {
        foreach ([self::GROUP_WORK, self::GROUP_LOST, self::GROUP_DONE] as $group) {
            $row[$group . '_pct'] = $row['total'] ? (int) round($row[$group] * 100 / $row['total']) : 0;
        }

        return $row;
    }

    /** Менеджеры по убыванию числа новых запросов, затем по имени */
    private function sortManagers(array $managers): array
    {
        uksort($managers, fn($a, $b) => [$managers[$b]['total'], $a] <=> [$managers[$a]['total'], $b]);

        return $managers;
    }
}
