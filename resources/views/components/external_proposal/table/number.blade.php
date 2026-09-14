{{--
    Ячейка «Номер» таблицы КП OSMOVIEW CP (patch v27).

    Каталог общий на обе темы, поэтому иконки — классом `fas fa-*`.

    @var \App\Modules\Pub\ExternalProposal\Models\ExternalProposal $row
--}}
<div class="fw-bold text-nowrap">
    @if(!empty($row->external_number))
        {{ $row->external_number }}
    @else
        <span class="text-muted">—</span>
    @endif
</div>
