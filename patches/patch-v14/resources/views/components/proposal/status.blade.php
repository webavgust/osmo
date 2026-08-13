@php
    /**
     * Бейдж статуса КП.
     *
     * <x-proposal.status :proposal="$proposal" />
     * <x-proposal.status :proposal="$proposal" editable="1" />
     * <x-proposal.status :proposal="$proposal" editable="1" as="btn" />
     *
     * as="btn" — вид кнопки: нужен там, где статус стоит в ряду кнопок
     * и должен совпадать с ними по высоте.
     */
    $status = $proposal->status_decorate;
    $reason = $proposal->reason_decorate;
    $editable = !empty($editable);
    $as_btn = ($as ?? '') === 'btn';

    // secondary в Metronic — светло-серый: светлый текст на светлом фоне не читается
    $palette = fn($color) => in_array($color, ['secondary', 'light', 'white', '', null], true) ? 'dark' : $color;

    $color = $palette($status['color']);
    $class = $as_btn ? 'btn btn-sm btn-light-' . $color : 'badge badge-light-' . $color;
@endphp

<span class="d-inline-flex align-items-center gap-2">
    @if($editable)
        <a href="javascript:box({href: '{{ route('proposal.box_status', [$proposal, $proposal->iteration]) }}'})"
           class="{{ $class }} d-inline-flex align-items-center text-decoration-none text-nowrap"
           title="Сменить статус">
            <i class="fa-light {{ $status['icon'] }} fs-7 me-2"></i>
            {{ $status['label'] }}
            <i class="fa-light fa-pen fs-8 ms-2 opacity-50"></i>
        </a>
    @else
        <span class="{{ $class }} d-inline-flex align-items-center text-nowrap">
            <i class="fa-light {{ $status['icon'] }} fs-7 me-2"></i>
            {{ $status['label'] }}
        </span>
    @endif

    @if($reason)
        <span class="badge badge-light-{{ $palette($reason['color']) }}"
              title="{{ $proposal->status_comment ?: $reason['hint'] }}">
            {{ $reason['label'] }}
        </span>
    @endif
</span>
