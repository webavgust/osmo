@extends('layouts.layout')

@section('content')
    @php
            $group_out = 0;
            $group_hl = session('show_id') ?? $_GET['hl'] ?? null;
    @endphp
    <!-- -------------------------------------------------------------- -->
    <!-- Page wrapper  -->
    <!-- -------------------------------------------------------------- -->
    <style>
        #table_scenarios.filtered span.scenario_name {
            font-weight: 500;
        }
        #table_scenarios:not(.filtered) span.search {
            display: none;
        }
        .todo-list-container,
        .todo-listing,
        .todo-listing > .table-responsive{
            min-height: 400px;
        }
        tr.bg-odd {
            background: #eff4ff;
        }

        .right-part:not(:has(#show_unactive:checked)) tr.unactive {
            display: none!important
        }
    </style>

    <div class="email-app todo-box-container">
        <!-- -------------------------------------------------------------- -->
        <!-- Left Part -->
        <!-- -------------------------------------------------------------- -->
        <div class="left-part list-of-tasks">
            <a
                class="
                fa-light fa-bars
                btn btn-success
                show-left-part
                d-block d-md-none
              "
                href="javascript:void(0)"
            ></a>
            <div class="scrollable" style="height: 100%">
                <div class="p-3">
                    <a class="btn btn-primary d-block" href="{{ route('scenario_group.create') }}" id="add-group">Добавить группу</a>
                </div>

                <div class="divider"></div>
                <ul class="list-group">
                    @foreach($groups as $i => $group)
                        @php $active = 0; @endphp
                        @if((!empty($group_hl) && $group_hl == $group->id) || (empty($group_hl) && $i == 0)) @php $group_out = $group->id; $active = 1; @endphp  @endif
                        <x-scenario.group_list_item :group="$group" :active="$active"></x-scenario.group_list_item>
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
                                    <i class="fa-light fa-magnifying-glass text-muted"></i>
                                </span>
                                <input
                                    type="text"
                                    class="form-control"
                                    placeholder="Поиск сценария"
                                    aria-describedby="search"
                                    id="table_search"
                                />
                                    <a
                                        class="btn btn-primary"
                                        href="{{ route('scenario.create') }}/{{ $group_out }}"
                                        id="add-scenario"
                                    >
                                        <i class="fa-light fa-plus me-1"></i>
                                        Создать сценарий
                                    </a>
                                    <a
                                        class="btn btn-light"
                                        href="{{ route('scenario_group.edit') }}/{{ $group_out }}"
                                        id="edit-scenario-group"
                                    >
                                        Редактировать группу
                                    </a>

                                <div>
                                    <input type="checkbox" class="btn-check" autocomplete="off" id="show_unactive">
                                    <label class="btn btn-light-danger fw-semibold rounded-pill" for="show_unactive"
                                        style="border-top-left-radius: 0!important; border-bottom-left-radius: 0!important; padding-bottom: 7px"
                                    >Показывать неактивные</label>
                                </div>

                            </div>
                </div>

                <!-- Todo list-->
                <div class="todo-listing">

                    <div class="">
                        <table class="table table-row-dashed table-row-gray-300 align-middle h-100" id="table_scenarios">
                            <thead class="fw-bold text-muted bg-light">
                            <tr class="fs-6">
                                <th class="ps-3" width="1">Номер</th>
                                <th class="px-0" width="1"></th>
                                <th class="">Сценарий</th>
                                <th class="">Нейросервисы</th>
                                <th class="" width="300">Стоимость</th>
                                <th width="30"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($groups as $group)
                                @foreach($group->scenarios as $scenario)
                                    <tr group="{{ $group->id }}" id="{{ $scenario->id }}" @class(["fs-6", "d-none", "unactive" => !$scenario->active, "bg-odd" => $loop->odd, "bg-light-warning" => $scenario->neuroservices->contains(env('SERVICE_NEED_CORRECTION'))])>
                                        <td @class(["ps-3 p-1 text-nowrap"])>
                                            {{ $scenario->number }}
                                        </td>
                                        <td class="px-0">
                                            @if($scenario->cb_registered)
                                                <x-ui.icon.solid icon="fa-circle-check" class="text-success me-2 fs-5 mt-1 cursor-help" title="Зарегистрировано"/>
                                            @endif
                                        </td>
                                        <td @class(["ps-4 p-1"])>
                                            <div>
                                                <div>
                                                    @if(!$scenario->active)
                                                        <span class="text-danger">[!]</span>
                                                    @endif
                                                    <span class="scenario_name">{{ $scenario->name }}</span>
                                                </div>
                                                <span class="search">
                                                    {{ $group->name }}
                                                </span>

                                                @if($scenario->neuroservices->contains(env('SERVICE_NEED_CORRECTION')))
                                                    <div class="mt-1 text-warning">
                                                        <x-ui.icon.regular icon="fa-triangle-exclamation" class="me-1"/>
                                                        Содержит нейросервис "До выяснения"
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-1">
                                            @if($scenario->neuroservices->isEmpty())
                                                <span class="text-danger">Нет добавленных нейросервисов!</span>
                                            @else
                                                <a href="javascript:void(0)" onclick="javascript:$(this).next('.d-none').removeClass('d-none');$(this).remove(); " class="text-nowrap">{{ tools()->num_rus($scenario->neuroservices->count(), ["нейросервиса", "нейросервис", "нейросервисов"], true) }}</a>

                                                <div class="d-none">
                                                    @foreach($scenario->neuroservices as $service)
                                                        <div>
                                                            <span class="text-info fw-bold">[{{ $service->neuroservice_group->name }}]</span> {{ $service->name }}
                                                        </div>
                                                    @endforeach
                                                </div>

                                            @endif
                                        </td>
                                        <td class="p-0 align-content-start">
                                            <table class="table  m-0" style="border-top-color: white; border-bottom-color: white">
                                                @foreach($scenario->cost_rules as $count => $rule)
                                                    <tr @class(["border-bottom" => !$loop->last])>
                                                        <td width="150" class="p-2 text-start">от {{ $count }} шт.</td>
                                                        <td width="150" class="p-2 text-end">{{ tools()->cost_normalize($rule['y']) }} ₽</td>
                                                        <td width="150" class="p-2 text-end">{{ tools()->cost_normalize($rule['u']) }} ₽</td>
                                                    </tr>
                                                @endforeach
                                            </table>

                                            <div class="text-nowrap">


                                            </div>
                                        </td>
                                        <td class="text-end p-1 pe-4">
                                                <div class="dropdown todo-action-dropdown">
                                                    <button class=" btn btn-link text-dark p-1 text-decoration-none todo-action-dropdown" type="button" id="more-action-1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa-light fa-ellipsis-vertical"></i>
                                                    </button>
                                                    <div
                                                        class="dropdown-menu dropdown-menu-right"
                                                    >
                                                        <a class="dropdown-item" href="{{ route('scenario.edit', $scenario) }}"><i class="fas fa-edit text-info me-1"></i>Редактировать</a>
                                                        <a class="dropdown-item" href="javascript:void(0);" onclick="javascript:delete_process({{$scenario->id}})" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="far fa-trash-alt text-danger me-1"></i>
                                                            Удалить
                                                        </a>
                                                        <form method="POST" action="{{route('scenario.delete', $scenario)}}">
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
                    <h4 class="modal-title" id="danger-header-modalLabel">Удаление сценария</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Не удалять"></button>
                </div>
                <div class="modal-body">
                    <h5 class="mt-0">Внимание!</h5>
                    <p>
                        Удаление сценария необратимо!
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
                        class="btn btn-light-danger fw-semibold"
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

        function delete_process(scenario_id)
        {
            window.scenario_id = scenario_id;
        }
        function delete_confirm()
        {
            scenario_id = window.scenario_id;
            $("tr[id=" + scenario_id + "]").find("form").submit();
        }
        function show_group(id) {
            $("#table_search").val('');

            $("#add-scenario").attr("href", "{{ route('scenario.create') }}/" + id);
            $("#edit-scenario-group").attr("href", "{{ route('scenario_group.edit') }}/" + id);

            $("table#table_scenarios tr[group]").addClass("d-none");
            $("table#table_scenarios tr[group='" + id + "']").removeClass("d-none");

            search_reset();

        }

        function search_process() {
            $("table#table_scenarios").addClass("filtered");
            $("table#table_scenarios tr[group]").addClass("d-none");

            $("table#table_scenarios tr[group]").each(function() {
                var firstTdText = $(this).find("td:first").text().toLowerCase(); // Получаем текст первого td
                var secondTdText = $(this).find("td:nth-child(3)").text().toLowerCase(); // Получаем текст первого td

                if (firstTdText.includes(search.toLowerCase()) || secondTdText.includes(search.toLowerCase())) { // Проверяем, содержится ли запрос в тексте
                    $(this).removeClass("d-none"); // Убираем класс d-none
                } else {
                    $(this).addClass("d-none"); // (опционально) добавляем класс d-none, если не соответствует
                }
            });
        }

        function search_reset() {
            $("table#table_scenarios").removeClass("filtered");
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
