<?php

namespace App\Modules\Pub\Desktop\Widgets\Proposal;

use App\Modules\Pub\Desktop\Metrics\MetricRegistry;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Services\ProposalStatusService;

/**
 * КП по статусам (patch v30): сколько КП в работе, выиграно и проиграно
 * по последним редакциям, и конверсия решённых.
 */
class ProposalStatusWidget extends Widget
{
    public static function id(): string { return 'proposal_status'; }

    public static function name(): string { return 'КП по статусам'; }

    public static function category(): string { return 'proposal'; }

    public static function description(): string
    {
        return 'В работе, выиграно, проиграно и конверсия: все КП, отправленные в периоде или только свои';
    }

    public static function icon(): string { return 'fa-list-check'; }

    public static function sizes(): array { return ['8x2', '4x2', '8x4']; }

    public static function defaultSize(): string { return '8x2'; }

    public static function order(): int { return 100; }

    public static function usesPeriod(): bool { return true; }

    public static function ttl(): int { return 300; }

    public static function fields(): array
    {
        return [
            ['key' => 'scope', 'type' => 'select', 'label' => 'Какие КП', 'default' => 'all',
                'options' => ['all' => 'Все', 'period' => 'Отправленные в периоде'], 'hint' => 'Период — по дате отправки первой редакции'],
            ['key' => 'mine', 'type' => 'bool', 'label' => 'Только мои (менеджер — я)', 'default' => false],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('proposal.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        return static::pack(['in_work' => 37, 'won' => 52, 'lost' => 19], 73.2, null);
    }

    /**
     * Счётчики статусов и конверсия
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['statuses' => [['key', 'label', 'color', 'icon', 'count']], 'total', 'conversion', 'period_label']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $rows = ProposalStatusService::latestIterations();
        $period_label = null;

        if ($settings['scope'] === 'period') {
            $period = $ctx->periodFor($settings);
            $groups = MetricRegistry::groupsSentBetween($period['from'], $period['to'])->flip();
            $rows = $rows->filter(fn($row) => $groups->has($row->group));
            $period_label = $period['label'];
        }

        if ($settings['mine']) {
            $user_id = (int) ($ctx->user?->id ?? 0);
            $rows = $rows->filter(fn($row) => (int) $row->manager_id === $user_id);
        }

        return static::pack(ProposalStatusService::counters($rows), ProposalStatusService::conversion($rows), $period_label);
    }

    /**
     * Статусы с подписями и цветами из ProposalStatus::data()
     *
     * @param array $counters статус => количество
     * @param float $conversion
     * @param string|null $period_label
     * @return array
     */
    protected static function pack(array $counters, float $conversion, ?string $period_label): array
    {
        $statuses = [];

        foreach (ProposalStatus::cases() as $case) {
            $info = $case->data();
            // secondary в Metronic светло-серый — как в компоненте x-proposal.status, показываем dark
            $color = in_array($info['color'], ['secondary', 'light', 'white', ''], true) ? 'dark' : $info['color'];

            $statuses[] = [
                'key' => $case->value,
                'label' => $info['label'],
                'color' => $color,
                'icon' => $info['icon'],
                'count' => (int) ($counters[$case->value] ?? 0),
            ];
        }

        return [
            'statuses' => $statuses,
            'total' => array_sum(array_column($statuses, 'count')),
            'conversion' => $conversion,
            'period_label' => $period_label,
        ];
    }
}
