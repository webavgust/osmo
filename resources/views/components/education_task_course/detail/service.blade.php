<div class="tr">
    <span class="th">
        {{ $service->service->name }}
    </span>
    <span class="td">
        {{ $service->count }} *
        @if(empty($service->cost))
            цена не указана
        @else
            {{ tools()->cost_normalize($service->cost) }} ₽
            = {{ tools()->cost_normalize($service->total) }} ₽
        @endif
    </span>
</div>
