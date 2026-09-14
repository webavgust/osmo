{{--
    Ячейка «Заказчик» таблицы КП OSMOVIEW CP (patch v27).

    @var \App\Modules\Pub\ExternalProposal\Models\ExternalProposal $row
--}}
@if(!empty($row->customer)){{ $row->customer }}@else<span class="text-muted">—</span>@endif
