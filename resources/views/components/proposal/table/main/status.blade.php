@php
    /**
     * Колонка «Статус» в списке КП.
     * Рендерится на сервере в ProposalService::tableDefault().
     *
     * Только статус: привязка к сделке и переход в сводную карточку
     * живут в своих колонках.
     */
    $status = $row->status_decorate;
    // patch v44: причин может быть несколько — в подсказке все через запятую
    $reasons = implode(", ", array_column($row->reasons_decorate, "label"));
    // secondary в Metronic — светло-серый: светлый текст на светлом фоне не читается
    $palette = fn($color) => in_array($color, ['secondary', 'light', 'white', '', null], true) ? 'dark' : $color;
@endphp
<div class="cell text-center">
    <span
       @class([
            "badge d-inline-flex align-items-center text-decoration-none",
            "badge-light-" . $palette($status['color']),
            "cursor-pointer" => $reasons !== "",
            $status['text'] ?? null
        ])
        @if($reasons !== "")
            data-bs-toggle="popover" data-bs-placement="bottom" title="{{ $reasons }}"
        @endif
    >

        <i class="fa-light {{ $status['icon'] }} fs-6 me-2"></i>
        <span class="fs-7">{{ $status['label'] }}</span>

    </span>
</div>
