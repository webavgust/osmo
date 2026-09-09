@extends('layouts.layout')

@section('styles')
    @parent
    <link rel="stylesheet" href="/assets/libs/bootstrap-table/dist/bootstrap-table.min.css"/>

    <style>
        /* панель инструментов bootstrap-table — как в списке КП */
        .fixed-table-toolbar .bs-bars { padding-top: 0; }
        .fixed-table-toolbar .search .form-control { min-width: 240px; }
        .bootstrap-table .fixed-table-container .table thead th .th-inner { padding: .75rem 1.25rem .75rem .5rem; }
        .fixed-table-container thead th .desc { background-position-y: 8px; }
        .fixed-table-container thead th .asc { background-position-y: 17px; }
    </style>
@endsection


@section('content')
    <div class="card">
        <div class="card-header pt-4 min-h-auto">
            <div class="card-toolbar m-0">
                {{-- вкладки, кнопка «Фильтр» и модалка; тулбар bootstrap-table
                     заберёт себе, поэтому в шапке останутся только вкладки --}}
                @include('bitrix.deal._filter')
            </div>
        </div>

        <div class="card-body pt-2">
            @include('bitrix.deal._table')
        </div>
    </div>
@endsection

@section('breadcrumb_right')
    {{-- href передаём привязкой: интерполяция {{ }} экранировала бы «&» второй раз
         (компонент подставляет ссылку внутрь onclick) и фильтр в попап не доехал бы --}}
    <x-ui.a.box :href="route('crm-deal.box.export', \App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService::query($params))"
                btn_type="light-success" class="fw-bold">
        <i class="fa-light fa-file-excel fs-5 me-2"></i>
        Выгрузить в Excel
    </x-ui.a.box>
@endsection

@section('js')
    @parent
    <script src="/assets/libs/bootstrap-table/dist/bootstrap-table.min.js"></script>
    <script src="/assets/libs/bootstrap-table/dist/bootstrap-table-locale-all.min.js"></script>
@endsection
