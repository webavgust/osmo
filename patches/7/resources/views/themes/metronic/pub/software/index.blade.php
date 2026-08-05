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

        tr.unactive td {
            background: #EEE;
            color: #AAA;
        }
    </style>
@endsection


@section('content')
    <div id="filter">
        <button class="btn btn-light-success d-none" data-bs-toggle="modal" data-bs-target="#filter-modal">
            <i class="fa-light fa-filter"></i>
            Фильтр <span class="count @unless($filter) d-none @endunless">(@if($filter){{ count($filter) }}) @endif</span>
        </button>

        <button type="button" id="filter_clear" class="
                @unless($filter) d-none @endunless
            btn btn-sm btn-icon btn-pure btn-outline
            delete-row-btnКу
" data-bs-toggle="tooltip" data-original-title="Delete" data-bs-original-title="" title="">
            <i class="fa-light fa-xmark" aria-hidden="true"></i> Убрать
        </button>

        <x-ui.a.outline href="{{ route('software.create') }}" btn_type="info" class="ms-1">
            <x-ui.icon.light icon="fa-plus"/>
            Добавить
        </x-ui.a.outline>

    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body pt-2">
                        <table class="table"
                               id="table_data"
                               data-search="true"
                               {{--                                data-search-text="Баба"--}}
                               data-toolbar="#filter"
                               data-page="1"
                               data-pagination="true"
                               data-page-size="50"
                               data-page-list="[10, 25, 50, 100]"
                               data-side-pagination="server"
                               data-locale="ru-RU"
                               data-responsible="true"
                               data-row-style="rowStyle"
                               data-row-attributes="rowAttributes"
                               data-url="{{ route("api.softwares.list_table", ['_token' => auth()->user()->ajax_token]) }}"
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
                            <h4 class="mt-4">Фильтр по полям</h4>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-light-danger fw-semibold"
                            data-bs-dismiss="modal"
                        >
                            Отменить
                        </button>

                        <button
                            type="button"
                            class="ms-3 btn btn-light-success fw-semibold"
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
            $table = $('#table_data');
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
                url: '{{ route('api.softwares.filter.remove', ['_token' => auth()->user()->ajax_token]) }}',
                method: 'get',
                dataType: 'json',
                success: function (response) {
                    if (response.result == 'success') {
                        $("form#filter select ").each(function (index, item) {
                            $(this).val(null).trigger('change');
                        });
                        $("form#filter input[type='text']").each(function (index, item) {
                            $(this).val(null);
                        });
                        $("form#filter input[type='checkbox']").each(function (index, item) {
                            $(this).prop('checked', false);
                        });

                        $("[name='status[unfinished]']").prop("checked", 1);
                        $("[name='status[finished]']").prop("checked", 1);

                        $('#table_data').bootstrapTable('selectPage', 1);
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
                url: '{{ route('api.softwares.filter', ['_token' => auth()->user()->ajax_token]) }}',
                method: 'post',
                dataType: 'json',
                data: $("form#filter").serialize(),
                success: function (response) {
                    if (response.result == 'success') {
                        console.log("REFRESH");
                        $('#filter-modal').modal('toggle');
                        $('#table_data').bootstrapTable('refresh');

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




        function nameFormatter(value) {

            return `<span class="">` + value + `</span>`;
        }

        function boxFormatter(value, row) {
            if(!value) {
                return `-`;
            } else {
                return `<a class="btn btn-light-primary ms-1 py-1 px-2 fs-1" onclick="javascript:box({href: '{{ route('software.box_extended') }}/` + row.id + `'})" btn_type="info" type="button">
                            <i class="fa-regular fa-eye" ></i> Посмотреть
                        </a>`;
            }
        }

        function typeFormatter(value) {
            if(value == 'once') {
                return 'Одноразовый';
            } else {
                return '?';
            }
        }

        function regionFormatter(value) {

            return `<span class="fw-bold">` + (value ?? '-') + `</span>`;
        }

        function countFormatter(value, row) {

            if(value > 0) {
                return `<span class="fw-bold">` + value + `</span>`;
            } else {
                return `<span class="text-light-secondary">` + value + `</span>`;
            }
        }

        function plusFormatter(value, row) {

            if(value) {
                return `<span class="fw-bold"><i class="fa-solid fa-plus text-success"></i></span>`;
            } else {
                return `<span class="fw-bold"><i class="fa-light fa-dash text-light-secondary"></i></span>`;
            }
        }

        function actionFormatter(value, row) {
            ret = `
                <div class="dropdown-action">
                    <div class="dropdown todo-action-dropdown">
                        <button class=" btn btn-link text-dark p-1 text-decoration-none todo-action-dropdown" type="button" id="more-action-1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa-light fa-ellipsis-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="{{ route('software.edit') }}/` + row.id + `">
                                <i class="fas fa-edit text-warning me-2"></i> Редактировать
                            </a>
                            <a class="dropdown-item" href="javascript:row_delete(` +  row.id + `)">
                                <i class="fas fa-trash text-danger me-2"></i> Удалить
                            </a>


                        </div>
                    </div>
                </div>
            `;

            return ret;
        }

        var columns  =[
            {
                field: "name",
                title: "Название",
                align: "left",
                sortable: true,
                formatter: nameFormatter
            },
            {
                field: "extended",
                title: "Дополнение",
                align: "center",
                formatter: plusFormatter
            },
            {
                field: "notice",
                title: "Примечание",
                align: "center",
                formatter: plusFormatter
            },
            {
                field: "cb_nds",
                title: "НДС",
                align: "center",
                formatter: plusFormatter
            },
            {
                field: "count",
                title: "Длительность",
                align: "center",
                sortable: true,
                width: 50,
            },
            {
                field: "cost",
                title: "Цена",
                align: "center",
                sortable: true,
                width: 50,
            },
            // {
            //     field: "type",
            //     title: "Периодичность",
            //     align: "left",
            //     width: 200,
            //     sortable: true,
            //     formatter: typeFormatter
            // },
            {
                field: "id",
                title: "",
                align: "right",
                width: 150,
                formatter: boxFormatter
            },
            {
                field: "id",
                title: "",
                width: 20,
                align: "right",
                formatter: actionFormatter
            },
        ];
        $("#table_data").bootstrapTable("destroy").bootstrapTable({
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
            classes = [];
            return Object.assign({}, {}, {
                classes: classes
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

        function row_delete(id) {
            if(!confirm("Вы действительно хотите удалить эту запись?")) return;
            $("body").block(block_default);
            $.ajax({
                url: "{{ route('api.software.delete') }}/" + id + "?_token=" + csrf_token(),
                type: "DELETE",
                dataType: "json",
                success: function (response) {
                    if (response.result == 'success') {
                        $("#table_data").bootstrapTable("refresh");
                        $("body").unblock();
                    } else {
                        toastr.error("Не получилось удалить запись", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        $("body").unblock();
                    }
                },
                error: function () {
                    toastr.error("Не получилось удалить запись", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $("body").unblock();
                }
            });
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
            "startDate": "01/01/2022",
            "endDate": "03/02/2022",
            "minDate": "01/01/2020"
        });
    </script>
@endsection
