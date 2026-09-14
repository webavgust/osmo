{{--
    Ячейка «Перенос» таблицы КП OSMOVIEW CP (patch v27).

    @var \App\Modules\Pub\ExternalProposal\Models\ExternalProposal $row
--}}
@php
    $proposal = $row->proposal;
    $transferred_by = $row->transferred_user?->full_name ?? $row->transferred_user?->name;
@endphp

@if(empty($row->proposal_group))
    <span class="badge badge-light">не перенесено</span>
@else
    @if(!empty($proposal))
        <a href="{{ route('proposal.detail', [$proposal, $proposal->iteration]) }}" class="badge badge-light-success fs-7" title="Открыть наше КП"><i class="fas fa-arrow-right-to-bracket me-1"></i>{{ $proposal->number }}</a>
    @else
        <span class="badge badge-light-warning">КП не найдено</span>
    @endif
    <div class="fs-9 text-muted mt-1 text-nowrap" title="{{ $transferred_by }}">{{ $row->transferred_at?->format('d.m.Y H:i') }}</div>
@endif
