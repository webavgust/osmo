@php
    /**
     * Колонка «Статус» в списке КП.
     * Рендерится на сервере в ProposalService::tableDefault().
     */
    $status = $row->status_decorate;
    $reason = $row->reason_decorate;
@endphp

<div class="d-flex flex-column align-items-center gap-1">
    <a href="javascript:box({href: '{{ route('proposal.box_status', [$row, $row->iteration]) }}'})"
       class="badge badge-light-{{ $status['color'] }} d-inline-flex align-items-center text-decoration-none"
       title="Сменить статус">
        <i class="fa-light {{ $status['icon'] }} fs-8 me-2"></i>
        {{ $status['label'] }}
    </a>

    @if($reason)
        <span class="fs-8 text-{{ $reason['color'] }} text-truncate" style="max-width: 140px"
              title="{{ $row->status_comment ?: $reason['hint'] }}">
            {{ $reason['label'] }}
        </span>
    @endif

    @if($row->crm_deal_id)
        <span class="fs-8 text-muted" title="Привязана сделка Битрикс24">
            <i class="fa-light fa-link fs-8 me-1"></i>#{{ $row->crm_deal_id }}
        </span>
    @endif
</div>
