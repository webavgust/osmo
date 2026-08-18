@extends('layouts.layout')

@section('styles')
    @parent
@endsection


@section('content')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" integrity="sha512-BNaRQnYJYiPSqHHDb58B0yaPfCu+Wgds8Gp/gU33kqBtgNS4tSPHuGibyoeqMV/TJlSKda6FXzoEyYGjTe+vXA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <link href="/assets/libs/tablesaw/dist/tablesaw.css" rel="stylesheet" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-2">
                <x-bitrix.dashboard.sales/>
            </div>
            <div class="col-2">
                <x-bitrix.dashboard.licenses/>
            </div>
            <div class="col-2">
                <x-bitrix.dashboard.services/>
            </div>
            <div class="col-2">
                <x-bitrix.dashboard.devcost/>
            </div>
            <div class="col-2">
                <x-bitrix.dashboard.platform/>
            </div>
            <div class="col-2">
                <x-bitrix.dashboard.servicesRaw/>
            </div>
        </div>


{{--        <x-bitrix.dashboard.deal_issues/>--}}


        <div class="row d-flex align-items-stretch mb-4">
            <div class="col-7">
                <x-bitrix.dashboard.tbl_industry_name/>
            </div>
            <div class="col-5">
                <x-bitrix.dashboard.tbl_industry_graph/>
            </div>
        </div>


        <div class="row d-flex align-items-stretch">
            <div class="col-6">
                <h2>Кварталы</h2>

                <div class="row">
                    <div class="col-12 mb-4">
                        <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold">
                            <li class="nav-item">
                                <a class="nav-link d-flex active" data-bs-toggle="tab" href="#quarter_country_status" role="tab" aria-selected="true">
                                    <span>Страна > Статус</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex" data-bs-toggle="tab" href="#quarter_status_country" role="tab" aria-selected="false">
                                    <span>Менеджер > Статус</span>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane active" id="quarter_country_status" role="tabpanel">
                                <x-bitrix.dashboard.tbl_country_status__quarter/>
                            </div>
                            <div class="tab-pane " id="quarter_status_country" role="tabpanel">
                                <x-bitrix.dashboard.tbl_manager_status__quarter/>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="col-6">
                <h2>6 месяцев</h2>
                <div class="row">
                    <div class="col-12 mb-4">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link d-flex active" data-bs-toggle="tab" href="#month_country_status" role="tab" aria-selected="true">
                                        <span>Страна > Статус</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link d-flex" data-bs-toggle="tab" href="#month_status_country" role="tab" aria-selected="false">
                                        <span>Статус > Страна</span>
                                    </a>
                                </li>
                            </ul>


                            <div class="tab-content">
                                <div class="tab-pane active" id="month_country_status" role="tabpanel">
                                    <x-bitrix.dashboard.tbl_country_status__month/>
                                </div>
                                <div class="tab-pane " id="month_status_country" role="tabpanel">
                                    <x-bitrix.dashboard.tbl_status_country__month/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
@endsection

@section('breadcrumb_right')
    <div class="d-flex align-items-center justify-content-end fs-5">

        @if($deals_issues->count())
            <x-ui.a.box href="{{ route('crm-deal.box.issues') }}" btn_type="danger" class=" fw-bolder fs-5 ms-2">
                <x-ui.icon.regular icon="fa-triangle-exclamation" class="text-white"/>
                <span>{{ $deals_issues->count() }}</span>
            </x-ui.a.box>
        @endif


        <x-ui.a.box href="{{ route('dashboard.box.currency') }}" btn_type="light-info" class="text-success fw-bolder fs-5 ms-2">
            {{ $currency->slug }} ({{ $currency->symbol }})
        </x-ui.a.box>

        <div>
            <x-ui.a.box href="{{ route('dashboard.box.filter') }}" :btn_type="empty($filter) ? 'light-secondary' : 'success'"
                    @class(["fw-bolder fs-5 ms-2", "text-secondary" => empty($filter), "text-white" => !empty($filter)])>
                Фильтр
                @if(!empty($filter))
                    ({{ count($filter) }})
                @endif
            </x-ui.a.box>



            @if(!empty($filter))
                <x-ui.a.ajax url="{{ route('api.bitrix.dashboard.remove_filter') }}" reload="1" confirm-message="Вы действительно хотите убрать фильтр?">
                    <x-ui.icon.solid icon="fa-xmark"></x-ui.icon.solid>
                    Убрать
                </x-ui.a.ajax>
            @endif
        </div>

    </div>
@endsection

@section('js')
    <script src="/assets/libs/tablesaw/dist/tablesaw.jquery.js"></script>
@endsection
