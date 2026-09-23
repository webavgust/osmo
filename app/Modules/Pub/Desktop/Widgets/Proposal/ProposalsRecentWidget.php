<?php

namespace App\Modules\Pub\Desktop\Widgets\Proposal;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalLink;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Services\ProposalStatusService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Последние КП (patch v30): недавно изменённые КП — номер, компания, статус,
 * сумма основного варианта и ссылка на карточку.
 *
 * Строки — последние редакции групп (ProposalStatusService::latestIterations()),
 * порядок по updated_at. Сумма берётся у основного варианта (variants: is_main desc, id)
 * и показывается в валюте самого КП — ровно как в колонке «Стоимость» списка КП,
 * без пересчёта курса. Статусы — три из v27.
 *
 * Второстепенные КП связки (patch v33) в отбор и счётчики не входят, а, как в списке КП,
 * стоят ветками сразу под строкой своего главного (packTree()).
 */
class ProposalsRecentWidget extends Widget
{
    /** Сколько дней редакция считается свежей */
    public const FRESH_DAYS = 7;

    public static function id(): string { return 'proposals_recent'; }

    public static function name(): string { return 'Последние КП'; }

    public static function category(): string { return 'proposal'; }

    public static function description(): string
    {
        return 'Недавно изменённые КП: номер, компания, статус и сумма основного варианта';
    }

    public static function icon(): string { return 'fa-clock-rotate-left'; }

    public static function sizes(): array { return ['8x8', '8x4', '16x8']; }

    public static function defaultSize(): string { return '8x8'; }

    public static function order(): int { return 120; }

    public static function ttl(): int { return 300; }

