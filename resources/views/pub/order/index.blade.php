@extends('layouts.layout')

@section('styles')
    <link rel="stylesheet" href="/assets/libs/bootstrap-table/dist/bootstrap-table.min.css">
    <link href="/dist/modules/daterangepicker/daterangepicker.css" rel="stylesheet"/>

    <style>
        .comment div.alert   {
            cursor: pointer;
        }
        tr[data-index]:hover .comment  div.alert  {
            background: #EEE;
        }
        tr[data-index] .comment div.alert:hover {
            background: #DDD;
            color: #444!important;
        }
        .comment .title {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 11px;
        }

        tr.warning td {
            background: #ffffed;
        }
        tr.danger td {
            background: #ffeded;
        }

        @media screen and (max-width: 900px) {
            .comment {
                min-width: 80vw;
            }
        }
    </style>

@endsection

    @section('content')
    <div id="filter">
        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#filter-modal">
            <i class="mdi mdi-filter-outline"></i>
            Фильтр <span class="count @unless($filter) d-none @endunless">(@if($filter){{ count($filter) }}
                ) @endif</span>
        </button>

        <button type="button" id="filter_clear" class="
                @unless($filter) d-none @endunless
            btn btn-sm btn-icon btn-pure btn-outline
            delete-row-btnКу
