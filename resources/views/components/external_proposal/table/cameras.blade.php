{{--
    Ячейка «Камеры» таблицы КП OSMOVIEW CP (patch v27).

    @var \App\Modules\Pub\ExternalProposal\Models\ExternalProposal $row
--}}
@if(!empty($row->cameras)){{ tools()->cost_normalize($row->cameras) }}@else<span class="text-muted">—</span>@endif
