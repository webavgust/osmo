<li class="list-group-item p-0 border-0 mb-1">
    <a href="javascript:void(0)"
       class="todo-link d-flex align-items-center p-3 text-decoration-none @if($active) active @endif"
       id="group-{{ $group->id }}" data-group-id="{{ $group->id }}">
        <span class="d-flex align-items-center">
            @if($group->icon)<i class="{{ $group->icon }} fs-5 me-3"></i>@endif
            {{ $group->name }}
        </span>

        @if($group->neuroservices->count())
            <span class="badge badge-light-info rounded-pill ms-auto">{{ $group->neuroservices()->count() }}</span>
        @endif
    </a>
</li>