" data-bs-toggle="tooltip" data-original-title="Delete" data-bs-original-title="" title="">
            <i class="ti-close" aria-hidden="true"></i> Убрать
        </button>

        @if(!empty($presets))
            <div class="btn-group" role="group">
                <button id="btnGroupDrop1" type="button" class="btn btn-outline-secondary font-weight-medium dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Быстрый фильтр
                </button>
                <div class="dropdown-menu" aria-labelledby="btnGroupDrop1" style="">
                    @foreach($presets as $chr => $preset)
                        <a class="dropdown-item p-0 ps-2" href="javascript:set_preset('{{ $chr }}')">{{ $preset['name'] }}</a>
                    @endforeach
                </div>
            </div>
        @endif

        @if(auth()->user()->isAdmin())
            <button data-url="/url" type="button" class="btn waves-effect waves-light btn-outline-primary btn-progress-bar" onclick="javascript:progress($(this), '{{ route('api.order.sync_all', ['_token' => auth()->user()->ajax_token ]) }}')">
            <loader>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    <span class="progress-bar progress-bar-striped
                                                bg-primary
                                                progress-bar-animated" style="width: 100%"></span>
                </loader>
                <span class="name">Синхронизировать с порталом</span>
            </button>
        @endif

    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body pt-2">
                        <table class="table"
                               id="table_orders"
                               data-search="true"
                               {{--                                data-search-text="Баба"--}}
                               data-toolbar="#filter"
                               data-page="1"
                               data-pagination="true"
                               data-page-size="10"
                               data-page-list="[10, 25, 50, 100]"
                               data-side-pagination="server"
                               data-locale="ru-RU"
                               data-responsible="true  "
                               data-row-style="rowStyle"
                               data-url="{{ route("api.order.list", ['_token' => auth()->user()->ajax_token]) }}"
                        ></table>

                    </div>
                </div>
            </div>
        </div>
    </div>


    <div
        id="filter-modal"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="bs-example-modal-md"
        aria-hidden="true"
    >
        <form id="filter">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header d-flex align-items-center">
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Закрыть"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="container">


                            <h4>Фильтр по статусам</h4>
                            <div class="d-md-flex gap-2">
                                <div>
                                    <input type="checkbox" class="btn-check" id="status_finished" autocomplete="off"
                                           @if(!isset($filter['is_finished']) || (isset($filter['is_finished']) && !$filter['is_finished'])) checked @endif
                                           name="status[unfinished]">
                                    <label class="
                                          btn btn-outline-success
                                          font-weight-medium
                                          rounded-pill
                                        " for="status_finished">Незавершённые</label>
                                </div>

                                <div class="me-5">
                                    <input type="checkbox" class="btn-check" id="status_unfinished" autocomplete="off"
                                           @if(!isset($filter['is_finished']) || (isset($filter['is_finished']) && $filter['is_finished'])) checked @endif
                                           name="status[finished]">
                                    <label class="
                                          btn btn-outline-secondary
                                          font-weight-medium
                                          rounded-pill
                                        " for="status_unfinished">Завершённые</label>
                                </div>

                                <div>
                                    <input type="checkbox" class="btn-check" id="status_archive" autocomplete="off"
                                           @if(!empty($filter['is_archived'])) checked @endif
                                           name="is_archived" value="1">
                                    <label class="
                                          btn btn-outline-danger
                                          font-weight-medium
                                          rounded-pill
                                        " for="status_archive">Архивные</label>
                                </div>
                            </div>

                            <h4 class="mt-4">Фильтр по полям</h4>
                            <div class="mt-4 row">
                                <label class="col-sm-3 text-end control-label col-form-label">ID заказа</label>
                                <div class="col-sm-9">
                                    <input class=" form-control"
                                            style="height: 36px; width: 100%" name="order_id" value="@if(!empty($filter['order_id'])) {{ $filter['order_id'] }} @endif">
                                </div>
                            </div>
                            <div class="mt-1 row">
                                <label class="col-sm-3 text-end control-label col-form-label">Имя заказа</label>
                                <div class="col-sm-9">
                                    <input class=" form-control"
                                           style="height: 36px; width: 100%" name="order_name" value="@if(!empty($filter['order_name'])) {{ $filter['order_name'] }} @endif">
                                </div>
                            </div>

                            <h4 class="mt-4">Фильтр по датам</h4>

                            <div class="mt-4 row">
                                <label class="col-sm-3 text-end control-label col-form-label"
                                       title="Дата заключения договора">ДЗД</label>
                                <div class="col-sm-9">
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input me-0" id="checkbox3"
                                                       name="cb_contract_conclusion" value="1"
                                                       @if(!empty($filter['contract_conclusion'])) checked @endif>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control daterange"
                                               aria-label="Text input with checkbox" name="contract_conclusion"
                                               value="@if(!empty($filter['contract_conclusion'])) {{ $filter['contract_conclusion'] }} @endif">
                                    </div>

                                </div>
                            </div>
                            <div class="mt-2 row">
                                <label class="col-sm-3 text-end control-label col-form-label"
                                       title="Дата получения (техническое задание отправлено)">ДП</label>
                                <div class="col-sm-9">
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input me-0" id="checkbox3"
                                                       name="cb_order_sent_to_techdep" value="1"
                                                       @if(!empty($filter['order_sent_to_techdep'])) checked @endif>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control daterange"
                                               aria-label="Text input with checkbox" name="order_sent_to_techdep"
                                               value="@if(!empty($filter['order_sent_to_techdep'])) {{ $filter['order_sent_to_techdep'] }} @endif">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 row">
                                <label class="col-sm-3 text-end control-label col-form-label"
                                       title="Дата окончания">ДО</label>
                                <div class="col-sm-9">
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input me-0" id="checkbox3"
                                                       name="cb_md_specify_finaldate" value="1"
                                                       @if(!empty($filter['md_specify_finaldate'])) checked @endif>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control daterange"
                                               aria-label="Text input with checkbox" name="md_specify_finaldate"
                                               value="@if(!empty($filter['md_specify_finaldate'])) {{$filter['md_specify_finaldate']}} @endif">
                                    </div>
                                </div>
                            </div>


                            <h4 class="mt-4">Фильтр по пользователям</h4>
                            <div class="mt-4 row">
                                <label class="col-sm-3 text-end control-label col-form-label">Автор</label>
                                <div class="col-sm-9">
                                    <select class="select2 form-control" multiple="multiple"
                                            style="height: 36px; width: 100%" name="author[]">
                                        @foreach($users['author'] as $user)
                                            <option value="{{$user->id}}"
                                                    @if(!empty($filter['author']) && in_array($user->id, $filter['author'])) selected @endif >{{$user->fullname}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mt-2 row">
                                <label class="col-sm-3 text-end control-label col-form-label">Менеджер</label>
                                <div class="col-sm-9">
                                    <select class="select2 form-control" multiple="multiple"
                                            style="height: 36px; width: 100%" name="manager[]">
                                        @foreach($users['manager'] as $user)
                                            <option value="{{$user->id}}"
                                                    @if(!empty($filter['manager']) && in_array($user->id, $filter['manager'])) selected @endif >{{$user->fullname}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mt-2 row">
                                <label class="col-sm-3 text-end control-label col-form-label">Куратор</label>
                                <div class="col-sm-9">
                                    <select class="select2 form-control" multiple="multiple"
                                            style="height: 36px; width: 100%" name="curator[]">
                                            @foreach($users['curator'] as $user)
                                                <option value="{{$user->id}}"
                                                        @if(!empty($filter['curator']) && in_array($user->id, $filter['curator'])) selected @endif >{{$user->fullname}}</option>
                                            @endforeach
                                    </select>

                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" value="0" name="curator[]"
                                               id="curatorCb"
                                               @if(!empty($filter) && !empty($filter['curator']) && in_array(0, $filter['curator'])) checked @endif>
                                        <label class="form-check-label" for="curatorCb">
                                            Без куратора
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="
                                      btn btn-light-danger
                                      text-danger
                                      font-weight-medium
                                      waves-effect
                                    "
                            data-bs-dismiss="modal"
                        >
                            Отменить
                        </button>

                        <button
                            type="button"
                            class="
                                        ms-3
                                      btn btn-light-success
                                      text-success
                                      font-weight-medium
                                      waves-effect
                                    "
                            onclick="javascript:filter();"
                        >
                            Применить
                        </button>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </form>
    </div>
@endsection

@section('js')
    @parent
    <script src="/assets/libs/bootstrap-table/dist/bootstrap-table.min.js"></script>
    <script src="/assets/libs/bootstrap-table/dist/bootstrap-table-locale-all.min.js"></script>
    <script src="/assets/libs/bootstrap-table/dist/extensions/export/bootstrap-table-export.min.js"></script>
    <script src="/dist/modules/daterangepicker/moment.min.js"></script>
    <script src="/dist/modules/daterangepicker/daterangepicker.js"></script>

    <script>
        $(document).ready(function () {
            $table = $('#table_orders');
            $table.bootstrapTable('resetView', {height: false});

            $(".select2").select2({
                dropdownParent: $("#filter-modal")
            });

            $("#filter_clear").on("click", function () {
                filter_remove();
            });

            $(".daterange").each(function() {
               if($(this).val() && !$(this).parents('.input-group').find("input[type='checkbox']").prop("checked")) $(this).val('');
            });
        });


        function filter_remove() {
            $.ajax({
                url: '{{ route('api.order.filter.remove', ['_token' => auth()->user()->ajax_token]) }}',
                method: 'get',
                dataType: 'json',
                success: function (response) {
                    if (response.result == 'success') {
                        $("form#filter select ").each(function (index, item) {
                            $(this).select2().val(null).trigger('change');
                        });
                        $("form#filter input[type='text']").each(function (index, item) {
                            $(this).val(null);
                        });
                        $("form#filter input[type='checkbox']").each(function (index, item) {
                            $(this).prop('checked', false);
                        });

                        $("[name='status[unfinished]']").prop("checked", 1);
                        $("[name='status[finished]']").prop("checked", 1);

                        $('#table_orders').bootstrapTable('selectPage', 1);
                        $("#filter_clear").addClass("d-none");
                        $("#filter .count").addClass("d-none")
                        toastr.success("Фильтр применён", "Это успех!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });

                    } else {
                        toastr.error("Произошла ошибка!", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                }
            });
        }

        function set_preset(preset) {
            $.ajax({
                url: '{{ route('api.order.set_preset', ['_token' => auth()->user()->ajax_token]) }}',
                method: 'post',
                dataType: 'json',
                data: {
                    preset: preset
                },
                success: function (response) {
                    if (response.result == 'success') {
                        location.reload();
                    } else {
                        toastr.error("Произошла ошибка!", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                }
            });
        }
        function filter() {
            $.ajax({
                url: '{{ route('api.order.filter', ['_token' => auth()->user()->ajax_token]) }}',
                method: 'post',
                dataType: 'json',
                data: $("form#filter").serialize(),
                success: function (response) {
                    if (response.result == 'success') {
                        $('#filter-modal').modal('toggle');
                        $('#table_orders').bootstrapTable('selectPage', 1);

                        if (response.rules_count > 0) {
                            $("#filter .count").removeClass("d-none").html('(' + response.rules_count + ')');
                            $("#filter_clear").removeClass("d-none");
                        } else {
                            $("#filter .count").addClass("d-none");
                            $("#filter_clear").addClass("d-none");
                        }

                        toastr.success("Фильтр применён", "Это успех!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });

                    } else {
                        toastr.error("Произошла ошибка!", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                }
            });

        }


        function doFormatter(value, row) {
            if (!value) return '';
            if(row.is_danger) {
                return `<span class="mb-1 mt-1 ps-1 pe-1 badge bg-danger text-white" target="_blank">` + value + `</span>`;
            } else if(row.is_warning) {
                return `<span class="mb-1 mt-1 ps-1 pe-1 badge bg-warning text-white" target="_blank">` + value + `</span>`;
            } else {
                return '<span class="mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark">' + value + '</span>';
            }
        }


        function dateFormatter(value) {
            if (!value) return '';
            return '<span class="mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark">' + value + '</span>';
        }

        function idFormatter(value) {
            @can('order_view_detail')
                return '<a href="{{ route('order.detail') }}/' + value + '">' + value + '</a>';
            @else
                return value;
            @endcan

        }

        function avatar_out(value) {
            thumb = value.personal_photo && value.personal_photo['45'] ? '/storage/' + value.personal_photo['45'] : '{{ config('settings.user_avatar_default') }}';
            return `
                        @can('users_view_profile') <a href="{{ route('users.view') }}/` + value.id + `"> @endcan
            <div class="d-flex align-items-center">
                <img src="` + thumb + `" class="rounded-circle" alt="user" width="32">
                                <div class="ms-2">
                                    <div class="user-meta-info">
                                        <h6 class="user-name mb-0 font-weight-medium">
                                            ` + value.name + ' ' + value.last_name + `
                                        </h6>
                                        <small class="user-work text-muted text-truncate float-left" data-occupation="Web Designer">` + value.work_department + `</small>
                                    </div>
                                </div>
                            </div>
                        @can('users_view_profile')</a> @endcan

            `;
        }
        function avatar_out_minified(value) {
            if(!value) return '';
            return `
                        @can('users_view_profile') <a href="{{ route('users.view') }}/` + value.id + `"> @endcan
                        ` + value.name + ' ' + value.last_name + `
                        @can('users_view_profile')</a> @endcan

            `;
        }

        function curatorFormatter(value) {
            if (value) {
                return avatar_out(value);
            }

            @can('order_became_curator')
                return `
                    <button type="button" class="btn d-flex align-items-center justify-content-center w-100 btn-outline-primary waves-effect waves-light w-100 ">
                        Стать куратором
                    </button>`;
            @endcan


                return `
                <div class="alert alert-light p-1 ps-2 m-0 text-muted" role="alert">
                    Куратор не назначен
                  </div>
            `;
        }

        function personFormatter(value) {
            if (value) {
                return avatar_out(value);
            }
            return '';
        }
        function orderFormatter(value, row, index) {
            str = ``;
            if (row.customer_name) {
                @can('order_info_company')
                    str = `<strong><a href="{{ env('PORTAL_URL') }}/projects/clients/` + row.customer_id + `/" class="mb-1 mt-1 ps-1 pe-1 text-secondary" target="_blank">` + row.customer_name + `</a></strong><br/>`;
                @elsecan
                    str = `<strong>` + row.customer_name + `</strong><br/>`;
                @endcan
            }
            if(row.order_task) {
                str += `<i class="fa-regular fa-clipboard-check ms-2 text-danger cursor-help" title='Есть ТЗ'></i>`;
            }


            str += `
                <span class="mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark" target="_blank">` + value + `</span>
            `;

            return str;
        }

        function commentFormatter(value, row, index) {
            if(!value[0]) return null;
            return `
                <div class="comment">
                    <div class="title"><span>` + value[0]['user']['last_name'] + ' ' + value[0]['user']['name'] + `</span><span>` + value[0].created_at + `</span></div>
                    <div class="alert alert-light p-1 ps-2 m-0 @unless($is_curator) text-muted @endunless" role="alert" onclick="javascript:sidebar({href:'{{route('order-comment.sidebar')}}/` + row.id + `'})">
                    ` + value[0].text + `
                    </div>
                </div>

            `;
        }

        function infoFormatter(value, row) {
            @can('order_view_detail')
                str = `<div class="fs-16"><strong><a href="{{ route('order.detail') }}/` + row.id + `">` + row.id + `</a></strong></div>`;
            @else
                str = `<div>ID: <span class="mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark">`+ row.id + `</span></div>`;
            @endcan

            str +=
                    `<div>ДП: <span class="mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark">`+ row.order_sent_to_techdep + `</span></div>` +
                    (row.md_specify_finaldate ? `<div>ДО: <span class="mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark">`+ row.md_specify_finaldate + `</span></div>` : ``) +
                    `<div>Менеджер: <span class="mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark">`+ avatar_out_minified(row.manager) + `</span></div>`
                    @if(!$is_curator)
                        + (row.curator ? `<div>Куратор: <span class="mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark">`+ avatar_out_minified(row.curator) + `</span></div>` : ``)
                    @endif
                ;

            return str;
        }

        function actionsFormatter(value, row) {

            return `<a href="{{ route('order.detail') }}/` + row.id + `"><x-ui.icon.regular icon="fa-ellipsis"></x-ui.icon.regular></a>`;
        }

        var columns  =[
                @if($is_admin)
                {
                    title: "Номер",
                    field: "id",
                    align: "center",
                    valign: "top",
                    sortable: true,
                    width: 1,
                    formatter: idFormatter
                },
                @endif
                {
                    title: "Информация о заказе",
                    field: "general",
                    align: "left",
                    valign: "top",
                    width: 1,
                    visible: false,
                    formatter: infoFormatter
                },
                {
                    title: "Заказ",
                    field: "order_name",
                    align: "left",
                    valign: "top",
                    width: 1,
                    formatter: orderFormatter
                },
                {
                    title: "ДП",
                    titleTooltip: 'Дата получения (техническое задание отправлено)',
                    field: "order_sent_to_techdep",
                    align: "center",
                    valign: "top",
                    width: 100,
                    sortable: true,
                    formatter: dateFormatter
                },
                {
                    title: "ДО",
                    titleTooltip: 'Дата окончания',
                    field: "md_specify_finaldate",
                    align: "center",
                    valign: "top",
                    width: 100,
                    sortable: true,
                    formatter: doFormatter
                },
                {
                    field: "price",
                    title: "Менеджер",
                    field: "manager",
                    align: "left",
                    valign: "top",
                    width: 1,
                    formatter: personFormatter
                },
                @if(!$is_curator)
                {
                    title: "Куратор",
                    field: "curator",
                    align: "left",
                    valign: "top",
                    width: 1,
                    formatter: curatorFormatter
                },
                @endif
                {
                    field: "comments",
                    title: "Комментарий",
                    align: "left",
                    valign: "top",
                    width: 500,
                    formatter: commentFormatter
                },

                {
                    title: "ДК 1",
                    titleTooltip: 'Дата контроля 1',
                    field: "last_control_date",
                    align: "center",
                    valign: "top",
                    width: 100,
                    sortable: true,
                    formatter: dateFormatter
                },
                {
                    title: "ДК 2",
                    titleTooltip: 'Дата контроля 2',
                    field: "second_control_date",
                    align: "center",
                    valign: "top",
                    width: 100,
                    sortable: true,
                    formatter: dateFormatter
                },
                @can('order_view_detail')
                {
                    field: "id",
                    title: "",
                    align: "left",
                    valign: "top",
                    width: 1,
                    formatter: actionsFormatter
                },
                @endcan
            ];
        // if(window.screen.width < 1500) {
        //     columns = columns.filter((el, index) => {
        //         return el.title == ''
        //     });
        // }
        var $table = $("#table_orders").bootstrapTable("destroy").bootstrapTable({
            height: 800,
            icons: {
                paginationSwitchDown: "far fa-caret-square-down",
                paginationSwitchUp: "far fa-caret-square-up",
                refresh: "fas fa-history",
                toggleOff: "fas fa-toggle-off",
                toggleOn: "fas fa-toggle-on",
                columns: "fas fa-list",
                fullscreen: "fas fa-expand",
                detailOpen: "fas fa-plus-circle",
                detailClose: "far fa-times-circle",
                export: "fas fa-share-alt",
            },
            columns: columns
        });

        function rowStyle(row, index) {
            const obj = {};

            if (row.is_archived) {
                return Object.assign({}, obj, {classes: 'archived'});
            } else if (row.is_finished) {
                return Object.assign({}, obj, {classes: 'finished'});
            } else if (row.is_danger) {
                return Object.assign({}, obj, {classes: 'danger'});
            } else if (row.is_warning) {
                return Object.assign({}, obj, {classes: 'warning'});
            }
            return obj;
        }


        $(".daterange").daterangepicker({
            "minYear": 2020,
            "autoApply": true,
            ranges: {
                '2020 - НВ': [moment().year(2020).startOf('year'), moment()],
                '7 дней': [moment().subtract(6, 'days'), moment()],
                '30 дней': [moment().subtract(29, 'days'), moment()],
                'Прошлый месяц': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Этот месяц': [moment().startOf('month'), moment()],
                'Этот год': [moment().startOf('year'), moment()],
                'В будущем': [moment(), moment().add('year', 10).endOf('year')],
            },
            "locale": {

                "format": "DD.MM.YYYY",
                "separator": " - ",
                "applyLabel": "Применить",
                "cancelLabel": "Отменить",
                "fromLabel": "От",
                "toLabel": "До",
                "customRangeLabel": "Свой",
                "weekLabel": "Н",
                "daysOfWeek": ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],
                "monthNames": ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"],
                "firstDay": 1
            },

            "alwaysShowCalendars": true,
            "minDate": "01/01/2020"
        });

        function tableColumnVisible() {
            if(screen.width < 1440) {
                //$table.bootstrapTable('hideColumn', 'id');
                $table.bootstrapTable('hideColumn', 'order_sent_to_techdep');
                $table.bootstrapTable('hideColumn', 'md_specify_finaldate');
                $table.bootstrapTable('hideColumn', 'manager');
                @unless($is_curator) $table.bootstrapTable('hideColumn', 'curator'); @endunless

                $table.bootstrapTable('showColumn', 'general');
            } else {
                $table.bootstrapTable('hideColumn', 'general');

                //$table.bootstrapTable('showColumn', 'id');
                $table.bootstrapTable('showColumn', 'order_sent_to_techdep');
                $table.bootstrapTable('showColumn', 'md_specify_finaldate');
                $table.bootstrapTable('showColumn', 'manager');
                @unless($is_curator)  $table.bootstrapTable('showColumn', 'curator'); @endunless
            }
        }
        tableColumnVisible();

        $(document).ready(function() {
            $( window ).resize(function() {
               tableColumnVisible();
           });
        });
    </script>
@endsection
