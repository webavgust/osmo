<li class="list-group-item p-0 border-0 mb-1">
    <a href="javascript:void(0)"
       class="todo-link d-flex align-items-center p-3 text-decoration-none @if($active) active @endif"
       id="group-{{ $group->id }}" data-group-id="{{ $group->id }}">
        <span class="d-flex align-items-center">
            @if($group->number)
                <span class="badge badge-light text-gray-600 fw-bold me-3">{{ $group->number }}</span>
            @endif
            @if($group->icon)<i class="{{ $group->icon }} fs-5 me-2"></i>@endif
            {{ $group->name }}
        </span>

        @if($group->scenarios->count())
            <span @class(["badge rounded-pill fw-semibold ms-auto",
                "badge-light-info" => !$group->has_empty,
                "badge-light-danger" => $group->has_empty
            ])>{{ $group->scenarios()->count() }}</span>
        @endif
    </a>
</li>
