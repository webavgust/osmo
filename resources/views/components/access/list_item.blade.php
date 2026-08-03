<div id="{{$access->id}}" type="access" class="todo-item p-3 border-bottom position-relative group-{{$groupId}} " @if($groupOut != $groupId) style="display: none;" @endif>
    <div class="inner-item d-flex align-items-start">
        <div class="w-100">
            <div class=" checkbox checkbox-info d-flex align-items-start form-check">
                <div>
                    <div class="content-todo">
                        <h5
                            class="font-weight-medium fs-4 todo-header"
                            data-todo-header="{{ $access->name }}"
                        >
                            {{ $access->name }}

                            @if($access->protected)
                                <div class="me-2 ms-1 badge bg-secondary">Защищенный</div>
                            @endif

                            <div class="
                      mb-1
                      badge
                      font-weight-medium
                      bg-light-info
                      text-info
                    ">{{ $access->code  }}</div>
                        </h5>
                        <div
                            class="todo-subtext text-muted fs-3"
                        >
                            {{ $access->description }}
                        </div>
                    </div>
                </div>
                @if(!$access->protected)
                    @can('access_create')
                        <div class="ms-auto">
                            <div class="dropdown-action">
                                <div class="dropdown todo-action-dropdown">
                                    <button class=" btn btn-link text-dark p-1 dropdown-toggle text-decoration-none todo-action-dropdown" type="button" id="more-action-1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="icon-options-vertical"></i>
                                    </button>
                                    <div
                                        class="dropdown-menu dropdown-menu-right"
                                    >
                                            <a class="dropdown-item" href="{{ route('access.edit', $access) }}"><i class="fas fa-edit text-info me-1"></i>Редактировать</a>
                                            <a class="dropdown-item" href="javascript:void(0);" onclick="javascript:delete_process({{$access->id}})" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="far fa-trash-alt text-danger me-1"></i>
                                                Удалить
                                            </a>
                                        <form method="POST" action="{{route('access.delete', $access)}}">
                                            @method('DELETE')
                                            @csrf
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endcan
                @endif
            </div>
            <!-- Content -->
        </div>
    </div>
</div>
