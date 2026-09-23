<?php

namespace App\Modules\Pub\Desktop\Widgets\Funnel;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\DealProject\Models\DealProject;
use App\Modules\Pub\DealProject\Services\DealProjectService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Partner\Models\Partner;
use Illuminate\Support\Carbon;

/**
 * Проекты по сделкам (patch v30): сводка по сущности «Проект» из патча v24.
 *
 * Проекты живут в реестре сделок (своей страницы у них нет), поэтому виджет считает
 * то же, что показывают вкладки реестра: действующие проекты (deal_projects без
 * archived_at), архивные и «ждут сопоставления» — сделки в проектных стадиях
 * (DealProjectService::PROJECT_STAGES), у которых проекта ещё нет, а компания Битрикса
 * не сопоставлена партнёру портала: такой сделке проект не завести (см. DealProjectSeedService).
 *
 * Сумма проекта — сделки Битрикса, привязанные к нему (deal_project_deals), пересчитанные
 * в валюту виджета по курсу на сегодня; сделки без курса в сумму не попадают (skipped).
 * Спецификации считаются по deal_project_specifications.
 */
class ProjectsWidget extends Widget
{
    /** Режимы: что показывать списком */
    public const MODES = [
        'active' => 'Действующие',
        'archive' => 'Архив',
        'waiting' => 'Ждут сопоставления партнёра',
    ];

    public static function id(): string { return 'projects'; }

    public static function name(): string { return 'Проекты по сделкам'; }

    public static function category(): string { return 'funnel'; }

    public static function description(): string
    {
        return 'Сколько проектов по сделкам, их суммы и что ждёт сопоставления';
    }

    public static function icon(): string { return 'fa-diagram-project'; }

