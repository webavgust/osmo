<a href="{{ route('logger.detail', [$instance->getModuleSlug(), $instance->id]) }}">
    <div @class([
        'px-3',
        'py-2',
        'text-decoration-line-through text-danger' => $instance->trashed()
    ])>
        <span class="me-3">№{{ $instance->id }}</span>
        <strong>{{ _date($instance->start_at) }}</strong>
        <span class="ms-3">
            {{ $instance->start_at->format("H:i") }}
            -
            {{ $instance->end_at->format("H:i") }}
        </span>
    </div>
</a>
