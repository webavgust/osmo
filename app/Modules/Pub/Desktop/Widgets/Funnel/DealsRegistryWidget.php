<?php

namespace App\Modules\Pub\Desktop\Widgets\Funnel;

use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use Illuminate\Support\Carbon;

/**
 * Реестр сделок (patch v30): короткий список последних сделок реестра Битрикс24.
 *
 * Данные — CrmDealRegistryService::rows(): тот же отбор, что у страницы «Реестр сделок
 * Битрикс24» (сделки с since(), сортировка по дате создания вниз), поэтому виджет
 * показывает ровно то же, что покажет страница по ссылке «Открыть страницу».
 *
 * Фильтр упрощён до одного значения на поле (вкладка, стадия, менеджер, привязка КП):
 * на столе множественный выбор страницы негде разместить. Суммы сделок не складываются —
 * в реестре они в разных валютах, как и на странице.
 */
class DealsRegistryWidget extends Widget
{
    /** Цвет стадии по семантике Битрикса — как в таблице реестра */
    public const SEMANTIC = ['S' => 'success', 'F' => 'danger', 'P' => 'primary'];

    /** Символы валют реестра */
    public const SYMBOLS = ['RUB' => '₽', 'USD' => '$', 'EUR' => '€', 'CNY' => '¥'];

    /** Вкладки реестра */
    public const MODES = [
        CrmDealRegistryService::MODE_ALL => 'Все сделки',
        CrmDealRegistryService::MODE_PROJECTS => 'С проектом',
        CrmDealRegistryService::MODE_ARCHIVE => 'Архив проектов',
    ];

    public static function id(): string { return 'deals_registry'; }

    public static function name(): string { return 'Реестр сделок'; }

    public static function category(): string { return 'funnel'; }

    public static function description(): string
    {
        return 'Последние сделки Битрикс24 с переходом в реестр';
    }

    public static function icon(): string { return 'fa-table-list'; }

    public static function sizes(): array { return ['16x8', '32x8', '32x12']; }

    public static function defaultSize(): string { return '16x8'; }

    public static function order(): int { return 1300; }

    public static function ttl(): int { return 600; }

