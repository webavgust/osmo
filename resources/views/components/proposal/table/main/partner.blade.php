<div class="cell">
    <a href="{{ route('partner.detail', $row->partner) }}">
            <x-ui.badge.default type="warning" class="text-dark">
        {{ $row->partner->name }}
        </x-ui.badge.default>
    </a>
    <span class="px-1">--></span>
    <a href="{{ route('company.detail', $row->company) }}" class="text-dark">
        <x-ui.badge.default type="primary" class="text-white">
            {{ $row->company->name }}
        </x-ui.badge.default>
    </a>
</div>