    public static function fields(): array
    {
        return [
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько КП', 'default' => 10, 'min' => 3, 'max' => 50],
            ['key' => 'mine', 'type' => 'bool', 'label' => 'Только мои (менеджер — я)', 'default' => false],
            ['key' => 'status', 'type' => 'select', 'label' => 'Статус', 'default' => 'all', 'options' => fn() => static::statusOptions()],
            ['key' => 'show_cost', 'type' => 'bool', 'label' => 'Показывать сумму и валюту', 'default' => true],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('proposal.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $rows = static::sampleRows();

        return [
            'rows' => static::sampleBranch($rows, 1),
            'total' => 128,
            'fresh' => count(array_filter($rows, fn($row) => $row['fresh'])),
        ];
    }

    /**
     * Недавно изменённые КП
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [...], 'total', 'fresh' — изменено за неделю среди всех КП по отбору]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $rows = ProposalStatusService::latestIterations();
        $rows = static::applyStatus($rows, (string) $settings['status']);

        if (!empty($settings['mine'])) {
            $user_id = (int) ($ctx->user?->id ?? 0);
            $rows = $rows->filter(fn($row) => (int) $row->manager_id === $user_id);
        }

        $total = $rows->count();
        // «за неделю» — по всем КП отбора, а не только по показанным: список ограничен настройкой
        $fresh_from = now()->subDays(static::FRESH_DAYS);
        $fresh = $rows->filter(fn($row) => ($row->updated_at ?? $row->created_at)?->gte($fresh_from))->count();

        $take = $rows->sortByDesc(fn($row) => $row->updated_at ?? $row->created_at)
            ->take((int) $settings['limit'])
            ->values();

        return ['rows' => static::packTree($take), 'total' => $total, 'fresh' => $fresh];
    }

    /**
     * Варианты статусов для настройки
     *
     * @return array
     */
    public static function statusOptions(): array
    {
        $options = ['all' => 'Все'];

        foreach (ProposalStatus::cases() as $case) {
            $options[$case->value] = $case->data()['label'];
        }

        return $options;
    }

    /**
     * Отбор по статусу; пустой статус считается «В работе», как в ProposalStatusService::counters()
     *
     * @param Collection $rows
     * @param string $status код статуса или 'all'
     * @return Collection
     */
    public static function applyStatus(Collection $rows, string $status): Collection
    {
        if ($status === 'all' || !ProposalStatus::tryFrom($status)) return $rows->values();

        return $rows->filter(fn($row) => ($row->status ?? ProposalStatus::IN_WORK->value) === $status)->values();
    }

    /**
     * Строки списка для вьюхи. Общая упаковка для всех трёх списочных виджетов КП
     * («Последние КП», «Мои КП», «КП без сделки»)
     *
     * @param Collection $proposals редакции КП
     * @return array [['group', 'number', 'name', 'company', 'partner', 'manager', 'status', 'status_label',
     *     'status_color', 'status_icon', 'amount', 'symbol', 'date', 'updated', 'days', 'fresh',
     *     'url', 'status_url', 'deal_url', 'is_child', 'main_ref']]
     */
    public static function pack(Collection $proposals): array
    {
        if ($proposals->isEmpty()) return [];

        // связи подгружаем одним заходом: список короткий, но без этого будет N+1
        $proposals->load(['company', 'partner', 'variants', 'manager']);

        $currencies = DesktopContext::currencies();

        return $proposals->map(function (Proposal $row) use ($currencies) {
            $status = $row->status_enum;
            $info = $status->data();
            // secondary в Metronic почти не виден — как в виджете «КП по статусам», показываем dark
            $color = in_array($info['color'], ['secondary', 'light', 'white', ''], true) ? 'dark' : $info['color'];

            $variant = $row->variants->first();
            $updated = $row->updated_at ?? $row->created_at;

            return [
                'group' => (string) $row->group,
                'number' => trim((string) $row->number),
                'name' => (string) $row->name,
                'company' => (string) ($row->company?->name ?? ''),
                'partner' => (string) ($row->partner?->name ?? ''),
                'manager' => (string) ($row->manager?->full_name ?? $row->manager?->name ?? ''),
                'status' => $status->value,
                'status_label' => $info['label'],
                'status_color' => $color,
                'status_icon' => $info['icon'],
                // нулевая сумма — «нет расчёта», как прочерк в колонке «Стоимость» списка КП
                'amount' => !empty($variant?->cost_total) ? (float) $variant->cost_total : null,
                'symbol' => (string) ($currencies[(string) $row->currency_slug]->symbol ?? $row->currency_slug),
                'date' => $row->sended_at?->format('d.m.Y'),
                'updated' => $updated?->format('d.m.Y'),
                'days' => $row->sended_at ? (int) $row->sended_at->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null,
                'fresh' => $updated !== null && $updated->gte(now()->subDays(static::FRESH_DAYS)),
                'url' => route('proposal.detail', [$row->group, $row->iteration]),
                'status_url' => route('proposal.box_status', [$row->group, $row->iteration]),
                'deal_url' => route('proposal.box_deal', [$row->group, $row->iteration]),
                'is_child' => false,
                'main_ref' => null,
            ];
        })->all();
    }

    /**
     * Строки списка с ветками второстепенных КП (patch v33). В отбор и счётчики второстепенные
     * не входят (counted()), но, как в списке КП (ProposalService::tableDefault()), стоят сразу
     * под строкой своего главного — приглушены, без смены статуса и привязки сделки
     *
     * @param Collection $proposals КП отбора (последние редакции, без второстепенных)
     * @return array строки pack(); у веток is_child = true и main_ref — «№ AA793» главного
     */
    public static function packTree(Collection $proposals): array
    {
        if ($proposals->isEmpty()) return [];

        $proposals = new EloquentCollection($proposals->all());
        $proposals->load('secondary_links');

        // ветки — одним запросом на весь список
        $groups = $proposals->flatMap->secondary_links->pluck('secondary_group')->unique()->values();
        $children = $groups->isEmpty() ? collect() : Proposal::query()
            ->whereIn('proposals.group', $groups->all())
            ->latestIteration()
            ->get()
            ->keyBy('group');

        $items = new EloquentCollection();
        $mains = [];

        foreach ($proposals as $row) {
            $items->push($row);
            $mains[] = null;

            foreach ($row->secondary_links as $link) {
                $child = $children->get($link->secondary_group);
                if (!$child) continue;

                $items->push($child);
                $mains[] = $row;
            }
        }

        $rows = static::pack($items);

        foreach ($rows as $i => $row) {
            if ($mains[$i] === null) continue;

            // второстепенное только для просмотра: статус и сделку у него не меняют
            $rows[$i]['is_child'] = true;
            $rows[$i]['main_ref'] = ProposalLink::refOf($mains[$i]);
            $rows[$i]['status_url'] = null;
            $rows[$i]['deal_url'] = null;
        }

        return $rows;
    }

    /**
     * Образцовые строки для превью библиотеки — общие для списочных виджетов КП
     *
     * @param string|null $status принудительный статус
     * @return array
     */
    public static function sampleRows(?string $status = null): array
    {
        $sample = [
            ['1024', 'Видеоаналитика на складе', 'ООО «Альфа»', 'ГК Восток', 'won', 4800000, 3, 12],
            ['1023', 'Пилот на проходной', 'АО «Вектор»', 'Ташкент-Софт', 'in_work', 1250000, 6, 21],
            ['1021', 'Контроль периметра', 'ООО «Гранит»', 'Луч', 'in_work', 3100000, 9, 34],
            ['1018', 'Распознавание номеров', 'ООО «Дельта»', 'Алмаз', 'lost', 760000, 15, 48],
            ['1015', 'Платформа и 4 сценария', 'ПАО «Енисей»', 'Норд', 'in_work', 9400000, 24, 61],
        ];

        // ещё 35 строк: высокому блоку есть чем заполниться (номера, компании и суммы — по кругу)
        $names = ['Учёт посетителей', 'Камеры на парковке', 'Досмотр грузовиков', 'Лицензии на год', 'Расширение сценариев', 'Контроль СИЗ', 'Поиск по лицам'];
        $companies = ['ООО «Орион»', 'АО «Сигма»', 'ООО «Каскад»', 'ПАО «Волга»', 'ООО «Меридиан»', 'АО «Кристалл»', 'ООО «Радуга»', 'ООО «Прогресс»'];
        $partners = ['ГК Восток', 'Луч', 'Алмаз', 'Норд', 'Ташкент-Софт'];
        $codes = ['in_work', 'in_work', 'won', 'in_work', 'lost'];

        for ($i = 0; $i < 35; $i++) {
            $sample[] = [
                (string) (1012 - $i * 3), $names[$i % 7], $companies[$i % 8], $partners[$i % 5], $codes[$i % 5],
                600000 + ($i * 7919 % 97) * 90000, 25 + $i * 4, 64 + $i * 6,
            ];
        }

        $rows = [];

        foreach ($sample as $i => [$number, $name, $company, $partner, $code, $amount, $updated_days, $days]) {
            $case = ProposalStatus::tryFrom($status ?? $code) ?? ProposalStatus::IN_WORK;
            $info = $case->data();
            $color = in_array($info['color'], ['secondary', 'light', 'white', ''], true) ? 'dark' : $info['color'];

            $rows[] = [
                'group' => 'sample-' . $i,
                'number' => $number,
                'name' => $name,
                'company' => $company,
                'partner' => $partner,
                'manager' => 'Анна Иванова',
                'status' => $case->value,
                'status_label' => $info['label'],
                'status_color' => $color,
                'status_icon' => $info['icon'],
                'amount' => (float) $amount,
                'symbol' => '₽',
                'date' => now()->subDays($days)->format('d.m.Y'),
                'updated' => now()->subDays($updated_days)->format('d.m.Y'),
                'days' => $days,
                'fresh' => $updated_days <= static::FRESH_DAYS,
                'url' => null,
                'status_url' => null,
                'deal_url' => null,
                'is_child' => false,
                'main_ref' => null,
            ];
        }

        return $rows;
    }

    /**
     * Образцовая ветка второстепенного КП (patch v33) под строкой $after — превью показывает дерево,
     * как живой список со связкой
     *
     * @param array $rows строки sampleRows()
     * @param int $after индекс строки главного КП
     * @return array
     */
    public static function sampleBranch(array $rows, int $after = 1): array
    {
        if (!isset($rows[$after])) return $rows;

        $main = $rows[$after];
        $child = array_merge($main, [
            'group' => $main['group'] . '-secondary',
            'number' => (string) ((int) $main['number'] - 260),
            'amount' => $main['amount'] !== null ? round($main['amount'] * 0.9, -4) : null,
            'updated' => now()->subDays(128)->format('d.m.Y'),
            'fresh' => false,
            'is_child' => true,
            'main_ref' => '№ ' . $main['number'],
        ]);

        array_splice($rows, $after + 1, 0, [$child]);

        return $rows;
    }
}
