@extends('layouts.layout')

@section('styles')
    @parent
    <link rel="stylesheet" href="/assets/libs/bootstrap-table/dist/bootstrap-table.min.css"/>
    <link rel="stylesheet" href="/dist/modules/daterangepicker/daterangepicker.css" />

    <style>
        .comment div.alert { cursor: pointer; }
        tr[data-index]:hover .comment div.alert { background: var(--bs-gray-100); }
        tr[data-index] .comment div.alert:hover { background: var(--bs-gray-200); color: var(--bs-gray-800) !important; }
        tr[trashed] td { background: var(--bs-danger-light) !important; }
        tr[trashed] td:first-of-type { border-left: 3px solid var(--bs-danger); }
        .comment .title { display: flex; justify-content: space-between; font-weight: bold; font-size: 11px; }
        tr.unactive td { background: var(--bs-gray-100); color: var(--bs-gray-500); }

        /* панель инструментов bootstrap-table */
        .fixed-table-toolbar .bs-bars { padding-top: 0; }
        .fixed-table-toolbar .search .form-control { min-width: 240px; }
        .bootstrap-table .fixed-table-container .table thead th .th-inner { padding: .75rem 1.5rem .75rem .75rem; }
        .fixed-table-container thead th .desc { background-position-y: 8px; }
        .fixed-table-container thead th .asc { background-position-y: 17px; }

        #table_data td { padding: 0; }
        #table_data .cell { padding: 8px 2px; }
    </style>
@endsection


