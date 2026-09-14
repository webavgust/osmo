<div class="cell">
    <div class="fw-bolder fs-7 align-center d-flex justify-content-start">
        <span>
            @if(!empty($row->external))
                {{-- перенесённое КП: только облачко справа от номера, подробности — в попапе --}}
                <a href="{{ route('external_proposal.box_detail', $row->external) }}"
                   onclick="javascript:box({href: this.href}); return false;"
                   class="text-info ms-1"
                   title="{{ $row->external->source_label }} {{ $row->external->external_number }}@if($row->external->transferred_at) · перенесено {{ $row->external->transferred_at->format('d.m.Y') }}@endif">
                    <i class="fas fa-cloud-arrow-down"></i>
                </a>
            @endif
            <a href="{{ route('proposal.detail', [$row, $row->iteration]) }}">
                {{ $row->name }}
            </a>
        </span>

        @if($row->iteration > 1)
            <x-ui.badge.light_rounded type="primary" class="text-white ms-2 mb-1">
                {{ $row->iteration }}
            </x-ui.badge.light_rounded>
        @endif
    </div>
</div>
