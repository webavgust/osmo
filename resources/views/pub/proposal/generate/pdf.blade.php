@extends('layouts.generate')


@section('content')

    @php
        function cost_out($amount, \App\Modules\Pub\Proposal\Models\Proposal $proposal) {
            if(!$amount) return '-';
            $amount = round($amount);

            if(!$proposal->isForeignCurrency) {
                return '<nobr>' . tools()->cost_normalize($amount) . ' ' . $proposal->currency->symbol . '</nobr>';
            } else {
                return '<nobr>' . $proposal->currency->symbol . ' ' . tools()->cost_normalize($amount, separator: ',') . '.00</nobr>';
            }
        }
        $cur_symbol = $proposal->currency->symbol;
    @endphp

    <link rel="stylesheet" type="text/css" href="/assets/libs/quill/dist/quill.snow.css"/>
    <link rel="stylesheet" type="text/css" href="/css/app.css"/>


    <style>
        *, h1, h2 {
            color: #333;
        }

        .text-danger, .text-danger * {
            color: #fc4b6c !important;
        }
        .text-warning, .text-warning * {
            color: #ffb22b !important;
        }

        :#print span, #print div {
            color: #444;
        }
        tr td, tr th {
            border-color: #BAC7E1;
        }
        .control {
            position: fixed;
            bottom: 20px;
            right: 20px;
        }
        /*[keep] {*/
        /*    background: #ff00000d;*/
        /*    border: 2px solid red;*/
        /*}*/
        body {
            background: white;
        }
        table#table-summary tr {
            page-break-inside: avoid; /* Предотвращает разрыв внутри строк */
            page-break-after: auto; /* Автоматически устанавливает разрыв после строки */
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
        }
        table#table-summary tr td:last-of-type,
        table#table-summary tr th:last-of-type
        {
            border-right: 1px solid #BAC7E1;
        }

        table#table-summary tr.caption {
            background: #AEBDDC;
            color: #444;
            font-weight: 700;
            font-size: 20px;
        }

        table#table-summary tr.result {
            background: #AEBDDC;
            color: #444;
            font-weight: bold;
        }

        table#table-summary tr.subcaption {
            background: #F1F1F1;
            font-weight: bold;
            color: #444;
            font-size: 20px;
        }

        table#table-summary {
            border-bottom: 1px solid #BAC7E1;
        }

        table#table-summary tr.clear td {
            height: 10px;
            visibility: hidden;
            border: 0;
        }
        input.manual {
            font-size: 15px;
            color: #67757c;
            font-weight: 300;
            margin-left: -2px;
            border: 1px solid #DDD !important;
            padding: 2px;
        }
        .textarea {
            font-size: 15px;
            color: #67757c;
            font-weight: 300;
            margin-left: -2px;
        }
        .textarea p {
            white-space: normal !important;
        }

        #print textarea {
            border: 1px solid #DDD!important;
        }
        .card .card-table .tr {
            border-bottom-color: #AAA;
        }
        ul, ol {
            text-align: left;
        }
        @media print {
            .control {
                display: none;
            }

            #print input.manual,
            #print textarea {
                resize: none; /* Disable resizing */
                overflow: hidden;
                border-color: transparent!important;
            }
            .page-break {
                page-break-before: always; /* Forces a page break before this element */
            }
            .ql-toolbar {
                display: none;
            }
            .ql-container {
                border: 0!important;
            }
            table#table-summary.glue {
                page-break-inside: avoid; /* Запретить разрыв страницы внутри таблицы */
            }
        }

        textarea.custom {
            width: 100%;
            margin-top: 40px;
        }
        .ql-editor {
            padding: 20px 10px;
        }
        .ql-editor li {
            margin-bottom: 10px;
        }
    </style>

    <div style="width: 297mm; margin: 0 auto; padding: 10mm; box-sizing: border-box;" id="print" contentEditable="true">
        <div class="text-center">
            <h1 class="mb-5">{{ __('proposal_pdf.title') }}</h1>
        </div>

        <div class="d-flex justify-content-between" keep="top-line">
            <div contentEditable="false"><span class="fw-bold">{{ __('proposal_pdf.number') }}: {{ $proposal->number }} {{ __('proposal_pdf.number_from') }} {{ $proposal->sended_at->format('d.m.Y') }}</span></div>
            <div><span class="fw-bold">{{ __('proposal_pdf.to') }}: {{ $data['form']['contact'] ?? $proposal->partner->contact ?? '-' }}</div>
            <div><span class="fw-bold">{{ __('proposal_pdf.company') }}: {{ $proposal->partner->name ?? '-' }}</div>
        </div>

        <p class="my-4">{{ __('proposal_pdf.intro') }}</p>

        <p class="my-4 fw-bold">{{ __('proposal_pdf.end_user') }}: <span keep="company-name">{{ $proposal->company->name ?? '-' }}</span></p>

        <p class="mt-4 mb-0 fw-bold">{{ __('proposal_pdf.tcp_consists') }}:</p>
        <div class="card">
            <div style="width: 65%" keep="tcp_consists">
                <div class="card-table mt-2">
                <div class="tr">
                    <span class="th">{{ __('proposal_pdf.content_1') }}</span>
                    <span class="td">
                        1
                     </span>
                </div>
                <div class="tr">
                    <span class="th">{{ __('proposal_pdf.content_2') }}</span>
                    <span class="td">
                        2
                     </span>
                </div>
                <div class="tr">
                    <span class="th">{{ __('proposal_pdf.content_3') }}</span>
                    <span class="td">
                        4
                     </span>
                </div>
                <div class="tr">
                    <span class="th">{{ __('proposal_pdf.content_4') }}</span>
                    <span class="td">
                        6
                     </span>
                </div>
{{--                <div class="tr">--}}
{{--                    <span class="th">{{ __('proposal_pdf.content_5') }}</span>--}}
{{--                    <span class="td">--}}
{{--                        5--}}
{{--                     </span>--}}
{{--                </div>--}}
{{--                <div class="tr">--}}
{{--                    <span class="th">{{ __('proposal_pdf.content_6') }}</span>--}}
{{--                    <span class="td">--}}
{{--                        6--}}
{{--                     </span>--}}
{{--                </div>--}}
            </div>
            </div>
        </div>

        <div class="pt-4 mt-4">
            <table class="table no-wrap w-100 mb-0" id="table-summary">
                <tr class="caption">
                    <th colspan="4" class="text-center">
                        {{ __('proposal_pdf.proposal_title') }}
                    </th>
                </tr>
            </table>

                @foreach($variants as $variant)
                    @php
                        $total_cost_client = 0;

                        $has_customer_discount =
                            ($variant->proposal_platforms->isNotEmpty() && $variant->proposal_platforms?->where('discount', '>', 0)->count() > 0) ||
                            ($variant->proposal_scenarios->isNotEmpty() && $variant->proposal_scenarios?->where('discount', '>', 0)->count() > 0) ||
                            ($variant->proposal_works->isNotEmpty() && $variant->proposal_works->where('discount_customer', '>', 0)->count() > 0);


                        $has_partner_discount =
                            $variant->platform_discount_partner_p > 0 ||
                            $variant->neuro_discount_partner_p > 0 ||
                            ($variant->proposal_works->isNotEmpty() && $variant->proposal_works->where('discount_partner', '>', 0)->count() > 0);

                    @endphp
                    <table class="table no-wrap w-100 border-bottom-0" id="table-summary"  keep="{{ $variant->id }}__proposal">
                    @if($loop->iteration > 1)
                        <tr class="clear"><td colspan="4" style="height: 80px"/></tr>
                    @endif
                    <tr class="subcaption">
                        <th class="text-wrap" rowspan="2">
                            @if(!empty($data['variant'][$variant->id]['name']))
                                {{ $data['variant'][$variant->id]['name'] }}
                            @else
                                {{ __('proposal_pdf.variant_name', ['num' => $loop->iteration]) }}
                            @endif
                            <span class="text-secondary">({{ __('proposal_pdf.proposal_application') }} {{ $loop->iteration }})</span>
                        </th>
                        <th colspan="4" class="text-center">{{ __('proposal_pdf.proposal_cost_title') }}</th>
                    </tr>
                   <tr class="subcaption">
                        <th width="150" class="text-center py-1">
                            <div class="fs-4">{{ __('proposal_pdf.proposal_amount') }} @if($has_customer_discount) ** @endif</div>
                            <div class="fw-normal fs-2">{{ __('proposal_pdf.proposal_amount_nds') }}</div>
                        </th>
                        <th width="150" class="text-center py-1">
                            <div class="fs-4">{{ __('proposal_pdf.proposal_amount_partner') }} @if($has_partner_discount) * @endif</div>
                            <div class="fw-normal fs-2">{{ __('proposal_pdf.proposal_amount_nds') }}</div>
                        </th>
                        <th width="120" class="text-center fs-4 py-1" valign="middle">{{ __('proposal_pdf.proposal_tax') }}</th>
                        <th width="150" class="text-center fs-4 py-1">
                            <div class="fs-4">{{ __('proposal_pdf.proposal_total') }} @if($has_partner_discount) * @endif</div>
                            <div class="fw-normal fs-2">{{ __('proposal_pdf.proposal_total_2') }}</div>
                        </th>
                    </tr>

                    @if(!empty($variant->soft_cost_total))
                           @php  $total_cost_client += $variant->soft_discount_customer; @endphp
                        <tr>
                            <td>{{ __('proposal_pdf.proposal_soft') }}</td>
                            <td class="text-end">
                                {!! cost_out($variant->soft_discount_customer, $proposal) !!}
                            </td>
                            <td class="text-end">
                                {!! cost_out($variant->soft_cost_total, $proposal) !!}
                            </td>
                            <td class="text-end">
                                {!! cost_out($variant->soft_nds_cost_total, $proposal) !!}
                            </td>
                            <td class="text-end">
                                {!! cost_out($variant->soft_cost_total + $variant->soft_nds_cost_total, $proposal) !!}
                            </td>
                        </tr>
                    @endif

                    @if($variant->platform_cost_total || $variant->neuro_cost_total)
                            <tr class="fw-bold">
                                <td>{{ __('proposal_pdf.proposal_software_group') }}</td>
                                <td class="text-end">
                                    {!! cost_out($variant->platform_discount_customer + $variant->neuro_discount_customer, $proposal) !!}
                                </td>
                                <td class="text-end">
                                    {!! cost_out($variant->platform_cost_total + $variant->neuro_cost_total, $proposal) !!}
                                </td>
                                <td class="text-end">
                                    {!! cost_out($variant->platform_nds_cost_total + $variant->neuro_nds_cost_total, $proposal) !!}
                                </td>
                                <td class="text-end">
                                    {!! cost_out($variant->platform_cost_total + $variant->platform_nds_cost_total + $variant->neuro_cost_total + $variant->neuro_nds_cost_total, $proposal) !!}
                                </td>
                            </tr>
                    @endif
                    @if(!empty($variant->platform_cost_total))
                           @php
                               $total_cost_client += $variant->platform_discount_customer;
                               $platforms_count = $variant->proposal_platforms()->where('count', '>', 0)->count();
                           @endphp

                        <tr>
                            <td class="ps-5 fs-3 py-2 fs-2">
                                @if($platforms_count > 1)
                                    {{ __('proposal_pdf.proposal_platform_group_title') }}
                                @else
                                    {{ __('proposal_pdf.proposal_platform') }}
                                @endif
                            </td>
                            <td class="text-end py-2 fs-2">
                                {!! cost_out($variant->platform_discount_customer, $proposal) !!}
                            </td>
                            <td class="text-end py-2 fs-2">
                                {!! cost_out($variant->platform_cost_total, $proposal) !!}
                            </td>
                            <td class="text-end py-2 fs-2">
                                {!! cost_out($variant->platform_nds_cost_total, $proposal) !!}
                            </td>
                            <td class="text-end py-2 fs-2">
                                {!! cost_out($variant->platform_cost_total + $variant->platform_nds_cost_total, $proposal) !!}
                            </td>
                        </tr>

                            @if($platforms_count > 1)
                                @foreach($variant->proposal_platforms as $platform)
                                    <tr>
                                        <td class="ps-5 fs-2 py-2">
                                            <span class="ps-5">
                                                @if(\Illuminate\Support\Str::contains($platform->description, 'платформа'))
                                                    {{ __('proposal_pdf.proposal_platform') }}
                                                @else
                                                    {{ __('proposal_pdf.proposal_platform_ai_agent') }}
                                                @endif
                                            </span>
                                        </td>
                                        <td class="text-end py-2 fs-1">
                                            @php
                                                $raw_cost = $platform->cost;
                                                $customer_discount = $raw_cost / 100 * $platform->discount;
                                                $client_cost = $raw_cost - $customer_discount;
                                            @endphp
                                            {!! cost_out($client_cost * $platform->count, $proposal) !!}
                                        </td>
                                        <td class="text-end py-2 fs-1">
                                            @php
                                                $partner_discount = $client_cost / 100 * $variant->platform_discount_partner_p;
                                                $partner_cost = ($client_cost - $partner_discount);
                                            @endphp
                                            {!! cost_out($partner_cost * $platform->count, $proposal) !!}
                                        </td>
                                        <td class="text-end py-2 fs-1">
                                            {!! cost_out($platform->nds, $proposal) !!}
                                        </td>
                                        <td class="text-end py-2 fs-1">
                                            {!! cost_out($partner_cost * $platform->count + $platform->nds, $proposal) !!}
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                    @endif
                    @if(!empty($variant->neuro_cost_total))
                           @php  $total_cost_client += $variant->neuro_discount_customer; @endphp
                        <tr>
                            <td class="ps-5 fs-3 py-2 fs-2">{{ __('proposal_pdf.proposal_neuro') }}</td>
                            <td class="text-end py-2 fs-2">
                                {!! cost_out($variant->neuro_discount_customer, $proposal) !!}
                            </td>
                            <td class="text-end py-2 fs-2">
                                {!! cost_out($variant->neuro_cost_total, $proposal) !!}
                            </td>
                            <td class="text-end py-2 fs-2">
                                {!! cost_out($variant->neuro_nds_cost_total, $proposal) !!}
                            </td>
                            <td class="text-end py-2 fs-2">
                                {!! cost_out($variant->neuro_cost_total + $variant->neuro_nds_cost_total, $proposal) !!}
                            </td>
                        </tr>
                    @endif
                    @if(!empty($variant->work_cost_total))
                        @php
                            $groups = collect();
                            $block_total = 0;
                            foreach($variant->proposal_works as $pw) {
                                if(!$pw->proposal_work->cb_process) continue;

                                $group = $pw->proposal_work->group ?? 'Без группы';
                                if(empty($groups[$group])) $groups[$group] = collect();
                                $groups[$group]->push($pw);
                            }

                            $block_total = $variant->work_cost_total_base - $variant->work_discount_customer;
