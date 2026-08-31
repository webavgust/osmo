@extends('layouts.layout')

@section('styles')
    @parent
    <link href="/assets/libs/tablesaw/dist/tablesaw.css" rel="stylesheet" />
    <style>
        /* сводные таблицы дашборда: компактнее и с читаемой шапкой */
        #dashboard .table > :not(caption) > * > * { padding: .4rem .5rem; }
        #dashboard .table thead th { background: var(--bs-gray-100); font-weight: 600; }
        /*#dashboard .card { height: 100%; }*/
    </style>
@endsection

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" integrity="sha512-BNaRQnYJYiPSqHHDb58B0yaPfCu+Wgds8Gp/gU33kqBtgNS4tSPHuGibyoeqMV/TJlSKda6FXzoEyYGjTe+vXA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <div id="dashboard">

        {{-- Показатели --}}
        <div class="row g-4 mb-8">
            <div class="col-6 col-md-4 col-xl"><x-bitrix.dashboard.sales/></div>
            <div class="col-6 col-md-4 col-xl"><x-bitrix.dashboard.licenses/></div>
            <div class="col-6 col-md-4 col-xl"><x-bitrix.dashboard.services/></div>
            <div class="col-6 col-md-4 col-xl"><x-bitrix.dashboard.devcost/></div>
            <div class="col-6 col-md-4 col-xl"><x-bitrix.dashboard.platform/></div>
            <div class="col-6 col-md-4 col-xl"><x-bitrix.dashboard.servicesRaw/></div>
            <div class="col-6 col-md-4 col-xl"><x-pub.license-key.renewal/></div>
        </div>

        {{-- Отрасли --}}
        <div class="row g-4 mb-8">
            <div class="col-12 col-xl-7"><x-bitrix.dashboard.tbl_industry_name/></div>
            <div class="col-12 col-xl-5"><x-bitrix.dashboard.tbl_industry_graph/></div>
        </div>

        {{-- Периоды --}}
        <div class="row g-4">
            <div class="col-12 col-xxl-6">
                <div class="card">
                    <div class="card-header min-h-auto align-items-end">
                        <h3 class="card-title align-items-start flex-column mb-3 mt-5">
                            <span class="card-label fw-bold text-gray-900">Кварталы</span>
                        </h3>
                        <div class="card-toolbar m-0">
                            <ul class="nav nav-tabs nav-line-tabs nav-stretch border-0 fs-6 fw-semibold" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#quarter_country_status" role="tab">
                                        Страна → Статус
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#quarter_status_country" role="tab">
                                        Менеджер → Статус
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="tab-content">
                            <div class="tab-pane active" id="quarter_country_status" role="tabpanel">
                                <x-bitrix.dashboard.tbl_country_status__quarter/>
                            </div>
                            <div class="tab-pane" id="quarter_status_country" role="tabpanel">
                                <x-bitrix.dashboard.tbl_manager_status__quarter/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xxl-6">
                <div class="card">
                    <div class="card-header min-h-auto align-items-end">
                        <h3 class="card-title align-items-start flex-column mb-3 mt-5">
                            <span class="card-label fw-bold text-gray-900">6 месяцев</span>
                        </h3>
                        <div class="card-toolbar m-0">
                            <ul class="nav nav-tabs nav-line-tabs nav-stretch border-0 fs-6 fw-semibold" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#month_country_status" role="tab">
                                        Страна → Статус
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#month_status_country" role="tab">
                                        Статус → Страна
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="tab-content">
                            <div class="tab-pane active" id="month_country_status" role="tabpanel">
                                <x-bitrix.dashboard.tbl_country_status__month/>
                            </div>
                            <div class="tab-pane" id="month_status_country" role="tabpanel">
                                <x-bitrix.dashboard.tbl_status_country__month/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('breadcrumb_right')
    <div class="d-flex align-items-center flex-wrap gap-2">

        @if($deals_issues->count())
            <x-ui.a.box href="{{ route('crm-deal.box.issues') }}" btn_type="danger" class="fw-bold">
                <i class="fa-light fa-triangle-exclamation text-white me-2"></i>
                <span>{{ $deals_issues->count() }}</span>
            </x-ui.a.box>
        @endif

        <x-ui.a.box href="{{ route('dashboard.box.currency') }}" btn_type="light-success" class="fw-bold">
            <i class="fa-solid fa-ruble-sign me-2"></i>
            {{ $currency->slug }} ({{ $currency->symbol }})
        </x-ui.a.box>

        <x-ui.a.box href="{{ route('dashboard.box.filter') }}" :btn_type="empty($filter) ? 'light' : 'success'" class="fw-bold">
            <i class="fa-light fa-filter me-2"></i>
            Фильтр
            @if(!empty($filter))
                ({{ count($filter) }})
            @endif
        </x-ui.a.box>

        @if(!empty($filter))
            <x-ui.a.ajax url="{{ route('api.bitrix.dashboard.remove_filter') }}" reload="1"
                         confirm-message="Вы действительно хотите убрать фильтр?"
                         class="btn btn-light-danger fw-bold">
                <i class="fa-light fa-xmark me-2"></i>
                Убрать
            </x-ui.a.ajax>
        @endif

    </div>
@endsection

@section('js')
    <script src="/assets/libs/tablesaw/dist/tablesaw.jquery.js"></script>
@endsection
