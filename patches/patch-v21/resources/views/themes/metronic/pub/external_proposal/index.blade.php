@extends('layouts.layout')

@section('styles')
    @parent
    <link rel="stylesheet" href="/assets/libs/bootstrap-table/dist/bootstrap-table.min.css"/>

    <style>
        /* панель инструментов bootstrap-table */
        .fixed-table-toolbar .bs-bars { padding-top: 0; }
        .fixed-table-toolbar .search .form-control { min-width: 240px; }
        .bootstrap-table .fixed-table-container .table thead th .th-inner { padding: .75rem 1.5rem .75rem .75rem; }
        .fixed-table-container thead th .desc { background-position-y: 8px; }
        .fixed-table-container thead th .asc { background-position-y: 17px; }

        #table_data td { vertical-align: middle; }
        #table_data tr.no-payload td { color: var(--bs-gray-600); }
        #table_data tr.transferred td { background: var(--bs-success-light); }
    </style>
@endsection

@section('breadcrumb_right')
    @if(!$api_configured)
        <span class="badge badge-light-warning fs-8" title="Заполните OSMOVIEW_CP_URL и OSMOVIEW_CP_KEY в .env">
            <i class="fa-light fa-triangle-exclamation me-1"></i> API не настроен
        </span>
    @endif
@endsection

