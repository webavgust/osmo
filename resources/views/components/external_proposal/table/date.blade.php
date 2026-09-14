{{--
    Ячейка «Дата» таблицы КП OSMOVIEW CP (patch v27).

    @var \App\Modules\Pub\ExternalProposal\Models\ExternalProposal $row
--}}
<span class="text-nowrap">{{ $row->created_at_remote?->format('d.m.Y') ?? '—' }}</span>
