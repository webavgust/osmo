<div class="cell">
    {{ $row->number }}
    @if(!empty($row->external))
        {{-- перенесённое КП: только облачко справа от номера, подробности — в попапе --}}
        <a href="{{ route('external_proposal.box_detail', $row->external) }}"
           onclick="javascript:box({href: this.href}); return false;"
           class="text-info ms-1"
           title="{{ $row->external->source_label }} {{ $row->external->external_number }}@if($row->external->transferred_at) · перенесено {{ $row->external->transferred_at->format('d.m.Y') }}@endif">
            <i class="fas fa-cloud-arrow-down"></i>
        </a>
    @endif
</div>
