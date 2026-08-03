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
    </style>
@endsection


@section('content')
    <div id="filter">
        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#filter-modal">
            <i class="mdi mdi-filter-outline"></i>
            Фильтр <span class="count @unless($filter) d-none @endunless">(@if($filter){{ count($filter) }}) @endif</span>
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
                               data-page-size="10"
                               data-page-list="[10, 25, 50, 100]"
                               data-side-pagination="server"
                               data-locale="ru-RU"
                               data-responsible="true  "
                               data-row-style="rowStyle"
                               data-row-attributes="rowAttributes"
                               data-url="{{ route("api.visit.list_table", ['_token' => auth()->user()->ajax_token]) }}"
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
                                @foreach($statuses as $status => $ar)
                                    <div>
                                        <input type="checkbox" class="btn-check" id="status_{{$status}}" autocomplete="off"
                                               @if(!empty($filter['status'][$status])) checked @endif
                                               name="status[{{$status}}]" value="{{$status}}">
                                        <label class="
                                              btn btn-outline-{{$ar['color']}}
                                              font-weight-medium
                                              rounded-pill
                                            " for="status_{{$status}}">{{$ar['name']}}</label>
                                    </div>
                                @endforeach
                            </div>


                            <h4 class="mt-4">Фильтр по полям</h4>
                            <div class="mt-4 row">
                                <label class="col-sm-4 text-end control-label col-form-label"
                                       title="Дата создания">Заказчик</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input me-0" id="checkbox3"
                                                       name="cb_client_name" value="1"
                                                       @if(!empty($filter['client_name'])) checked @endif>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control "
                                               aria-label="Text input with checkbox" name="client_name"
                                               value="@if(!empty($filter['client_name'])) {{ $filter['client_name'] }} @endif">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 row">
                                <label class="col-sm-4 text-end control-label col-form-label"
                                       title="Дата создания">Договор</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input me-0" id="checkbox3"
                                                       name="cb_contract_name" value="1"
                                                       @if(!empty($filter['contract_name'])) checked @endif>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control "
                                               aria-label="Text input with checkbox" name="contract_name"
                                               value="@if(!empty($filter['contract_name'])) {{ $filter['contract_name'] }} @endif">
                                    </div>
                                </div>
                            </div>

                            <h4 class="mt-4">Фильтр по датам</h4>

                            <div class="mt-4 row">
                                <label class="col-sm-4 text-end control-label col-form-label"
                                       title="Дата создания">Создание</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input me-0" id="checkbox3"
                                                       name="cb_created_at" value="1"
                                                       @if(!empty($filter['created_at'])) checked @endif>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control daterange"
                                               aria-label="Text input with checkbox" name="created_at"
                                               value="@if(!empty($filter['created_at'])) {{ $filter['created_at'] }} @endif">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 row">
                                <label class="col-sm-4 text-end control-label col-form-label"
                                       title="Дата создания">Выезд (план)</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input me-0" id="checkbox3"
                                                       name="cb_plan_visit_at" value="1"
                                                       @if(!empty($filter['plan_visit_at'])) checked @endif>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control daterange"
                                               aria-label="Text input with checkbox" name="plan_visit_at"
                                               value="@if(!empty($filter['plan_visit_at'])) {{ $filter['plan_visit_at'] }} @endif">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 row">
                                <label class="col-sm-4 text-end control-label col-form-label"
                                       title="Дата создания">Выезд (факт)</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input me-0" id="checkbox3"
                                                       name="cb_fact_visit_at" value="1"
                                                       @if(!empty($filter['fact_visit_at'])) checked @endif>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control daterange"
                                               aria-label="Text input with checkbox" name="fact_visit_at"
                                               value="@if(!empty($filter['fact_visit_at'])) {{ $filter['fact_visit_at'] }} @endif">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 row">
                                <label class="col-sm-4 text-end control-label col-form-label"
                                       title="Дата создания">Дата заявки</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input me-0" id="checkbox3"
                                                       name="cb_dp_annex_date" value="1"
                                                       @if(!empty($filter['dp_annex_date'])) checked @endif>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control daterange"
                                               aria-label="Text input with checkbox" name="dp_annex_date"
                                               value="@if(!empty($filter['dp_annex_date'])) {{ $filter['dp_annex_date'] }} @endif">
                                    </div>
                                </div>
                            </div>


                            <h4 class="mt-4">Фильтр по пользователям</h4>
                            <div class="mt-4 row">
                                <label class="col-sm-4 text-end control-label col-form-label">Автор</label>
                                <div class="col-sm-8">
                                    <select class="select2 form-control" multiple="multiple"
                                            style="height: 36px; width: 100%" name="creator[]">
                                        @foreach($users['creator'] as $user)
                                            <option value="{{$user->id}}"
                                                    @if(!empty($filter['creator']) && in_array($user->id, $filter['creator'])) selected @endif >{{$user->fullname}}</option>
                                        @endforeach
                                    </select>
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
                url: '{{ route('api.visit.filter.remove', ['_token' => auth()->user()->ajax_token]) }}',
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
                url: '{{ route('api.visit.filter', ['_token' => auth()->user()->ajax_token]) }}',
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


        function dateFormatter(value) {
            if (!value) return '';
            m = moment(value);
            return '<span class="mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark">' + m.format('DD.MM.YYYY') + '</span>';
        }

        function ObjectFormatter(value, row) {
            if(!row.order_task_address || !row.order_task_address.object)
                return '?';

            @can('order_task_view')
                return '<a href="{{ route('order_task_object.detail') }}/' + row.order_task_address.object.id + '">' + row.order_task_address.object.name + '</a>';
            @else
                return row.order_task_address.object.name;
            @endcan
        }

        function contractSubFormatter(value, row) {
            if(row.sub_contract) {
                @can('contract_view')
                    return '<a href="{{ route('contract.detail') }}/' + row.sub_contract.contract_id + '/sub/' + row.sub_contract.slug + '">' + row.sub_contract.slug + '</a>';
                @else
                    return row.sub_contract.slug;
                @endcan
            } else {
                return '';
            }
        }

        function ActFormatter(value, row) {
            if (row.can.view_detail) {
                return `<a href="` + row.route + `">` + row.number.id + `</a>`;
            } else {
                return row.order_task_id;
            }
        }
        function IDFormatter(value, row) {
            if(value) {
                @can('order_task_view')
                    return '<a href="{{ route('order_task.detail') }}/' + value + '">' + value + '</a>';
                @else
                    return value;
                @endcan
            } else {
                return '';
            }
        }
        function contractBlockFormatter(value, row) {
            if(value) {
                @can('order_task_view')
                    return '<a href="{{ route('order_task.detail') }}/' + row.id+ '">' + row.block_id + '</a>' + `<sup>&nbsp;` + row.iteration + `</sup>`;
                @else
                    return row.block_id + `<span class='text-muted'>.` + row.iteration + `</span>`;
                @endcan
            } else {
                return '';
            }
        }
        function orderFormatter(value, row) {
            if(value)
            {
                @can('order_view_detail')
                    return '<a href="{{ route('order.detail') }}/' + value + '">' + value + '</a>';
                @else
                    return value;
                @endcan
            } else {
                return `
                <div id='attach_` + row.id + `'>-</div>
                `;
            }
        }
        function statusFormatter(value, row) {
            return `
             <span class="mb-1 badge bg-` + row.status_decorate.color + `">` + row.status_decorate.name + `</span>
            `;
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
                                        <small class="user-work text-muted text-truncate float-left">` + value.work_department + `</small>
                                    </div>
                                </div>
                            </div>
                        @can('users.view')</a> @endcan

            `;
        }
        function personFormatter(value, row) {
            if (value) {
                return avatar_out(value);
            }
            return '';
        }
        function samplesFormatter(value, row) {
            return `<div class="input-group mb-3">
                            <span style="width: 30px" class="justify-content-center input-group-text px-2 py-1 font-12 cursor-help" title="Кол-во проб для отбора">` + row.samples.all + `</span>
                            <span style="width: 30px" class="justify-content-center input-group-text px-2 py-1 font-12 bg-light-info fw-bold text-info cursor-help" title="Кол-во отобранных проб">` + (row.samples.assets > 0 ? row.samples.assets :  '') + `</span>
                            <span style="width: 30px" class="justify-content-center input-group-text px-2 py-1 font-12 bg-light-primary fw-bold text-primary cursor-help" title="Кол-во обработанных проб">` + (row.samples.finished > 0 ? row.samples.finished :  '') + `</span>
                        </div>`;
        }
        function btnDetailFormatter(value, row) {
            if (row.can.view_detail) {
                return `<a href="` + row.route + `" class="btn waves-effect waves-light btn-outline-primary d-flex align-items-center justify-content-between">
                  <i class="fa-regular fa-arrow-right"></i>
                </a>`;
            }
        }

        function clientFormatter(value, row) {
            ret = ``;
            if(row.client.id) {
                ret += `<div data-sort="` + row.client.name + `" class="fs-3"><a href="` + row.client.link + `">` + row.client.name + `</a></div>`;
            }
            if(row.agreement.name) {
                ret += `<div class="fs-2"><a href="` + row.agreement.link + `">` + row.agreement.name + `</a></div>`;
            }



            if(row.annex.name) {
                if (row.annex.date) {
                    row.annex.name += ` от ` + row.annex.date;
                }
                ret += `<div class='font-14 mt-1'><i class="fa-duotone fa-diagram-subtask"></i> ` + row.annex.name + `</div>`;
            } else {
                ret += `<div class='font-14 mt-1'><i class="fa-duotone fa-diagram-subtask me-2"></i>?</div>`;
            }

            return ret;
        }



        var columns  =[
            {
                title: "Номер акта",
                field: "visit_number",
                valign: "middle",
                width: 100,
                align: "center",
                sortable: true,
                formatter: ActFormatter
            },
            {
                title: "ТЗ",
                field: "order_task_id",
                valign: "middle",
                align: "center",
                formatter: IDFormatter
            },
            {
                field: "client_name",
                title: "Заказчик",
                align: "left",
                sortable: true,
                sorter: function(a, b) {
                    return a.client.name.localeCompare(b.client.name);
                },
                align: "left",
                formatter: clientFormatter
            },
            {
                title: "Объект",
                field: "id",
                valign: "left",
                formatter: ObjectFormatter
            },
            {
                title: "Выезд (план)",
                field: "plan_visit_at",
                align: "left",
                sortable: true,
                width: 120,
                formatter: dateFormatter
            },
            {
                title: "Выезд (факт)",
                field: "fact_visit_at",
                align: "left",
                sortable: true,
                width: 120,
                formatter: dateFormatter
            },
            {
                field: "samplers",
                title: "Пробы",
                align: "center",
                formatter: samplesFormatter
            },
            {
                title: "Статус",
                field: "status_decorate",
                align: "center",
                valign: "middle",
                width: 180,
                formatter: statusFormatter
            },
            {
                'title': '',
                width: 50,
                formatter: btnDetailFormatter
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
            sortable: true,
            columns: columns
        });

        function rowStyle(row, index) {
            return Object.assign({}, {}, {
                classes: 'status_' + row.status.chr
            });
        }
        function rowAttributes(row, index) {
            return {
                'id': row.id
            }
        }

        $(function() {
            $('#table').on('post-body.bs.table', function (e) {
                $('[data-toggle="popover"]').popover()
            })
        })



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
    </script>
@endsection
