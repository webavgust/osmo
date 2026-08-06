@extends('layouts.layout')

@section('styles')
    @parent
    <link rel="stylesheet" href="/assets/libs/bootstrap-table/dist/bootstrap-table.min.css"/>
    <link
        rel="stylesheet"
        type="text/css"
        href="/assets/libs/ckeditor/samples/toolbarconfigurator/lib/codemirror/neo.css"
    />
    <style>
        @if($proposal->isForeignCurrency)
            #proposal[currency='RUB'] span[currency='{{ $proposal->currency->slug }}'],
        #proposal[currency='{{ $proposal->currency->slug }}'] span[currency='RUB'] {
            display: none;
        }
        @endif

        table#table-summary .textarea
        {
            white-space: normal !important;
        }
        table#table-summary .textarea p:last-of-type {
            margin-bottom: 0;
        }
        table#table-summary th {
            border-left: 1px solid #9CAFD4;
            border-top: 1px solid #9CAFD4;
        }
        table#table-summary td {
            border-left: 1px solid #BAC7E1;
            border-top: 1px solid #BAC7E1;
            text-wrap:wrap;
        }

        table#table-summary tr.caption  {
            background: #AEBDDC;
        }
        table#table-summary tr.subcaption  {
            background: #F1F1F1;
            font-weight: bold;
            color: #444;
        }
        table#table-summary   {
            border-bottom: 1px solid #BAC7E1;
            border-right: 1px solid #BAC7E1;
        }

        .textarea p {
            white-space: normal !important;
        }
    </style>
@endsection


