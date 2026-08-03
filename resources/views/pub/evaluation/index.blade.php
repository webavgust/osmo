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
        tr[trashed] td {
            background: #ffebeb !important;
        }
        tr[trashed] td:first-of-type {
            border-left: 3px solid #ff7d7d;
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
                               data-url="{{ route("api.evaluation.list_table", ['_token' => auth()->user()->ajax_token]) }}"
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
                                       title="Дата создания">Дата создание</label>
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
                                       title="Дата создания">Принятие оценки</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input me-0" id="checkbox3"
                                                       name="cb_approved_at" value="1"
                                                       @if(!empty($filter['approved_at'])) checked @endif>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control daterange"
                                               aria-label="Text input with checkbox" name="approved_at"
                                               value="@if(!empty($filter['approved_at'])) {{ $filter['approved_at'] }} @endif">
                                    </div>

                                </div>
                            </div>


                            <h4 class="mt-4">Фильтр по пользователям</h4>
                            <div class="mt-4 row">
                                <label class="col-sm-4 text-end control-label col-form-label">Автор</label>
                                <div class="col-sm-8">
                                    <select class="select2 form-control" multiple="multiple"
                                            style="height: 36px; width: 100%" name="creator[]">
                                        @foreach($users['created_by'] ?? [] as $user)
                                            <option value="{{$user->id}}"
                                                    @if(!empty($filter['created_by']) && in_array($user->id, $filter['created_by'])) selected @endif >{{$user->fullname}}</option>
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
                dropdownParent: $("#filter-modal .modal-body")
            });

            $("#filter_clear").on("click", function () {
                filter_remove();
            });

            $(".daterange").each(function() {
                if($(this).val() && !$(this).parents('.input-group').find("input[type='checkbox']").prop("checked")) $(this).val('');
            });


            $('.fixed-table-body').on('show.bs.dropdown', function () {
                $('.fixed-table-body').css( "overflow", "inherit" );
            });

            $('.fixed-table-body').on('hide.bs.dropdown', function () {
                $('.fixed-table-body').css( "overflow", "auto" );
            })

        });


        function filter_remove() {
            $.ajax({
                url: '{{ route('api.evaluation.filter.remove', ['_token' => auth()->user()->ajax_token]) }}',
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
                url: '{{ route('api.evaluation.filter', ['_token' => auth()->user()->ajax_token]) }}',
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
            if (!value) return '-';

            const date = moment(value, "YYYY-MM-DDTHH:MM:S");
            return date.format("DD.MM.YYYY")
            return '<span class="mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark">' + value + '</span>';
        }

        function clientFormatter(value, row) {
            if(row && row.sub_contract) {
                var str = '';

                if(row.portal.client_id) {
                    @can('contract_view')
                        str += `<div class="fs-3"><a href="{{ env('PORTAL_URL') }}/projects/clients/` + row.portal.client_id + `/">` + row.portal.client_name + ` (` + row.sub_contract.contract_id + `)` + `</a></div>`;
                    @else
                        str += `<div class="fs-3">` + row.sub_contract.contract_id + `</div>`;
                    @endcan
                } else {
                    str += '<div>?</div>';
                }


                if(row.sub_contract.contract_id) {
                    str += `<div class="fs-2"><a href="{{ env('PORTAL_URL') }}/projects/contracts/` + row.sub_contract.contract_id + `/">` + (row.portal.contract_name ?? '?') + `</a></div>`;
                } else {
                    str += `<div class="fs-2">?</div>`;
                }



                if(row.sub_contract.slug !== '0') {

                    @can('sub_contract_view')
                        str += `<div class='font-14 mt-1'><a href="/contract/` + row.sub_contract.contract_id + `/sub/` + row.sub_contract.slug + `">` + row.sub_contract.slug + `</a></div>`;
                    @else
                        str += `<div class='font-14 mt-1'>` + row.sub_contract.slug + `</div>`;
                    @endcan

                }


                if(row.portal.annex_name) {
                    if (row.portal.annex_date) {
                        row.portal.annex_name += ` от ` + moment.unix(row.portal.annex_date).format('DD.MM.YYYY');
                    }

                    str += `<div class='font-14 mt-1'><i class="fa-duotone fa-diagram-subtask"></i> ` + row.portal.annex_name + `</div>`;
                } else {
                    str += `<div class='font-14 mt-1'><i class="fa-duotone fa-diagram-subtask me-2"></i>?</div>`;
                }
                return str;
            } else {
                return '';
            }
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

        function IDFormatter(value, row) {
            if(value) {
                @can('evaluation_view')
                    return '<a href="{{ route('evaluation.detail') }}/' + row.id+ '">' + row.id + '</a>';
                @else
                    return row.id;
                @endcan
            } else {
                return '';
            }
        }
        function contractBlockFormatter(value, row) {
            if(value) {
                @can('evaluation_view')
                    return '<a href="{{ route('evaluation.detail') }}/' + row.id+ '">' + row.block_id + '</a>';
                @else
                    return row.block_id;
                @endcan
            } else {
                return '';
            }
        }


        function statusFormatter(value, row) {
            str = `<span class="mb-1 badge bg-` + row.status_decorate.color + `">` + row.status_decorate.name + `</span>`;
            return str;
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
                                    <nobr>` + value.name + ' ' + value.last_name + `</nobr>
                                </h6>
                            </div>
                        </div>
                    </div>
                @can('users_view_profile')</a> @endcan

            `;
        }

        function personFormatter(value, row) {
            str = '-';

            if (row.responsible) {
                str = row.approved_at  ? `<span date-sort="` + row.approved_at + `"></span>` : ``;
                str += avatar_out(row.responsible);
                if(row.approved_at)
                    str += `<div class="fs-2" style="padding-left: 40px">` + moment(row.approved_at, 'YYYY-MM-DD HH:mm:ss').format('DD.MM.YYYY') + `</div>`;
            }
            return str;
        }



        function commentFormatter(value, row, index) {
            if(!value) return null;
            return `
                <div class="comment ">
                    <div class="alert alert-light p-1 text-center m-0" role="alert" onclick="javascript:sidebar({href:'{{route('evaluation.sidebar_comment')}}/` + row.id + `'})">Смотреть</div>
                </div>

            `;
        }



        function btnDetailFormatter(value, row) {
            @can('evaluation_view')

                return `<a href="{{ route('evaluation.detail') }}/` + row.id + `" class="btn waves-effect waves-light btn-outline-primary d-flex align-items-center justify-content-between">
                          <i class="fa-regular fa-arrow-right"></i>
                        </a>`;

            @else
                return row.id;
            @endcan
        }


        function creatorFormatter(value, row) {
            str = `<span date-sort="` + row.created_at + `"></span>`;
            if (value) {
                str += avatar_out(row.creator);
            }
            str += `<div class="fs-2" style="padding-left: 40px">` + moment(row.created_at, 'YYYY-MM-DD HH:mm:ss').format('DD.MM.YYYY HH:mm:ss') + `</div>`;

            return str;
        }



        var columns  =[
            {
                title: "ID",
                field: "id",
                valign: "middle",
                sortable: true,
                width: 20,
                align: "center",
                formatter: IDFormatter
            },
            {
                field: "created_time",
                title: "Дата создания и автор",
                align: "left",
                sortable: true,
                width: 120,
                formatter: creatorFormatter,
            },
            {
                title: "Заказчик",
                field: "contract_id",
                valign: "middle",
                align: "start",
                formatter: clientFormatter
            },
            {
                field: "responsible",
                title: "Оценка",
                align: "left",
                width: 120,
                formatter: personFormatter
            },
            {
                field: "comment",
                title: "Комментарий",
                align: "center",
                valign: "center",
                formatter: commentFormatter
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
                title: "",
                field: "id",
                align: "center",
                valign: "middle",
                width: 50,
                formatter: btnDetailFormatter
            },
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
            columns: columns
        });

        function rowStyle(row, index) {
            return Object.assign({}, {}, {
                classes: 'status_' + row.status
            });
        }
        function rowAttributes(row, index) {
            obj = {
                'id': row.id
            };
            if(row.trashed)
                obj.trashed = true;
            return obj;
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
            "minDate": "01/01/2024"
        });
    </script>
@endsection
