@extends('layouts.layout')

@section('content')
    @php
        $group_hl = session('show_id') ?? $_GET['hl'] ?? null;
    @endphp
    <!-- -------------------------------------------------------------- -->
    <!-- Page wrapper  -->
    <!-- -------------------------------------------------------------- -->
    <style>
        #table_neuroservices:not(.filtered) span.search {
            display: none;
        }

        .todo-list-container,
        .todo-listing,
        .todo-listing > .table-responsive{
            min-height: 400px;
        }
    </style>

    <div class="email-app todo-box-container">
        <!-- -------------------------------------------------------------- -->
        <!-- Left Part -->
        <!-- -------------------------------------------------------------- -->
        <div class="left-part list-of-tasks">
            <a
                class="
                ti-menu ti-close
                btn btn-success
                show-left-part
                d-block d-md-none
              "
                href="javascript:void(0)"
            ></a>
            <div class="scrollable" style="height: 100%">
                <div class="p-3">
                    <a class="waves-effect waves-light btn btn-info d-block" href="{{ route('neuroservice_group.create') }}" id="add-group">Добавить группу</a>
                </div>

                <div class="divider"></div>
                <ul class="list-group">
                    @foreach($groups as $i => $group)
                        @php $active = 0; @endphp
                        @if((!empty($group_hl) && $group_hl == $group->id) || (empty($group_hl) && $i == 0)) @php $group_out = $group->id; $active = 1; @endphp  @endif
                        <x-neuroservice.group_list_item :group="$group" :active="$active"></x-neuroservice.group_list_item>
                    @endforeach
                </ul>
            </div>
        </div>
        <!-- -------------------------------------------------------------- -->
        <!-- Right Part -->
        <!-- -------------------------------------------------------------- -->
        <div class="right-part mail-list bg-white overflow-auto">
            <div id="todo-list-container">

                <div class="p-3 border-bottom">
                            <div class="input-group searchbar">
                                <span class="input-group-text" id="search">
                                    <i class="icon-magnifier text-muted"></i>
                                </span>
                                <input
                                    type="text"
                                    class="form-control"
                                    placeholder="Поиск нейросервиса"
                                    aria-describedby="search"
                                    id="table_search"
                                />
                                    <a
                                        class="btn waves-effect waves-light btn-primary"
                                        href="{{ route('neuroservice.create') }}/{{ $group_out }}"
                                        id="add-neuroservice"
                                    >
                                        <i class="mdi mdi-plus me-1"></i>
                                        Создать сервис
                                    </a>
                                    <a
                                        class="btn waves-effect waves-light btn-outline-secondary"
                                        href="{{ route('neuroservice_group.edit') }}/{{ $group_out }}"
                                        id="edit-neuroservice-group"
                                    >
                                        Редактировать группу
                                    </a>
                            </div>
                </div>

                <!-- Todo list-->
                <div class="todo-listing">

                    <div class="table-responsive">
                        <table class="table customize-table v-middle h-100" id="table_neuroservices">
                            <thead class="table-secondary">
                            <tr>
                                <th class="text-secondary px-0" width="1"></th>
                                <th class="text-secondary">Нейросервис</th>
                                <th class="text-secondary" width="300">Стоимость</th>
                                <th class="text-secondary text-center" width="50">Сценариев</th>
                                <th width="30"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($groups as $group)
                                @foreach($group->neuroservices()->get() as $neuroservice)
                                    <tr group="{{ $group->id }}" id="{{ $neuroservice->id }}" class="d-none">
                                        <td class="pe-0">
                                            @if($neuroservice->cb_registered)
                                                <x-ui.icon.solid icon="fa-circle-check" class="text-success me-2 fs-5 mt-1 cursor-help" title="Зарегистрировано"/>
                                            @endif
                                        </td>
                                        <td class="ps-3 p-1">
                                            <div>{{ $neuroservice->name }}</div>
                                            <div class="fs-1 text-primary">{{ $neuroservice->tech_name }}</div>
                                            <span class="search fw-bold">
                                                {{ $group->name }}
                                            </span>
                                        </td>
                                        <td class="p-1">
                                            <div>
                                                <x-ui.badge.default type="info" class="p-0 d-flex-inline align-items-center py-1">
                                                    <span class="px-2 fs-3 fw-bold me-2 border-end border-1 border-white">1</span>
                                                    <span class="fs-2 me-2 d-inline-block" style="width: 60px">{{ tools()->cost_normalize($neuroservice->cost['year'] ?? 0) }} ₽</span>
                                                </x-ui.badge.default>

                                                <x-ui.badge.default type="primary" class="p-0 d-flex-inline align-items-center py-1">
                                                    <span class="ps-2 pe-1 fs-3 fw-bold me-2 border-end border-1 border-white">
                                                        <i class="fa-solid fa-infinity"></i>
                                                    </span>

                                                    @if($neuroservice->cost['unlimited'] ?? 0 > 0)
                                                        <span class="fs-2 me-2">{{ tools()->cost_normalize($neuroservice->cost['unlimited'] ?? '0') }} ₽</span>
                                                    @else
                                                        <span class="fs-2 me-2">
                                                            {{ $multiplier * 100 }} % =
                                                            {{ !empty($neuroservice->cost['year']) ? tools()->cost_normalize($neuroservice->cost['year'] * $multiplier) : '0'   }} ₽
                                                        </span>
                                                    @endif
                                                </x-ui.badge.default>
                                            </div>
                                        </td>
                                        <td class="text-center p-1">
                                            <x-ui.a.box href="{{ route('neuroservice.box_scenarios', $neuroservice) }}">
                                                {{ $neuroservice->scenarios->count() }}
                                            </x-ui.a.box>
                                        </td>
                                        <td class="text-end p-1 pe-4">
                                                <div class="dropdown todo-action-dropdown">
                                                    <button class=" btn btn-link text-dark p-1 text-decoration-none todo-action-dropdown" type="button" id="more-action-1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                                        <i class="icon-options-vertical"></i>
                                                    </button>
                                                    <div
                                                        class="dropdown-menu dropdown-menu-right"
                                                    >
                                                        <a class="dropdown-item" href="{{ route('neuroservice.edit', $neuroservice) }}"><i class="fas fa-edit text-info me-1"></i>Редактировать</a>
                                                        <a class="dropdown-item" href="javascript:void(0);" onclick="javascript:delete_process({{$neuroservice->id}})" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="far fa-trash-alt text-danger me-1"></i>
                                                            Удалить
                                                        </a>
                                                        <form method="POST" action="{{route('neuroservice.delete', $neuroservice)}}">
                                                            @method('DELETE')
                                                            @csrf
                                                        </form>
                                                    </div>
                                                </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>



    </form>
    <div
        id="delete-modal"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="danger-header-modalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class=" modal-header modal-colored-header bg-danger text-white">
                    <h4 class="modal-title" id="danger-header-modalLabel">Удаление нейросервиса</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Не удалять"></button>
                </div>
                <div class="modal-body">
                    <h5 class="mt-0">Внимание!</h5>
                    <p>
                        Удаление нейросервиса необратимо!
                    </p>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Не удалять
                    </button>
                    <button
                        type="button"
                        onclick="javascript:delete_confirm();"
                        class="
                                btn btn-light-danger
                                text-danger
                                font-weight-medium
                              "
                    >
                        УДАЛИТЬ
                    </button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>

    <!-- -------------------------------------------------------------- -->
    <!-- End Wrapper -->


