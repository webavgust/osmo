<div class="cell">
    <a href="{{ route('partner.detail', $row->partner) }}">
        <x-ui.badge.light type="info" class="text-info-700 bg-hover-info text-hover-white">
            {{ $row->partner->name }}
        </x-ui.badge.light>
    </a>
    <span class="px-1 text-dark-800">--></span>
    @if($row->company)
        <a href="{{ route('company.detail', $row->company) }}">
            <x-ui.badge.light type="primary" class="text-primary-700 bg-hover-primary text-hover-white">
                {{ $row->company->name }}
            </x-ui.badge.light>
        </a>
    @else
        <x-ui.badge.light type="secondary" class="text-muted">заказчик не указан</x-ui.badge.light>
    @endif
</div>
