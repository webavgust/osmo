<div class="tr">
    <span class="th">
        {{ $work->work->name }}
        @if(!empty($work->contractor))
            ({{ $work->contractor->name }})
        @endif

    </span>
    <span class="td">
        {{ $work->count }} *
        @if(empty($work->cost))
            цена не указана
        @else
            {{ tools()->cost_normalize($work->cost) }} ₽
            = {{ tools()->cost_normalize($work->total) }} ₽
        @endif
                                    </span>
</div>
