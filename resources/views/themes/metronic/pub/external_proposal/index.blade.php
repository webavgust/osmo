@extends('layouts.layout')

@section('styles')
    @parent
    <link rel="stylesheet" href="/assets/libs/bootstrap-table/dist/bootstrap-table.min.css"/>

    <style>
        /* панель bootstrap-table не нужна: «Фильтр» — в тулбаре страницы, поиск — в шапке карточки */
        .fixed-table-toolbar { display: none; }
        .bootstrap-table .fixed-table-container .table thead th .th-inner { padding: .75rem 1.5rem .75rem .75rem; }
        .fixed-table-container thead th .desc { background-position-y: 8px; }
        .fixed-table-container thead th .asc { background-position-y: 17px; }

        #table_data td { vertical-align: middle; }
        #table_data tr.no-payload td { color: var(--bs-gray-600); }
        #table_data tr.transferred td { background: #e6ffef; }
        #table_data tr.transferred:has(+ tr.transferred) { border-bottom: 1px solid #cfedda; }
    </style>
@endsection

@section('breadcrumb_right')
    @if(!$api_configured)
        <span class="badge badge-light-warning fs-8" title="Заполните OSMOVIEW_CP_URL и OSMOVIEW_CP_KEY в .env">
            <i class="fa-light fa-triangle-exclamation me-1"></i> API не настроен
        </span>
    @endif

    {{-- «Фильтр» и «Убрать» — рядом с «Действиями»; сама модалка в _filter --}}
    @php
        $service = \App\Modules\Pub\ExternalProposal\Services\ExternalProposalService::class;

        // «Фильтр (n)»: считаем только заданные правила — те, у которых значение
        // отличается от «не важно». Строка поиска приезжает из адреса и уезжает
        // вместе с формой, поэтому тоже считается правилом
        $rules_count = collect(['currency', 'license'])
                ->filter(fn($key) => $params[$key] !== '')
                ->count()
            + ($params['transferred'] !== $service::DEFAULTS['transferred'] ? 1 : 0)
            + ($params['q'] !== '' ? 1 : 0);
    @endphp

    <button type="button" class="btn btn-light-info"
            data-bs-toggle="modal" data-bs-target="#external_filter_modal">
        <i class="fa-light fa-filter fs-5 me-2"></i>
        Фильтр <span class="count filter-count @unless($rules_count) d-none @endunless">{{ $rules_count }}</span>
    </button>

    <a href="{{ route('external_proposal.index') }}"
       class="@unless($service::filtered($params)) d-none @endunless me-2 text-dark-500 text-hover-dark"
       id="external_filter_clear">
        <i class="fa-light fa-xmark fs-5 me-2" aria-hidden="true"></i> Убрать
    </a>

    {{-- «Действия»: сюда переехала кнопка «Обновить из API» из тулбара таблицы (patch v27) --}}
    <div class="dropdown">
        <button type="button" class="btn btn-primary" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-light fa-ellipsis-vertical fs-5 me-2"></i>
            Действия
        </button>

        <div class="dropdown-menu dropdown-menu-end">
            <a href="javascript:void(0);" id="btn_sync" class="dropdown-item" onclick="javascript:external_sync();"
               title="Забрать список из API OSMOVIEW CP и загрузить detail для новых записей">
                <i class="fa-light fa-cloud-arrow-down text-primary me-2"></i> Обновить из API
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Модалка фильтра (кнопки — в тулбаре страницы) --}}
    @include('pub.external_proposal._filter')

    <div class="card">
        <div class="card-header pt-4 pb-3 min-h-auto">
            <div class="card-title m-0">
                <h3 class="m-0">Список внешних КП</h3>
            </div>
            <div class="card-toolbar m-0 d-flex align-items-center gap-4">
                <span class="fs-7 text-muted">
                    Всего {{ $totals['count'] }}
                    · перенесено {{ $totals['transferred'] }}
                </span>

                {{-- поиск по таблице: передаётся встроенному поиску bootstrap-table, его поле спрятано --}}
                <input type="search" id="external_search" class="form-control form-control-sm w-250px"
                       placeholder="Поиск" autocomplete="off"/>
            </div>
        </div>

        <div class="card-body p-2">
            <table class="table table_data"
                   id="table_data"
                   data-search="true"
                   data-pagination="true"
                   data-page-size="25"
                   data-page-list="[25, 50, 100]"
                   data-side-pagination="client"
                   data-locale="ru-RU"
                   data-row-attributes="rowAttributes"
                   data-url="{{ route('api.external_proposal.list_table', array_merge(['_token' => auth()->user()->ajax_token], $params)) }}"
            ></table>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script src="/assets/libs/bootstrap-table/dist/bootstrap-table.min.js"></script>
    <script src="/assets/libs/bootstrap-table/dist/bootstrap-table-locale-all.min.js"></script>

    <script>
        function rowAttributes(row) {
            var classes = [];
            if (!row.has_payload) classes.push('no-payload');
            // поле transferred теперь содержит разметку ячейки, признак — is_transferred
            if (row.is_transferred) classes.push('transferred');
            return { 'class': classes.join(' '), 'data-id': row.id };
        }

        // Ячейки собираются на сервере (blade-шаблоны
        // components/external_proposal/table/*), поэтому formatter'ов у колонок
        // больше нет. Сортировка осталась клиентской, но сравнивала бы разметку,
        // поэтому у сортируемых колонок задан sortName: он указывает на «сырое»
        // поле строки (number_raw, name_raw, …), которое API отдаёт рядом с
        // HTML. Так порядок считается по числам, датам и тексту, как раньше.
        var columns = [
            { field: 'number', title: 'Номер', width: 110, sortable: true, sortName: 'number_raw' },
            { field: 'name', title: 'Название', sortable: true, sortName: 'name_raw' },
            { field: 'customer', title: 'Заказчик', sortable: true, sortName: 'customer_raw' },
            { field: 'cameras', title: 'Камеры', align: 'center', width: 90, sortable: true, sortName: 'cameras_raw' },
            { field: 'date', title: 'Дата', width: 110, sortable: true, sortName: 'date_raw' },
            { field: 'license', title: 'Валюта / лицензии', width: 180 },
            { field: 'transferred', title: 'Перенос', align: 'center', width: 150, sortable: true, sortName: 'transferred_raw' },
            { field: 'actions', title: '', width: 190, align: 'right' }
        ];

        /** Строка изменилась (перенос, загрузка detail) — перечитать таблицу */
        function external_table_refresh() {
            $('#table_data').bootstrapTable('refresh', { silent: true });
        }

        /**
         * Заблокировать пункт «Обновить из API» на время запроса.
         *
         * Пункт переехал в меню «Действия» и стал <a class="dropdown-item">,
         * а не <button>: prop('disabled') на ссылке ничего не делает, поэтому
         * блокируем классом .disabled — bootstrap гасит у него pointer-events,
         * и onclick не срабатывает. prop() оставлен на случай, если пункт
         * когда-нибудь снова станет кнопкой.
         */
        function external_sync_lock(flag) {
            $('#btn_sync').toggleClass('disabled', flag).prop('disabled', flag);
        }

        function external_sync() {
            external_sync_lock(true);
            body_block();

            $.ajax({
                url: '{{ route('api.external_proposal.sync') }}',
                type: 'POST',
                data: { _token: csrf_token() },
                dataType: 'json',
                success: function (response) {
                    body_unblock();
                    external_sync_lock(false);

                    if (response.result !== 'success') {
                        toastr.error(response.message ?? 'Не получилось обновить', 'Это провал!', { progressBar: true, timeOut: 8000 });
                        return;
                    }

                    toastr.success(response.message, 'Это успех!', { progressBar: true, timeOut: 5000 });
                    external_table_refresh();
                },
                error: function (xhr) {
                    body_unblock();
                    external_sync_lock(false);
                    toastr.error('Ошибка запроса (' + xhr.status + ')', 'Это провал!', { progressBar: true, timeOut: 5000 });
                }
            });
        }

        function external_fetch(id, url) {
            body_block();

            $.ajax({
                url: url,
                type: 'POST',
                data: { _token: csrf_token() },
                dataType: 'json',
                success: function (response) {
                    body_unblock();

                    if (response.result !== 'success') {
                        toastr.error(response.message ?? 'Не получилось загрузить', 'Это провал!', { progressBar: true, timeOut: 8000 });
                        return;
                    }

                    toastr.success(response.message, 'Это успех!', { progressBar: true, timeOut: 3000 });
                    external_table_refresh();
                },
                error: function (xhr) {
                    body_unblock();
                    toastr.error('Ошибка запроса (' + xhr.status + ')', 'Это провал!', { progressBar: true, timeOut: 5000 });
                }
            });
        }

        $(document).ready(function () {
            $('#table_data').bootstrapTable({
                columns: columns,
                sortName: 'date',
                sortOrder: 'desc',
                classes: 'table table-row-bordered align-middle',
                showRefresh: false,
                // пагинация на клиенте: таблице нужен массив строк
                responseHandler: function (response) {
                    return response && response.rows ? response.rows : response;
                },
                formatNoMatches: function () {
                    return 'Записей нет — «Действия» → «Обновить из API» или импортируйте JSON';
                }
            });

            // поиск из шапки карточки: с паузой, как у встроенного поля таблицы
            var search_timer;
            $('#external_search').on('input', function () {
                var value = this.value;
                clearTimeout(search_timer);
                search_timer = setTimeout(function () {
                    $('#table_data').bootstrapTable('resetSearch', value);
                }, 300);
            });

            // Фильтр: select2 поднимаем сами, с dropdownParent на модалку —
            // иначе список уезжает в body и оказывается под backdrop'ом.
            // allowClear только там, где в списке есть пустой пункт «не важно»
            var $filter = $('#external_filter_modal');
            if ($filter.length) {
                // модалка живёт в карточке — уводим в body, чтобы её не обрезал
                // контекст наложения (bootstrap-table двигает только тулбар)
                if (!$filter.parent().is('body')) $filter.appendTo('body');

                $filter.find('select.external_filter_select').each(function () {
                    if ($(this).data('select2')) return;

                    $(this).select2({
                        width: '100%',
                        dropdownParent: $filter,
                        placeholder: 'не важно',
                        allowClear: $(this).find('option[value=""]').length > 0
                    });
                });
            }
        });
    </script>
@endsection