    public static function sizes(): array { return ['8x4', '8x8', '16x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 1400; }

    public static function ttl(): int { return 600; }

    public static function usesCurrency(): bool { return true; }

    public static function fields(): array
    {
        return [
            ['key' => 'mode', 'type' => 'select', 'label' => 'Режим', 'default' => 'active', 'options' => static::MODES,
                'hint' => '«Ждут сопоставления» — сделки в проектных стадиях, которым не завести проект: компания Битрикса не сопоставлена партнёру'],
            ['key' => 'partner', 'type' => 'select', 'label' => 'Партнёр', 'default' => 'all',
                'hint' => 'Партнёры, у которых есть проекты; на режим «ждут сопоставления» не влияет — партнёра у таких сделок как раз нет',
                'options' => fn() => ['all' => 'все'] + Partner::query()
                    ->whereIn('id', DealProject::query()->distinct()->pluck('partner_id')->all() ?: [0])
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->map(fn($name) => (string) $name)
                    ->all()],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько строк', 'default' => 8, 'min' => 1, 'max' => 30],
            ['key' => 'show_amount', 'type' => 'bool', 'label' => 'Суммы сделок', 'default' => true],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        $mode = (string) ($settings['mode'] ?? 'active');

        // у проектов нет своей страницы: они живут вкладками реестра сделок
        return match ($mode) {
            'archive' => route('crm-deal.index', ['mode' => CrmDealRegistryService::MODE_ARCHIVE]),
            'waiting' => route('crm-deal.index', ['has_proposal' => 'all', 'stage' => DealProjectService::PROJECT_STAGES]),
            default => route('crm-deal.index', ['mode' => CrmDealRegistryService::MODE_PROJECTS]),
        };
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $mode = (string) ($settings['mode'] ?? 'active');

        // 30 строк (предел настройки «Сколько строк»): высокому блоку есть чем заполниться
        $partners = [['ГК «Восток»', 'Роснефть'], ['Ташкент-Софт', 'UzGas'], ['ООО «Гранит»', 'Гранит-Сервис'],
            ['АО «Вектор»', ''], ['Северсталь-Инфо', 'Северсталь'], ['ТехноПарк', 'КамАЗ'], ['Интегра', 'Сибур'],
            ['Цифровые решения', 'Росатом'], ['Альфа-Системы', ''], ['ВолгаСофт', 'Лукойл']];
        $amounts = [8400000.0, 3150000.0, 5600000.0, 11250000.0, 2700000.0, 6900000.0, 1850000.0, 4300000.0];

        $sample = [];
        for ($i = 0; $i < 30; $i++) {
            [$partner, $company] = $partners[$i % count($partners)];
            $sample[] = [$partner, $company, 12 + $i * 11, 1 + ($i * 3) % 5, ($i * 2) % 5, $amounts[$i % count($amounts)], $i % 4 === 1];
        }

        $rows = array_map(fn($row) => [
            'title' => $mode === 'waiting' ? 'Поставка, ' . $row[0] : 'Проект от ' . now()->subDays($row[2])->format('d.m.Y'),
            'partner' => $mode === 'waiting' ? '' : $row[0],
            'company' => $row[1] ?: $row[0],
            'date' => now()->subDays($row[2])->format('d.m.Y'),
            'deals' => $row[3],
            'specs' => $row[4],
            'amount' => $row[5],
            'pilot' => $row[6],
            'color' => $mode === 'waiting' ? 'warning' : ($row[6] ? 'warning' : 'info'),
            'stage' => 'Invoice + Specification',
            'box_url' => null,
            'deal_url' => null,
        ], $sample);

        return static::pack($mode, $rows, ['active' => 34, 'archive' => 6, 'waiting' => 3], array_sum(array_column($rows, 'amount')), 0, '₽',
            (string) ($settings['partner'] ?? 'all') !== 'all' ? 'ГК «Восток»' : null);
    }

    /**
     * Счётчики проектов и список по выбранному режиму
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['mode', 'mode_label', 'counts', 'total', 'amount', 'skipped', 'symbol',
     *                'partner', 'rows' => [['title', 'partner', 'company', 'date', 'deals',
     *                'specs', 'amount', 'pilot', 'color', 'stage', 'box_url', 'deal_url']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $mode = array_key_exists((string) $settings['mode'], static::MODES) ? (string) $settings['mode'] : 'active';
        $currency = $ctx->currencyFor($settings);
        $partner_id = (string) $settings['partner'] !== 'all' ? (int) $settings['partner'] : null;
        $limit = (int) $settings['limit'];

        $waiting = static::waitingDeals();

        $counts = [
            'active' => DealProject::query()->active()
                ->when($partner_id, fn($builder) => $builder->where('partner_id', $partner_id))->count(),
            'archive' => DealProject::query()->archived()
                ->when($partner_id, fn($builder) => $builder->where('partner_id', $partner_id))->count(),
            'waiting' => $waiting->count(),
        ];

        $partner_name = $partner_id ? (string) (Partner::find($partner_id)?->name ?? '') : null;

        if ($mode === 'waiting') {
            [$rows, $amount, $skipped] = static::waitingRows($waiting, $limit, $currency);

            return static::pack($mode, $rows, $counts, $amount, $skipped, $ctx->symbol($currency), $partner_name);
        }

        // все проекты режима, а не первые $limit: сумма под счётчиком — по всем проектам,
        // как и сам счётчик (раньше она считалась только по показанным строкам)
        $projects = DealProject::query()
            ->when($mode === 'archive', fn($builder) => $builder->archived(), fn($builder) => $builder->active())
            ->when($partner_id, fn($builder) => $builder->where('partner_id', $partner_id))
            ->with(['partner:id,name', 'company:id,name', 'deals'])
            ->withCount(['specifications'])
            ->orderByDesc('id')
            ->get();

        // суммы сделок проектов — одним запросом в базу Битрикса
        $deal_ids = $projects->flatMap(fn(DealProject $project) => $project->dealIds())->unique()->values()->all();
        $deals = empty($deal_ids)
            ? collect()
            : CrmDeal::whereIn('id', $deal_ids)->get(['id', 'opportunity', 'currency_id'])->keyBy('id');

        $total = 0.0;
        $skipped = 0;
        $rows = [];

        foreach ($projects as $project) {
            $amount = 0.0;

            foreach ($project->dealIds() as $deal_id) {
                $deal = $deals->get($deal_id);
                if (empty($deal)) continue;

                $converted = CurrencyService::convertAmount((float) $deal->opportunity, CurrencyService::slug($deal->currency_id), $currency, now());

                if ($converted === null) {
                    $skipped++;
                    continue;
                }

                $amount += $converted;
            }

            $total += $amount;

            if (count($rows) >= $limit) continue;

            $rows[] = [
                'title' => (string) $project->label,
                'partner' => (string) ($project->partner?->name ?? ''),
                'company' => (string) ($project->company?->name ?? ''),
                'date' => $project->date_start?->format('d.m.Y') ?? '—',
                'deals' => $project->deals->count(),
                'specs' => (int) $project->specifications_count,
                'amount' => round($amount, 2),
                'pilot' => (bool) $project->is_pilot,
                'color' => $project->is_archived ? 'secondary' : ($project->is_pilot ? 'warning' : 'info'),
                'stage' => '',
                'box_url' => route('deal_project.box_info', $project),
                'deal_url' => null,
            ];
        }

        return static::pack($mode, $rows, $counts, round($total, 2), $skipped, $ctx->symbol($currency), $partner_name);
    }

    /**
     * Сделки в проектных стадиях, которым не завести проект: проекта нет,
     * а компания Битрикса не сопоставлена партнёру портала
     *
     * @return \Illuminate\Support\Collection
     */
    protected static function waitingDeals()
    {
        $projects = DealProjectService::forDeals();
        $partners = DealProjectService::partnerByCompany();

        return CrmDeal::query()
            ->where('date_create', '>=', CrmDealRegistryService::since())
            ->whereIn('stage_name', DealProjectService::PROJECT_STAGES)
            ->orderByDesc('date_create')
            ->get(['id', 'title', 'company_id', 'company_name', 'stage_name', 'date_create', 'opportunity', 'currency_id'])
            ->filter(fn(CrmDeal $deal) => !$projects->has((int) $deal->id) && empty($partners[(int) $deal->company_id]))
            ->values();
    }

    /**
     * Строки режима «ждут сопоставления»
     *
     * @param \Illuminate\Support\Collection $deals
     * @param int $limit
     * @param string $currency
     * @return array [rows, amount, skipped]
     */
    protected static function waitingRows($deals, int $limit, string $currency): array
    {
        $total = 0.0;
        $skipped = 0;
        $rows = [];

        foreach ($deals as $deal) {
            $converted = CurrencyService::convertAmount((float) $deal->opportunity, CurrencyService::slug($deal->currency_id), $currency, now());

            if ($converted === null) {
                $skipped++;
            } else {
                $total += $converted;
            }

            if (count($rows) >= $limit) continue;

            $rows[] = [
                'title' => (string) ($deal->title ?: 'без названия'),
                'partner' => '',
                'company' => (string) ($deal->company_name ?: ''),
                'date' => $deal->date_create ? Carbon::parse($deal->date_create)->format('d.m.Y') : '—',
                'deals' => 1,
                'specs' => 0,
                'amount' => $converted === null ? 0.0 : round($converted, 2),
                'pilot' => false,
                'color' => 'warning',
                'stage' => (string) ($deal->stage_name ?: ''),
                'box_url' => route('deal_project.box_form', $deal->id),
                'deal_url' => CrmDealRegistryService::url($deal->id),
            ];
        }

        return [$rows, round($total, 2), $skipped];
    }

    /**
     * Сборка данных виджета
     *
     * @param string $mode
     * @param array $rows
     * @param array $counts
     * @param float $amount
     * @param int $skipped
     * @param string $symbol
     * @param string|null $partner
     * @return array
     */
    protected static function pack(string $mode, array $rows, array $counts, float $amount, int $skipped,
                                   string $symbol, ?string $partner): array
    {
        return [
            'mode' => $mode,
            'mode_label' => static::MODES[$mode] ?? static::MODES['active'],
            'counts' => [
                'active' => (int) ($counts['active'] ?? 0),
                'archive' => (int) ($counts['archive'] ?? 0),
                'waiting' => (int) ($counts['waiting'] ?? 0),
            ],
            'total' => (int) ($counts[$mode] ?? 0),
            'amount' => $amount,
            'skipped' => $skipped,
            'symbol' => $symbol,
            'partner' => $partner,
            'rows' => array_values($rows),
        ];
    }
}
