<?php

namespace App\Modules\Pub\Desktop\Widgets\Proposal;

use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Pub\ContractSpecification\Services\SpecProposalService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalLink;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Services\ProposalDealService;
use App\Modules\Pub\Proposal\Services\ProposalLinkService;

/**
 * Карточка КП (patch v30): выбранное в настройках КП с живыми данными —
 * номер, компания, статус, сумма основного варианта, дата отправки и ссылка на карточку.
 *
 * КП выбирается полем entity (тип proposal), где id — group КП: показывается
 * последняя редакция группы, как в «Быстрой ссылке» (LinkWidget::proposal()).
 * Сумма берётся у основного варианта (variants: is_main desc, id) и показывается
 * в валюте самого КП, без пересчёта курса — ровно как в колонке «Стоимость» списка КП
 * и в виджете «Последние КП». Статусы — три из v27.
 *
 * Связка КП (patch v33): у второстепенного КП карточка помечает, к какому главному оно
 * относится, — его сумма в расчётах не участвует; у главного — сколько второстепенных.
 */
class ProposalCardWidget extends Widget
{
    public static function id(): string { return 'proposal_card'; }

    public static function name(): string { return 'Карточка КП'; }

    public static function category(): string { return 'proposal'; }

    public static function description(): string
    {
        return 'Мини-карточка выбранного КП: номер, компания, статус, сумма и дата отправки';
    }

    public static function icon(): string { return 'fa-file-invoice'; }

