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

        <x-ui.a.outline href="{{ route('proposal.create') }}" btn_type="info" class="ms-1">
            <x-ui.icon.light icon="fa-plus"/>
            Создать КП
        </x-ui.a.outline>
    </div>

    @foreach($managers as $manager)
        <div id="filter_{{ $manager->id }}">
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

            <x-ui.a.outline href="{{ route('proposal.create') }}" btn_type="info" class="ms-1">
                <x-ui.icon.light icon="fa-plus"/>
                Создать КП
            </x-ui.a.outline>
        </div>
    @endforeach

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab_all" role="tab">
                            <span>Все</span>
                        </a>
                    </li>
                    @foreach($managers as $manager)
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab_{{ $manager->id }}" role="tab">
                                <span>{{ $manager->full_name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="card">
                    <div class="card-body pt-2">

                        <div class="tab-content">
                            <div class="tab-pane active" id="tab_all" role="tabpanel">
                                <table class="table table_data"
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
                                       data-url="{{ route("api.proposals.list_table", ['_token' => auth()->user()->ajax_token]) }}"
                                ></table>
                            </div>

                            @foreach($managers as $manager)
                                <div class="tab-pane" id="tab_{{ $manager->id }}" role="tabpanel">
                                    <table class="table table_data"
                                           id="table_data_{{ $manager->id }}"
                                           data-search="true"
                                           {{--                                data-search-text="Баба"--}}
                                           data-toolbar="#filter_{{ $manager->id }}"
                                           data-page="1"
                                           data-pagination="true"
                                           data-page-size="50"
                                           data-page-list="[10, 25, 50, 100]"
                                           data-side-pagination="server"
                                           data-locale="ru-RU"
                                           data-responsible="true"
                                           data-row-style="rowStyle"
                                           data-row-attributes="rowAttributes"
                                           data-url="{{ route("api.proposals.list_table", ['manager' => $manager, '_token' => auth()->user()->ajax_token]) }}"
                                    ></table>
                                </div>
                            @endforeach
                        </div>

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
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header d-flex align-items-center">
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Закрыть"
                        ></button>
                    </div>
                    <div class="modal-body pt-0">
                        <div class="container">
                            <h4 class="mb-4">Фильтр по полям</h4>
                            <div class="mt-4 row">
                                <label class="col-sm-3 text-end control-label col-form-label">Партнёр</label>
                                <div class="col-sm-9">
                                    <x-ui.select.single class="select2" name="partner" required :items="$partners" id="id" value-name="label" :value="$filter['partner'] ?? null"></x-ui.select.single>
                                </div>
                            </div>
                            <div class="mt-4 row">
                                <label class="col-sm-3 text-end control-label col-form-label">Компания</label>
                                <div class="col-sm-9">
                                    <x-ui.select.single class="select2" name="company" required :items="$companies" id="id" value-name="label" :value="$filter['company'] ?? null"></x-ui.select.single>
                                </div>
                            </div>
                            <div class="mt-4 row">
                                <label class="col-sm-3 text-end control-label col-form-label">Сценарий</label>
                                <div class="col-sm-9">
                                    <x-ui.select.single class="select2" name="scenario" required :items="$scenarios" id="id" value-name="label" :value="$filter['scenario'] ?? null"></x-ui.select.single>
                                </div>
                            </div>
                            <div class="mt-4 row">
                                <label class="col-sm-3 text-end control-label col-form-label">Нейросервис</label>
                                <div class="col-sm-9">
                                    <x-ui.select.single class="select2" name="neuroservice" required :items="$neuroservices" id="id" value-name="label" :value="$filter['neuroservice'] ?? null"></x-ui.select.single>
                                </div>
                            </div>
                            <h4 class="mt-5">Фильтр по полям</h4>
                            <div class="mt-4 row">
                                <label class="col-sm-3 text-end control-label col-form-label"
                                       title="Дата КП">Дата КП</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control daterange"
                                       aria-label="Text input with checkbox" name="sended_at"
                                       value="@if(!empty($filter['sended_at'])){{ $filter['sended_at'] }}@endif"
                                       style="width: 200px"
                                    >
                                </div>
                            </div>
                            <div class="mt-4 row">
                                <label class="col-sm-3 text-end control-label col-form-label"
                                       title="Стоимость">Стоимость</label>
                                <div class="col-sm-9">
                                    <div class="input-group flex-grow-0" style="width: 270px">
                                        <input type="number" class="form-control text-end" name="cost_from"
                                               value="@if(!empty($filter['cost_from'])){{ $filter['cost_from'] }}@endif">
                                        <span class="input-group-text" id="basic-addon1">
                                            <x-ui.icon.regular icon="fa-dash"/>
                                        </span>
                                        <input type="number" class="form-control text-end" name="cost_to"
                                               value="@if(!empty($filter['cost_to'])){{ $filter['cost_to'] }}@endif">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 row">
                                <label class="col-sm-3 text-end control-label col-form-label"></label>
                                <div class="col-sm-9">
                                    <div class="form-check">
                                        <input name="hasEmptyScenario" class="form-check-input" type="checkbox" value="1" id="cbHasEmptyScenario" @checked($filter['cbHasEmptyScenario'] ?? false)>
                                        <label class="form-check-label" for="cbHasEmptyScenario">
                                            В сценарии нет нейросервисов
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
            $(".table_data").each(function() {
               $(this).bootstrapTable('resetView', {height: false});
            });

            $(".select2").select2({
                dropdownParent: $("#filter-modal .modal-body"),
                width: '100%'
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
                url: '{{ route('api.proposals.filter.remove', ['_token' => auth()->user()->ajax_token]) }}',
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


                        $(".table_data").each(function() {
                            $(this).bootstrapTable('selectPage', 1);
                        });

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
                url: '{{ route('api.proposals.filter', ['_token' => auth()->user()->ajax_token]) }}',
                method: 'post',
                dataType: 'json',
                data: $("form#filter").serialize(),
                success: function (response) {
                    if (response.result == 'success') {
                        $('#filter-modal').modal('toggle');
                        $(".table_data").each(function() {
                            $(this).bootstrapTable('refresh');
                        });
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






        function IDFormatter(value, row) {
            if(value) {
                @can('education_application_view')
                    return '<a href="{{ route('proposal.detail') }}/' + row.id+ '">' + row.id + '</a>';
                @else
                    return row.id;
                @endcan
            } else {
                return '';
            }
        }

        function activeFormatter(value, row) {
            if(row.active) {
                return `<i class="fa-sharp fa-solid fa-check text-success fs-6"></i>`;
            } else {
                return `<i class="fa-sharp fa-regular fa-xmark text-secondary fs-6"></i>`;
            }
        }

        function typeFormatter(value) {

            return `<span class="fw-bold">`
                    + value.label
                + `</span>`;
        }

        function nameFormatter(value, row) {

            str = '';
                    if(row.hasEmptyScenarios)
                str += `<i class="fa-solid fa-triangle-exclamation me-1 text-danger cursor-help" title="Есть сценарии без указанных нейросервисов"></i>`;
            str +=  `<a href="` + row.link.detail + `">`
                + row.name
                + `</a>`
                + `<sup class="ms-1">`
                    + row.iteration
                + `</sup>`
                ;

                return str;
        }
        function companyFormatter(value, row) {
            if(!row.company) return `?`;
            return `<a href="` + row.link.company + `">`
                + row.company.name
                + `</a>`;
        }

        function partnerFormatter(value, row) {

            return `<span class="cursor-help" title="` + row.partner.grade_decorate.description + `" >`
                    + row.partner.name
                + `</span>`;
            // return `<span class="fw-bold cursor-help" title="` + row.partner.grade_decorate.description + `" style="color: ` + row.partner.grade_decorate.color.medal + `">`
            //         + `<i class="fa-solid fa-medal me-1"></i>`
            //         + row.partner.name
            //     + `</span>`;
        }

        function regionFormatter(value) {

            return `<span class="fw-bold">` + (value ?? '-') + `</span>`;
        }

        function actionFormatter(value, row) {
            ret = `
                <div class="dropdown-action">
                    <div class="dropdown todo-action-dropdown">
                        <button class=" btn btn-link text-dark p-1 text-decoration-none todo-action-dropdown" type="button" id="more-action-1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="` + row.link.edit + `">
                                <i class="fas fa-edit text-warning me-2"></i> Редактировать
                            </a>
                            <a class="dropdown-item" href="javascript:row_delete('` +  row.link.delete + `')">
                                <i class="fas fa-trash text-danger me-2"></i> Удалить
                            </a>`;

            if(row.iteration > 1)
                ret += `<a class="dropdown-item" href="javascript:sidebar({ href: '{{ route('proposal.sidebar_iterations') }}/` + row.id + `'})">
                                <i class="fas fa-copy text-primary me-2"></i> Посмотреть редакции
                </a>`;

            ret += `
                        </div>
                    </div>
                </div>
            `;

            return ret;
        }

        function variantFormatter(value, row) {
            if(!row.variants[0] || !row.variants[0].cost_total) {
                return '-';
            } else {
                return `<span class="text-nowrap">` + cost_normalize(row.variants[0].cost_total) + `₽</span>`;
            }
        }

        function dateFormatter(value, row) {
            return moment(value).format('DD.MM.YYYY');
        }

        var columns  =[
            {
                field: "number",
                title: "Номер",
                align: "center",
                width: 125,
                sortable: true,
            },
            {
                field: "partner",
                title: "Партнёр",
                align: "left",
                width: 300,
                sortable: true,
                // formatter: partnerFormatter,
            },
            {
                field: "company",
                title: "Компания",
                align: "left",
                sortable: true,
                // formatter: companyFormatter,
            },
            {
                field: "name",
                title: "Название",
                align: "left",
                sortable: true,
                // formatter: nameFormatter,
            },
            // {
            //     field: "variants_count",
            //     title: "Вариантов",
            //     align: "left",
            //     width: 30,
            //     sortable: true,
            // },
            {
                field: "cost",
                title: "Стоимость",
                align: "right",
                width: 125,
                sortable: true,
                // formatter: variantFormatter,
            },
            {
                field: "date",
                title: "Дата КП",
                align: "right",
                width: 30,
                // formatter: dateFormatter,
            },
            {
                field: "updated_at",
                title: "Изменено",
                align: "right",
                sortable: true,
                // formatter: dateFormatter,
            },
            {
                field: "actions",
                title: " ",
                width: 20,
                align: "right",
            },
        ];

        $(".table_data").each(function() {
            $(this).bootstrapTable("destroy").bootstrapTable({
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
        });
        console.log($(this));

        function rowStyle(row, index) {
            classes = [];
            // if(!row.active) classes.push("unactive");
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

        function row_delete(url) {
            if(!confirm("Вы действительно хотите удалить эту запись?")) return;
            $("body").block(block_default);
            $.ajax({
                url: url + "?_token=" + csrf_token(),
                type: "DELETE",
                dataType: "json",
                success: function (response) {
                    if (response.result == 'success') {

                        $(".table_data").each(function() {
                            $(this).bootstrapTable('refresh');
                        });
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
        }, function(start, end) {
            // Эта функция будет вызвана при выборе дат
            $(".daterange").val(start.format('DD.MM.YYYY') + ' - ' + end.format('DD.MM.YYYY'));
        });

        // Установка значений по умолчанию
        @if(!empty($filter['sended_at']))
            @php
                $dates = explode(" - ", $filter['sended_at']);
            @endphp
            var startDate = moment('{{ $dates[0] }}', 'DD.MM.YYYY');
            var endDate = moment('{{ $dates[1] }}', 'DD.MM.YYYY');
            $(".daterange").data('daterangepicker').setStartDate(startDate);
            $(".daterange").data('daterangepicker').setEndDate(endDate);

            $(".daterange").click();
            $(".show-calendar").hide();
        @endif
    </script>
@endsection
