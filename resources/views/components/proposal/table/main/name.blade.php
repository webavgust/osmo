{{-- patch v33: tree = 'child' — ветка дерева: второстепенное КП строкой под своим главным --}}
@php($is_branch = ($tree ?? null) === 'child')
<div class="cell">
    <div class="fw-bolder fs-7 align-center d-flex justify-content-start">
        @if($is_branch)
            <span class="osmo-tree-branch"></span>
        @endif
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
            {{-- patch v33: главное КП — значок связки, номера второстепенных в подсказке --}}
            @if(!empty($link_secondaries) && $link_secondaries->isNotEmpty())
                <i class="fas fa-link text-info ms-1"
                   title="Главное КП, связано с {{ $link_secondaries->map(fn($item) => \App\Modules\Pub\Proposal\Models\ProposalLink::refOf($item))->join(', ') }}"></i>
            @endif
        </span>

        @if($row->iteration > 1)
            <x-ui.badge.light_rounded type="primary" class="text-white ms-2 mb-1">
                {{ $row->iteration }}
            </x-ui.badge.light_rounded>
        @endif
    </div>
    {{-- patch v33: второстепенное КП — плашка; под главным (ветка) номер главного не повторяется --}}
    @if(!empty($link_main))
        <div @class(['osmo-tree-indent' => $is_branch])>
            <a href="{{ route('proposal.detail', [$link_main, $link_main->iteration]) }}"
               title="Второстепенное к {{ \App\Modules\Pub\Proposal\Models\ProposalLink::refOf($link_main) }}: только просмотр, в расчётах не участвует">
                <x-ui.badge.light type="warning" class="fs-8">
                    @if($is_branch)
                        второстепенное
                    @else
                        второстепенное → {{ \App\Modules\Pub\Proposal\Models\ProposalLink::refOf($link_main) }}
                    @endif
                </x-ui.badge.light>
            </a>
        </div>
    @endif
</div>
