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
    @php
        $service = \App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService::class;

        // вкладка живёт в адресе, поэтому это ссылки, а не bootstrap-табы:
        // отбор и вкладку можно отправить одной ссылкой (patch v24)
        $tabs = [
            $service::MODE_ALL => 'Сделки',
            $service::MODE_PROJECTS => 'Проекты',
            $service::MODE_ARCHIVE => 'Архив проектов',
        ];

        // при смене вкладки фильтр не тащим: у вкладок свои значения по умолчанию
        $tab_link = fn($code) => route('crm-deal.index', $code === $service::MODE_ALL ? [] : ['mode' => $code]);
    @endphp

    <div class="card">
        <div class="card-header pt-4 min-h-auto">
            <div class="card-toolbar m-0">
                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold" role="tablist">
                    @foreach($tabs as $code => $label)
                        <li class="nav-item">
                            <a class="nav-link @if($mode === $code) active @endif" href="{{ $tab_link($code) }}">
                                {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                {{-- кнопка «Фильтр» и модалка; тулбар bootstrap-table заберёт
                     себе, поэтому в шапке останутся только вкладки --}}
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
    <x-ui.a.box :href="route('crm-deal.box.export', array_merge(
                    \App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService::query($params),
                    $mode === \App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService::MODE_ALL ? [] : ['mode' => $mode]
                ))"
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