@section('content')
    {{-- Тулбар таблицы (bootstrap-table переносит его в свою панель) --}}
    <div id="toolbar" class="d-flex flex-wrap align-items-center gap-2">
        <button type="button" id="btn_sync" class="btn btn-primary" onclick="javascript:external_sync();"
                title="Забрать список из API OSMOVIEW CP и загрузить detail для новых записей">
            <i class="fa-light fa-cloud-arrow-down fs-5 me-2"></i>
            Обновить из API
        </button>

        <form method="get" action="{{ route('external_proposal.index') }}" class="d-flex align-items-center gap-2 ms-lg-4" id="filter_form">
            <select name="transferred" class="form-select form-select-solid" style="width: 190px" onchange="this.form.submit()">
                <option value="all" @selected($params['transferred'] === 'all')>Все записи</option>
                <option value="no" @selected($params['transferred'] === 'no')>Не перенесённые</option>
                <option value="yes" @selected($params['transferred'] === 'yes')>Перенесённые</option>
            </select>

            <input type="text" name="q" value="{{ $params['q'] }}" class="form-control form-control-solid" style="width: 240px"
                   placeholder="Номер, название, заказчик">

            @if($params['q'] !== '' || $params['transferred'] !== 'all')
                <a href="{{ route('external_proposal.index') }}" class="btn btn-light-danger" title="Сбросить фильтр">
                    <i class="fa-light fa-xmark fs-5"></i>
                </a>
            @endif
        </form>
    </div>

    <div class="card">
        <div class="card-header pt-4 min-h-auto">
            <div class="card-title m-0">
                <h3 class="m-0">КП OSMOVIEW CP</h3>
            </div>
            <div class="card-toolbar m-0 fs-7 text-muted">
                Всего {{ $totals['count'] }}
                · перенесено {{ $totals['transferred'] }}
                · без детальных данных {{ $totals['without_payload'] }}
            </div>
        </div>

        <div class="card-body pt-2">
            <table class="table table_data"
                   id="table_data"
                   data-search="true"
                   data-toolbar="#toolbar"
                   data-pagination="true"
                   data-page-size="25"
                   data-page-list="[25, 50, 100]"
                   data-side-pagination="client"
                   data-locale="ru-RU"
                   data-row-attributes="rowAttributes"
                   data-url="{{ route('api.external_proposal.list_table', ['_token' => auth()->user()->ajax_token, 'q' => $params['q'], 'transferred' => $params['transferred']]) }}"
            ></table>

            <div class="fs-8 text-muted mt-3">
                Записи приходят из генератора КП Алексея (OSMOVIEW CP) по кнопке «Обновить из API».
                Пока приложение Алексея закрыто аутентификацией AI Studio, ответ <code>detail</code>
                можно залить командой <code>php artisan external-proposal:import файл.json</code>.
                @if($api_url)
                    Адрес API: <code>{{ $api_url }}</code>.
                @endif
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script src="/assets/libs/bootstrap-table/dist/bootstrap-table.min.js"></script>
    <script src="/assets/libs/bootstrap-table/dist/bootstrap-table-locale-all.min.js"></script>

    <script>
        var LICENSE_LABELS = {
            unlimited: {label: 'бессрочные', color: 'success'},
            year: {label: 'годовые', color: 'primary'},
            mixed: {label: 'смешанные', color: 'warning'}
        };

        function escapeHtml(value) {
            return $('<span>').text(value ?? '').html();
        }

        function rowAttributes(row) {
            var classes = [];
            if (!row.has_payload) classes.push('no-payload');
            if (row.transferred) classes.push('transferred');
            return { 'class': classes.join(' '), 'data-id': row.id };
        }

        function numberFormatter(value, row) {
            return '<div class="fw-bold text-nowrap">' + (value ? escapeHtml(value) : '<span class="text-muted">—</span>') + '</div>';
        }

        function nameFormatter(value, row) {
            var html = '<a href="javascript:void(0);" onclick="box({href: \'' + row.link.detail + '\'})" class="fw-semibold">' + escapeHtml(value) + '</a>';
            if (!row.has_payload) {
                html += '<div class="fs-9 text-muted"><i class="fa-light fa-circle-info me-1"></i>детальные данные не загружены</div>';
            }
            return html;
        }

        function customerFormatter(value) {
            return value ? escapeHtml(value) : '<span class="text-muted">—</span>';
        }

        function camerasFormatter(value) {
            return value ? cost_normalize(value) : '<span class="text-muted">—</span>';
        }

        function dateFormatter(value, row) {
            return '<span class="text-nowrap">' + (row.date_label ?? '—') + '</span>';
        }

        function licenseFormatter(value, row) {
            if (!row.has_payload) return '<span class="text-muted">—</span>';

            var html = '<span class="badge badge-light-dark me-1">' + escapeHtml(row.currency) + '</span>';
            if (row.license && LICENSE_LABELS[row.license]) {
                html += '<span class="badge badge-light-' + LICENSE_LABELS[row.license].color + '">' + LICENSE_LABELS[row.license].label + '</span>';
            }
            html += '<div class="fs-9 text-muted mt-1 text-nowrap">сценариев ' + (row.items ?? 0) + ' · работ ' + (row.works ?? 0)
                + (row.vat ? ' · НДС' : ' · без НДС') + '</div>';
            return html;
        }

        function transferFormatter(value, row) {
            if (!row.transferred) {
                return '<span class="badge badge-light">не перенесено</span>';
            }

            var html = '';
            if (row.proposal) {
                html += '<a href="' + row.proposal.url + '" class="badge badge-light-success fs-7" title="Открыть наше КП">'
                    + '<i class="fa-light fa-arrow-right-to-bracket me-1"></i>' + escapeHtml(row.proposal.number) + '</a>';
            } else {
                html += '<span class="badge badge-light-warning">КП не найдено</span>';
            }
            html += '<div class="fs-9 text-muted mt-1 text-nowrap" title="' + (row.transferred_by ? escapeHtml(row.transferred_by) : '') + '">' + (row.transferred_at ?? '') + '</div>';
            return html;
        }

        function actionsFormatter(value, row) {
            // у перенесённой записи (зелёная строка) кнопки зелёные, а перенос
            // превращается в «Дублировать» — он создаёт ещё одно новое КП
            var main = row.transferred ? 'btn-success' : 'btn-light-primary';
            var html = '<div class="d-flex justify-content-end gap-1 text-nowrap">';

            if (row.has_payload) {
                html += '<button type="button" class="btn btn-sm ' + main + '" onclick="box({href: \'' + row.link.transfer + '\'})" title="'
                    + (row.transferred ? 'Перенести ещё раз как новое КП' : 'Перенести в наше КП') + '">'
                    + (row.transferred
                        ? '<i class="fa-light fa-copy fs-6 me-1"></i>Дублировать'
                        : '<i class="fa-light fa-arrow-right-to-bracket fs-6 me-1"></i>Перенести')
                    + '</button>';
            } else {
                html += '<button type="button" class="btn btn-sm ' + main + '" onclick="external_fetch(' + row.id + ', \'' + row.link.fetch + '\')" title="Загрузить detail из API">'
                    + '<i class="fa-light fa-cloud-arrow-down fs-6 me-1"></i>Загрузить</button>';
            }

            // «Подробнее» — последней кнопкой в ряду
            html += '<button type="button" class="btn btn-sm ' + (row.transferred ? 'btn-success' : 'btn-light') + '" onclick="box({href: \'' + row.link.detail + '\'})" title="Подробнее">'
                + '<i class="fa-light fa-eye fs-6"></i></button>';

            html += '</div>';
            return html;
        }

        var columns = [
            { field: 'number', title: 'Номер', width: 110, sortable: true, formatter: numberFormatter },
            { field: 'name', title: 'Название', sortable: true, formatter: nameFormatter },
            { field: 'customer', title: 'Заказчик', sortable: true, formatter: customerFormatter },
            { field: 'cameras', title: 'Камеры', align: 'center', width: 90, sortable: true, formatter: camerasFormatter },
            { field: 'date', title: 'Дата', width: 110, sortable: true, formatter: dateFormatter },
            { field: 'license', title: 'Валюта / лицензии', width: 180, formatter: licenseFormatter },
            { field: 'transferred', title: 'Перенос', align: 'center', width: 150, sortable: true, formatter: transferFormatter },
            { field: 'actions', title: '', width: 190, align: 'right', formatter: actionsFormatter }
        ];

        /** Строка изменилась (перенос, загрузка detail) — перечитать таблицу */
        function external_table_refresh() {
            $('#table_data').bootstrapTable('refresh', { silent: true });
        }

        function external_sync() {
            var btn = $('#btn_sync');
            btn.prop('disabled', true);
            body_block();

            $.ajax({
                url: '{{ route('api.external_proposal.sync') }}',
                type: 'POST',
                data: { _token: csrf_token() },
                dataType: 'json',
                success: function (response) {
                    body_unblock();
                    btn.prop('disabled', false);

                    if (response.result !== 'success') {
                        toastr.error(response.message ?? 'Не получилось обновить', 'Это провал!', { progressBar: true, timeOut: 8000 });
                        return;
                    }

                    toastr.success(response.message, 'Это успех!', { progressBar: true, timeOut: 5000 });
                    external_table_refresh();
                },
                error: function (xhr) {
                    body_unblock();
                    btn.prop('disabled', false);
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
                    return 'Записей нет — нажмите «Обновить из API» или импортируйте JSON';
                }
            });
        });
    </script>
@endsection
