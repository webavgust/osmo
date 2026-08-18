@extends('layouts.layout')

@section('styles')
    <style>
        tr.hl td {
            background: #F2F2F2;
        }
    </style>
@endsection
@section('content')

    <div class="container-fluid">
        <div>
            <!-- Nav tabs -->
            <ul class="nav nav-tabs fs-5" role="tablist">
                <li class="nav-item active">
                    <a class="nav-link  active" data-bs-toggle="tab" href="#report1" role="tab" aria-selected="false">
                        <span>Отчёт 1</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#report2" role="tab" aria-selected="true">
                        <span>Отчёт 2</span>
                    </a>
                </li>
            </ul>
            <!-- Tab panes -->
            <div class="tab-content bg-white">
                <div class="tab-pane p-3 active" id="report1" role="tabpanel">
                    <div class="p-3">
                        <form method="post" action="{{ route('report-download.china1') }}">
                            @csrf

                            <h4>Курсы валют (на {{ $currency_date->format("d.m.Y") }})</h4>
                            <div class="d-flex justify-content-start mb-2">
                                <div class="me-2">
                                    <span class="input-group-text p-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="currency" id="curRUB" value="RUB">
                                            <label class="form-check-label mb-0 fs-5" for="curRUB">
                                              RUB (₽)
                                            </label>
                                        </div>
                                    </span>
                                </div>

                                <? foreach ($currencies as $cur):
                                        if(!empty(!empty($rates[$cur->slug]->amount))) {
                                            $rate = $rates[$cur->slug]->amount < 1 ? round($rates[$cur->slug]->amount, 5) : round($rates[$cur->slug]->amount, 2);
                                        } else {
                                            $rate = 0;
                                        }

                                    ?>
                                    <div class="me-2">
                                        <span class="input-group-text p-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="currency" id="cur{{ $cur->slug }}" value="{{ $cur->slug }}" @checked($cur->slug == 'CNY')>
                                                <label class="form-check-label mb-0 fs-5" for="cur{{ $cur->slug }}">
                                                  {{ $cur->slug }} ({{ $cur->symbol }}) =
                                                </label>
                                            </div>

                                            <input name="rates[{{ $cur->slug }}]" currency="{{ $cur->slug }}" type="number"
                                                   class="currency form-control flex-grow-0 fs-5 p-1"
                                                   value="{{ $rate }}" style="width: 100px">
                                        </span>
                                    </div>
                                <? endforeach; ?>
                            </div>

                            <h4 class="mt-5">Таблица</h4>
                            <div class="table-responsive">
                                <table class="bg-white table table-bordered" id="payments">
                                    <tr>
                                        <th class="p-0 pt-2">
                                            <div class="form-check d-flex justify-content-center">
                                                <input class="form-check-input cb_all" type="checkbox"
                                                       checked="">
                                            </div>
                                        </th>
                                        <th class="py-1 px-2 fs-5 text-center">
                                            客户<br/>
                                            CUSTOMER
                                        </th>
                                        <th class="py-1 px-2 fs-5 text-center">
                                            国家<br/>
                                            COUNTRY
                                        </th>
                                        <th class="py-1 px-2 fs-5 text-center">
                                            经销商<br/>
                                            DEALER
                                        </th>
                                        <th class="py-1 px-2 fs-5 text-center">
                                            金额<br/>
                                            AMOUNT
                                        </th>
                                        <th class="py-1 px-2 fs-5 text-center">
                                            日期<br/>
                                            FROM
                                        </th>
                                        <th class="py-1 px-2 fs-5 text-center">
                                            结束日期<br/>
                                            TO
                                        </th>
                                        <th class="py-1 px-2 fs-5 text-center">
                                            延长<br/>
                                            PROLONGATION
                                        </th>
                                        <th class="py-1 px-2 fs-5 text-center">
                                            平台<br/>
                                            PLATFORM
                                        </th>
                                        <th class="py-0 px-2 fs-5 text-center">
                                            神经服务<br/>
                                            NEUROSERVICES
                                        </th>
                                    </tr>
                                    @php
                                        $group_i = 1;
                                    @endphp
                                    @foreach($data_report_1 as $dri => $row)
                                        @php
                                            if(!empty($row[0]))
                                                $group_i++;
                                        @endphp

                                        <tr @class(['hl' => $group_i % 2 == 0, 'sep_partner' => !empty($row[0]['rowspan']), "sep_company" => empty($row[0]['rowspan']) && !empty($row[1]['rowspan'])])>
                                            {{-- CUSTOMER --}}
                                            @if(!empty($row[0]))
                                                <td rowspan="{{ $row[1]['rowspan'] ?? 1 }}"
                                                    class="px-0 py-1 fs-3 text-start">
                                                    <div class="form-check d-flex justify-content-center pt-2">
                                                        <input name="key[]" class="form-check-input cb_once"
                                                               type="checkbox" value="{{ $row[0]['system'] }}"
                                                               checked="">
                                                    </div>
                                                </td>

                                                <td rowspan="{{ $row[1]['rowspan'] ?? 1 }}" @class(array_merge(["p-2fs-5 text-start"], $row[1]['class'] ?? []))>
                                                    <a href="{{ route('company.detail', $row[0]['id']) }}">{{ $row[0]['cell'] }}</a>
                                                </td>
                                            @endif


                                            {{-- COUNTRY --}}
                                            @if(!empty($row[1]))
                                                <td rowspan="{{ $row[0]['rowspan'] ?? 1 }}" @class(array_merge(["p-2fs-5 text-start"], $row[0]['class'] ?? []))>
                                                    {{ $row[1]['cell'] }}
                                                </td>
                                            @endif

                                            {{-- DEALER --}}
                                            @if(!empty($row[2]))
                                                <td rowspan="{{ $row[2]['rowspan'] ?? 1 }}" @class(array_merge(["p-2fs-5 text-start"], $row[2]['class'] ?? []))>
                                                    <a href="{{ route('partner.detail', $row[2]['id']) }}">{{ $row[2]['cell'] }}</a>
                                                </td>
                                            @endif

                                            {{-- AMOUNT --}}
                                            @if(!empty($row[3]))
                                                <td rowspan="{{ $row[3]['rowspan'] ?? 1 }}" @class(array_merge(["p-2fs-5 text-end text-nowrap"], $row[3]['class'] ?? []))>
                                                    @if($row[3]['cell'])
                                                        <span data-currency="{{ $row[3]['currency'] }}"
                                                              data-amount="{{ $row[3]['cell'] }}"></span>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            @endif

                                            {{-- FROM --}}
                                            @if(!empty($row[4]))
                                                <td rowspan="{{ $row[4]['rowspan'] ?? 1 }}" @class(array_merge(["p-2fs-5 text-center text-nowrap"], $row[4]['class'] ?? []))>
                                                    {{ $row[4]['cell'] }}
                                                </td>
                                            @endif

                                            {{-- TO --}}
                                            @if(!empty($row[5]))
                                                <td rowspan="{{ $row[5]['rowspan'] ?? 1 }}" @class(array_merge(["p-2fs-5 text-center text-nowrap"], $row[5]['class'] ?? []))>
                                                    {{ $row[5]['cell'] }}
                                                </td>
                                            @endif


                                            {{-- PROLONGATION --}}
                                            @if(!empty($row[6]))
                                                <td rowspan="{{ $row[6]['rowspan'] ?? 1 }}" @class(array_merge(["p-2fs-5 text-center text-nowrap"], $row[6]['class'] ?? []))>
                                                    {{ $row[6]['cell'] }}
                                                </td>
                                            @endif

                                            {{-- PLATFORM --}}
                                            @if(!empty($row[7]))
                                                <td rowspan="{{ $row[7]['rowspan'] ?? 1 }}" @class(array_merge(["p-2fs-5 text-center text-nowrap"], $row[7]['class'] ?? []))>
                                                    {{ $row[7]['cell'] }}
                                                </td>
                                            @endif


                                            {{-- NEUROSERVICES --}}
                                            <td rowspan="{{ $row[8]['rowspan'] ?? 1 }}" @class(array_merge(["p-0 text-start"], $row[8]['class'] ?? []))>
                                                @if(!empty($row[8]))
                                                    {!! $row[8]['cell'] !!}
                                                @endif
                                            </td>

                                        </tr>
                                    @endforeach
                                </table>
                            </div>

                            <div class="text-end mt-2">
                                <x-ui.button.default btn_type="primary"
                                                     onclick="javascript:$(this).parents('form').submit();">
                                    <x-ui.icon.regular icon="fa-file-excel" class="me-1"/>
                                    Скачать отчёт
                                </x-ui.button.default>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="tab-pane p-3" id="report2" role="tabpanel">
                    <div class="text-end">
                        <x-ui.a.default class="ms-1" btn_type="info" href="{{ route('report-download.china1') }}">
                            <x-ui.icon.regular icon="fa-file-excel" class="me-1"/>
                            Скачать отчёт 2
                        </x-ui.a.default>
                    </div>
                    <h3>Тут будет второй отчёт</h3>
                </div>

            </div>
        </div>
    </div>

