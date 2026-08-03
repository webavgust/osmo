@extends('layouts.layout')

@section('styles')
    @parent
    <link rel="stylesheet" href="/assets/libs/bootstrap-table/dist/bootstrap-table.min.css"/>
    <link rel="stylesheet" href="/dist/modules/daterangepicker/daterangepicker.css" />

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

        tr.expired { background: #fff5f8; }
        tr.good { background: #f9fff5; }
        .bs-bars {
            width: calc(100% - 220px);
        }
    </style>
@endsection


@section('content')

    <div id="filter" class="w-100">

        <x-ui.a.box href="{{ route('plan-visits.box_create') }}" class="btn btn-outline-success">
            <i class="mdi mdi-plus-outline"></i>
            Добавить запись
        </x-ui.a.box>

        <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#filter-modal">
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
                               data-page="2"
                               data-pagination="true"
                               data-page-size="100"
                               data-page-list="[10, 25, 50, 100]"
                               data-side-pagination="server"
                               data-locale="ru-RU"
                               data-responsible="true  "
                               data-row-style="rowStyle"
                               data-row-attributes="rowAttributes"
                               data-url="{{ route("api.plan-visits.list_table", ['_token' => auth()->user()->ajax_token]) }}"
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
                            <div class="d-md-flex gap-2 flex-wrap">
                                    <div>
                                        <input type="checkbox" class="btn-check" id="status_expired" autocomplete="off"
                                               @if(!empty($filter['status']['status_expired'])) checked @endif
                                               name="status[status_expired]" value="expired">
                                        <label class="
                                              btn btn-outline-danger
                                              font-weight-medium
                                              rounded-pill
                                            " for="status_expired">Просрочено</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" id="status_waiting" autocomplete="off"
                                               @if(!empty($filter['status']['status_waiting'])) checked @endif
                                               name="status[status_waiting]" value="waiting">
                                        <label class="
                                              btn btn-outline-secondary
                                              font-weight-medium
                                              rounded-pill
                                            " for="status_waiting">Ждёт выезда</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="btn-check" id="status_good" autocomplete="off"
                                               @if(!empty($filter['status']['status_good'])) checked @endif
                                               name="status[status_good]" value="good">
                                        <label class="
                                              btn btn-outline-success
                                              font-weight-medium
                                              rounded-pill
                                            " for="status_good">Создан выезд</label>
                                    </div>
                            </div>

                            <h4 class="mt-4">Фильтр по датам</h4>

                            <div class="mt-4 row">
                                <div class="col-sm-9">
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input me-0" id="checkbox3"
                                                       name="cb_date" value="1"
                                                       @if(!empty($filter['date'])) checked @endif>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control daterange"
                                               aria-label="Text input with checkbox" name="date"
                                               value="@if(!empty($filter['date'])) {{ trim($filter['date']) }} @endif">
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
    <script src="/assets/libs/select2/dist/js/select2.full.min.js"></script>

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
                url: '{{ route('api.plan-visits.filter.remove', ['_token' => auth()->user()->ajax_token]) }}',
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

        function filter() {
            $.ajax({
                url: '{{ route('api.plan-visits.filter', ['_token' => auth()->user()->ajax_token]) }}',
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

        function statusFormatter(value, row) {
            if(row.is_expired) {
                return `<i class="fa-solid fa-xmark text-danger"></i>`;
            } else if(row.is_good) {
                return `<i class="fa-solid fa-check text-success"></i>`;
            } else {
                return ``;
            }
        }

        function dateFormatter(value) {
            if (!value) return '';
            return '<span class="mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark">' + value + '</span>';
        }


        function IDFormatter(value, row) {
            if(value) {
                @can('order_task_view')
                    return '<a href="{{ route('order_task.detail') }}/' + row.id+ '">' + row.id + '</a>';
                @else
                    return row.id;
                @endcan
            } else {
                return '';
            }
        }

        function contractFormatter(value, row) {
            return value;
        }

        function orderFormatter(value, row) {
                @can('order_view_detail')
                    return '<a href="{{ route('order_task.detail') }}/' + value.id + '">' + value.name + '</a>';
                @else
                    return value.name;
                @endcan
        }

        function avatar_out(value) {
            thumb = value.personal_photo && value.personal_photo['45'] ? '/storage/' + value.personal_photo['45'] : '{{ config('settings.user_avatar_default') }}';
            return `
                @can('users_view_profile') <a href="{{ route('users.view') }}/` + value.id + `"> @endcan
                    <div class="d-flex align-items-center">
                        <img src="` + thumb + `" class="rounded-circle" alt="user" width="16">
                        <span class="ms-2">
                            ` + value.last_name + ' ' + value.name + `
                        </span>
                    </div>
                @can('users.view')</a> @endcan

            `;
        }
        function samplersFormatter(value, row) {
            ret = ``;
            $(value).each(function(index, item) {
                console.log(item);

                ret += avatar_out(item);
            });
            return ret;
        }

        function visitControlFormatter(value, row) {
            ret = ``;
            $(row.visits).each(function(index, visit) {
                ret += `
                    <a href='{{ route('order_task_object.detail') }}/` + visit.object_id +`?visit=` + visit.id + `'>
                    <div><div class="badge font-14  ` + visit.status.color.badge + `">`
                    + `<span>` + visit.date + ` | </span>`
                    + `<span>` + visit.status.name + `</span>`
                    + `</div></div></a>`;
            });

            ret += `
                <a href="javascript:void(0);" onclick="javascript:box({href:'{{ route('plan-visits.box_bind') }}/` + row.id + `'})" type="button">
                    <i class="fa-light fa-circle-plus text-success font-18 mt-1"></i>
                </a>
            `;

            return ret;
        }

        function actionFormatter(value, row) {
            ret = `
                <a href="javascript:void(0);" onclick="javascript:box({href:'{{ route('plan-visits.box_edit') }}/` + row.id + `'})" type="button" class="btn btn-outline-warning p-0 px-1 me-1" style="width: 30px">
                    <i class="fa-solid fa-edit"></i>
                </a>
            `;
            ret += `
                <button class="btn btn-outline-danger p-0 px-1" style="width: 30px" onclick="javascript:delete_plan(` + row.id + `)">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            `;

            return ret;
        }

        function delete_plan(id) {
            if(confirm('Вы действительно хотите удалить этот выезд?')) {
                $.ajax({
                    method: 'DELETE',
                    url: '{{ route('api.plan-visits.delete') }}/' + id + '?_token={{ _token() }}',
                    dataType: 'json',
                    success: function(answer) {
                        var tableData = $("#table_orders").bootstrapTable('remove', {
                            field: 'id',
                            values: [id]
                        });
                    }
                })
            }
        }


        var columns =[
            {
                width: 20,
                formatter: statusFormatter
            },
            {
                title: "Дата",
                field: "date",
                valign: "left",
                sortable: true,
                width: 80,
                align: "center"
            }
            ,{
                title: "Заказчик",
                field: "contract",
                valign: "middle",
                sortable: true,
                width: 120,
                align: "left",
                formatter: contractFormatter
            }
            ,{
                title: "ТЗ",
                field: "order",
                valign: "middle",
                sortable: true,
                width: 300,
                align: "left",
                formatter: orderFormatter
            }
            ,{
                title: "Пробоотборщик",
                field: "samplers",
                align: "left",
                valign: "middle",
                formatter: samplersFormatter
            }
            ,{
                title: "Созданные выезды",
                field: "id",
                align: "left",
                valign: "middle",
                formatter: visitControlFormatter
            }
            ,{
                'title': '',
                width: 100,
                formatter: actionFormatter
            }
        ];




        $("#table_orders").bootstrapTable("destroy").bootstrapTable({
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
            columns: columns,
            rowAttributes: function(row, index) {
                return {
                    'id': row.id
                };
            }
        });




        $(".daterange").daterangepicker({
            "minYear": 2023,
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



        function rowStyle(row, index) {
            const obj = {};

            if (row.is_expired) {
                return Object.assign({}, obj, {classes: 'expired'});
            } else if (row.is_good) {
                return Object.assign({}, obj, {classes: 'good'});
            }
            return obj;
        }

    </script>
@endsection
