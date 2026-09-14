<?php

namespace App\Modules\Pub\Desktop\Widgets\Proposal;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Models\ProposalCrmDeal;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Services\ProposalStatusService;

/**
 * КП без сделки (patch v30): сколько КП не привязано к сделке Битрикс24 и какие именно.
 *
 * Привязки живут на группе КП (proposal_crm_deals.proposal_group), поэтому «без сделки» —
 * это группа, у которой нет ни одной строки привязки. Таких КП не видно в воронке
 * и в расхождениях с Битрикс24. Строки, суммы основных вариантов и статусы —
 * общие с виджетом «Последние КП» (ProposalsRecentWidget::pack()).
 */
class ProposalsNoDealWidget extends Widget
{
    public static function id(): string { return 'proposals_nodeal'; }

    public static function name(): string { return 'КП без сделки'; }

    public static function category(): string { return 'proposal'; }

    public static function description(): string
    {
        return 'КП без привязки к сделке Битрикс24: сколько их и какие именно';
    }

    public static function icon(): string { return 'fa-link-slash'; }

    public static function sizes(): array { return ['8x4', '4x2']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 140; }

    public static function ttl(): int { return 300; }

    public static function fields(): array
    {
        return [
            ['key' => 'only_in_work', 'type' => 'bool', 'label' => 'Только в работе', 'default' => true,
                'hint' => 'Иначе считаются и решённые КП'],
            ['key' => 'mine', 'type' => 'bool', 'label' => 'Только мои (менеджер — я)', 'default' => false],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько КП в списке', 'default' => 10, 'min' => 3, 'max' => 50],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('proposal.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $rows = ProposalsRecentWidget::sampleRows($settings['only_in_work'] ? ProposalStatus::IN_WORK->value : null);

        // счётчик — по числу образцовых строк: высокому блоку есть чем заполниться
        return ['rows' => $rows, 'count' => count($rows)];
    }

    /**
     * КП без привязки к сделке Битрикс24
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [...], 'count']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $linked = ProposalCrmDeal::query()->distinct()->pluck('proposal_group')->flip();

        $rows = ProposalStatusService::latestIterations()
            ->filter(fn($row) => !$linked->has((string) $row->group));

        if (!empty($settings['only_in_work'])) {
            $rows = ProposalsRecentWidget::applyStatus($rows, ProposalStatus::IN_WORK->value);
        }

        if (!empty($settings['mine'])) {
            $user_id = (int) ($ctx->user?->id ?? 0);
            $rows = $rows->filter(fn($row) => (int) $row->manager_id === $user_id);
        }

        $take = $rows->sortByDesc(fn($row) => $row->sended_at?->timestamp ?? 0)
            ->take((int) $settings['limit'])
            ->values();

        return ['rows' => ProposalsRecentWidget::pack($take), 'count' => $rows->count()];
    }
}
