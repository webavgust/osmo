@extends('layouts.layout')

@section('styles')
    @parent
    <link rel="stylesheet" href="/assets/libs/bootstrap-table/dist/bootstrap-table.min.css"/>
    <link rel="stylesheet" href="/dist/modules/daterangepicker/daterangepicker.css" />
    <style>
        th.bl, td.bl {
            border-left: 3px solid #b3d2fa;
        }
        tr.sep_partner td {
            border-top: 3px solid #CCC;
        }
        tr.sep_company td {
            border-top: 2px solid #ccc;
        }

        .separator {
            border-bottom: 1px solid #DDD;
            margin-top: 5px;
            margin-bottom: 5px;
        }
        td.manual {
            background: #ffeeee;
        }
        td.bold_red {
            font-weight: 400;
            color: #df1212;
        }
    </style>
@endsection


@section('content')

    <div class="container-fluid">

        <div id="filter" class="mb-3">
            <button class="btn btn-light-success" data-bs-toggle="modal" data-bs-target="#filter-modal">
                <i class="fa-light fa-filter"></i>
                Фильтр <span class="count @unless($filter) d-none @endunless">(@if($filter){{ count($filter) }}) @endif</span>
            </button>

            <button type="button" id="filter_clear" class="
                @unless($filter) d-none @endunless
            btn btn-sm btn-icon btn-pure btn-outline
            delete-row-btnКу" data-bs-toggle="tooltip" data-original-title="Delete" data-bs-original-title="" title="">
                <i class="fa-light fa-xmark" aria-hidden="true"></i> Убрать
            </button>
        </div>

        <div class="table-responsive">
            <table class="bg-white table table-bordered" id="payments">
                <tr>
                    <th class="py-1 px-2">Партнёр</th>
                    <th class="py-1 px-2">Компания</th>
                    <th class="py-1 px-2">КП</th>
                    <th class="py-1 px-2">Номер договора</th>
                    <th class="py-1 px-2">Спецификация</th>
                    <th class="text-center py-1 px-2">Сценарий</th>
                    <th class="text-center py-1 px-2">Нейросервис</th>
                </tr>
                @foreach($data as $row)
                    <tr @class(['sep_partner' => !empty($row[0]['rowspan']), "sep_company" => empty($row[0]['rowspan']) && !empty($row[1]['rowspan'])])>
                        {{-- ПАРТНЁР --}}
                        @if(!empty($row[0]))
                            <td rowspan="{{ $row[0]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 text-start"], $row[0]['class'] ?? []))>
                                <a href="{{ route('partner.detail', $row[0]['system']) }}">{{ $row[0]['cell'] }}</a>
                            </td>
                        @endif

                        {{-- КОМПАНИЯ --}}
                        @if(!empty($row[1]))
                            <td rowspan="{{ $row[1]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 text-start"], $row[1]['class'] ?? []))>
                                <a href="{{ route('company.detail', $row[1]['system']) }}">{{ $row[1]['cell'] }}</a>
                            </td>
                        @endif

                        {{-- Название КП --}}
                        @if(!empty($row[2]))
                            <td rowspan="{{ $row[2]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 text-start"], $row[2]['class'] ?? []))>
                                @if(!empty($row[2]['link']))
                                    <a href="{{ $row[2]['link'] }}">
                                        {!! $row[2]['cell'] !!}
                                    </a>
                                @else
                                    {!! $row[2]['cell'] !!}
                                @endif
                            </td>
                        @endif

                        {{-- Номер договора --}}
                        @if(!empty($row[3]))
                            <td rowspan="{{ $row[3]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 text-start text-nowrap"], $row[3]['class'] ?? []))>
                                <div>{{ $row[3]['cell'] }}</div>

                                @if($row[3]['org']->id == 2)
                                    <div class="text-info fs-2">
                                        {{ $row[3]['org']->name }}
                                    </div>
                                @endif
                            </td>
                        @endif

                        {{-- Спецификации --}}
                        @if(!empty($row[4]))
                            <td rowspan="{{ $row[4]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 text-start"], $row[4]['class'] ?? []))>
                                {{ $row[4]['cell'] }}
                            </td>
                        @endif

                        {{-- Сценарий --}}
                        @if(!empty($row[5]))
                            <td rowspan="{{ $row[5]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 text-start text-nowrap"], $row[5]['class'] ?? []))>
                                {{ $row[5]['cell'] }}
                            </td>
                        @endif

                        {{-- Нейронка --}}
                        @if(!empty($row[6]))
                            <td rowspan="{{ $row[6]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 text-start text-nowrap"], $row[6]['class'] ?? []))>
                                {{ $row[6]['cell'] }}
                            </td>
                        @endif


                    </tr>
                @endforeach
            </table>
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

                            <div class="row">
                                <label class="col-sm-4 col-form-label fw-semibold text-lg-end">Компания</label>
                                <div class="col-sm-8">
                                    <x-ui.select.single class="select2" name="company" :items="$companies" id="id" value-name="label" :value="$filter['company'] ?? null"></x-ui.select.single>
                                </div>
                            </div>

                            <div class="separator my-3"></div>

                            <div class="mb-4 row">
                                <label class="col-sm-4 col-form-label fw-semibold text-lg-end">Номер договора</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control text-start flex-grow-0" name="contract_number" style="width: 200px" value="{{ $filter['contract_number'] ?? '' }}">
                                </div>
                            </div>


                            <div class="separator my-3"></div>

                            <div class="row">
                                <label class="col-sm-4 col-form-label fw-semibold text-lg-end"
                                       title="Дата КП">Дата оплаты  (план или факт)</label>
                                <div class="col-sm-8 d-flex justify-content-start align-items-center">
                                    <input type="text" class="form-control date_both daterange "
                                           aria-label="Text input with checkbox" name="date_both"
                                           value="@if(!empty($filter['date_both'])){{ $filter['date_both'] }}@endif"
                                           style="width: 200px"
                                    >

                                    <x-ui.icon.regular icon="fa-xmark" @class(["ms-3 cursor-pointer text-danger", "d-none" => empty($filter['date_both'])])  />
                                </div>
                            </div>

                            <div class="row mt-4 ">
                                <label class="col-sm-4 col-form-label fw-semibold text-lg-end"
                                       title="Дата КП">Дата оплаты  (план)</label>
                                <div class="col-sm-8 d-flex justify-content-start align-items-center">
                                    <input type="text" class="form-control date_plan daterange "
                                           aria-label="Text input with checkbox" name="date_plan"
                                           value="@if(!empty($filter['date_plan'])){{ $filter['date_plan'] }}@endif"
                                           style="width: 200px"
                                    >

                                    <x-ui.icon.regular icon="fa-xmark" @class(["ms-3 cursor-pointer text-danger", "d-none" => empty($filter['date_plan'])])  />
                                </div>
                            </div>
                            <div class="mt-4 row">
                                <label class="col-sm-4 col-form-label fw-semibold text-lg-end"
                                       title="Дата КП">Дата оплаты (факт)</label>
                                <div class="col-sm-8 d-flex justify-content-start align-items-center">
                                    <input type="text" class="form-control date_fact daterange "
                                           aria-label="Text input with checkbox" name="date_fact"
                                           value="@if(!empty($filter['date_fact'])){{ $filter['date_fact'] }}@endif"
                                           style="width: 200px"
                                    >

                                    <x-ui.icon.regular icon="fa-xmark" @class(["ms-3 cursor-pointer text-danger", "d-none" => empty($filter['date_fact'])])  />

                                </div>
                            </div>
                            <div class="mt-4 row">
                                <label class="col-sm-4 col-form-label fw-semibold text-lg-end"
                                       title="Дата КП">Сумма оплаты (факт)</label>
                                <div class="col-sm-8">
                                    <div class="input-group mb-3 justify-content-start">
                                        <input type="text" class="form-control text-end flex-grow-0" name="amount_fact[from]" style="width: 100px" value="{{ $filter['amount_fact']['from'] ?? '' }}">
                                        <span class="mx-2 mt-1 flex-grow-0"><x-ui.icon.regular icon="fa-dash"/></span>
                                        <input type="text" class="form-control text-end flex-grow-0" name="amount_fact[to]" style="width: 100px" value="{{ $filter['amount_fact']['to'] ?? '' }}">
                                        <span/>
                                    </div>
                                </div>
                            </div>

                            <div class="separator my-3"></div>

                            <div class="mt-4 row">
                                <label class="col-sm-4 col-form-label fw-semibold text-lg-end"
                                       title="Дата КП">Фильтры</label>
                                <div class="col-sm-8">
                                    <div class="form-check pt-2">
                                        <input class="form-check-input" type="checkbox" value="1" id="cb_payment_diff" name="cb_payment_diff" @checked(!empty($filter['cb_payment_diff']))>
                                        <label class="form-check-label" for="cb_payment_diff">
                                            Оплата не совпадает
                                        </label>
                                    </div>
                                    <div class="form-check pt-2">
                                        <input class="form-check-input" type="checkbox" value="1" id="cb_payment_late" name="cb_payment_late" @checked(!empty($filter['cb_payment_late']))>
                                        <label class="form-check-label" for="cb_payment_diff">
                                            Просрочка оплаты
                                        </label>
                                    </div>
                                </div>
                            </div>


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
    <script src="/dist/modules/daterangepicker/moment.min.js"></script>
    <script src="/dist/modules/daterangepicker/daterangepicker.js"></script>

    <script>

        function filter() {
            $.ajax({
                url: '{{ route('api.report.payment.filter', ['_token' => auth()->user()->ajax_token]) }}',
                method: 'post',
                dataType: 'json',
                data: $("form#filter").serialize(),
                success: function (response) {
                    if (response.result == 'success') {

                        toastr.success("Фильтр применён", "Это успех!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });

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

        function filter_remove() {
            $.ajax({
                url: '{{ route('api.report.payment.filter_remove', ['_token' => auth()->user()->ajax_token]) }}',
                method: 'get',
                dataType: 'json',
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

        $(document).ready(function() {
            $(".daterange").daterangepicker({
                "minYear": 2024,
                "autoApply": false,
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
                "minDate": "01/01/2024",
                "startDate": "{{ now()->startOfYear()->format("d/m/Y") }}", // Установите значение по умолчанию в null
                "endDate": "{{ now()->addYear()->endOfYear()->format("d/m/Y") }}"    // Установите значение по умолчанию в null
            }).on("change", function() {
                $(this).siblings('i').removeClass("d-none"); // Очищаем значение input
            });


            // Установка значений по умолчанию
            @if(!empty($filter['date_plan']))
            @php
                $dates = explode(" - ", $filter['date_plan']);
            @endphp
            var startDate = moment('{{ $dates[0] }}', 'DD.MM.YYYY'); var endDate = moment('{{ $dates[1] }}', 'DD.MM.YYYY');
            $(".date_plan").data('daterangepicker').setStartDate(startDate);
            $(".date_plan").data('daterangepicker').setEndDate(endDate);
            $(".date_plan").click();
            $(".show-calendar").hide();
            @else
            $(".date_plan").val('');
            @endif

            @if(!empty($filter['date_fact']))
            @php
                $dates = explode(" - ", $filter['date_fact']);
            @endphp
            var startDate = moment('{{ $dates[0] }}', 'DD.MM.YYYY'); var endDate = moment('{{ $dates[1] }}', 'DD.MM.YYYY');
            $(".date_fact").data('daterangepicker').setStartDate(startDate);
            $(".date_fact").data('daterangepicker').setEndDate(endDate);
            $(".date_fact").click();
            $(".show-calendar").hide();
            @else
            $(".date_fact").val('');
            @endif

            @if(!empty($filter['date_both']))
            @php
                $dates = explode(" - ", $filter['date_both']);
            @endphp
            var startDate = moment('{{ $dates[0] }}', 'DD.MM.YYYY'); var endDate = moment('{{ $dates[1] }}', 'DD.MM.YYYY');
            $(".date_both").data('daterangepicker').setStartDate(startDate);
            $(".date_both").data('daterangepicker').setEndDate(endDate);
            $(".date_both").click();
            $(".show-calendar").hide();
            @else
            $(".date_both").val('');
            @endif

            @if(!empty($filter['date_realization']))
            @php
                $dates = explode(" - ", $filter['date_realization']);
            @endphp
            var startDate = moment('{{ $dates[0] }}', 'DD.MM.YYYY'); var endDate = moment('{{ $dates[1] }}', 'DD.MM.YYYY');
            $(".date_realization").data('daterangepicker').setStartDate(startDate);
            $(".date_realization").data('daterangepicker').setEndDate(endDate);
            $(".date_realization").click();
            $(".show-calendar").hide();
            @else
            $(".date_realization").val('');
            @endif




            $("#filter_clear").on("click", function () {
                filter_remove();
            });


            $(".fa-xmark").on("click", function() {
                $(this).siblings('input').val(''); // Очищаем значение input
                $(this).addClass("d-none");
            });

            $(".select2").select2({
                dropdownParent: $("#filter-modal .modal-body"),
                width: '100%'
            });
        });
    </script>
@endsection
