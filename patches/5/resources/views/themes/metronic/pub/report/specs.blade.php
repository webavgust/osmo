@extends('layouts.layout')

@section('breadcrumb_right')
    <div class="d-flex align-items-center">
        <div class="btn-group" role="group" aria-label="Basic example">
            <a type="button" @class(["
                        btn btn-light-primary
                        text-primary
                        fw-semibold
                        align-content-center
                      ", "active" => empty($mode) || $mode == 'filtered'])
                href="?mode=filtered">
                Отфильтрованные
            </a>
            <a type="button" @class(["
                        btn btn-light-info
                        text-info
                        fw-semibold
                        align-content-center
                      ", "active" => !empty($mode) && $mode !== 'filtered'])
            href="?mode=all">
                Все спецификации
            </a>
        </div>


        <div class="reports ms-3 d-flex align-items-center pt-1">
            <a href="{{ route('report-download.specs', ['mode' => 'pdf']) }}" class="ms-2">
                <x-ui.icon.regular icon="fa-file-pdf" class="ms-2 text-danger fs-7"></x-ui.icon.regular>
            </a>
            <a href="{{ route('report-download.specs', ['mode' => 'excel']) }}" class="ms-2">
                <x-ui.icon.regular icon="fa-file-excel" class="ms-2 text-success fs-7"></x-ui.icon.regular>
            </a>
        </div>
    </div>
@endsection

@section('styles')
    @parent
    <link rel="stylesheet" href="{{ url('assets/libs/bootstrap-table/dist/bootstrap-table.min.css') }} "/>
    <link rel="stylesheet" href="{{ url('dist/modules/daterangepicker/daterangepicker.css') }}" />

    <style>
        th.bl, td.bl {
            border-left: 3px solid #b3d2fa!important;
        }
        tr.sep_partner td {
            border-top: 3px solid #CCC!important;
        }
        tr.sep_client td {
            border-top: 2px solid #ccc!important;
        }

        .separator {
            border-bottom: 1px solid #DDD!important;
            margin-top: 5px;
            margin-bottom: 5px;
        }
    </style>
@endsection

@section('content')
    @php
        $contractTypes = \App\Modules\Pub\Contract\Models\ContractType::getDecorated();
        $future = $past = $plan = [1 => 0, 2 => 0];
    @endphp
    <div class="container-fluid">
        <div class="table-responsive">
            <form id="active">
                <table class="bg-white table table-bordered" id="payments">
                 <tr class="fs-6">
                     <th class="py-1 px-2">Партнёр</th>
                     <th class="py-1 px-2">Клиент</th>
                     <th class="py-1 px-2">Номер договора</th>
                     <th class="py-1 px-2">Спецификация</th>
                     <th class="py-1 px-2">Конфигурация</th>
                     <th class="py-1 px-2">Состав</th>
                 </tr>
             @foreach($data as $row)
                 <tr @class(['sep_partner' => !empty($row[0]['rowspan']), "sep_client" => empty($row[0]['rowspan']) && !empty($row[1]['rowspan'])])>
                     {{-- ПАРТНЁР --}}
                     @if(!empty($row[0]))
                         <td rowspan="{{ $row[0]['rowspan'] ?? 1 }}" class="p-2 text-start">
                             <a href="{{ route('partner.detail', $row[0]['system']) }}">{{ $row[0]['cell'] }}</a>
                         </td>
                     @endif

                    {{-- КЛИЕНТ --}}
                     @if(!empty($row[1]))
                         <td rowspan="{{ $row[1]['rowspan'] ?? 1 }}" class="p-2 text-start">
                             <a href="{{ route('company.detail', $row[1]['system']) }}">{{ $row[1]['cell'] }}</a>
                         </td>
                     @endif



                     {{-- Номер договора --}}
                     @if(!empty($row[2]))
                         <td rowspan="{{ $row[2]['rowspan'] ?? 1 }}" class="p-2 text-start text-nowrap">
                             <div>{{ $row[2]['cell'] }}</div>

                             @if($row[2]['org']->id == 2)
                                 <div class="text-info fs-2">
                                     {{ $row[2]['org']->name }}
                                 </div>
                             @endif
                         </td>
                     @endif



                     {{--  спецификация --}}
                     @if(!empty($row[3]))
                         <td rowspan="{{ $row[3]['rowspan'] ?? 1 }}" class="p-2 text-start text-nowrap">
                             @if(!empty($mode) && $mode !== 'filtered')
                                 <div class="form-check">
                                     <input name="active[{{ $row[3]['instance']->id }}]"  class="form-check-input" type="checkbox" value="1" id="flexCheckDefault"
                                         @checked(empty($row[3]['instance']->report_data['specs']['disabled']))>
                                     <label class="form-check-label" for="flexCheckDefault">
                                         {{ $row[3]['cell'] }}
                                     </label>
                                 </div>
                             @else
                                 <div>{{ $row[3]['cell'] }}</div>
                             @endif

                         </td>
                     @endif

{{--                      конфигурация --}}
                     @if(!empty($row[4]))
                         <td rowspan="{{ $row[4]['rowspan'] ?? 1 }}" class="p-1 text-center text-nowrap">
                             <div>
                                 <code class="fs-4">{{ $row[4]['cell'] }}</code>
                             </div>
                         </td>
                     @endif

{{--                      сценарий --}}
                     @if(!empty($row[5]))
                         <td rowspan="{{ $row[5]['rowspan'] ?? 1 }}" @class(["p-2 text-start text-nowrap", "text-info" => !empty($row[5]['handle'])])>
                             <div>{{ $row[5]['cell'] }}</div>
                         </td>
                     @endif
                 </tr>
             @endforeach


         </table>
            </form>
        </div>
        <div class="text-end">
            @if(!empty($mode) && $mode !== 'filtered')
                <x-ui.button.default btn_type="primary" onclick="save_active()">Сохранить</x-ui.button.default>

                <script>
                    function save_active() {
                        $("body").block(block_default);
                        $.ajax({
                            url: "{{ route('api.report.specs.active', ['_token' => _token() ]) }}",
                            type: "POST",
                            dataType: "json",
                            data: $("form#active").serialize(),
                            success: function (response) {
                                if (response.result == 'success') {
                                    location.replace(response.url);
                                } else {
                                    toastr.error("Не получилось сохранить данные", "Это провал!", {
                                        progressBar: true,
                                        "timeOut": 3000,
                                    });
                                    $("body").unblock();
                                }
                            },
                            error: function () {
                                toastr.error("Не получилось сохранить данные", "Это провал!", {
                                    progressBar: true,
                                    "timeOut": 3000,
                                });
                                $("body").unblock();
                            }
                        });
                    }
                </script>
            @endif
        </div>
    </div>

@endsection

@section('js')
    <script src="/dist/modules/daterangepicker/moment.min.js"></script>
    <script src="/dist/modules/daterangepicker/daterangepicker.js"></script>

    <script>
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
