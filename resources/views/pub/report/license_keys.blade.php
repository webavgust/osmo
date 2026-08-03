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
        td.expired {
            background: #ffe6e6;
        }
        td.warning {
            background: #fff6ca;
        }
        td.bold_red {
            font-weight: 400;
            color: #df1212;
        }

        td.unactive {
            color: #dc0404;
            opacity: .5;
        }
    </style>
@endsection


@section('content')

    <div class="container-fluid">

        <div id="filter" class="mb-3">
            <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#filter-modal">
                <i class="mdi mdi-filter-outline"></i>
                Фильтр <span class="count @unless($filter) d-none @endunless">(@if($filter){{ count($filter) }}) @endif</span>
            </button>

            <button type="button" id="filter_clear" class="
                @unless($filter) d-none @endunless
            btn btn-sm btn-icon btn-pure btn-outline
            delete-row-btnКу" data-bs-toggle="tooltip" data-original-title="Delete" data-bs-original-title="" title="">
                <i class="ti-close" aria-hidden="true"></i> Убрать
            </button>
        </div>

        <div class="table-responsive">
            <table class="bg-white table table-bordered" id="payments">
                <tr>
                    <th class="py-1 px-2">Партнёр</th>
                    <th class="py-1 px-2">Компания</th>
{{--                    <th class="py-1 px-2">КП</th>--}}
                    <th class="py-1 px-2">Номер договора</th>
                    <th class="py-1 px-2">Спецификация</th>
                    <th class=" py-1 px-2">Ключ</th>
                    <th class="text-center py-1 px-2">С</th>
                    <th class="text-center py-1 px-2">По</th>
                    <th class="text-center py-1 px-2">Камер</th>
                    <th class="text-center py-1 px-2" style="width: 250px">Коммент</th>
                </tr>
                @foreach($data as $row)
                    <tr @class(['sep_partner' => !empty($row[0]['rowspan']), "sep_company" => empty($row[0]['rowspan']) && !empty($row[1]['rowspan'])])>
                        {{-- ПАРТНЁР --}}
                        @if(!empty($row[0]))
                            <td rowspan="{{ $row[0]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 py-1 fs-2 text-start"], $row[0]['class'] ?? []))>
                                <a href="{{ route('partner.detail', $row[0]['system']) }}">{{ $row[0]['cell'] }}</a>
                            </td>
                        @endif

                        {{-- КОМПАНИЯ --}}
                        @if(!empty($row[1]))
                            <td rowspan="{{ $row[1]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 py-1 fs-2 text-start"], $row[1]['class'] ?? []))>
                                <a href="{{ route('company.detail', $row[1]['system']) }}">{{ $row[1]['cell'] }}</a>
                            </td>
                        @endif

                        {{-- Название КП --}}
                        @if(0 && !empty($row[2]))
                            <td rowspan="{{ $row[2]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 py-1 fs-2 text-start"], $row[2]['class'] ?? []))>
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
                        @if(!empty($row[2]))
                            <td rowspan="{{ $row[2]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 py-1 fs-2 text-start text-nowrap"], $row[2]['class'] ?? []))>
                                <div>{!! $row[2]['cell'] !!} </div>

                                @if(!empty($row[2]['org']))
                                    <div class="text-info fs-2">
                                        {!!  $row[2]['org']->name !!}
                                    </div>
                                @endif
                            </td>
                        @endif

                        {{-- Спецификации --}}
                        @if(!empty($row[3]))
                            <td rowspan="{{ $row[3]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 py-1 fs-2 text-start"], $row[3]['class'] ?? []))>
                                {!! $row[3]['cell'] !!}
                            </td>
                        @endif

                        {{-- Ключ --}}
                        @if(!empty($row[4]))
                            <td rowspan="{{ $row[4]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 py-1 fs-2 text-start text-nowrap"], $row[4]['class'] ?? []))>
                                {{ $row[4]['cell'] }}
                            </td>
                        @endif

                        {{-- Дата, с --}}
                        @if(!empty($row[5]))
                            <td rowspan="{{ $row[5]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 py-1 fs-2 text-center text-nowrap"], $row[5]['class'] ?? []))>
                                {{ $row[5]['cell'] }}
                            </td>
                        @endif

                        {{-- Дата, по --}}
                        @if(!empty($row[6]))
                            <td rowspan="{{ $row[6]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 py-1 fs-2 text-center text-nowrap"], $row[6]['class'] ?? []))>
                                {{ $row[6]['cell'] }}
                            </td>
                        @endif

                        {{-- Камер --}}
                        @if(!empty($row[7]))
                            <td rowspan="{{ $row[7]['rowspan'] ?? 1 }}" @class(array_merge(["p-2 py-1 fs-2 text-center text-nowrap"], $row[7]['class'] ?? []))>
                                {{ $row[7]['cell'] }}
                            </td>
                        @endif

                        {{-- Коммент --}}
                        @if(!empty($row[8]))
                            <td rowspan="{{ $row[8]['rowspan'] ?? 1 }}" @class(array_merge(["p-0 text-start"], $row[8]['class'] ?? [])) style="width: 120px">
                                @if(!empty($row[8]['cell']))
                                    <a href="javascript:void(0);" onclick="javscript:$(this).next('span').removeClass('d-none'); $(this).remove();" class="mx-2 fs-2"">Показать</a>
                                    <span class="d-none">
                                    {!! $row[8]['cell'] !!}
                                    </span>
                                @endif
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
                                <label class="col-sm-4 text-end control-label col-form-label">Компания</label>
                                <div class="col-sm-8">
                                    <x-ui.select.single class="select2" name="company" :items="$companies" id="id" value-name="label" :value="$filter['company'] ?? null"></x-ui.select.single>
                                </div>
                            </div>

                            <div class="separator my-3"></div>

                            <div class="mb-4 row">
                                <label class="col-sm-4 text-end control-label col-form-label">Номер договора</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control text-start flex-grow-0" name="contract_number" style="width: 200px" value="{{ $filter['contract_number'] ?? '' }}">
                                </div>
                            </div>

                            <div class="separator my-3"></div>

                            <div class="row">
                                <label class="col-sm-4 text-end control-label col-form-label"
                                       title="Дата, c">Дата, c</label>
                                <div class="col-sm-8 d-flex justify-content-start align-items-center">
                                    <input type="text" class="form-control active_from daterange "
                                           aria-label="Text input with checkbox" name="active_from"
                                           value="@if(!empty($filter['active_from'])){{ $filter['active_from'] }}@endif"
                                           style="width: 200px"
                                    >

                                    <x-ui.icon.regular icon="fa-xmark" @class(["ms-3 cursor-pointer text-danger", "d-none" => empty($filter['active_from'])])  />
                                </div>
                            </div>

                            <div class="mt-4 row">
                                <label class="col-sm-4 text-end control-label col-form-label"
                                       title="Дата, c">Дата, до</label>
                                <div class="col-sm-8 d-flex justify-content-start align-items-center">
                                    <input type="text" class="form-control active_to daterange "
                                           aria-label="Text input with checkbox" name="active_to"
                                           value="@if(!empty($filter['active_to'])){{ $filter['active_to'] }}@endif"
                                           style="width: 200px"
                                    >

                                    <x-ui.icon.regular icon="fa-xmark" @class(["ms-3 cursor-pointer text-danger", "d-none" => empty($filter['active_to'])])  />
                                </div>
                            </div>

                            <div class="separator my-3"></div>


                            <div class="mt-4 row">
                                <label class="col-sm-4 text-end control-label col-form-label"
                                       title="Дата КП">Фильтры</label>
                                <div class="col-sm-8">
                                    <div class="form-check pt-2">
                                        <input class="form-check-input" type="checkbox" value="1" id="cb_show_unactive" name="cb_show_unactive" @checked(!empty($filter['cb_show_unactive']))>
                                        <label class="form-check-label" for="cb_show_unactive">
                                            Показать неактивные
                                        </label>
                                    </div>
                                    <div class="form-check pt-2">
                                        <input class="form-check-input" type="checkbox" value="1" id="cb_expired_3" name="cb_expired_3" @checked(!empty($filter['cb_expired_3']))>
                                        <label class="form-check-label" for="cb_expired_3">
                                            Заканчиваются в течении трёх месяцев
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
    <script src="/dist/modules/daterangepicker/moment.min.js"></script>
    <script src="/dist/modules/daterangepicker/daterangepicker.js"></script>

    <script>

        function filter() {
            $.ajax({
                url: '{{ route('api.report.license_keys.filter', ['_token' => auth()->user()->ajax_token]) }}',
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
                url: '{{ route('api.report.license_keys.filter_remove', ['_token' => auth()->user()->ajax_token]) }}',
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
                "minDate": "01/01/2022",
                "startDate": "{{ now()->startOfYear()->format("d/m/Y") }}", // Установите значение по умолчанию в null
                "endDate": "{{ now()->addYear()->endOfYear()->format("d/m/Y") }}"    // Установите значение по умолчанию в null
            }).on("change", function() {
                $(this).siblings('i').removeClass("d-none"); // Очищаем значение input
            });


            // Установка значений по умолчанию
            @if(!empty($filter['active_from']))
            @php
                $dates = explode(" - ", $filter['active_from']);
            @endphp
                var startDate = moment('{{ $dates[0] }}', 'DD.MM.YYYY'); var endDate = moment('{{ $dates[1] }}', 'DD.MM.YYYY');
                $(".active_from").data('daterangepicker').setStartDate(startDate);
                $(".active_from").data('daterangepicker').setEndDate(endDate);
                $(".active_from").click();
                $(".show-calendar").hide();
                @else
                $(".active_from").val('');
            @endif

            @if(!empty($filter['active_to']))
            @php
                $dates = explode(" - ", $filter['active_to']);
            @endphp
                var startDate = moment('{{ $dates[0] }}', 'DD.MM.YYYY'); var endDate = moment('{{ $dates[1] }}', 'DD.MM.YYYY');
                $(".active_to").data('daterangepicker').setStartDate(startDate);
                $(".active_to").data('daterangepicker').setEndDate(endDate);
                $(".active_to").click();
                $(".show-calendar").hide();
                @else
                $(".active_to").val('');
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
