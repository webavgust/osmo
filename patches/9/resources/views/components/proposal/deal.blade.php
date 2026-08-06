@php
    /**
     * Привязка КП к сделкам Битрикса.
     *
     * <x-proposal.deal :proposal="$proposal" />
     *
     * У одного КП может быть несколько сделок: показываем первую и счётчик.
     */
    $links = \App\Modules\Pub\Proposal\Services\ProposalDealService::links($proposal);
    $main = $links->first();
    $url = route('proposal.box_deal', [$proposal, $proposal->iteration]);
@endphp

@if($links->isNotEmpty())
    <a href="javascript:box({href: '{{ $url }}'})"
       class="badge badge-light-success d-inline-flex align-items-center text-decoration-none"
       title="{{ $links->map(fn($link) => '#' . $link->crm_deal_id . ' ' . ($link->deal?->title ?: 'нет в выгрузке'))->implode(', ') }}">
        <i class="fa-light fa-link fs-7 me-2"></i>
        Сделка #{{ $main->crm_deal_id }}
        @if($links->count() > 1)
            <span class="ms-1">+{{ $links->count() - 1 }}</span>
        @endif
    </a>
@else
    <a href="javascript:box({href: '{{ $url }}'})"
       class="badge badge-light-secondary d-inline-flex align-items-center text-decoration-none"
       title="Привязать сделку Битрикс24">
        <i class="fa-light fa-link-slash fs-7 me-2"></i>
        Нет сделки
    </a>
@endif
