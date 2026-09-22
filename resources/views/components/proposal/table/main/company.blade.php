<div class="cell">
    @if($row->company)
        <a href="{{ route('company.detail', $row->company) }}" class="text-dark">
            {{ $row->company->name }}
        </a>
    @else
        <span class="text-muted">заказчик не указан</span>
    @endif
</div>