    public static function fields(): array
    {
        return [
            ['key' => 'mode', 'type' => 'select', 'label' => 'Вкладка реестра', 'default' => CrmDealRegistryService::MODE_ALL,
                'options' => static::MODES],
            ['key' => 'has_proposal', 'type' => 'select', 'label' => 'Привязано КП', 'default' => 'all',
                'hint' => 'На странице реестра по умолчанию показываются сделки без КП — это работа, которую ещё не посчитали',
                'options' => CrmDealRegistryService::HAS_PROPOSAL],
            ['key' => 'stage', 'type' => 'select', 'label' => 'Стадия', 'default' => 'all',
                'options' => fn() => ['all' => 'любая'] + collect(CrmDealRegistryService::options()['stages'])
                    ->mapWithKeys(fn($item) => [(string) $item['id'] => (string) $item['name']])->all()],
            ['key' => 'manager', 'type' => 'select', 'label' => 'Менеджер', 'default' => 'all',
                'options' => fn() => ['all' => 'все'] + collect(CrmDealRegistryService::options()['managers'])
                    ->mapWithKeys(fn($item) => [(string) $item['id'] => (string) $item['name']])->all()],
            // по умолчанию — максимум: сколько строк влезет, решает высота блока (подгон .desk-fit)
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько сделок', 'default' => 50, 'min' => 1, 'max' => 50,
                'hint' => 'Не больше, чем влезает по высоте блока'],
        ];
    }

    /**
     * Отбор реестра из настроек виджета
     *
     * @param array $settings
     * @return array ['mode', 'params']
     */
    protected static function filter(array $settings): array
    {
        $mode = CrmDealRegistryService::mode($settings['mode'] ?? null);
        $one = fn($key) => ($settings[$key] ?? 'all') !== 'all' ? [(string) $settings[$key]] : [];

        return [
            'mode' => $mode,
            'params' => CrmDealRegistryService::params([
                'stage' => $one('stage'),
                'manager' => $one('manager'),
                'has_proposal' => (string) ($settings['has_proposal'] ?? 'all'),
            ], CrmDealRegistryService::modeDefaults($mode)),
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        ['mode' => $mode, 'params' => $params] = static::filter(static::normalize($settings));

        $query = CrmDealRegistryService::query($params);
        if ($mode !== CrmDealRegistryService::MODE_ALL) $query['mode'] = $mode;

        return route('crm-deal.index', $query);
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $sample = [
            [4821, 'Лицензии OSMO, ГК «Восток»', 'Contracting', 'P', 'ГК «Восток»', 'Роснефть', 'Россия', 4200000.0, 'RUB', '2026-114', true],
            [4817, 'Пилот, Ташкент-Софт', 'Pilot project', 'P', 'Ташкент-Софт', 'UzGas', 'Узбекистан', 38000.0, 'USD', null, true],
            [4802, 'Продление, ООО «Гранит»', 'Invoice + Specification', 'P', 'ООО «Гранит»', '', 'Россия', 1750000.0, 'RUB', '2026-097', false],
            [4790, 'Внедрение, АО «Вектор»', 'Execution (PRE-PAYMENT)', 'S', 'АО «Вектор»', 'Вектор-Юг', 'Россия', 9300000.0, 'RUB', '2026-090', false],
            [4771, 'Тендер, «Алмаз»', 'Competition/tender', 'F', 'Алмаз', '', 'Казахстан', 12000000.0, 'RUB', null, false],
        ];

        // 40 сделок (режется настройкой, как в data()) — высоким блокам должно быть чем заполниться
        $rows = [];
        for ($i = 0; $i < min(40, max(1, (int) $settings['limit'])); $i++) {
            $row = $sample[$i % count($sample)];
            $rows[] = [
                'id' => $row[0] - intdiv($i, count($sample)) * 50,
                'title' => $row[1],
                'stage' => $row[2],
                'color' => static::SEMANTIC[$row[3]] ?? 'dark',
                'date' => now()->subDays($i * 3)->format('d.m.Y'),
                'manager' => ['Анна Смирнова', 'Пётр Волков'][$i % 2],
                'company' => $row[4],
                'customer' => $row[5],
                'country' => $row[6],
                'amount' => $row[7],
                'symbol' => static::SYMBOLS[$row[8]] ?? $row[8],
                'proposal' => $row[9],
                'proposal_name' => $row[9] ? 'КП ' . $row[9] : null,
                'proposal_url' => null,
                'project' => $row[10],
                'url' => null,
            ];
        }

        return [
            'rows' => $rows,
            'total' => 38,
            'with_proposal' => 21,
            'mode' => CrmDealRegistryService::MODE_ALL,
            'mode_label' => static::MODES[CrmDealRegistryService::MODE_ALL],
            'filtered' => false,
        ];
    }

    /**
     * Последние сделки реестра
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [['id', 'title', 'stage', 'color', 'date', 'manager', 'company',
     *                            'customer', 'country', 'amount', 'symbol', 'proposal',
     *                            'proposal_name', 'proposal_url', 'project', 'url']],
     *                'total', 'with_proposal', 'mode', 'mode_label', 'filtered']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        ['mode' => $mode, 'params' => $params] = static::filter($settings);

        $rows = CrmDealRegistryService::rows($params, null, $mode);

        $list = $rows->take((int) $settings['limit'])->map(function ($row) {
            $proposal = $row->proposal;
            $project = $row->project;

            return [
                'id' => (int) $row->id,
                'title' => (string) ($row->title ?: 'без названия'),
                'stage' => (string) ($row->stage_name ?: '—'),
                'color' => static::SEMANTIC[(string) $row->stage_semantic_id] ?? 'dark',
                'date' => $row->date_create ? Carbon::parse($row->date_create)->format('d.m.Y') : '—',
                'manager' => (string) ($row->manager ?: ''),
                'company' => (string) ($row->company_name ?: ''),
                'customer' => (string) ($row->customer_name ?: ''),
                'country' => (string) $row->country,
                'amount' => (float) $row->opportunity,
                'symbol' => static::SYMBOLS[(string) $row->currency_id] ?? (string) $row->currency_id,
                'proposal' => $proposal ? (string) ($proposal->number ?: 'КП') : null,
                'proposal_name' => $proposal?->name,
                'proposal_url' => $proposal ? route('proposal.detail', [$proposal, $proposal->iteration]) : null,
                'project' => (bool) $project,
                'url' => (string) $row->deal_url,
            ];
        })->values()->all();

        return [
            'rows' => $list,
            'total' => $rows->count(),
            'with_proposal' => $rows->filter(fn($row) => !empty($row->proposal))->count(),
            'mode' => $mode,
            'mode_label' => static::MODES[$mode] ?? static::MODES[CrmDealRegistryService::MODE_ALL],
            'filtered' => CrmDealRegistryService::filtered($params, CrmDealRegistryService::modeDefaults($mode)),
        ];
    }
}
