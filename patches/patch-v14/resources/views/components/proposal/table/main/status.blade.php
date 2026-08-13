@php
    /**
     * Колонка «Статус» в списке КП.
     * Рендерится на сервере в ProposalService::tableDefault().
     *
     * Только статус: привязка к сделке и переход в сводную карточку
     * живут в своих колонках.
     */
    $status = $row->status_decorate;
    $reason = $row->reason_decorate;

    // secondary в Metronic — светло-серый: светлый текст на светлом фоне не читается
    $palette = fn($color) => in_array($color, ['secondary', 'light', 'white', '', null], true) ? 'dark' : $color;
@endphp

<div class="d-flex flex-column align-items-center gap-1">
    <a href="javascript:box({href: '{{ route('proposal.box_status', [$row, $row->iteration]) }}'})"
       class="badge badge-light-{{ $palette($status['color']) }} d-inline-flex align-items-center text-decoration-none"
       title="Сменить статус">
        <i class="fa-light {{ $status['icon'] }} fs-8 me-2"></i>
        {{ $status['label'] }}
    </a>

    @if($reason)
        <span class="badge badge-light-{{ $palette($reason['color']) }} text-truncate" style="max-width: 140px"
              title="{{ $row->status_comment ?: $reason['hint'] }}">
            {{ $reason['label'] }}
        </span>
    @endif
</div>
