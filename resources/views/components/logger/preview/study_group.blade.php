<a href="{{ route('logger.detail', [$instance->getModuleSlug(), $instance->id]) }}">
    <div @class([
        'px-3',
        'py-2',
        'text-decoration-line-through text-danger' => $instance->trashed()
    ])>
        Учебная группа №{{ $instance->id }}
    </div>
</a>