@section('content')
    @php
        function cost_out($amount, \App\Modules\Pub\Proposal\Models\Proposal $proposal) {
            $amount = round($amount);
            $ret = '<span class="amount text-nowrap">' . tools()->cost_normalize($amount). ' ' . $proposal->currency->symbol . '</span>';

            return $ret;
        }


        $discount_total = [
            'partner' => ['platform' => 0, 'soft' => 0, 'neuro' => 0, 'work' => 0 ],
            'customer' => ['platform' => 0,  'soft' => 0, 'neuro' => 0, 'work' => 0 ],
        ];

    @endphp
    <div class="container-fluid" id="proposal" currency="RUB">
        <div class="row">
            <div class="col-1 d-flex flex-column align-items-stretch">
                <h6 style="margin-top: 10px">Варианты КП</h6>
                @foreach($iterations as $iteration)
                    <div class="mb-1 d-flex flex-column align-items-end">
                        @if($iteration->id == $proposal->id)
                            <div class="d-flex justify-content-end w-100">
                                <button type="button"
                                        class="w-100 btn waves-effect waves-light btn-primary text-nowrap position-relative cursor p-0 fs-2"
                                        href="{{ route('proposal.detail', $iteration) }}">
                                    <div class="d-flex justify-content-between m-1">
                                        <span class="ms-1">{{ $iteration->iteration }}</span>
                                        <span class="flex-grow-1">{{ $iteration->number }}</span>
                                        <span class="ms-1">{{ $iteration->currency->symbol }}</span>
                                    </div>


                                    <div class="fs-1 m-1">
                                        {{ tools()->date($iteration->sended_at) }}
                                    </div>
                                    @if(!empty($iteration->name_alt))
                                        <div class="fs-1 text-truncate px-2 border-top border-1 border-light-primary mt-1 py-1">
                                            {{ $iteration->name_alt }}
                                        </div>
                                    @endif
                                </button>

                                {{--                                <a type="button"--}}
                                {{--                                   class="btn border-start-0 btn-outline-light-secondary text-nowrap px-1 py-3 align-items-center"--}}
                                {{--                                   href="{{ route('proposal.edit', [$iteration, $iteration->iteration]) }}">--}}
                                {{--                                    <x-ui.icon.regular icon="fa-edit"/>--}}
                                {{--                                </a>--}}
                            </div>


                            <div class="mt-1 mb-2 w-100">
                                @foreach($proposal->variants as $variant)
                                    <a href="javascript:void(0)" onclick="javascript:tab({{ $variant->id }})"
                                       @class([
                                            "invoice-user message-item listing-user border-bottom p-0 align-items-center rounded-3 d-flex",
                                            "bg-light-secondary" => $loop->first
                                       ])
                                       proposal-id="{{ $variant->id }}"
                                       style="margin-left: 10px;"
                                    >


                                        <div class="user-img position-relative d-inline-block m-1">
                                            <button
                                                class="btn bg-white text-black btn-circle fw-bold fs-1" style="width: 16px; height: 16px; line-height: 6px">
                                                {{ $loop->iteration }}
                                            </button>
                                        </div>
                                        <div @class(["m-1 text-truncate text-dark"]) style="font-size: 11px">
                                            {!! cost_out($variant->cost_total, $proposal) !!}
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @else

                            <a class="d-flex justify-content-end w-100" href="{{ route('proposal.detail', [$iteration, $iteration->iteration]) }}">
                                <button type="button"
                                        class="w-100 btn waves-effect waves-light btn-light-secondary text-nowrap position-relative cursor p-0 fs-2"
                                        href="{{ route('proposal.detail', $iteration) }}">
                                    <div class="fs-3 m-1 d-flex justify-content-between">
                                        <span class="ms-1">{{ $iteration->iteration }}</span>
                                        <span class="flex-grow-1">{{ $iteration->number }}</span>
                                        <span class="ms-1">{{ $iteration->currency->symbol }}</span>
                                    </div>
                                    <div class="fs-1 m-1">
                                        {{ tools()->date($iteration->sended_at) }}
                                    </div>
                                    @if(!empty($iteration->name_alt))
                                        <div class="fs-1 text-truncate px-2 border-top border-1 border-light-primary mt-1 py-1">
                                            {{ $iteration->name_alt }}
                                        </div>
                                    @endif
                                </button>

                                {{--                                <a type="button"--}}
                                {{--                                   class="btn border-start-0 btn-outline-light-secondary text-nowrap px-1 py-3 align-items-center"--}}
                                {{--                                   href="{{ route('proposal.edit', [$iteration, $iteration->iteration]) }}">--}}
                                {{--                                    <x-ui.icon.regular icon="fa-edit"/>--}}
                                {{--                                </a>--}}
                            </a>


                        @endif

                    </div>
                @endforeach
            </div>
            <div class="col-11">
                <div class="d-flex align-items-top justify-content-between mb-2">
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <h2 class="m-0">
                            {{ $proposal->name }}
                            @if(!empty($proposal->name_alt))
                                <sup><code class="ms-1">{{ $proposal->name_alt }}</code></sup>
                            @endif
                        </h2>

                        {{-- КП, сделки Битрикса, договоры, спецификации, платежи и лицензии на одном экране --}}
                        <x-proposal.summary :proposal="$proposal"/>
                    </div>


                    <div class="d-flex align-items-center fs-4">
                        <div>

                            {{ $proposal->partner->name }}
                        </div>

                        <x-ui.icon.regular icon="fa-arrow-right" class="mx-3"/>

                        @if(!empty($proposal->company))
                            <a href="{{ route('company.detail', $proposal->company) }}">
                                <x-ui.icon.regular icon="fa-building" class="me-1"/>
                                {{ $proposal->company->name }}
                            </a>
                        @else
                            ?
                        @endif
                    </div>
                </div>

                @if($proposal->variants->isEmpty())
                    <x-ui.notification.light type="danger" class="bg-white">
                        Тут почему-то нет вариантов расчёта
                    </x-ui.notification.light>
                @else
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="invoice-inner-part w-100 border-light-secondary border-start">
                                @foreach($proposal->variants as $variant)
                                    <div style="display: none;"
                                         @class(["border-4 border-danger tab"])
                                         id="tab_{{ $variant->id }}"
                                    >
                                        <div class="card card-body p-0">
                                            <div class="
                                                  invoice-header
                                                  d-flex
                                                  align-items-center
                                                  border-bottom
                                                  px-4 py-3
                                                " style="padding-bottom: 12px!important">

                                                <div class="d-flex align-items-center align-items-center">
                                                    <h3 class="font-weight-medium text-uppercase m-0">
                                                        Коммерческое предложение
                                                    </h3>

                                                    <div class="dropdown-action ms-2" style="margin-top: 3px">
                                                        <div class="dropdown todo-action-dropdown">
                                                            <button class="btn btn-link text-dark p-1 text-decoration-none todo-action-dropdown" type="button" id="more-action-1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <x-ui.icon.regular icon="fa-ellipsis-vertical" class="fs-5"/>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-right" style="">
                                                                <a class="dropdown-item" href="{{ route('proposal.edit', [$proposal, $proposal->iteration]) }}">
                                                                    <i class="fas fa-edit text-warning me-2"></i> Редактировать
                                                                </a>

                                                                <x-ui.a.box class="dropdown-item" href="{{ route('proposal.box_generate_pdf', [$proposal, $proposal->iteration]) }}">
                                                                    <i class="fas fa-file-pdf text-danger me-2"></i> Создать PDF
                                                                </x-ui.a.box>

                                                                <x-ui.a.box class="dropdown-item" href="{{ route('proposal.box_convert', [$proposal, $proposal->iteration]) }}">
                                                                    <i class="fas fa-arrow-right-arrow-left text-primary me-2"></i> Конвертировать в валюту
                                                                </x-ui.a.box>

                                                                <div class="dropdown-divider"></div>

                                                                <a class="dropdown-item" href="{{ route('proposal_tools.price_history', $proposal) }}">
                                                                    <i class="fas fa-chart-line text-info me-2"></i> История изменения цен
                                                                </a>

                                                                <x-ui.a.box class="dropdown-item" href="{{ route('proposal_tools.box_clone', [$proposal, $proposal->iteration]) }}">
                                                                    <i class="fas fa-clone text-success me-2"></i> Клонировать КП
                                                                </x-ui.a.box>
                                                            </div>
                                                        </div>
                                                    </div>



                                                </div>


                                                <div class="d-flex justify-content-end ms-auto">
                                                    @if($proposal->isForeignCurrency)
                                                        <div class="me-3">
                                                            <x-ui.badge.light type="danger">
                                                                <div>{{ $proposal->currency->name }}</div>
                                                                <div class="mt-1 text-nowrap ">1 {{ $proposal->currency->symbol }} = {{ $proposal->currency_rate }} ₽</div>
                                                            </x-ui.badge.light>
                                                        </div>
                                                    @endif

                                                    <div>
                                                        <h4 class="invoice-number mb-0">№ {{ $proposal->number ?? '?' }}
                                                            @if($proposal->variants->count() > 1)
                                                                <sup>
                                                                    <span class="mb-1 badge bg-primary">{{ $loop->iteration }} / {{ $proposal->variants->count() }}</span>
                                                                </sup>
                                                            @endif
                                                        </h4>
                                                        <div class="fs-2 text-end me-2">от {{ $proposal->sended_at->format("d.m.Y") }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="custom-invoice" class="table-responsive p-3" style="display: block;">

                                                <table id="table-summary" class="table no-wrap w-100">
                                                    @if($variant->proposal_software->where('count', '>', 0)->isNotEmpty())
                                                        <tr class="subcaption">
                                                            <td rowspan="2"></td>
                                                            <td rowspan="2" class="text-center fw-bold">ПЛАТФОРМА (ПО)</td>
                                                            <th rowspan="2" class="text-center fw-bold" valign="top">ЦЕНА</th>
                                                            <th colspan="2" class="text-center fw-bold" valign="top">СКИДКА</th>
                                                            <th rowspan="2" class="text-center fw-bold" valign="top">ЦЕНА ИТОГ</th>
                                                            <td rowspan="2" class="text-center fw-bold">КОЛ-ВО</td>
                                                            <td rowspan="2" class="text-center fw-bold">ИТОГО</td>
                                                            <td rowspan="2" class="text-center fw-bold">ПРИМЕЧАНИЕ</td>
                                                        </tr>
                                                        <tr class="subcaption">
                                                            <td class="text-center fw-normal p-1" style="width: 120px">Заказчик</td>
                                                            <td class="text-center fw-normal p-1" style="width: 120px">Партнёр</td>
                                                        </tr>
                                                        @foreach($variant->proposal_software as $software)
                                                            @continue(!$software->count)
                                                            <tr @class(["bg-light-warning text-warning" => !$software->proposal_software->cb_process])>
                                                                <td class="text-center align-center">{{ $loop->iteration }}</td>
                                                                <td class="align-center fs-3 textarea">{!! $software->proposal_software->description !!}</td>
                                                                <td class="align-center text-center">
                                                                    <div><nobr>{!! cost_out($software->cost, $proposal) !!}</nobr></div>
                                                                </td>
                                                                <td class="text-center">
                                                                    @php
                                                                        $discount_customer = $software->discount_customer > 0 ? $software->cost / 100 * $software->discount_customer : 0;
                                                                        $discount_total['customer']['soft'] += $discount_customer * $software->count;
                                                                    @endphp
                                                                    @if($discount_customer > 0)
                                                                        <div class="text-danger">
                                                                            - {!! cost_out($discount_customer, $proposal) !!}
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center">
                                                                    @php
                                                                        $discount_partner = ($software->cost - $discount_customer) / 100 * $variant->soft_discount_partner_p;
                                                                        $discount_total['partner']['soft'] += $discount_partner * $software->count;
                                                                    @endphp
                                                                    @if($discount_partner > 0)
                                                                        <div class="text-danger">
                                                                            - {!! cost_out($discount_partner, $proposal) !!}
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center text-nowrap">
                                                                    = {!! cost_out($software->total, $proposal) !!}
                                                                </td>
                                                                <td class="align-center text-center"><nobr>{{ tools()->cost_normalize($software->count) }}</nobr></td>
                                                                <td class="align-center text-center">
                                                                    <div><nobr>
                                                                            {!! cost_out($software->total, $proposal) !!}
                                                                        </nobr></div>
                                                                </td>
                                                                <td class="align-center fs-3 text-center textarea">
                                                                    @if($software->discount_customer)
                                                                        <div>С учётом скидки {{ $software->discount_customer }}% для заказчика</div>
                                                                    @endif
                                                                    {!! $software->proposal_software->notice !!}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                        <tr>
                                                            <td colspan="3"></td>
                                                            <td class="text-center">
                                                                @if($discount_total['customer']['soft'] > 0)
                                                                    <span class="fw-bold">=
                                                                        {!! cost_out($discount_total['customer']['soft'], $proposal) !!}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                @if($discount_total['partner']['soft'] > 0)
                                                                    <span class="fw-bold">=
                                                                        {!! cost_out($discount_total['partner']['soft'], $proposal) !!}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td colspan="2"/>
                                                            <td class="text-center">
                                                                <span class="fw-bold">=
                                                                    {!! cost_out($variant->soft_cost_total, $proposal) !!}
                                                                </span>
                                                                @if($variant->soft_nds_cost_total > 0)
                                                                    <div class="text-info fs-1 font-weight-normal">
                                                                        НДС =
                                                                        {!! cost_out(round($variant->soft_nds_cost_total, 2), $proposal) !!}
                                                                    </div>
                                                                @endif
                                                            </td>
                                                            <td></td>
                                                        </tr>
                                                    @endif

                                                    @if($variant->proposal_platforms->where('count', '>', 0)->isNotEmpty())
                                                        <tr class="subcaption">
                                                            <td rowspan="2"></td>
                                                            <td rowspan="2" class="text-center fw-bold">ПЛАТФОРМА</td>
                                                            <th rowspan="2" class="text-center fw-bold" valign="top">ЦЕНА</th>
                                                            <th colspan="2" class="text-center fw-bold" valign="top">СКИДКА</th>
                                                            <th rowspan="2" class="text-center fw-bold" valign="top">ЦЕНА ИТОГ</th>
                                                            <td rowspan="2" class="text-center fw-bold">КОЛ-ВО</td>
                                                            <td rowspan="2" class="text-center fw-bold">ИТОГО</td>
                                                            <td rowspan="2" class="text-center fw-bold">ПРИМЕЧАНИЕ</td>
                                                        </tr>
                                                        <tr class="subcaption">
                                                            <td class="text-center fw-normal p-1">Заказчик</td>
                                                            <td class="text-center fw-normal p-1">Партнёр</td>
                                                        </tr>
                                                        @foreach($variant->proposal_platforms as $platform)
                                                            @continue(!$platform->count)
                                                            <tr @class(["bg-light-warning text-warning" => !$platform->cb_process])>
                                                                <td class="text-center">{{ $loop->iteration }}</td>
                                                                <td class="text-wrap">
                                                                    {!! $platform->description !!}
                                                                </td>
                                                                <td class="text-center">
                                                                    {!! cost_out($platform->cost, $proposal) !!}
                                                                </td>
                                                                <td class="text-center">
                                                                    @php
                                                                        $discount_customer = $platform->discount > 0 ? $platform->cost / 100 * $platform->discount : 0;
                                                                        $discount_total['customer']['platform'] += $discount_customer * $platform->count;
                                                                    @endphp
                                                                    @if($discount_customer > 0)
                                                                        <div class="text-danger text-nowrap">
                                                                            - {!! cost_out($discount_customer, $proposal) !!}
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center">
                                                                    @php
                                                                        $discount_partner = ($platform->cost - $discount_customer) / 100 * $variant->platform_discount_partner_p;
                                                                        $discount_total['partner']['platform'] += $discount_partner * $platform->count;
                                                                    @endphp

                                                                    @if($discount_partner > 0)
                                                                        <div class="text-danger text-nowrap">
                                                                            - {!! cost_out($discount_partner, $proposal) !!}
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center text-nowrap">
                                                                    = {!! cost_out($platform->cost_discount, $proposal) !!}
                                                                </td>
                                                                <td class="text-center">{{ tools()->cost_normalize($platform->count) }}</td>
                                                                <td class="text-center">
                                                                    {!! cost_out($platform->cost_total, $proposal) !!}
                                                                </td>
                                                                <td class="text-center">
                                                                    @if($platform->discount)
                                                                        С учётом скидки {{ $platform->discount }}% для заказчика
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach

                                                        <tr>
                                                            <td colspan="3"></td>
                                                            <td class="text-center">
                                                                @if($discount_total['customer']['platform'] > 0)
                                                                    <span class="fw-bold">=
                                                                        {!! cost_out($discount_total['customer']['platform'], $proposal) !!}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                @if($discount_total['partner']['platform'] > 0)
                                                                    <span class="fw-bold">=
                                                                        {!! cost_out($discount_total['partner']['platform'], $proposal) !!}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td colspan="2"/>
                                                            <td class="text-center">
                                                                <span class="fw-bold">=
                                                                    {!! cost_out($variant->platform_cost_total, $proposal) !!}
                                                                </span>

                                                                @if($variant->platform_nds_cost_total > 0)
                                                                    <div class="text-info fs-1 font-weight-normal">
                                                                        НДС =
                                                                        {!! cost_out(round($variant->platform_nds_cost_total, 2), $proposal) !!}
                                                                    </div>
                                                                @endif

                                                            </td>
                                                            <td></td>
                                                        </tr>
                                                    @endif




                                                    @if($variant->proposal_scenarios->where('count', '>', 0)->isNotEmpty())
                                                        <tr class="subcaption">
                                                            <td rowspan="2"></td>
                                                            <td rowspan="2" class="text-center fw-bold">НЕЙРОСЕРВИСЫ</td>
                                                            <th rowspan="2" class="text-center fw-bold" valign="top">ЦЕНА</th>
                                                            <th colspan="2" class="text-center fw-bold" valign="top">СКИДКА</th>
                                                            <th rowspan="2" class="text-center fw-bold" valign="top">ЦЕНА ИТОГ</th>
                                                            <td rowspan="2" class="text-center fw-bold">ЛИЦЕНЗИИ</td>
                                                            <td rowspan="2" class="text-center fw-bold">ИТОГО</td>
                                                            <td rowspan="2" class="text-center fw-bold">ПРИМЕЧАНИЕ</td>
                                                        </tr>
                                                        <tr class="subcaption">
                                                            <td class="text-center fw-normal p-1">Заказчик</td>
                                                            <td class="text-center fw-normal p-1">Партнёр</td>
                                                        </tr>
                                                        @foreach($variant->proposal_scenarios as $scenario)
                                                            @continue(!$scenario->count)
                                                            <tr @class(["bg-light-warning text-warning" => !$scenario->cb_process])>
                                                                <td class="text-center">{{ $loop->iteration }}</td>
                                                                <td class="text-wrap">
                                                                    @if(!empty($scenario->mnemonic_name))
                                                                        <div><x-ui.badge.default type="warning">Переименовано</x-ui.badge.default></div>
                                                                        <span class="cursor-help" title="{{ $scenario->scenario->name }}">{{ $scenario->mnemonic_name }}</span>
                                                                    @else
                                                                        @if($scenario->real_name !== $scenario->scenario->name)
                                                                            <div><x-ui.badge.default type="danger" class="cursor-help" title="Название отличается от актуального: {{ $scenario->scenario->name  }}">Название изменилось</x-ui.badge.default></div>
                                                                        @endif
                                                                        {{ $scenario->real_name }}
                                                                    @endif


                                                                </td>
                                                                <td class="text-center">
                                                                    {!! cost_out($scenario->cost, $proposal) !!}
                                                                </td>
                                                                <td class="text-center">
                                                                    @php
                                                                        $discount_customer = $scenario->discount > 0 ? $scenario->cost / 100 * $scenario->discount : 0;
                                                                        $discount_total['customer']['neuro'] += $discount_customer * $scenario->count;
                                                                    @endphp
                                                                    @if($discount_customer > 0)
                                                                        <div class="text-danger text-nowrap">
                                                                            - {!! cost_out($discount_customer, $proposal) !!}
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center">
                                                                    @php
                                                                        $discount_partner = ($scenario->cost - $discount_customer) / 100 * $variant->neuro_discount_partner_p;
                                                                        $discount_total['partner']['neuro'] += $discount_partner * $scenario->count;
                                                                    @endphp

                                                                    @if($discount_partner > 0)
                                                                        <div class="text-danger text-nowrap">
                                                                            - {!! cost_out($discount_partner, $proposal) !!}
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center text-nowrap">
                                                                    = {!! cost_out($scenario->cost_discount, $proposal) !!}
                                                                </td>
                                                                <td class="text-center">{{ tools()->cost_normalize($scenario->count) }}</td>
                                                                <td class="text-center">
                                                                    {!! cost_out($scenario->cost_total, $proposal) !!}
                                                                </td>
                                                                <td class="text-center">
                                                                    @if($scenario->discount)
                                                                        С учётом скидки {{ $scenario->discount }}% для заказчика
                                                                    @endif

                                                                    @if(!empty($scenario->comment))
                                                                        {!! $scenario->comment !!}
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach

                                                        <tr>
                                                            <td colspan="3"></td>
                                                            <td class="text-center">
                                                                @if($discount_total['customer']['neuro'] > 0)
                                                                    <span class="fw-bold">=
                                                                        {!! cost_out($discount_total['customer']['neuro'], $proposal) !!}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                @if($discount_total['partner']['neuro'] > 0)
                                                                    <span class="fw-bold">=
                                                                        {!! cost_out($discount_total['partner']['neuro'], $proposal) !!}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td colspan="2"/>
                                                            <td class="text-center">
                                                                <span class="fw-bold">=
                                                                    {!! cost_out($variant->neuro_cost_total, $proposal) !!}
                                                                </span>
                                                                @if($variant->neuro_nds_cost_total > 0)
                                                                    <div class="text-info fs-1 font-weight-normal">
                                                                        НДС =
                                                                        {!! cost_out(round($variant->neuro_nds_cost_total, 2), $proposal) !!}
                                                                    </div>
                                                                @endif
                                                            </td>
                                                            <td></td>
                                                        </tr>
                                                    @endif



                                                    @if($variant->proposal_works->where('count', '>', 0)->isNotEmpty())
                                                        @php
                                                            $groups = collect();
                                                            foreach($variant->proposal_works as $pw) {
                                                                $group = $pw->proposal_work->group ?? 'Без группы';

                                                                if(empty($groups[$group])) $groups[$group] = collect();
                                                                $groups[$group]->push($pw);
                                                            }
                                                        @endphp

                                                        <tr class="subcaption">
                                                            <td rowspan="2"></td>
                                                            <td rowspan="2" class="text-center fw-bold">РАБОТЫ</td>
                                                            <th rowspan="2" class="text-center fw-bold" valign="top">ЦЕНА</th>
                                                            <th colspan="2" class="text-center fw-bold" valign="top">СКИДКА</th>
                                                            <th rowspan="2" class="text-center fw-bold" valign="top">ЦЕНА ИТОГ</th>
                                                            <td rowspan="2" class="text-center fw-bold">ЧАСЫ</td>
                                                            <td rowspan="2" class="text-center fw-bold">ИТОГО</td>
                                                            <td rowspan="2" class="text-center fw-bold">ПРИМЕЧАНИЕ</td>
                                                        </tr>
                                                        <tr class="subcaption">
                                                            <td class="text-center fw-normal p-1">Заказчик</td>
                                                            <td class="text-center fw-normal p-1">Партнёр</td>
                                                        </tr>

                                                        @foreach($groups as $group_name => $works)
                                                            @php
                                                                $group_discount_customer = $group_discount_total = $group_cost_total = $group_nds_total = 0;
                                                            @endphp
                                                            @if($groups->count() > 1)
                                                                <tr class="bg-light-primary">
                                                                    <td colspan="9" class="fw-bold fs-h3">{{ $group_name }}</td>
                                                                </tr>
                                                            @endif

                                                            @foreach($works as $work)
                                                                @continue(!$work->count)
                                                                <tr @class(["bg-light-warning text-warning" => !$work->proposal_work->cb_process])>
                                                                    <td class="text-center align-center">{{ $loop->iteration }}</td>
                                                                    <td class="align-center fs-3 textarea">{!! $work->proposal_work->description !!} </td>
                                                                    <td class="align-center text-center">
                                                                        <div><nobr>{!! cost_out($work->cost, $proposal) !!}</nobr></div>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        @php
                                                                            $discount_customer = $work->discount_customer > 0 ? round($work->cost / 100 * $work->discount_customer, 0) : 0;
                                                                            $discount_total['customer']['work'] += $discount_customer * $work->count;
                                                                            $group_discount_customer += $discount_customer * $work->count;
                                                                        @endphp
                                                                        @if($discount_customer > 0)
                                                                            <div class="text-danger text-nowrap">
                                                                                - {!! cost_out($discount_customer, $proposal) !!}
                                                                            </div>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-center">
                                                                        @php
                                                                            $discount_partner = $work->discount_partner ? ($work->cost - $discount_customer) / 100 * $work->discount_partner : 0;
                                                                            $discount_total['partner']['work'] += $discount_partner * $work->count;
                                                                            $group_discount_total+= $discount_partner * $work->count;
                                                                            $group_nds_total += $work->nds;
                                                                        @endphp
                                                                        @if($discount_partner > 0)
                                                                            <div class="text-danger text-nowrap">
                                                                                - {!! cost_out($discount_partner, $proposal) !!}
                                                                            </div>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-center text-nowrap">
                                                                        = {!! cost_out($work->cost - $discount_customer - $discount_partner, $proposal) !!}
                                                                    </td>
                                                                    <td class="align-center text-center"><nobr>{{ tools()->cost_normalize($work->count) }}</nobr></td>
                                                                    <td class="align-center text-center">
                                                                        @php
                                                                            $group_cost_total += $work->total;
                                                                        @endphp
                                                                        <div><nobr>
                                                                                {!! cost_out($work->total, $proposal) !!}
                                                                            </nobr></div>
                                                                    </td>
                                                                    <td class="align-center fs-3 text-center textarea">
                                                                        @if($work->discount_customer)
                                                                            <div>С учётом скидки {{ $work->discount_customer }}% для заказчика</div>
                                                                        @endif
                                                                        {!! $work->proposal_work->notice !!}
                                                                    </td>
                                                                </tr>
                                                            @endforeach

                                                            @if($groups->count() > 1)
                                                                <tr>
                                                                    <td colspan="3" class="py-1 fs-2"></td>
                                                                    <td class="text-center text-nowrap py-1 fs-2">
                                                                        @if($group_discount_customer > 0)
                                                                            <span class="fw-bold text-nowrap">=
                                                                                        {!! cost_out($group_discount_customer, $proposal) !!}
                                                                                    </span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-center text-nowrap py-1 fs-2">
                                                                        @if($group_discount_total > 0)
                                                                            <span class="fw-bold text-nowrap">=
                                                                                        {!! cost_out($group_discount_total, $proposal) !!}
                                                                                    </span>
                                                                        @endif
                                                                    </td>
                                                                    <td colspan="2" class="py-1 fs-2"/>
                                                                    <td class="text-center text-nowrap py-1 fs-2">
                                                                                <span class="fw-bold text-nowrap">=
                                                                                    {!! cost_out($group_cost_total, $proposal) !!}
                                                                                </span>
                                                                        @if($variant->work_nds_cost_total > 0)
                                                                            <div class="text-info fs-1 font-weight-normal">
                                                                                НДС =
                                                                                {!! cost_out(round($group_nds_total, 2), $proposal) !!}
                                                                            </div>
                                                                        @endif
                                                                    </td>
                                                                    <td class="py-1 fs-2"></td>
                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                        <tr style="border-top: 2px solid #AAA">
                                                            <td colspan="3"></td>
                                                            <td class="text-center text-nowrap">
                                                                @if($discount_total['customer']['work'] > 0)
                                                                    <span class="fw-bold text-nowrap">=
                                                                        {!! cost_out($discount_total['customer']['work'], $proposal) !!}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center text-nowrap">
                                                                @if($discount_total['partner']['work'] > 0)
                                                                    <span class="fw-bold text-nowrap">=
                                                                        {!! cost_out($discount_total['partner']['work'], $proposal) !!}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td colspan="2"/>
                                                            <td class="text-center">
                                                                <span class="fw-bold">=
                                                                    {!! cost_out($variant->work_cost_total, $proposal) !!}
                                                                </span>
                                                                @if($variant->work_nds_cost_total > 0)
                                                                    <div class="text-info fs-1 font-weight-normal">
                                                                        НДС =
                                                                        {!! cost_out(round($variant->work_nds_cost_total, 2), $proposal) !!}
                                                                    </div>
                                                                @endif
                                                            </td>
                                                            <td></td>
                                                        </tr>
                                                    @endif

                                                    <tr style="border-top: 4px solid #AAA">
                                                        <td colspan="3"/>
                                                        <td class="text-center">
                                                            <div class="fw-bold fs-5">
                                                                @if(array_sum($discount_total['customer']) > 0)
                                                                    {!! cost_out(array_sum($discount_total['customer']), $proposal) !!}
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="fw-bold fs-5">
                                                                @if(array_sum($discount_total['partner']) > 0)
                                                                    {!! cost_out(array_sum($discount_total['partner']), $proposal) !!}
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td colspan="2"/>
                                                        <td class="text-center">
                                                            <div class="fw-bold fs-5">
                                                                {!! cost_out(round($variant->cost_total, 2), $proposal) !!}
                                                            </div>
                                                            @if($variant->nds_cost_total)
                                                                <div class="fs-2 text-nowrap">(в том числе НДС:<br/>
                                                                    {!! cost_out(round($variant->nds_cost_total, 2), $proposal) !!}
                                                                    )</div>
                                                            @endif
                                                        </td>
                                                        <td>

                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>

                                        <div>
                                            <x-proposal.extra-pays :variant="$variant"/>
                                        </div>

                                        <div>
                                            <x-proposal.hardware-table :variant="$variant"/>
                                        </div>


                                        <div>
                                            <x-proposal_variant.task :variant="$variant"/>
                                        </div>

                                    </div>

                                @endforeach

                                <x-proposal.log-table :proposal="$proposal" />

                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection


@section('js')
    @parent

    <script src="/assets/libs/ckeditor/ckeditor.js"></script>
    <script>
        var variant_selected = {{ $proposal->variants->first()?->id ?? null}};

        $(document).ready(function() {
            $("button[currency]").on("click", function() {
                currency = $(this).attr("currency");
                $("#currency_selector button").addClass("btn-light-info text-info").removeClass("btn-info");
                $("#currency_selector button[currency='" + currency+ "']").removeClass("btn-light-info text-info").addClass("btn-info");

                $("#proposal").attr("currency", currency);
            });

        });

        function tab(id) {
            variant_selected = id;
            $("a[proposal-id]").removeClass("bg-light-secondary");
            $("a[proposal-id='" + id + "']").addClass("bg-light-secondary");

            $(".tab").hide();
            $(".tab#tab_" + id).show('fade', {}, 500);
        }

        function hardware_delete(id) {
            if(!confirm("Вы действительно хотите удалить эту запись?")) return;
            $("body").block(block_default);
            $.ajax({
                url: "{{ route('api.hardware.delete', [$proposal, $proposal->iteration]) }}?_token=" + csrf_token(),
                type: "DELETE",
                data: {
                    variant: variant_selected,
                    id: id
                },
                dataType: "json",
                success: function (response) {
                    if (response.result == 'success') {
                        location.reload();
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
        @if(!empty($proposal->variants[0]->id))
        tab({{ $proposal->variants[0]->id }});
        @endif
    </script>
@endsection
