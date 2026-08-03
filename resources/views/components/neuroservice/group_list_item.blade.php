
<li class="list-group-item p-0 border-0">
    <a
        href="javascript:void(0)"
        class="todo-link
            @if($active) active @endif
        list-group-item-action p-3 d-flex align-items-center"
        id="group-{{$group->id}}" data-group-id="{{$group->id }}"
    >
        <span>
            @if($group->icon)<i class="{{$group->icon}} me-2"></i>@endif
            {{ $group->name }}
        </span>

        @if($group->neuroservices->count())
            <span class="todo-badge badge bg-light-info text-info rounded-pill px-3 font-weight-medium ms-auto">{{ $group->neuroservices()->count()}}</span>
        @endif
    </a>
</li>