@section('content')
    {{-- Тулбар таблицы «Все» (bootstrap-table переносит его в свою панель) --}}
    <div id="filter" class="d-flex flex-wrap gap-2">
        <button class="btn btn-light-primary" data-bs-toggle="modal" data-bs-target="#filter-modal">
            <i class="fa-light fa-filter fs-5 me-2"></i>
            Фильтр <span class="count @unless($filter) d-none @endunless">(@if($filter){{ count($filter) }}) @endif</span>
        </button>

        <button type="button" id="filter_clear" class="btn btn-light-danger @unless($filter) d-none @endunless"
                data-bs-toggle="tooltip" title="Сбросить фильтр">
            <i class="fa-light fa-xmark fs-5 me-2" aria-hidden="true"></i> Убрать
        </button>

        <a href="{{ route('proposal.create') }}" class="btn btn-primary">
            <i class="fa-light fa-plus fs-5 me-2"></i>
            Создать КП
        </a>
    </div>

    @foreach($managers as $manager)
        <div id="filter_{{ $manager->id }}" class="d-flex flex-wrap gap-2">
            <button class="btn btn-light-primary" data-bs-toggle="modal" data-bs-target="#filter-modal">
                <i class="fa-light fa-filter fs-5 me-2"></i>
                Фильтр <span class="count @unless($filter) d-none @endunless">(@if($filter){{ count($filter) }}) @endif</span>
            </button>

            <button type="button" id="filter_clear" class="btn btn-light-danger @unless($filter) d-none @endunless"
                    data-bs-toggle="tooltip" title="Сбросить фильтр">
                <i class="fa-light fa-xmark fs-5 me-2" aria-hidden="true"></i> Убрать
            </button>

            <a href="{{ route('proposal.create') }}" class="btn btn-primary">
                <i class="fa-light fa-plus fs-5 me-2"></i>
                Создать КП
            </a>
        </div>
    @endforeach

    <div class="card">
        <div class="card-header pt-4 min-h-auto">
            <div class="card-toolbar m-0">
                    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#tab_all" role="tab">Все</a>
                        </li>
                        @foreach($managers as $manager)
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab_{{ $manager->id }}" role="tab">
                                    {{ $manager->full_name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
            </div>

        </div>

        <div class="card-body pt-2">
            <div class="tab-content">
                <div class="tab-pane active" id="tab_all" role="tabpanel">
                    <table class="table table_data"
                           id="table_data"
                           data-search="true"
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


    <div id="filter-modal" class="modal fade" tabindex="-1" aria-hidden="true">
        <form id="filter">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title fw-bold">Фильтр</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Закрыть">
                            <i class="fa-light fa-xmark fs-2"></i>
                        </button>
                    </div>

                    <div class="modal-body py-8">
                        <div class="fs-6 fw-bold text-gray-800 mb-6">По связям</div>

                        <div class="row mb-5">
                            <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Партнёр</label>
                            <div class="col-sm-9">
                                <x-ui.select.single name="partner" select2 required :items="$partners" id="id" value-name="label" :value="$filter['partner'] ?? null"></x-ui.select.single>
                            </div>
                        </div>
                        <div class="row mb-5">
                            <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Компания</label>
                            <div class="col-sm-9">
                                <x-ui.select.single name="company" select2 required :items="$companies" id="id" value-name="label" :value="$filter['company'] ?? null"></x-ui.select.single>
                            </div>
                        </div>
                        <div class="row mb-5">
                            <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Сценарий</label>
                            <div class="col-sm-9">
                                <x-ui.select.single name="scenario" select2 required :items="$scenarios" id="id" value-name="label" :value="$filter['scenario'] ?? null"></x-ui.select.single>
                            </div>
                        </div>
                        <div class="row mb-5">
                            <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Нейросервис</label>
                            <div class="col-sm-9">
                                <x-ui.select.single name="neuroservice" select2 required :items="$neuroservices" id="id" value-name="label" :value="$filter['neuroservice'] ?? null"></x-ui.select.single>
                            </div>
                        </div>

                        <div class="separator separator-dashed my-8"></div>

                        <div class="fs-6 fw-bold text-gray-800 mb-6">По полям</div>

                        <div class="row mb-5">
                            <label class="col-sm-3 col-form-label fw-semibold text-sm-end" title="Дата КП">Дата КП</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control form-control-solid daterange" name="sended_at"
                                       value="@if(!empty($filter['sended_at'])){{ $filter['sended_at'] }}@endif"
                                       style="width: 220px" />
                            </div>
                        </div>

                        <div class="row mb-5">
                            <label class="col-sm-3 col-form-label fw-semibold text-sm-end" title="Стоимость">Стоимость</label>
                            <div class="col-sm-9">
                                <div class="input-group flex-grow-0" style="width: 290px">
                                    <input type="number" class="form-control form-control-solid text-end" name="cost_from"
                                           value="@if(!empty($filter['cost_from'])){{ $filter['cost_from'] }}@endif" />
                                    <span class="input-group-text">
                                        <i class="fa-light fa-dash"></i>
                                    </span>
                                    <input type="number" class="form-control form-control-solid text-end" name="cost_to"
                                           value="@if(!empty($filter['cost_to'])){{ $filter['cost_to'] }}@endif" />
                                </div>
                            </div>
                        </div>

                        <div class="row mb-5">
                            <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Статус</label>
                            <div class="col-sm-9">
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach(\App\Modules\Pub\Proposal\Models\ProposalStatus::getDecorated() as $code => $status)
                                        <label class="form-check form-check-custom form-check-solid me-3">
                                            <input class="form-check-input" type="checkbox" name="status[]"
                                                   value="{{ $code }}"
                                                   @checked(in_array($code, (array) ($filter['status'] ?? []))) />
                                            <span class="form-check-label fw-semibold text-{{ $status['color'] }}">
                                                <i class="fa-light {{ $status['icon'] }} me-1"></i>
                                                {{ $status['label'] }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="row mb-5">
                            <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Сделка Битрикс24</label>
                            <div class="col-sm-9">
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <select name="crm_deal" class="form-select form-select-solid" style="width: 200px">
                                        <option value="">Неважно</option>
                                        <option value="linked" @selected(($filter['crm_deal'] ?? '') === 'linked')>Привязана</option>
                                        <option value="empty" @selected(($filter['crm_deal'] ?? '') === 'empty')>Не привязана</option>
                                    </select>

                                    <div class="position-relative" style="width: 220px">
                                        <span class="position-absolute top-50 translate-middle-y ms-4 text-gray-500 fs-7">#</span>
                                        <input type="text" name="crm_deal_id" inputmode="numeric"
                                               class="form-control form-control-solid ps-10"
                                               value="{{ $filter['crm_deal_id'] ?? '' }}"
                                               placeholder="ID сделки" />
                                    </div>
                                </div>
                                <div class="form-text">Можно указать несколько ID через запятую</div>
                            </div>
                        </div>

                        <div class="row">
                            <label class="col-sm-3 col-form-label"></label>
                            <div class="col-sm-9">
                                <label class="form-check form-check-custom form-check-solid">
                                    <input name="hasEmptyScenario" class="form-check-input" type="checkbox" value="1"
                                           id="cbHasEmptyScenario" @checked($filter['cbHasEmptyScenario'] ?? false) />
                                    <span class="form-check-label fw-semibold text-gray-700">В сценарии нет нейросервисов</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отменить</button>
                        <button type="button" class="btn btn-primary" onclick="javascript:filter();">Применить</button>
                    </div>
                </div>
            </div>
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

            let select_params = { width: '100%' };

            $("select[select2]").select2(select_params);


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
                        $("form#filter select[name='crm_deal']").val('');
                        $("form#filter input[name='crm_deal_id']").val('');

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
                return `<i class="fa-solid fa-check text-success fs-6"></i>`;
            } else {
                return `<i class="fa-regular fa-xmark text-muted fs-6"></i>`;
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
        }

        function regionFormatter(value) {

            return `<span class="fw-bold">` + (value ?? '-') + `</span>`;
        }

        function actionFormatter(value, row) {
            ret = `
                <div class="dropdown-action">
                    <div class="dropdown">
                        <button class="btn btn-icon btn-sm btn-light btn-active-light-primary" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa-light fa-ellipsis-vertical fs-4"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end py-2">
                            <a class="dropdown-item px-4 py-2" href="` + row.link.edit + `">
                                <i class="fa-light fa-pen text-warning me-2"></i> Редактировать
                            </a>
                            <a class="dropdown-item px-4 py-2" href="javascript:row_delete('` +  row.link.delete + `')">
                                <i class="fa-light fa-trash text-danger me-2"></i> Удалить
                            </a>`;

            if(row.iteration > 1)
                ret += `<a class="dropdown-item px-4 py-2" href="javascript:sidebar({ href: '{{ route('proposal.sidebar_iterations') }}/` + row.id + `'})">
                                <i class="fa-light fa-copy text-primary me-2"></i> Посмотреть редакции
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
                width: 75,
                sortable: true,
            },
            {
                field: "name",
                title: "Название",
                align: "left",
                sortable: true,
            },
            {
                field: "partner",
                title: "Партнёр и компания",
                align: "left",
            },
            {
                field: "cost",
                title: "Стоимость",
                align: "right",
                width: 125,
                sortable: false,
            },
            {
                field: "deal",
                title: "Сделка",
                align: "center",
                width: 130,
            },
            {
                field: "date",
                title: "Дата КП",
                align: "center",
                width: 85,
                sortable: true,
            },
            {
                field: "updated_at",
                title: "Изменено",
                align: "center",
                width: 85,
                sortable: true,
            },
            {
                field: "status",
                title: "Статус",
                align: "center",
                width: 120,
                sortable: true,
            },
            {
                field: "summary",
                title: " ",
                width: 40,
                align: "center",
            },
        ];

        $(".table_data").each(function() {
            $(this).bootstrapTable("destroy").bootstrapTable({
                height: 800,
                icons: {
                    paginationSwitchDown: "fa-light fa-square-caret-down",
                    paginationSwitchUp: "fa-light fa-square-caret-up",
                    refresh: "fa-light fa-clock-rotate-left",
                    toggleOff: "fa-light fa-toggle-off",
                    toggleOn: "fa-light fa-toggle-on",
                    columns: "fa-light fa-list",
                    fullscreen: "fa-light fa-expand",
                    detailOpen: "fa-light fa-circle-plus",
                    detailClose: "fa-light fa-circle-xmark",
                    export: "fa-light fa-share-nodes",
                },
                columns: columns
            });
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
