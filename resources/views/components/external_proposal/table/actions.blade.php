{{--
    Ячейка «Действия» таблицы КП OSMOVIEW CP (patch v27).

    У перенесённой записи (зелёная строка) кнопки зелёные, а перенос
    превращается в «Дублировать» — он создаёт ещё одно новое КП.

    @var \App\Modules\Pub\ExternalProposal\Models\ExternalProposal $row
--}}
@php
    $transferred = !empty($row->proposal_group);
    $main = $transferred ? 'btn-success' : 'btn-light-primary';
@endphp

<div class="d-flex justify-content-end gap-1 text-nowrap">
    @if($row->has_payload)
        <button type="button" class="btn btn-sm {{ $main }}" onclick="box({href: '{{ route('external_proposal.box_transfer', $row) }}'})" title="{{ $transferred ? 'Перенести ещё раз как новое КП' : 'Перенести в наше КП' }}">
            @if($transferred)
                <i class="fas fa-copy fs-6 me-1"></i>Дублировать
            @else
                <i class="fas fa-arrow-right-to-bracket fs-6 me-1"></i>Перенести
            @endif
        </button>
    @else
        <button type="button" class="btn btn-sm {{ $main }}" onclick="external_fetch({{ $row->id }}, '{{ route('api.external_proposal.fetch', $row) }}')" title="Загрузить detail из API">
            <i class="fas fa-cloud-arrow-down fs-6 me-1"></i>Загрузить
        </button>
    @endif

    {{-- кнопка «Подробнее» убрана по просьбе владельца: попап открывается кликом по названию КП --}}
</div>
