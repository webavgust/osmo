@php
    /**
     * Бейдж статуса КП.
     *
     * <x-proposal.status :proposal="$proposal" />
     * <x-proposal.status :proposal="$proposal" editable="1" />
     */
    $status = $proposal->status_decorate;
    $reason = $proposal->reason_decorate;
    $editable = !empty($editable);
@endphp

<span class="d-inline-flex align-items-center gap-2">
    @if($editable)
        <a href="javascript:box({href: '{{ route('proposal.box_status', [$proposal, $proposal->iteration]) }}'})"
           class="badge badge-light-{{ $status['color'] }} d-inline-flex align-items-center text-decoration-none"
           title="Сменить статус">
            <i class="fa-light {{ $status['icon'] }} fs-7 me-2"></i>
            {{ $status['label'] }}
            <i class="fa-light fa-pen fs-8 ms-2 opacity-50"></i>
        </a>
    @else
        <span class="badge badge-light-{{ $status['color'] }} d-inline-flex align-items-center">
            <i class="fa-light {{ $status['icon'] }} fs-7 me-2"></i>
            {{ $status['label'] }}
        </span>
    @endif

    @if($reason)
        <span class="badge badge-light-{{ $reason['color'] }}"
              title="{{ $proposal->status_comment ?: $reason['hint'] }}">
            {{ $reason['label'] }}
        </span>
    @endif
</span>
