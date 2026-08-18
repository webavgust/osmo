@php
    /**
     * Привязка КП к сделкам Битрикса.
     *
     * <x-proposal.deal :proposal="$proposal" />
     * <x-proposal.deal :proposal="$proposal" as="btn" />
     *
     * У одного КП может быть несколько сделок: показываем первую и счётчик.
     * as="btn" — вид кнопки, чтобы совпадать по высоте с соседними кнопками.
     */
    $links = \App\Modules\Pub\Proposal\Services\ProposalDealService::links($proposal);
    $main = $links->first();
    $url = route('proposal.box_deal', [$proposal, $proposal->iteration]);
    $as_btn = ($as ?? '') === 'btn';

    // привязки нет — жёлтая плашка: серая на сером фоне не читалась
    $color = $links->isNotEmpty() ? 'success' : 'dark';
    $class = $as_btn ? 'btn btn-sm btn-light-' . $color : 'badge badge-light-' . $color;
@endphp

@if($links->isNotEmpty())
    <a href="javascript:box({href: '{{ $url }}'})"
       class="{{ $class }} d-inline-flex align-items-center text-decoration-none text-nowrap"
       title="{{ $links->map(fn($link) => '#' . $link->crm_deal_id . ' ' . ($link->deal?->title ?: 'нет в выгрузке'))->implode(', ') }}">
        <i class="fa-light fa-link fs-7 me-2"></i>
        Сделка #{{ $main->crm_deal_id }}
        @if($links->count() > 1)
            <span class="ms-1">+{{ $links->count() - 1 }}</span>
        @endif
    </a>
@else
    <a href="javascript:box({href: '{{ $url }}'})"
       class="{{ $class }} d-inline-flex align-items-center text-decoration-none text-nowrap"
       title="Привязать сделку Битрикс24">
        <i class="fa-light fa-link-slash fs-7 me-2"></i>
        Нет сделки
    </a>
@endif
