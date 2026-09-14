<?php

namespace App\Modules\Pub\Desktop\Widgets\Proposal;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Services\ProposalStatusService;

/**
 * Мои КП (patch v30): КП текущего пользователя — номер, компания, статус,
 * сумма основного варианта и ссылка на карточку.
 *
 * Ответственный за КП — manager_id (отдельного «автора» у proposals нет,
 * страница списка тоже раскладывает КП по вкладкам менеджеров по этому полю).
 * Отбор строк, сумма основного варианта и трактовка статусов — общие с виджетом
 * «Последние КП» (ProposalsRecentWidget::pack()).
 */
class ProposalsMineWidget extends Widget
{
    public static function id(): string { return 'proposals_mine'; }

    public static function name(): string { return 'Мои КП'; }

    public static function category(): string { return 'proposal'; }

    public static function description(): string
    {
        return 'КП, где менеджер — я: по давности или по сумме, старые подсвечены';
    }

    public static function icon(): string { return 'fa-user-tie'; }

    public static function sizes(): array { return ['8x4', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 130; }

    public static function ttl(): int { return 300; }

    public static function fields(): array
    {
        return [
            ['key' => 'status', 'type' => 'select', 'label' => 'Статус', 'default' => ProposalStatus::IN_WORK->value,
                'options' => fn() => ProposalsRecentWidget::statusOptions()],
            ['key' => 'sort', 'type' => 'select', 'label' => 'Сортировка', 'default' => 'age',
                'options' => ['age' => 'По давности отправки', 'cost' => 'По сумме']],
            ['key' => 'stale', 'type' => 'number', 'label' => 'Подсвечивать старше, дней', 'default' => 14, 'min' => 1, 'max' => 365],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько КП', 'default' => 10, 'min' => 3, 'max' => 50],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('proposal.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $status = (string) $settings['status'];
        $rows = ProposalsRecentWidget::sampleRows($status === 'all' ? null : $status);

        return ['rows' => $rows, 'total' => count($rows), 'stale' => (int) $settings['stale']];
    }

    /**
     * КП текущего пользователя
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [...], 'total', 'stale']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $user_id = (int) ($ctx->user?->id ?? 0);
        $stale = (int) $settings['stale'];

        if ($user_id <= 0) {
            return ['rows' => [], 'total' => 0, 'stale' => $stale];
        }

        $rows = ProposalStatusService::latestIterations()
            ->filter(fn($row) => (int) $row->manager_id === $user_id);
        $rows = ProposalsRecentWidget::applyStatus($rows, (string) $settings['status']);

        $total = $rows->count();

        // сортировка по сумме читает основной вариант у каждой строки — подгружаем разом
        if ($settings['sort'] === 'cost') $rows->load('variants');

        // по давности — сначала самые старые: они и есть «зависшие»
        $take = ($settings['sort'] === 'cost'
            ? $rows->sortByDesc(fn($row) => (float) ($row->variants->first()?->cost_total ?? 0))
            : $rows->sortBy(fn($row) => $row->sended_at?->timestamp ?? 0))
            ->take((int) $settings['limit'])
            ->values();

        return [
            'rows' => ProposalsRecentWidget::pack($take),
            'total' => $total,
            'stale' => $stale,
        ];
    }
}