    public static function sizes(): array { return ['8x4', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 190; }

    public static function ttl(): int { return 300; }

    public static function fields(): array
    {
        return [
            ['key' => 'target', 'type' => 'entity', 'label' => 'КП', 'entities' => ['proposal'], 'required' => true, 'default' => null,
                'hint' => 'Поиск по номеру и названию; показывается последняя редакция'],
            ['key' => 'show_status', 'type' => 'bool', 'label' => 'Статус', 'default' => true],
            ['key' => 'show_amount', 'type' => 'bool', 'label' => 'Сумма основного варианта', 'default' => true],
            ['key' => 'show_date', 'type' => 'bool', 'label' => 'Дата отправки', 'default' => true],
            ['key' => 'show_manager', 'type' => 'bool', 'label' => 'Менеджер', 'default' => true],
            ['key' => 'show_deal', 'type' => 'bool', 'label' => 'Сделки Битрикс24', 'default' => true],
            ['key' => 'show_specs', 'type' => 'bool', 'label' => 'Спецификации', 'default' => false,
                'hint' => 'Спецификации рамочных договоров, к которым прикреплено КП'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('proposal.index');
    }

    /**
     * Group выбранного КП ('' — не выбрано)
     *
     * @param array $settings
     * @return string
     */
    public static function group(array $settings): string
    {
        return (string) ($settings['target']['type'] ?? '') === 'proposal'
            ? trim((string) ($settings['target']['id'] ?? ''))
            : '';
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
        $info = ProposalStatus::WON->data();

        return [
            'found' => true,
            'number' => 'AA-794',
            'name' => 'Видеоаналитика на складе: платформа и 4 сценария',
            'company' => 'ООО «Альфа»',
            'partner' => 'ГК Восток',
            'manager' => 'Анна Иванова',
            'status' => ProposalStatus::WON->value,
            'status_label' => $info['label'],
            'status_color' => $info['color'],
            'status_icon' => $info['icon'],
            'reason' => '',
            'comment' => '',
            'amount' => 4800000.0,
            'symbol' => '₽',
            'variants' => 3,
            'iteration' => 2,
            'iterations' => 2,
            'date' => now()->subDays(12)->format('d.m.Y'),
            'days' => 12,
            // несколько сделок и спецификаций: высокому блоку есть чем заполниться
            'deals' => [
                ['title' => 'Альфа · видеоаналитика склада', 'stage' => 'Договор подписан', 'main' => true, 'url' => null],
                ['title' => 'Альфа · расширение на второй склад', 'stage' => 'Согласование КП', 'main' => false, 'url' => null],
                ['title' => 'Альфа · техподдержка 2027', 'stage' => 'Новая', 'main' => false, 'url' => null],
            ],
            'deals_count' => 3,
            'specs' => [
                ['name' => '№ Д-45 · Спецификация № 12 от 01.08.2026', 'amount' => 4600000.0, 'symbol' => '₽', 'signed' => true],
                ['name' => '№ Д-45 · Спецификация № 13 от 15.08.2026', 'amount' => 1200000.0, 'symbol' => '₽', 'signed' => true],
                ['name' => '№ Д-45 · Спецификация № 14 от 01.09.2026', 'amount' => 850000.0, 'symbol' => '₽', 'signed' => false],
                ['name' => '№ Д-51 · Спецификация № 2 от 10.09.2026', 'amount' => 3100.0, 'symbol' => '$', 'signed' => false],
            ],
            'specs_count' => 4,
            'specs_sum' => 6650000.0,
            'external' => 'AK528',
            'url' => null,
            // связки нет — как у большинства КП
            'link' => '',
            'link_ref' => '',
            'link_url' => null,
            'link_count' => 0,
        ];
    }

    /**
     * Карточка выбранного КП
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['found', 'number', 'name', 'company', 'partner', 'manager', 'status',
     *     'status_label', 'status_color', 'status_icon', 'reason', 'comment', 'amount', 'symbol',
     *     'variants', 'iteration', 'iterations', 'date', 'days', 'deals', 'deals_count',
     *     'specs', 'specs_count', 'specs_sum', 'external', 'url', 'link', 'link_ref', 'link_url', 'link_count']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $empty = [
            'found' => false, 'number' => '', 'name' => '', 'company' => '', 'partner' => '', 'manager' => '',
            'status' => ProposalStatus::IN_WORK->value, 'status_label' => '', 'status_color' => 'dark', 'status_icon' => '',
            'reason' => '', 'comment' => '', 'amount' => null, 'symbol' => '₽', 'variants' => 0,
            'iteration' => 0, 'iterations' => 0, 'date' => null, 'days' => null,
            'deals' => [], 'deals_count' => 0, 'specs' => [], 'specs_count' => 0, 'specs_sum' => 0.0,
            'external' => '', 'url' => null,
            'link' => '', 'link_ref' => '', 'link_url' => null, 'link_count' => 0,
        ];

        $group = static::group($settings);
        if ($group === '') return $empty;

        $proposal = Proposal::with(['company', 'partner', 'manager', 'variants', 'currency', 'external'])
            ->where('group', $group)
            ->orderByDesc('iteration')
            ->orderByDesc('id')
            ->first();

        if ($proposal === null) return $empty;

        $status = $proposal->status_enum;
        $info = $status->data();
        // secondary в Metronic почти не виден — как в виджете «КП по статусам», показываем dark
        $color = in_array($info['color'], ['secondary', 'light', 'white', ''], true) ? 'dark' : $info['color'];

        $variant = $proposal->variants->first();
        $symbol = (string) ($proposal->currency?->symbol ?? $proposal->currency_slug ?? '₽');

        $card = [
            'found' => true,
            'number' => trim((string) $proposal->number),
            'name' => (string) $proposal->name,
            'company' => (string) ($proposal->company?->name ?? ''),
            'partner' => (string) ($proposal->partner?->name ?? ''),
            'manager' => (string) ($proposal->manager?->full_name ?? $proposal->manager?->name ?? ''),
            'status' => $status->value,
            'status_label' => (string) $info['label'],
            'status_color' => $color,
            'status_icon' => (string) $info['icon'],
            'reason' => (string) ($proposal->reason_decorate['label'] ?? ''),
            'comment' => (string) ($proposal->status_comment ?? ''),
            'amount' => $variant?->cost_total !== null ? (float) $variant->cost_total : null,
            'symbol' => $symbol,
            'variants' => $proposal->variants->count(),
            'iteration' => (int) $proposal->iteration,
            'iterations' => Proposal::where('group', $group)->count(),
            'date' => $proposal->sended_at?->format('d.m.Y'),
            'days' => $proposal->sended_at
                ? (int) $proposal->sended_at->copy()->startOfDay()->diffInDays(now()->startOfDay())
                : null,
            'external' => (string) ($proposal->external?->external_number ?? ''),
            'url' => route('proposal.detail', [$proposal, $proposal->iteration]),
        ];

        return array_merge($empty, $card, static::link($proposal), $this->deals($settings, $group), $this->specs($settings, $proposal));
    }

    /**
     * Связка КП (patch v33): второстепенное — ссылка на главное, главное — сколько второстепенных
     *
     * @param Proposal $proposal
     * @return array ['link' => ''|'secondary'|'main', 'link_ref', 'link_url', 'link_count']
     */
    protected static function link(Proposal $proposal): array
    {
        $main = ProposalLinkService::mainOf($proposal);

        if ($main !== null) {
            return [
                'link' => 'secondary',
                'link_ref' => ProposalLink::refOf($main),
                'link_url' => route('proposal.detail', [$main, $main->iteration]),
                'link_count' => 0,
            ];
        }

        $secondaries = ProposalLinkService::secondariesOf($proposal);
        if ($secondaries->isEmpty()) return [];

        return [
            'link' => 'main',
            'link_ref' => $secondaries->map(fn($row) => ProposalLink::refOf($row))->implode(', '),
            'link_url' => null,
            'link_count' => $secondaries->count(),
        ];
    }

    /**
     * Сделки Битрикс24, привязанные к группе КП (patch v29 — привязки живут на группе)
     *
     * @param array $settings
     * @param string $group
     * @return array ['deals', 'deals_count']
     */
    protected function deals(array $settings, string $group): array
    {
        if (empty($settings['show_deal'])) return [];

        $links = ProposalDealService::links($group);
        if ($links->isEmpty()) return ['deals' => [], 'deals_count' => 0];

        $deals = $links->map(fn($link) => [
            'title' => (string) ($link->deal?->title ?? '#' . $link->crm_deal_id),
            'stage' => (string) ($link->deal?->stage_name ?? ''),
            'main' => (bool) $link->is_main,
            'url' => CrmDealRegistryService::url((int) $link->crm_deal_id),
        ])->all();

        return ['deals' => $deals, 'deals_count' => count($deals)];
    }

    /**
     * Спецификации рамочных договоров, к которым прикреплено КП
     *
     * @param array $settings
     * @param Proposal $proposal
     * @return array ['specs', 'specs_count', 'specs_sum']
     */
    protected function specs(array $settings, Proposal $proposal): array
    {
        if (empty($settings['show_specs'])) return [];

        $specs = SpecProposalService::specifications($proposal);
        if ($specs->isEmpty()) return ['specs' => [], 'specs_count' => 0, 'specs_sum' => 0.0];

        $currencies = DesktopContext::currencies();

        $rows = $specs->map(fn($spec) => [
            // name_full строит подпись через ContractType — в виджете обходимся номером договора
            'name' => trim(($spec->contract?->number ? '№ ' . $spec->contract->number . ' · ' : '') . $spec->name),
            'amount' => $spec->amount !== null ? (float) $spec->amount : null,
            'symbol' => (string) ($currencies[(string) $spec->currency_slug]->symbol ?? $spec->currency_slug ?? '₽'),
            'signed' => (bool) $spec->is_signed,
        ])->all();

        // суммы спецификаций бывают в разных валютах — итог считаем только по валюте КП
        $sum = $specs
            ->filter(fn($spec) => (string) $spec->currency_slug === (string) $proposal->currency_slug)
            ->sum(fn($spec) => (float) $spec->amount);

        return ['specs' => $rows, 'specs_count' => count($rows), 'specs_sum' => (float) $sum];
    }
}