@endsection

@section('js')
    <script>
        var rates = [];

        $(document).ready(function () {
            // Обработчик события для главной галочки
            $('.cb_all').on('click', function () {
                // Получаем состояние главной галочки (отмечена или нет)
                var isChecked = $(this).prop('checked');

                // Устанавливаем такое же состояние для всех вложенных галочек
                $('.cb_once').prop('checked', isChecked);
            });

            // Обработчик события для вложенных галочек
            $('.cb_once').on('click', function () {
                // Проверяем, все ли вложенные галочки отмечены
                var allChecked = $('.cb_once').length === $('.cb_once:checked').length;
                // Устанавливаем состояние главной галочки в зависимости от состояния вложенных
                $('.cb_all').prop('checked', allChecked);
            });

            $("input.currency, input[name='currency']").on("change keyup", function () {
                recalc();
            });
        });

        var rates = [];
        rates['RUB'] = {
            'rate': 1,
            'symbol': '₽'
        };

        @foreach($currencies as $cur)
            rates['{{ $cur->slug }}'] = {
                'rate': 1,
                'symbol': '{{ $cur->symbol }}'
            };
        @endforeach


        function recalc() {
            // соберём курсы
            rate_selected = null;
            $("input.currency").each(function () {
                rates[$(this).attr("currency")].rate = $(this).val() - 0;
                if($(this).prev('.form-check').find('input').prop("checked")) {
                    rate_selected = $(this).attr("currency");
                }

            });
            if(!rate_selected) rate_selected = 'RUB';



            $("span[data-amount]").each(function () {
                row_amount = $(this).data("amount");
                row_currency = $(this).data("currency");

                row_amount_rub = row_currency == 'RUB' ? row_amount : row_amount * rates[row_currency].rate;

                row_amount_target = Math.round(row_amount_rub / rates[rate_selected].rate) + ' ' + rates[rate_selected].symbol;

                $(this).html(cost_normalize(row_amount_target));
            });

        }
        recalc();
    </script>
@endsection