//                             foreach($groups as $group_name => $works) {
//                                $block_total += $works->sum(function($instance) {
//                                    return $instance->count * $instance->cost - $instance->discount;
//                                });
//                            }

                           $total_cost_client += $block_total;
                        @endphp

                        <tr class="fw-bold">
                            <td>{{ __('proposal_pdf.proposal_work') }}</td>
                            <td class="text-end">
                                {!! cost_out($block_total, $proposal) !!}
                            </td>
                            <td class="text-end">
                                {!! cost_out($variant->work_cost_total, $proposal) !!}
                            </td>
                            <td class="text-end">
                                {!! cost_out($variant->work_nds_cost_total, $proposal) !!}
                            </td>
                            <td class="text-end">
                                {!! cost_out($variant->work_cost_total + $variant->work_nds_cost_total, $proposal) !!}
                            </td>
                        </tr>

                        @if($groups->count() > 1)
                            @foreach($groups as $group_name => $works)
                                @php

                                    $group_cost_total = $works->sum(function($work) {
                                        return ($work->count * $work->cost) - $work->discount;
                                    });
                                    $nds = $works->sum('nds');

                                    $cost_base = $works->sum(function($instance) {
                                        $raw_cost = $instance->count * $instance->cost;
                                        $customer_discount = $raw_cost / 100 * $instance->discount_customer;

                                        return   $raw_cost - $customer_discount;
                                    });
                                @endphp
                                <tr>
                                    <td class="ps-5 fs-3 py-2 fs-2">
                                        {{
                                            \Illuminate\Support\Facades\Lang::has("proposal_pdf.$group_name")
                                            ? __("proposal_pdf.$group_name")
                                            : $group_name }}
                                    </td>
                                    <td class="text-end py-2 fs-2">
                                        {!! cost_out($cost_base, $proposal) !!}
                                    </td>
                                    <td class="text-end py-2 fs-2">
                                        {!! cost_out($group_cost_total, $proposal) !!}
                                    </td>
                                    <td class="text-end py-2 fs-2">
                                        {!! cost_out($nds, $proposal) !!}
                                    </td>
                                    <td class="text-end py-2 fs-2">
                                        {!! cost_out($group_cost_total + $nds, $proposal) !!}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @endif

                    <tr class="result">
                        <td>{{ __('proposal_pdf.proposal_footer_total') }}</td>
                        <td class="text-end">
                            {!! cost_out($total_cost_client, $proposal) !!}
                        </td>
                        <td class="text-end">
                            {!! cost_out($variant->cost_total - $variant->nds_cost_total, $proposal) !!}
                        </td>
                        <td class="text-end">
                            {!! cost_out($variant->nds_cost_total, $proposal) !!}
                        </td>
                        <td class="text-end">
                            {!! cost_out($variant->cost_total, $proposal) !!}
                        </td>
                    </tr>
                </table>

                @if($has_partner_discount)
                    <div keep="{{ $variant->id }}__discount_partner_p">
                        * {!! __('proposal_pdf.proposal_partner_discount') !!}
                    </div>
                @endif

                @if($has_customer_discount)
                    <div keep="{{ $variant->id }}__discount_customer">
                        ** {!! __('proposal_pdf.proposal_customer_discount') !!}
                    </div>
                @endif


                @if($variant->extra_pays->isNotEmpty())
                    <div class="row">
                        <div class="mt-3 col-12">
                        <table id="table-summary" class="table no-wrap w-100">
                            <tr class="caption">
                                <th colspan="3" class="py-1 text-center">{{ __('proposal_pdf.proposal_extra_pays') }}</th>
                            </tr>
                            <tr class="subcaption">
                                <th class="py-1 fs-3 text-start text-dark fw-bold" valign="top">{{ __('proposal_pdf.proposal_extra_pays__name') }}</th>
                                <th width="1" class="py-1 fs-3 text-center text-dark fw-bold" valign="top">{{ __('proposal_pdf.proposal_extra_pays__percent') }}</th>
                                <th width="1" class="py-1 fs-3 text-center text-dark fw-bold" valign="top" width="1">{{ __('proposal_pdf.proposal_extra_pays__total') }}</th>
                            </tr>

                            @foreach($variant->extra_pays as $once)
                                <tr id="{{ $once->id }}">
                                    <td class="py-1 align-top">{!! $once->name ?? 'Без названия' !!}</td>
                                    <td class="py-1 px-3 align-top text-wrap">
                                        <div class="text-nowrap d-flex justify-content-between">
                                            <span class="d-flex justify-content-end align-items-center flex-grow-0" style="width: 50px">
                                                <span class="fw-bold">{{ $once->percent }} %</span>
                                                <x-ui.icon.regular icon="fa-equals" class="mx-2"/>
                                            </span>
                                            <span class="flex-grow-1 text-end">
                                                {!! cost_out(round($once->value), $proposal) !!}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-1 px-3">
                                        <div class="text-nowrap text-end">
                                            {!! cost_out(round($once->total), $proposal) !!}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                    </div>
                @endif

                @endforeach




        </div>

        @foreach($variants as $variant)
            <div class="page-break"></div>
            {{--  ПРИЛОЖЕНИЯ  --}}
            <div class="d-flex mt-3 fs-5 fw-bold">
                {{ __('proposal_pdf.variant_application') }} № {{ $loop->iteration }}
            </div>

            <div style="padding: 10px 0"></div>
            <div class="fs-6 fw-bold">{{ __('proposal_pdf.variant_tasks') }}:</div>
            <div style="padding: 10px 0"></div>


            <div>
                @if(!empty($variant->task))
                    {!! $variant->task !!}
                @endif
            </div>


            <div class="fw-bold" keep="{{ $loop->iteration }}__variant_cameras">{{ __('proposal_pdf.variant_cameras') }} –
                {{ __('proposal_pdf.variant_cameras_postfix', [
                    'number' => $data['variant'][$variant->id]['cameras'],
                    'ru' => !empty($data['variant'][$variant->id]['cameras'])
                    ? tools()->num_rus($data['variant'][$variant->id]['cameras'], ['штуки', 'штука', 'штук'], 1) : " штук"
                 ]) }}

             </div>

            <div keep="{{ $loop->iteration }}__neuroservices_count">{{ __('proposal_pdf.neuroservices_count') }} –
                {{ __('proposal_pdf.neuroservices_count_postfix', ['number' => $variant->proposal_scenarios->where('count', '>', 0)->count(), 'ru' => tools()->num_rus($variant->proposal_scenarios->where('count', '>', 0)->count(), ['штуки', 'штука', 'штук'], false)]) }}
                ({{ __('proposal_pdf.neuroservices_streams_postfix', ['number' => $variant->proposal_scenarios->sum('count'), 'ru' => tools()->num_rus($variant->proposal_scenarios->sum('count'), ['потока', 'поток', 'потоков'], false)]) }})
            </div>

            @if($variant->proposal_scenarios->isNotEmpty())
                <div contentEditable="true" keep="{{ $loop->iteration }}__neuroservices_ul">
                    {{ __('proposal_pdf.neuroservices_ul') }}:
                    <ul>
                    @foreach($variant->proposal_scenarios as $s)
                        <li>{{ $s->mnemonic_name ?? $s->real_name }}</li>
                    @endforeach
                    </ul>
                </div>
            @endif

            <div class="text-center mt-5">
                <h2 class="mb-4" contentEditable="true">{{ __('proposal_pdf.table_caption') }}</h2>
            </div>


            <table id="table-summary" class="table no-wrap w-100" keep="main_table">
                <tr class="caption">
                    <th class="text-center text-dark fw-bold fs-3 p-1" valign="top" width="30">№</th>
                    <th class="text-center text-dark fw-bold fs-3 p-1" valign="top">{{ __('proposal_pdf.tr_name') }}</th>
                    <th class="text-center text-dark fw-bold fs-3 p-1" valign="top">{{ __('proposal_pdf.tr_price') }}</th>
                    <th class="text-center text-dark fw-bold fs-3 p-1" valign="top">{{ __('proposal_pdf.tr_count') }}</th>
                    <th class="text-center text-dark fw-bold fs-3 p-1" valign="top">{{ __('proposal_pdf.tr_total') }}</th>
                    <th class="text-center text-dark fw-bold fs-3 p-1" valign="top">{{ __('proposal_pdf.tr_remark') }}</th>
                </tr>

                @if($variant->proposal_software->isNotEmpty())
                    <tr class="subcaption">
                        <td></td>
                        <td class="text-center fw-bold fs-4">{{ __('proposal_pdf.tr_platform_title') }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    @php
                        $i = 0;
                    @endphp
                    @foreach($variant->proposal_software as $software)
                        @continue(!$software->proposal_software->cb_process && empty($data['show_unprocessed']))
                        @continue(!$software->count)
                        @php
                            $i++;
                        @endphp
                        <tr @class(["bg-light-warning text-warning" => !$software->proposal_software->cb_process])>
                        <td class="text-center align-center">{{ $i }}
                            </td>
                            <td @class(["align-center text-wrap fs-3 textarea", "text-warning" => !$software->proposal_software->cb_process])>{!! $software->proposal_software->description !!}</td>
                            <td class="align-center text-center">
                                <div>
                                    @if($software->count > 0)
                                        <nobr>
                                            {!! cost_out(round($software->cost), $proposal) !!}
                                        </nobr>
                                        @if(round($software->discount) > 0)
                                            <div class="text-danger fs-1">
                                                &ndash;
                                                {!! cost_out(round($software->cost - ($software->total / $software->count), 2), $proposal) !!}
                                            </div>
                                        @endif
                                    @else
                                        {!! cost_out(0, $proposal) !!}
                                    @endif
                                </div>
                            </td>
                            <td class="align-center text-center"><nobr>
                                    {{ $software->count ? tools()->cost_normalize($software->count) : '-' }}
                                </nobr></td>
                            <td class="align-center text-center">
                                <div><nobr>
                                        {!! cost_out(round($software->total), $proposal) !!}
                                    </nobr></div>
                            </td>
                            <td class="align-center fs-3 text-center textarea">
                                <div style="min-height: 21px; color: rgb(103, 117, 124); font-size: 14px;">
                                    {!! $software->proposal_software->notice !!}
                                </div>
                                @if($software->discount)
                                    <div class="text-danger fs-1">
                                        {{ __('proposal_pdf.discount_label') }} {{ $variant->discount_partner_p }}%
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif

                @if($variant->proposal_platforms->isNotEmpty())
                    <tr class="subcaption">
                        <td></td>
                        <td class="text-center fw-bold fs-4">{{ __('proposal_pdf.proposal_platform') }}</td>
                        <td></td>
                        <td class="text-center fw-bold fs-4"></td>
                        <td></td>
                        <td class="text-center fw-bold fs-4"></td>
                    </tr>
                    @php
                        $i = 0;
                    @endphp

                    @foreach($variant->proposal_platforms as $platform)
                        @continue(!$platform->cb_process && empty($data['show_unprocessed']))
                        @continue(!$platform->count)
                        @php
                            $i++;
                        @endphp
                        <tr @class(["bg-light-warning text-warning" => !$platform->cb_process])>
                            <td class="text-center">{{ $i }}</td>
                            <td class="text-wrap">{!! $platform->description !!}</td>
                            <td class="text-center">
                                {!! cost_out(round($platform->cost), $proposal) !!}

                                @if(round($platform->cost - $platform->cost_discount) > 0)
                                    <div class="text-danger fs-1">
                                        &ndash;
                                        {!! cost_out(round($platform->cost - $platform->cost_discount), $proposal) !!}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                {{ $platform->count ? tools()->cost_normalize($platform->count) : '-' }}
                            </td>
                            <td class="text-center">
                                {!! cost_out($platform->cost_total, $proposal) !!}
                            </td>
                            <td class="text-center text-wrap">
                                <div style="min-height: 21px; color: rgb(103, 117, 124); font-size: 14px;">
                                    @if(!empty($platform->notice))
                                        {!! $platform->notice !!}
                                    @endif
                                </div>

                                @php
                                    $discounts = [];
                                    if($variant->platform_discount_partner_p) $discounts[] = __('proposal_pdf.discount_partner', ['discount' => $variant->platform_discount_partner_p]);
                                    if($platform->discount) $discounts[] = __('proposal_pdf.discount_client', ['discount' => $platform->discount]);
                                @endphp
                                @if(count($discounts) == 1)
                                    <div class="text-danger fs-1">
                                        {{ __('proposal_pdf.discount_label') }} {{ $discounts[0] }}
                                    </div>
                                @else
                                    <div class="text-danger fs-1">
                                        {{ __('proposal_pdf.discount_many_label') }}:
                                        @foreach($discounts as $discount_str)
                                            <div>{{ $discount_str }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif

                @if($variant->proposal_scenarios->isNotEmpty())
                    <tr class="subcaption">
                        <td></td>
                        <td class="text-center fw-bold fs-4 text-wrap">{{ __('proposal_pdf.tr_neuroservices_title') }}</td>
                        <td></td>
                        <td class="text-center fw-bold fs-4">{{ __('proposal_pdf.tr_licenses_title') }}</td>
                        <td></td>
                        <td class="text-center fw-bold fs-4"></td>
                    </tr>
                    @php
                        $i = 0;
                    @endphp

                    @foreach($variant->proposal_scenarios as $scenario)
                            @continue(!$scenario->cb_process && empty($data['show_unprocessed']))
                            @continue(!$scenario->count)
                            @php
                                $i++;
                            @endphp
                        <tr @class(["bg-light-warning text-warning" => !$scenario->cb_process])>
                            <td class="text-center">{{ $i }}</td>
                            <td class="text-wrap">{{ $scenario->mnemonic_name ?? $scenario->real_name }}</td>
                            <td class="text-center">
                                {!! cost_out(round($scenario->cost), $proposal) !!}

                                @if(round($scenario->cost - $scenario->cost_discount) > 0)
                                    <div class="text-danger fs-1">
                                        &ndash;
                                        {!! cost_out(round($scenario->cost - $scenario->cost_discount), $proposal) !!}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                {{ $scenario->count ? tools()->cost_normalize($scenario->count) : '-' }}
                            </td>
                            <td class="text-center">
                                {!! cost_out($scenario->cost_total, $proposal) !!}
                            </td>
                            <td class="text-center text-wrap">
                                <div style="min-height: 21px; color: rgb(103, 117, 124); font-size: 14px;">
                                    @if(!empty($scenario->comment))
                                        {!! $scenario->comment !!}
                                    @endif
                                </div>

                                @php
                                    $discounts = [];
                                    if($variant->neuro_discount_partner_p) $discounts[] = __('proposal_pdf.discount_partner', ['discount' => $variant->neuro_discount_partner_p]);
                                    if($scenario->discount) $discounts[] = __('proposal_pdf.discount_client', ['discount' => $scenario->discount]);

                                @endphp
                                @if(count($discounts) == 1)
                                    <div class="text-danger fs-1">
                                        {{ __('proposal_pdf.discount_label') }} {{ $discounts[0] }}
                                    </div>
                                @else
                                    <div class="text-danger fs-1">
                                        {{ __('proposal_pdf.discount_many_label') }}:
                                        @foreach($discounts as $discount_str)
                                            <div>{{ $discount_str }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif

                @if($variant->proposal_works->isNotEmpty())
                    <tr class="subcaption">
                        <td></td>
                        <td class="text-center fw-bold fs-4">{{ __('proposal_pdf.tr_works_title') }}</td>
                        <td></td>
                        <td class="text-center fw-bold fs-4">{{ __('proposal_pdf.tr_hours_title') }}</td>
                        <td></td>
                        <td></td>
                    </tr>
                    @php
                        $i = 0;
                    @endphp
                    @foreach($variant->proposal_works as $work)
                            @continue(!$work->proposal_work->cb_process && empty($data['show_unprocessed']))
                            @continue(!$work->count)
                            @php
                                $i++;
                            @endphp
                        <tr @class(["bg-light-warning text-warning" => !$work->proposal_work->cb_process])>
                            <td class="text-center align-center">{{ $i }}</td>
                            <td @class(["align-center text-wrap fs-3 textarea", "text-warning" => !$work->proposal_work->cb_process])>{!! $work->proposal_work->description !!} </td>
                            <td @class(["align-center text-center", "text-warning" => !$work->proposal_work->cb_process])>
                                <div>
                                    @if($work->count > 0)
                                        <nobr>
                                            {!! cost_out(round(($work->total + $work->discount) / $work->count, 2), $proposal) !!}
                                        </nobr>
                                        @if(round($work->discount) > 0)
                                            <div class="text-danger fs-1">
                                                &ndash;
                                                {!! cost_out(round($work->discount / $work->count, 2), $proposal) !!}
                                            </div>
                                        @endif
                                    @else
                                        @if($work->cost)
                                            {!! cost_out($work->cost, $proposal) !!}
                                        @else
                                            0 ₽
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td @class(["align-center text-center", "text-warning" => !$work->proposal_work->cb_process])><nobr>
                                    {{ $work->count ? tools()->cost_normalize($work->count) : '-' }}
                                </nobr></td>
                            <td @class(["align-center text-center", "text-warning" => !$work->proposal_work->cb_process])>
                                <div>
                                    <nobr>
                                        @if($work->total)
                                            {!! cost_out($work->total, $proposal) !!}
                                        @else
                                            0 ₽
                                        @endif
                                    </nobr>
                                </div>
                            </td>
                            <td @class(["align-center text-wrap fs-3 text-center textarea", "text-warning" => !$work->proposal_work->cb_process])>
                                <div style="min-height: 21px; color: rgb(103, 117, 124); font-size: 14px;">
                                    {!! $work->proposal_work->notice !!}
                                </div>

                                <br/>

                                @php
                                    $discounts = [];
                                    if($work->discount_partner) $discounts[] = __('proposal_pdf.discount_partner', ['discount' => $work->discount_partner]);
                                    if($work->discount_customer) $discounts[] = __('proposal_pdf.discount_client', ['discount' => $work->discount_customer]);
                                @endphp
                                @if(count($discounts) > 0)
                                    @if(count($discounts) == 1)
                                        <div class="text-danger fs-1">
                                            {{ __('proposal_pdf.discount_label') }} {{ $discounts[0] }}
                                        </div>
                                    @else
                                        <div class="text-danger fs-1">
                                            {{ __('proposal_pdf.discount_many_label') }}:
                                            @foreach($discounts as $discount_str)
                                                <div>{{ $discount_str }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach


                @endif

                <tr>
                    <td colspan="4" class="text-end fw-bold fs-5">
                        {{ __('proposal_pdf.proposal_total_cap') }}:
                    </td>
                    <td class="text-center">
                        <div class="fw-bold fs-5">
                            {!! cost_out($variant->cost_total - $variant->nds_cost_total, $proposal) !!}
                        </div>
                        @if($variant->nds_cost_total)
                            <div class="fs-2">(+ {{ __('proposal_pdf.proposal_tax') }}: {!! cost_out($variant->nds_cost_total, $proposal) !!})</div>
                        @endif
                    </td>
                    <td class="py-1 fs-2 align-content-center">
                        <div>{!! __('proposal_pdf.proposal_total_software', ['amount' => cost_out($variant->soft_cost_total + $variant->platform_cost_total + $variant->neuro_cost_total, $proposal)]) !!}</div>
                        <div>{!! __('proposal_pdf.proposal_total_services', ['amount' => cost_out($variant->work_cost_total, $proposal)]) !!}</div>

                    </td>
                </tr>
            </table>

                <div class="text-center mt-5">
                    <h2 class="mb-4">{{ __('proposal_pdf.hardware_requirements') }}</h2>
                </div>

                <table id="table-summary" class="table no-wrap w-100" keep="hardware_table">
                    <tr class="caption">
                        <th class="text-center text-dark fw-bold fs-3 p-1" valign="top" width="30">№</th>
                        <th class="text-center text-dark fw-bold fs-3 p-1" valign="top">{{ __('proposal_pdf.tr_name') }}</th>
                        <th class="text-center text-dark fw-bold fs-3 p-1" valign="top">{{ __('proposal_pdf.tr_count_full') }}</th>
                        <th class="text-center text-dark fw-bold fs-3 p-1" valign="top">{{ __('proposal_pdf.tr_model') }}</th>
                    </tr>
                        @forelse($variant->hardware as $hardware)
                            <tr>
                                <td class="text-wrap fs-3">{!! $loop->iteration !!}</td>
                                <td class="text-wrap fs-3">{!! $hardware->name !!}</td>
                                <td class="text-wrap fs-3">{!! $hardware->count !!}</td>
                                <td class="text-wrap fs-3">{!! html_entity_decode($hardware->params) !!}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4"/>
                            </tr>
                        @endforelse
                </table>



            <table id="table-summary" class="glue table no-wrap w-100 fs-3">
                <tr class="caption">
                    <th class="w-50 text-start text-dark fw-bold fs-5 p-1 ps-2" valign="top" width="30">{{ __('proposal_pdf.payment_terms_title') }}</th>
                    <th class="w-50 text-start text-dark fw-bold fs-5 p-1 ps-2" valign="top">{{ __('proposal_pdf.warranty_title') }}</th>
                </tr>
                <tr>
                    <td class="text-wrap border-end-0 border-bottom-0">
                         <ol>
                            <li class="ps-2">{{ __('proposal_pdf.payment') }}:
                                <p>
                                    {{ __('proposal_pdf.pre_payment') }}:
                                    <span keep="prepay">{!! cost_out($variant->final_prepay, $proposal) !!}</span>

                                    @if($variant->work_nds_cost_total)
                                        <span keep="prepay_tax">({{ __('proposal_pdf.tax_included') }}: {!! cost_out($variant->work_nds_cost_total, $proposal) !!} )</span>
                                    @endif
                                </p>
                                <p>
                                    {{ __('proposal_pdf.final_payment_licenses') }}:
                                    <span keep="final_payment">
                                        {!! cost_out( $variant->final_payment , $proposal) !!}
                                    </span>

                                    @if($variant->neuro_nds_cost_total || $variant->soft_nds_cost_total)
                                        ({{ __('proposal_pdf.tax_included') }}: <span keep="final_payment_nds">{!! cost_out($variant->neuro_nds_cost_total + $variant->soft_nds_cost_total, $proposal) !!})</span>
                                    @else
                                        ({{ __('proposal_pdf.tax_excluded_warning') }})
                                    @endif
                                </p>
                            </li>
                            <li class="ps-2 mt-2">{{ __('proposal_pdf.proposal_validity') }}</li>

                            <li class="ps-2 mt-2">{{ __('proposal_pdf.package_includes') }}:
                                <ul class="w-100">
                                    <li>{{ __('proposal_pdf.package_includes_1') }}</li>
                                    <li>{{ __('proposal_pdf.package_includes_2') }}</li>
                                </ul>
                            </li>
                        </ol>
                    </td>
                    <td class="text-wrap border-start-0 border-bottom-0">
                            <ol class="w-100" start="4">
                                <li class="ps-2">
                                    {{ __('proposal_pdf.sw_delivery_time') }}: {{ $data['variant'][$variant->id]['period_po'] ?? '' }}
                                </li>
                                <li class="ps-2 mt-2">
                                    {{ __('proposal_pdf.hw_delivery_time') }}: {{ $data['variant'][$variant->id]['period_pk'] ?? __('proposal_pdf.hw_delivery_time_self') }}
                                </li>
                                <li class="ps-2 mt-2">
                                    {{ __('proposal_pdf.warranty_description') }}
                                </li>
                                <li class="ps-2 mt-2">
                                    @php
                                        $amount = $proposal->variants->where('is_main', 1)->first()->proposal_works()->whereHas('proposal_work', function($builder) {
                                                       $builder->where('description', 'like', '%Support for the administration of the platform%');
                                                    })->first()->cost ?? 0;
                                    @endphp
                                    {!!  __('proposal_pdf.warranty_description_2', [
                                        'amount' =>  $amount ? cost_out((float)$amount, $proposal) : $cur_symbol . ' ...... ',
                                    ]) !!}
                                </li>
                            </ol>

                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="text-wrap border-bottom-0 ps-4 pt-4">
                        <div class="ps-1">
                            <p>{{ __('proposal_pdf.alert_1') }}</p>
                            <p>{{ __('proposal_pdf.alert_2') }}</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="text-end fw-bold border-top-0 pb-4 pe-4">
                        {{ __('proposal_pdf.regards') }},
                        @if($data['language'] == 'ru')
                            {{ $proposal->manager->full_name }}
                        @else
                            {{ $proposal->manager->full_name_en }}
                        @endif
                    </td>
                </tr>
            </table>

        @endforeach
    </div>

    <div class="control">
        <div class="d-flex flex-column justify-content-between ">
            <x-ui.a.box href="{{ route('proposal_pdf_template.box_templates', [$proposal, $proposal->iteration]) }}" btn_type="warning" id="template_load_btn" @class(["d-none" => $proposal->proposal_pdf_templates->isEmpty(), "mb-1"])>
                Загрузить черновик
            </x-ui.a.box>

            <x-ui.button.default btn_type="info" onclick="javascript:template_save();">
                Сохранить черновик
            </x-ui.button.default>
        </div>
    </div>

    <div id="box"></div>

@endsection

@section('js')

    <script src="/assets/libs/quill/dist/quill.min.js"></script>
    <script>
        $(document).ready(function() {
            $("div.editor").each(function() {
                var quill = new Quill("#" + $(this).attr("id"), {
                    theme: "snow",
                });
            });
            //
            //
            // $("div#print textarea").on("dblclick", function() {
            //     if(confirm("Удалить?")) $(this).remove();
            // });
            //
            // $("div#print p").on('dblclick', function() {
            //     var textarea = $('<textarea class="custom" placeholder="Введите текст..."></textarea>');
            //
            //     // Вставляем textarea перед элементом, на который был двойной клик
            //     $(this).before(textarea);
            //
            //     // Устанавливаем фокус на textarea
            //     textarea.focus();
            //
            //     textarea.on("dblclick", function() {
            //         if(confirm("Удалить?")) $(this).remove();
            //     });
            //
            // });

            rebind();
        });
        function rebind() {
            $(".card-table .tr").off("dblclick");

            $(".card-table .tr").on("dblclick", function() {
                html = $($(this)[0].outerHTML)
                $(".card-table .tr:last-of-type").after(html);
                rebind();
            });
        }

        function template_save() {
            var name = prompt("Введите название шаблона для сохранения");
            if(!name) {
                toastr.error("Не указано название шаблона", "Шаблон не сохранен!", {
                    progressBar: true,
                    "timeOut": 3000,
                });
                return false;
            }

            $("body").block(block_default);
            $.ajax({
                url: "{{ route('api.proposal_pdf_template.store', [$proposal, $proposal->iteration, '_token' => _token() ]) }}",
                type: "POST",
                dataType: "json",
                data: {
                    name: name,
                    html: $("#print").html(),
                },
                success: function (response) {
                    $("#template_load_btn").removeClass("d-none");
                    $("body").unblock();
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
@endsection