@endsection

@section('js')
    @parent
    <script src="/assets/libs/quill/dist/quill.min.js"></script>
    <script src="/dist/js/pages/todo/todo.js"></script>
    <script>
        var search = '';
        var current_group = {{ $group_out }};

        function delete_process(neuroservice_id)
        {
            window.neuroservice_id = neuroservice_id;
        }
        function delete_confirm()
        {
            neuroservice_id = window.neuroservice_id;
            $("tr[id=" + neuroservice_id + "]").find("form").submit();
        }
        function show_group(id) {
            $("#add-neuroservice").attr("href", "{{ route('neuroservice.create') }}/" + id);
            $("#edit-neuroservice-group").attr("href", "{{ route('neuroservice_group.edit') }}/" + id);

            $("table#table_neuroservices tr[group]").addClass("d-none");
            $("table#table_neuroservices tr[group='" + id + "']").removeClass("d-none");

            search_reset();
        }

        function search_process() {
            $("table#table_neuroservices").addClass("filtered");
            $("table#table_neuroservices tr[group]").addClass("d-none");

            $("table#table_neuroservices tr[group]").each(function() {
                if ($(this).find("td:nth-child(2)").text().toLowerCase().includes(search.toLowerCase())) { // Проверяем, содержится ли запрос в тексте
                    $(this).removeClass("d-none"); // Убираем класс d-none
                } else {
                    $(this).addClass("d-none"); // (опционально) добавляем класс d-none, если не соответствует
                }
            });
        }

        function search_reset() {
            $("table#table_neuroservices").removeClass("filtered");
            $("#table_search").val('');
        }

        $(document).ready(function() {
            $("#table_search").on("keyup change", function() {
                search = $(this).val();
                if(search) {
                    search_process(search);
                } else {
                    search_reset();
                    show_group(current_group);
                }
            });
            $(".list-group-item a[data-group-id]").on("click", function() {
                current_group = $(this).data("group-id");
                show_group(current_group);
            });
            show_group(current_group);
        });
    </script>
@endsection
