<div class="lesson plan noHover border-0 flex-column py-2 @if($work->deleted_at) deleted muted @endif">
    <div class="font-14 d-flex justify-content-between align-items-start">
        <span>{{ $work->work->name }}</span>

        @if(!empty($work->contractor))
            <x-ui.badge.light type="secondary">
                {{ $work->contractor->name }}
            </x-ui.badge.light>
        @endif

    </div>
    <div class="mt-1 d-flex justify-content-between">

        <x-ui.badge.light type="primary">
                {{ $work->count }} *
                @if(empty($work->cost))
                    цена не указана
                @else
                    {{ tools()->cost_normalize($work->cost) }} ₽
                    = {{ tools()->cost_normalize($work->total) }} ₽
                @endif
        </x-ui.badge.light>
    </div>
</div>

