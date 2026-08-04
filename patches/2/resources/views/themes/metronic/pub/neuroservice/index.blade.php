@extends('layouts.layout')

@section('content')
    @php
        $group_hl = session('show_id') ?? $_GET['hl'] ?? null;
    @endphp

    <style>
        #table_neuroservices:not(.filtered) span.search { display: none; }
        #neuroservice_groups .list-group-item a {
            border-radius: .475rem;
            color: var(--bs-gray-700);
        }
        #neuroservice_groups .list-group-item a:hover { background: var(--bs-gray-100); }
        #neuroservice_groups .list-group-item a.active {
            background: var(--bs-primary-light);
            color: var(--bs-primary);
            font-weight: 600;
        }
        #neuroservice_list { min-height: 420px; }
    </style>

    <div class="card card-flush">
        <div class="card-body p-0">
            <div class="d-flex flex-column flex-lg-row">

                {{-- Группы --}}
                <div class="flex-column w-100 w-lg-325px border-end border-gray-200 flex-shrink-0">
                    <div class="p-5 border-bottom border-gray-200">
                        <a href="{{ route('neuroservice_group.create') }}" id="add-group"
                           class="btn btn-light-primary w-100 d-flex flex-center">
                            <i class="fa-light fa-folder-plus fs-4 me-2"></i>
                            Добавить группу
                        </a>
                    </div>

                    <ul class="list-group list-group-flush p-3" id="neuroservice_groups">
                        @foreach($groups as $i => $group)
                            @php $active = 0; @endphp
                            @if((!empty($group_hl) && $group_hl == $group->id) || (empty($group_hl) && $i == 0))
                                @php $group_out = $group->id; $active = 1; @endphp
                            @endif
                            <x-neuroservice.group_list_item :group="$group" :active="$active"></x-neuroservice.group_list_item>
                        @endforeach
                    </ul>
                </div>

                {{-- Нейросервисы --}}
                <div class="flex-row-fluid" id="todo-list-container">
                    <div class="p-5 border-bottom border-gray-200 d-flex flex-wrap gap-3">
                        <div class="position-relative flex-grow-1" style="min-width: 240px">
                            <i class="fa-light fa-magnifying-glass fs-4 position-absolute top-50 translate-middle-y ms-4 text-gray-500"></i>
                            <input type="text" class="form-control form-control-solid ps-12"
                                   placeholder="Поиск нейросервиса" id="table_search" />
                        </div>

                        <a class="btn btn-primary" href="{{ route('neuroservice.create') }}/{{ $group_out }}" id="add-neuroservice">
                            <i class="fa-light fa-plus fs-4 me-1"></i>
                            Создать сервис
                        </a>

                        <a class="btn btn-light" href="{{ route('neuroservice_group.edit') }}/{{ $group_out }}" id="edit-neuroservice-group">
                            <i class="fa-light fa-pen fs-5 me-1"></i>
                            Редактировать группу
                        </a>
                    </div>

                    <div class="table-responsive" id="neuroservice_list">
                        <table class="table table-row-dashed table-row-gray-300 align-middle mb-0" id="table_neuroservices">
                            <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-4 rounded-start" width="1"></th>
                                <th>Нейросервис</th>
                                <th width="300">Стоимость</th>
                                <th class="text-center" width="50">Сценариев</th>
                                <th class="rounded-end" width="30"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($groups as $group)
                                @foreach($group->neuroservices()->get() as $neuroservice)
                                    <tr group="{{ $group->id }}" id="{{ $neuroservice->id }}" class="d-none">
                                        <td class="ps-4 pe-0">
                                            @if($neuroservice->cb_registered)
                                                <x-ui.icon.solid icon="fa-circle-check" class="text-success fs-5 cursor-help" title="Зарегистрировано"/>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="text-gray-800 fw-semibold">{{ $neuroservice->name }}</div>
                                            <div class="fs-8 text-primary">{{ $neuroservice->tech_name }}</div>
                                            <span class="search fw-bold fs-8 text-muted">{{ $group->name }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <span class="badge badge-light-info d-flex align-items-center gap-2 py-2">
                                                    <span class="fw-bold border-end border-info pe-2">1</span>
                                                    <span class="d-inline-block text-end" style="width: 70px">{{ tools()->cost_normalize($neuroservice->cost['year'] ?? 0) }} ₽</span>
                                                </span>

                                                <span class="badge badge-light-primary d-flex align-items-center gap-2 py-2">
                                                    <span class="fw-bold border-end border-primary pe-2">
                                                        <i class="fa-solid fa-infinity"></i>
                                                    </span>
                                                    @if($neuroservice->cost['unlimited'] ?? 0 > 0)
                                                        <span>{{ tools()->cost_normalize($neuroservice->cost['unlimited'] ?? '0') }} ₽</span>
                                                    @else
                                                        <span>
                                                            {{ $multiplier * 100 }} % =
                                                            {{ !empty($neuroservice->cost['year']) ? tools()->cost_normalize($neuroservice->cost['year'] * $multiplier) : '0' }} ₽
                                                        </span>
                                                    @endif
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <x-ui.a.box href="{{ route('neuroservice.box_scenarios', $neuroservice) }}">
                                                {{ $neuroservice->scenarios->count() }}
                                            </x-ui.a.box>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="dropdown">
                                                <button class="btn btn-icon btn-sm btn-light btn-active-light-primary" type="button"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa-light fa-ellipsis-vertical fs-4"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end py-2">
                                                    <a class="dropdown-item px-4 py-2" href="{{ route('neuroservice.edit', $neuroservice) }}">
                                                        <i class="fa-light fa-pen text-info me-2"></i>Редактировать
                                                    </a>
                                                    <a class="dropdown-item px-4 py-2" href="javascript:void(0);"
                                                       onclick="javascript:delete_process({{ $neuroservice->id }})"
                                                       data-bs-toggle="modal" data-bs-target="#delete-modal">
                                                        <i class="fa-light fa-trash text-danger me-2"></i>Удалить
                                                    </a>
                                                    <form method="POST" action="{{ route('neuroservice.delete', $neuroservice) }}">
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

    <div id="delete-modal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light-danger">
                    <h4 class="modal-title text-danger">Удаление нейросервиса</h4>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-danger" data-bs-dismiss="modal" aria-label="Не удалять">
                        <i class="fa-light fa-xmark fs-2"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <h5 class="mt-0">Внимание!</h5>
                    <p class="mb-0">Удаление нейросервиса необратимо!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Не удалять</button>
                    <button type="button" onclick="javascript:delete_confirm();" class="btn btn-danger">УДАЛИТЬ</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
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

            $("#neuroservice_groups a[data-group-id]").removeClass("active");
            $("#neuroservice_groups a[data-group-id='" + id + "']").addClass("active");

            $("table#table_neuroservices tr[group]").addClass("d-none");
            $("table#table_neuroservices tr[group='" + id + "']").removeClass("d-none");

            search_reset();
        }

        function search_process() {
            $("table#table_neuroservices").addClass("filtered");
            $("table#table_neuroservices tr[group]").addClass("d-none");

            $("table#table_neuroservices tr[group]").each(function() {
                if ($(this).find("td:nth-child(2)").text().toLowerCase().includes(search.toLowerCase())) {
                    $(this).removeClass("d-none");
                } else {
                    $(this).addClass("d-none");
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
