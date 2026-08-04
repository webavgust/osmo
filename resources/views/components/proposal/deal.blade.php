@php
    /**
     * Привязка КП к сделке Битрикса.
     *
     * <x-proposal.deal :proposal="$proposal" />
     */
    $deal = $proposal->crm_deal;
@endphp

@if($proposal->crm_deal_id)
    <a href="javascript:box({href: '{{ route('proposal.box_deal', [$proposal, $proposal->iteration]) }}'})"
       class="badge badge-light-success d-inline-flex align-items-center text-decoration-none"
       title="{{ $deal?->title ?: 'Сделка не найдена в выгрузке' }}">
        <i class="fa-light fa-link fs-7 me-2"></i>
        Сделка #{{ $proposal->crm_deal_id }}
    </a>
@else
    <a href="javascript:box({href: '{{ route('proposal.box_deal', [$proposal, $proposal->iteration]) }}'})"
       class="badge badge-light-secondary d-inline-flex align-items-center text-decoration-none"
       title="Привязать сделку Битрикс24">
        <i class="fa-light fa-link-slash fs-7 me-2"></i>
        Нет сделки
    </a>
@endif
