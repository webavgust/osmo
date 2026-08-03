<div class="lesson plan noHover border-0 flex-column py-2 @if($service->deleted_at) deleted muted @endif">
    <div class="font-14 d-flex justify-content-between align-items-start">
        <span>{{ $service->service->name }}</span>
    </div>
    <div class="mt-1 d-flex justify-content-between">
        <x-ui.badge.light type="primary">
                {{ $service->count }} *
                @if(empty($service->cost))
                    цена не указана
                @else
                    {{ tools()->cost_normalize($service->cost) }} ₽
                    = {{ tools()->cost_normalize($service->total) }} ₽
                @endif
        </x-ui.badge.light>
    </div>
</div>

