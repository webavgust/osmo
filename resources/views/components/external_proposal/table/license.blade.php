{{--
    Ячейка «Валюта / лицензии» таблицы КП OSMOVIEW CP (patch v27).

    Подписи и цвета типов лицензий берутся из ExternalProposal::LICENSE_TYPES —
    оттуда же собирается список в фильтре, чтобы они не разъехались.

    @var \App\Modules\Pub\ExternalProposal\Models\ExternalProposal $row
--}}
@php
    $license = $row->has_payload ? ($row->license_type ?: null) : null;
    $label = $license ? (\App\Modules\Pub\ExternalProposal\Models\ExternalProposal::LICENSE_TYPES[$license] ?? null) : null;
@endphp

@if(!$row->has_payload)
    <span class="text-muted">—</span>
@else
    <span class="badge badge-light-dark me-1">{{ $row->currency }}</span>
    @if(!empty($label))
        <span class="badge badge-light-{{ $label['color'] }}">{{ $label['label'] }}</span>
    @endif
    <div class="fs-9 text-muted mt-1 text-nowrap">сценариев {{ count($row->payload['items'] ?? []) }} · работ {{ count($row->payload['detailedWorks'] ?? []) }}{{ !empty($row->payload['isVatIncluded']) ? ' · НДС' : ' · без НДС' }}</div>
@endif
