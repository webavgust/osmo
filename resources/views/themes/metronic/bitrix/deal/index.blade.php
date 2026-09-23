@extends('layouts.layout')

@section('styles')
    @parent
    <link rel="stylesheet" href="/assets/libs/bootstrap-table/dist/bootstrap-table.min.css"/>

    <style>
        /* панель bootstrap-table не нужна: «Фильтр» — в тулбаре страницы, поиск — в шапке карточки.
           Только на странице реестра: во вкладке партнёра панель остаётся */
        .fixed-table-toolbar { display: none; }

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

        // число в скобках считается по умолчанию вкладки, а не по текущему фильтру
        $tab_hint = fn($code) => match($code) {
            $service::MODE_PROJECTS => 'Сделки с действующим проектом',
            $service::MODE_ARCHIVE => 'Сделки, проект которых отправлен в архив',
            default => 'Сделки с 2025 года, к которым ещё не привязано КП',
        };
    @endphp

    {{-- Модалка фильтра; кнопки «Фильтр» и «Убрать» — в тулбаре страницы (breadcrumb_right),
         поэтому тулбар для панели таблицы не рисуем --}}
    @include('bitrix.deal._filter', ['toolbar' => false])

    <div class="card">
        {{-- вкладки прижаты к нижней границе шапки, отступ сверху — у них самих;
             поле поиска по высоте шапки центрируется, отступы сверху и снизу равны --}}
        <div class="card-header min-h-auto">
            <div class="card-toolbar m-0 pt-4">
                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold" role="tablist">
                    @foreach($tabs as $code => $label)
                        <li class="nav-item">
                            <a class="nav-link @if($mode === $code) active @endif" href="{{ $tab_link($code) }}"
                               title="{{ $tab_hint($code) }}">
                                {{ $label }}
                                {{-- число — то, что вкладка покажет по клику (patch v25) --}}
                                <span class="text-muted fw-normal ms-1">({{ $counts[$code] ?? 0 }})</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card-toolbar m-0 d-flex align-items-center gap-4">
                {{-- поиск серверный, как был в панели таблицы: Enter уводит на тот же
                     адрес с q (CrmDealRegistryService::searchBase), отбор виден в ссылке --}}
                <input type="search" id="deal_search" class="form-control form-control-sm w-250px"
                       placeholder="Поиск" autocomplete="off" value="{{ $params['q'] }}"/>
            </div>
        </div>

        {{-- ускорение 23.09: поиск перерисовывает только эту часть (partial=1), без перезагрузки --}}
        <div class="card-body p-2" id="deal_table_wrap">
            @include('bitrix.deal._table')
        </div>
    </div>
@endsection

@section('breadcrumb_right')
    @php
        // выгрузка берёт тот же отбор и режим вкладки, что сейчас на экране
        $export_url = route('crm-deal.box.export', array_merge(
            \App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService::query($params),
            $mode === \App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService::MODE_ALL ? [] : ['mode' => $mode]
        ));
    @endphp

    {{-- «Фильтр» и «Убрать» — рядом с «Действиями», как на странице КП OSMOVIEW CP;
         модалка подключена в content (_filter без тулбара) --}}
    {{-- обёртка без своей коробки: поиск без перезагрузки меняет счётчик и «Убрать» (ускорение 23.09) --}}
    <span id="deal_filter_buttons" style="display: contents">
        @include('bitrix.deal._filter_buttons')
    </span>

    {{-- «Действия»: как на странице КП OSMOVIEW CP --}}
    <div class="dropdown">
        <button type="button" class="btn btn-primary" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-light fa-ellipsis-vertical fs-5 me-2"></i>
            Действия
        </button>

        <div class="dropdown-menu dropdown-menu-end">
            {{-- ссылка уходит в onclick через @js: «&» и кавычки экранируются один раз --}}
            <a href="javascript:void(0);" class="dropdown-item" id="deal_export" onclick="box({href: @js($export_url)})">
                <i class="fa-light fa-file-excel text-success me-2"></i> Выгрузить в Excel
            </a>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script src="/assets/libs/bootstrap-table/dist/bootstrap-table.min.js"></script>
    <script src="/assets/libs/bootstrap-table/dist/bootstrap-table-locale-all.min.js"></script>

    @php
        // адрес без q — тот же CrmDealRegistryService::searchBase, что и в _table.
        // В переменную, а не прямо в @json: @json режет выражение по запятым
        $deal_search_base = \App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService::searchBase($action, $params, $mode);
    @endphp

    <script>
        // Поиск из шапки карточки — серверный, отбор и вкладка сохраняются. Ускорение 23.09:
        // страница не перезагружается — сервер отдаёт только таблицу, кнопки фильтра и адрес
        // выгрузки (partial=1), адрес в строке браузера меняется через history.replaceState
        // (F5 и ссылка сохраняют поиск). Запускается сам через паузу после ввода, по Enter —
        // сразу, крестик сбрасывает
        $(document).ready(function () {
            var $search = $('#deal_search');
            var applied = $.trim($search.val());
            var timer, request;

            function deal_search_go() {
                clearTimeout(timer);
                var value = $.trim($search.val());
                if (value === applied) return;

                var url = @json($deal_search_base) + encodeURIComponent(value);
                if (request) request.abort();

                var $wrap = $('#deal_table_wrap').css('opacity', .5);
                request = $.ajax({
                    url: url + '&partial=1',
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        if (response.result !== 'success') return;

                        applied = value;
                        $wrap.html(response.table);
                        $('#deal_filter_buttons').html(response.filter_buttons);
                        $('#deal_filter_form input[name="q"]').val(value);
                        $('#deal_export').attr('onclick', 'box({href: ' + JSON.stringify(response.export_url) + '})');
                        history.replaceState(null, '', url);
                    },
                    error: function (xhr, status) {
                        if (status === 'abort') return;
                        // не вышло — старый путь: открыть страницу с поиском
                        location.href = url;
                    },
                    complete: function () {
                        $wrap.css('opacity', '');
                    }
                });
            }

            $search.on('keydown', function (event) {
                if (event.which !== 13) return;
                event.preventDefault();
                deal_search_go();
            });
            $search.on('input', function () {
                clearTimeout(timer);
                timer = setTimeout(deal_search_go, 300);
            });
            // крестик в поле type=search
            $search.on('search', deal_search_go);

            // открыли страницу по ссылке с поиском — курсор в поле, в конце текста
            if (applied !== '') {
                var field = $search.get(0);
                field.focus();
                field.setSelectionRange(field.value.length, field.value.length);
            }
        });
    </script>
@endsection
