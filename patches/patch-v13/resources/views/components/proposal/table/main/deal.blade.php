@php
    /**
     * Колонка «Сделка» в списке КП.
     *
     * Привязок может быть несколько: показываем главную и счётчик остальных.
     * Клик открывает попап привязки — тот же, что в карточке КП.
     */
    $links = \App\Modules\Pub\Proposal\Services\ProposalDealService::links($row);
    $main = $links->first();
    $url = route('proposal.box_deal', [$row, $row->iteration]);
@endphp

<div class="d-flex justify-content-center">
    @if($links->isNotEmpty())
        <a href="javascript:box({href: '{{ $url }}'})"
           class="badge badge-light-success d-inline-flex align-items-center text-decoration-none"
           title="{{ $links->map(fn($link) => '#' . $link->crm_deal_id . ' ' . ($link->deal?->title ?: 'нет в выгрузке'))->implode(', ') }}">
            <i class="fa-light fa-link fs-8 me-2"></i>
            #{{ $main->crm_deal_id }}
            @if($links->count() > 1)
                <span class="ms-1">+{{ $links->count() - 1 }}</span>
            @endif
        </a>
    @else
        <a href="javascript:box({href: '{{ $url }}'})"
           class="badge badge-light-secondary d-inline-flex align-items-center text-decoration-none"
           title="Привязать сделку Битрикс24">
            <i class="fa-light fa-link-slash fs-8 me-2"></i>
            Нет сделки
        </a>
    @endif
</div>
