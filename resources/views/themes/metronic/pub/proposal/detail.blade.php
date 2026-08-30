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
        :root {
            --table-summary-border: #e0e0e0;

        }
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
            border-left: 1px solid var(--table-summary-border);
            border-top: 1px solid var(--table-summary-border);
        }
        table#table-summary td {
            border-left: 1px solid var(--table-summary-border);
            border-top: 1px solid var(--table-summary-border);
            text-wrap:wrap;
        }

        table#table-summary tr.caption  {
            background: var(--bs-primary-light);
        }
        table#table-summary tr.subcaption  {
            background: #F1F1F1;
            font-weight: bold;
            color: #444;
        }
        table#table-summary   {
            border-bottom: 1px solid var(--table-summary-border);
            border-right: 1px solid var(--table-summary-border);
        }

        table#table-summary p {
            margin-bottom: 0;
        }
        table#table-summary p + p {
            margin-top: 1rem;
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

        // спецификации рамочных договоров, к которым прикреплено КП (patch v16)
        $frame_specs = \App\Modules\Pub\ContractSpecification\Services\SpecProposalService::specifications($proposal);

        // разложим их по блокам КП: номер договора показывается в шапке того
        // блока, ради которого договор заключён
        $frame_by_block = ['license' => collect(), 'platform' => collect(), 'services' => collect()];
        foreach($frame_specs as $frame_spec) {
            $frame_type = (string) $frame_spec->contract?->type;
            if(isset($frame_by_block[$frame_type])) $frame_by_block[$frame_type]->push($frame_spec);
        }

    @endphp
    <div class="container-fluid" id="proposal" currency="RUB">
        <div class="row">
            <div class="col-1 d-flex flex-column align-items-stretch mt-3">
                <h6>Варианты КП</h6>
                @foreach($iterations as $iteration)
                    <div class="mb-2 d-flex flex-column align-items-end">
                        @if($iteration->id == $proposal->id)
                            <div class="d-flex justify-content-end w-100">
                                <button type="button"
                                        class="w-100 btn waves-effect waves-light btn-info text-nowrap position-relative cursor p-0"
                                        href="{{ route('proposal.detail', $iteration) }}">
                                    <div class="d-flex justify-content-between m-1 fs-7">
                                        <span class="ms-1">{{ $iteration->iteration }}</span>
                                        <span class="flex-grow-1">{{ $iteration->number }}</span>
                                        <span class="ms-1">{{ $iteration->currency->symbol }}</span>
                                    </div>


                                    <div class="fs-7 m-1">
                                        {{ tools()->date($iteration->sended_at) }}
                                    </div>
                                    @if(!empty($iteration->name_alt))
                                        <div class="fs-7 text-truncate px-2 border-top border-1 border-light-primary mt-1 py-1">
                                            {{ $iteration->name_alt }}
                                        </div>
                                    @endif
                                </button>
                            </div>


                            <div class="mt-1 mb-2 w-100">
                                @foreach($proposal->variants as $variant)
                                    <a href="javascript:void(0)" onclick="javascript:tab({{ $variant->id }})"
                                       @class([
                                            "border-bottom p-0 align-items-center rounded-1 d-flex justify-content-between",
                                            "bg-gray-400" => $loop->first,
                                            "p-1",
                                            "text-dark"
                                       ])
                                       proposal-id="{{ $variant->id }}"
                                       style="margin-left: 10px;"
                                    >


                                        <span class="fw-bold bg-white rounded-4 w-20px h-20x d-flex align-items-center justify-content-center">{{ $loop->iteration }}</span>
                                        <div @class(["m-1 text-truncate"]) style="font-size: 11px">
                                            {!! cost_out($variant->cost_total, $proposal) !!}
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @else

                            <a class="d-flex justify-content-end w-100" href="{{ route('proposal.detail', [$iteration, $iteration->iteration]) }}">
                                <button type="button"
                                        class="w-100 btn waves-effect waves-light btn-secondary text-muted text-nowrap position-relative cursor p-0"
                                        href="{{ route('proposal.detail', $iteration) }}">
                                    <div class="d-flex justify-content-between m-1 fs-7">
                                        <span class="ms-1">{{ $iteration->iteration }}</span>
                                        <span class="flex-grow-1">{{ $iteration->number }}</span>
                                        <span class="ms-1">{{ $iteration->currency->symbol }}</span>
                                    </div>


                                    <div class="fs-7 m-1">
                                        {{ tools()->date($iteration->sended_at) }}
                                    </div>
                                    @if(!empty($iteration->name_alt))
                                        <div class="fs-7 text-truncate px-2 border-top border-1 border-light-primary mt-1 py-1">
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
                    @if($proposal->variants->isEmpty())
                        <x-ui.notification.light type="danger" class="bg-white">
                            Тут почему-то нет вариантов расчёта
                        </x-ui.notification.light>
                    @else
                                    <div class="d-flex justify-content-between">
                                        <div class="d-flex justify-content-start align-items-center">
                                            <h3 class="font-weight-medium text-uppercase m-0">
                                                КОММЕРЧЕСКОЕ ПРЕДЛОЖЕНИЕ
                                            </h3>
                                            <div class="dropdown-action ms-2 mb-1" style="margin-top: 1px">
                                                <div class="dropdown todo-action-dropdown">
                                                    <button class="btn btn-link text-dark p-1 text-decoration-none todo-action-dropdown" type="button" id="more-action-1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <x-ui.icon.solid icon="fa-ellipsis-vertical" class="fs-3 ms-1"/>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right" style="">
                                                        <a class="btn dropdown-item" href="{{ route('proposal.edit', [$proposal, $proposal->iteration]) }}">
                                                            <i class="fas fa-edit text-warning me-2 w-15px"></i> Редактировать
                                                        </a>

                                                        <div class="dropdown-divider"></div>

                                                        <x-ui.a.box class="dropdown-item" href="{{ route('proposal.box_generate_pdf', [$proposal, $proposal->iteration]) }}">
                                                            <i class="fas fa-file-pdf text-danger me-2"></i> Создать PDF
                                                        </x-ui.a.box>

                                                        <x-ui.a.box class="dropdown-item" href="{{ route('proposal_tools.box_excel', [$proposal, $proposal->iteration]) }}">
                                                            <i class="fas fa-file-excel text-success me-2"></i> Создать Excel
                                                        </x-ui.a.box>

                                                        <div class="dropdown-divider"></div>

                                                        <x-ui.a.box class="dropdown-item" href="{{ route('proposal_tools.price_history', $proposal) }}">
                                                            <i class="fas fa-chart-line text-info me-2"></i> История изменения цен
                                                        </x-ui.a.box>

                                                        <div class="dropdown-divider"></div>

                                                        <x-ui.a.box class="dropdown-item" href="{{ route('proposal_tools.box_clone', [$proposal, $proposal->iteration]) }}">
                                                            <i class="fas fa-clone text-success me-2"></i> Клонировать КП
                                                        </x-ui.a.box>

                                                        <x-ui.a.box class="dropdown-item" href="{{ route('proposal.box_convert', [$proposal, $proposal->iteration]) }}">
                                                            <i class="fas fa-arrow-right-arrow-left text-primary me-2"></i> Конвертировать в валюту
                                                        </x-ui.a.box>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="d-flex align-items-center flex-wrap justify-content-end gap-2">
                                            <x-proposal.status :proposal="$proposal" editable="1" as="btn"/>
                                            <x-proposal.deal :proposal="$proposal" as="btn"/>
                                            <x-proposal.summary :proposal="$proposal"/>
                                        </div>
                                    </div>

                                @foreach($proposal->variants as $variant)
                                    <div style="display: none;"
                                         class="tab"
                                         id="tab_{{ $variant->id }}"
                                    >

                                        <div class="card mt-1">
                                                <div class="card-header pt-3 pb-2 align-items-center">
                                                        <div class="left">
                                                            <div class="d-flex align-items-center justify-content-start">
                                                                <h2 class="mb-1">{{ $proposal->name }}</h2>
                                                                @if(!empty($proposal->name_alt))
                                                                    <sup><code class="ms-1">{{ $proposal->name_alt }}</code></sup>
                                                                @endif

                                                            </div>

                                                            <div class="info fs-7">
                                                                <x-ui.badge.light type="info">
                                                                    {{ $proposal->sended_at->format("d.m.Y") }}
                                                                </x-ui.badge.light>

                                                                @if($proposal->isForeignCurrency)
                                                                    <x-ui.badge.light type="danger">
                                                                        <div>{{ $proposal->currency->name }} (1 {{ $proposal->currency->symbol }} = {{ $proposal->currency_rate }} ₽)</div>
                                                                    </x-ui.badge.light>
                                                                @endif
                                                            </div>
                                                        </div>

                                                    <div class="right fs-4">
                                                        <div class="text-end">
                                                            <h4 class="invoice-number mb-0">№ {{ $proposal->number ?? '?' }}
                                                                @if($proposal->variants->count() > 1)
                                                                    <sup>
                                                                        <span class="mb-1 badge bg-primary text-white">{{ $loop->iteration }} / {{ $proposal->variants->count() }}</span>
                                                                    </sup>
                                                                @endif
                                                            </h4>
                                                        </div>
                                                        <div class="d-flex align-items-center justify-content-end fs-6">
                                                            <div>
                                                                <a href="{{ route('partner.detail', $proposal->partner) }}">
                                                                    {{ $proposal->partner->name }}
                                                                </a>
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

                                                </div>

                                                <div class="card-body">
                                                    <div class="table-responsive" style="display: block;">
                                                        <table id="table-summary" class="table table-bordered no-wrap w-100">
                                                            @if($variant->proposal_software->where('count', '>', 0)->isNotEmpty())
                                                                <tr class="subcaption">
                                                                    <td rowspan="2"></td>
                                                                    <td rowspan="2" class="fw-bold">
                                                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                                                            <span>ПЛАТФОРМА (ПО)</span>
                                                                            <span class="text-nowrap">
                                                                                @foreach($frame_by_block['license'] as $frame_spec)
                                                                                    <code class="fw-bold" title="Спецификация: {{ $frame_spec->name }}">{{ $frame_spec->contract->number ?? 'б/н' }}</code>
                                                                                @endforeach
                                                                                <a href="javascript:void(0)" class="ms-1" title="Прикрепить спецификацию по ПО"
                                                                                   onclick="javascript:box({href:'{{ route('contract_spec.box_spec', [$proposal, 'license']) }}'})">
                                                                                    <x-ui.icon.regular icon="{{ $frame_by_block['license']->isEmpty() ? 'fa-link' : 'fa-edit' }}"/>
                                                                                </a>
                                                                            </span>
                                                                        </div>
                                                                    </td>
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
                                                                            @if(!empty($software->proposal_software->notice))
                                                                                <div class="mb-3">{!! $software->proposal_software->notice !!}</div>
                                                                            @endif

                                                                            @if($software->discount_customer)
                                                                                <div>С учётом скидки {{ $software->discount_customer }}% для заказчика</div>
                                                                            @endif

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
                                                                    <td rowspan="2" class="fw-bold">
                                                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                                                            <span>ПЛАТФОРМА</span>
                                                                            <span class="text-nowrap">
                                                                                @foreach($frame_by_block['platform'] as $frame_spec)
                                                                                    <code class="fw-bold" title="Спецификация: {{ $frame_spec->name }}">{{ $frame_spec->contract->number ?? 'б/н' }}</code>
                                                                                @endforeach
                                                                                <a href="javascript:void(0)" class="ms-1" title="Прикрепить спецификацию по платформе"
                                                                                   onclick="javascript:box({href:'{{ route('contract_spec.box_spec', [$proposal, 'platform']) }}'})">
                                                                                    <x-ui.icon.regular icon="{{ $frame_by_block['platform']->isEmpty() ? 'fa-link' : 'fa-edit' }}"/>
                                                                                </a>
                                                                            </span>
                                                                        </div>
                                                                    </td>
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
                                                                        <td class="text-center align-items-start">{{ $loop->iteration }}</td>
                                                                        <td class="text-wrap">
                                                                            {!! $platform->description !!}
                                                                        </td>
                                                                        <td class="text-end">
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
                                                                        <td class="text-end text-nowrap">
                                                                            = {!! cost_out($platform->cost_discount, $proposal) !!}
                                                                        </td>
                                                                        <td class="text-center">{{ tools()->cost_normalize($platform->count) }}</td>
                                                                        <td class="text-end">
                                                                            {!! cost_out($platform->cost_total, $proposal) !!}
                                                                        </td>
                                                                        <td class="text-center">
                                                                            @if(!empty($platform->notice))
                                                                                <div class="mb-3">{!! $platform->notice !!}</div>
                                                                            @endif

                                                                            @if($platform->discount)
                                                                                <div class="text-info italic">С учётом скидки {{ $platform->discount }}% для заказчика</div>
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
                                                                    <td class="text-end">
                                                                        <span class="fw-bold">=
                                                                            {!! cost_out($variant->platform_cost_total, $proposal) !!}
                                                                        </span>

                                                                        @if($variant->platform_nds_cost_total > 0)
                                                                            <div class="text-info fs-7 font-weight-normal">
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
                                                                    <td rowspan="2" class="text-start fw-bold">НЕЙРОСЕРВИСЫ</td>
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
                                                                        <td class="text-end">
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
                                                                        <td class="text-end text-nowrap">
                                                                            = {!! cost_out($scenario->cost_discount, $proposal) !!}
                                                                        </td>
                                                                        <td class="text-center">{{ tools()->cost_normalize($scenario->count) }}</td>
                                                                        <td class="text-end">
                                                                            {!! cost_out($scenario->cost_total, $proposal) !!}
                                                                        </td>
                                                                        <td class="text-center">
                                                                            @if(!empty($scenario->comment))
                                                                                <div class="mb-3">{!! $scenario->comment !!}</div>
                                                                            @endif

                                                                            @if($scenario->discount)
                                                                                <div class="text-info italic">С учётом скидки {{ $scenario->discount }}% для заказчика</div>
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
                                                                    <td class="text-end">
                                                                        <span class="fw-bold">=
                                                                            {!! cost_out($variant->neuro_cost_total, $proposal) !!}
                                                                        </span>
                                                                        @if($variant->neuro_nds_cost_total > 0)
                                                                            <div class="text-info fs-7 font-weight-normal">
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
                                                                    <td rowspan="2" class="fw-bold">
                                                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                                                            <span>РАБОТЫ</span>
                                                                            <span class="text-nowrap">
                                                                                @foreach($frame_by_block['services'] as $frame_spec)
                                                                                    <code class="fw-bold" title="Спецификация: {{ $frame_spec->name }}">{{ $frame_spec->contract->number ?? 'б/н' }}</code>
                                                                                @endforeach
                                                                                <a href="javascript:void(0)" class="ms-1" title="Прикрепить спецификацию по услугам"
                                                                                   onclick="javascript:box({href:'{{ route('contract_spec.box_spec', [$proposal, 'services']) }}'})">
                                                                                    <x-ui.icon.regular icon="{{ $frame_by_block['services']->isEmpty() ? 'fa-link' : 'fa-edit' }}"/>
                                                                                </a>
                                                                            </span>
                                                                        </div>
                                                                    </td>
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
                                                                            <td class="align-center fs-7 textarea">{!! $work->proposal_work->description !!} </td>
                                                                            <td class="align-center text-end">
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
                                                                            <td class="text-end text-nowrap">
                                                                                = {!! cost_out($work->cost - $discount_customer - $discount_partner, $proposal) !!}
                                                                            </td>
                                                                            <td class="align-center text-end"><nobr>{{ tools()->cost_normalize($work->count) }}</nobr></td>
                                                                            <td class="align-center text-end">
                                                                                @php
                                                                                    $group_cost_total += $work->total;
                                                                                @endphp
                                                                                <div><nobr>
                                                                                        {!! cost_out($work->total, $proposal) !!}
                                                                                    </nobr></div>
                                                                            </td>
                                                                            <td class="align-center fs-7 text-center textarea">
                                                                                @if(!empty($work->proposal_work->notice))
                                                                                    <div class="mb-3">{!! $work->proposal_work->notice !!}</div>
                                                                                @endif

                                                                                @if($work->discount_customer)
                                                                                    <div class="text-info italic mt-2">С учётом скидки {{ $work->discount_customer }}% для заказчика</div>
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach

                                                                    @if($groups->count() > 1)
                                                                        <tr>
                                                                            <td colspan="3" class="py-1 fs-2"></td>
                                                                            <td class="text-end text-nowrap py-1 fs-7">
                                                                                @if($group_discount_customer > 0)
                                                                                    <span class="fw-bold text-nowrap">=
                                                                                                {!! cost_out($group_discount_customer, $proposal) !!}
                                                                                            </span>
                                                                                @endif
                                                                            </td>
                                                                            <td class="text-end text-nowrap py-1 fs-6">
                                                                                @if($group_discount_total > 0)
                                                                                    <span class="fw-bold text-nowrap">=
                                                                                                {!! cost_out($group_discount_total, $proposal) !!}
                                                                                            </span>
                                                                                @endif
                                                                            </td>
                                                                            <td colspan="2" class="py-1 fs-2"/>
                                                                            <td class="text-end text-nowrap py-1">
                                                                                        <span class="fw-bold text-nowrap">=
                                                                                            {!! cost_out($group_cost_total, $proposal) !!}
                                                                                        </span>
                                                                                @if($variant->work_nds_cost_total > 0)
                                                                                    <div class="text-info fs-7 font-weight-normal">
                                                                                        НДС =
                                                                                        {!! cost_out(round($group_nds_total, 2), $proposal) !!}
                                                                                    </div>
                                                                                @endif
                                                                            </td>
                                                                            <td class="py-1 fs-2"></td>
                                                                        </tr>
                                                                    @endif
                                                                @endforeach
                                                                <tr class="fs-4" style="border-top: 2px solid #AAA">
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
                                                                    <td class="text-end">
                                                                        <span class="fw-bold">=
                                                                            {!! cost_out($variant->work_cost_total, $proposal) !!}
                                                                        </span>
                                                                        @if($variant->work_nds_cost_total > 0)
                                                                            <div class="text-info fs-7 font-weight-normal">
                                                                                НДС =
                                                                                {!! cost_out(round($variant->work_nds_cost_total, 2), $proposal) !!}
                                                                            </div>
                                                                        @endif
                                                                    </td>
                                                                    <td></td>
                                                                </tr>
                                                            @endif

                                                            <tr class="fs-3" style="border-top: 4px solid #AAA">
                                                                <td colspan="3"/>
                                                                <td class="text-end">
                                                                    <div class="fw-bold fs-3">
                                                                        @if(array_sum($discount_total['customer']) > 0)
                                                                            {!! cost_out(array_sum($discount_total['customer']), $proposal) !!}
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                                <td class="text-end">
                                                                    <div class="fw-bold fs-3">
                                                                        @if(array_sum($discount_total['partner']) > 0)
                                                                            {!! cost_out(array_sum($discount_total['partner']), $proposal) !!}
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                                <td colspan="2"/>
                                                                <td class="text-end">
                                                                    <div class="fw-bold fs-3">
                                                                        {!! cost_out(round($variant->cost_total, 2), $proposal) !!}
                                                                    </div>
                                                                    @if($variant->nds_cost_total)
                                                                        <div class="fs-8 text-nowrap text-info">(в том числе НДС:<br/>
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
                                        </div>

                                        <x-proposal.extra-pays :variant="$variant"/>

                                        <x-proposal.hardware-table :variant="$variant"/>

                                        <x-proposal_variant.task :variant="$variant"/>
                                    </div>
                                @endforeach

                                <div class="mt-4">
                                    <x-proposal.log-table :proposal="$proposal" />
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
            $("a[proposal-id]").removeClass("bg-gray-400");
            $("a[proposal-id='" + id + "']").addClass("bg-gray-400");

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
