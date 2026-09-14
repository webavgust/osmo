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
<div class="cell text-center">
    <span
       @class([
            "badge d-inline-flex align-items-center text-decoration-none",
            "badge-light-" . $palette($status['color']),
            "cursor-pointer" => !empty($reason['label']),
            $status['text'] ?? null
        ])
        @if($reason)
            data-bs-toggle="popover" data-bs-placement="bottom" title="{{ $reason['label'] }}"
        @endif
    >

        <i class="fa-light {{ $status['icon'] }} fs-6 me-2"></i>
        <span class="fs-7">{{ $status['label'] }}</span>

    </span>
</div>
