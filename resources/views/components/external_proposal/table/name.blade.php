{{--
    Ячейка «Название» таблицы КП OSMOVIEW CP (patch v27).

    @var \App\Modules\Pub\ExternalProposal\Models\ExternalProposal $row
--}}
<a href="javascript:void(0);" onclick="box({href: '{{ route('external_proposal.box_detail', $row) }}'})" class="fw-semibold">{{ $row->name }}</a>
@unless($row->has_payload)
    <div class="fs-9 text-muted"><i class="fas fa-circle-info me-1"></i>детальные данные не загружены</div>
@endunless
