{{--
    Таблица реестра сделок Битрикса (patch v22, вёрстка v22.1 — как в списке КП).

    Повторяет таблицу списка КП (pub/proposal/index.blade.php): те же классы
    `table table_data`, тот же тулбар с поиском справа, та же пагинация и те же
    плашки в ячейках. Отличие одно: строк немного (сделки с 2025 года), поэтому
    таблица целиком отдаётся в браузер, а сортировка и постраничная навигация
    работают на клиенте — серверная пагинация КП здесь не нужна.

    Строка поиска — та самая, что рисует bootstrap-table, но заведена на сервер:
    в ней стоит текущий `q`, а Enter перезагружает страницу с этим `q` в адресе.
    Так поиск виден в ссылке и его уважает выгрузка в Excel.

    Параметры:
      $rows     — сделки из CrmDealRegistryService::rows();
      $params   — текущий отбор (нужен для строки поиска);
      $action   — адрес страницы (куда уходит поиск);
      $prefix   — префикс id тулбара, тот же, что у _filter;
      $table_id — id таблицы (во вкладке партнёра он будет свой, patch v23);
      $ajax     — во вкладке: поиск перерисовывает вкладку, а не страницу;
      $mode     — вкладка реестра: all | projects | archive (patch v24);
      $partner  — область партнёра, если таблица живёт во вкладке карточки;
      $toolbar  — забирать в панель таблицы тулбар _filter (по умолчанию да;
                  на странице реестра false — кнопки и поиск в шапке страницы).
--}}
@php
    $action = $action ?? route('crm-deal.index');
    $prefix = $prefix ?? 'deal';
    $table_id = $table_id ?? 'table_data';
    $ajax = $ajax ?? false;
    $mode = $mode ?? null;
    $partner = $partner ?? null;
    $toolbar = $toolbar ?? true;

    /** Цвет плашки стадии: S — выиграна, F — провалена, P — в работе */
    $semantic = ['S' => 'success', 'F' => 'danger', 'P' => 'primary'];

    /** Значки валют: суммы в реестре живут в валюте сделки */
    $symbols = ['RUB' => '₽', 'USD' => '$', 'EUR' => '€', 'CNY' => '¥'];

    // поиск уходит на сервер: собираем адрес страницы без q, q допишет скрипт
    $search_base = \App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService::searchBase($action, $params, $mode);
@endphp

{{-- Плотность строк как в списке КП. Специфичность важна: у самого
     bootstrap-table есть правило `.bootstrap-table .table > tbody > tr > td`
     `:not(.table-condensed)` и отступом 8px — перебить его можно только
     таким же числом классов в селекторе.
     По id не привязываемся — во вкладке партнёра id таблицы свой. --}}
<style>
    .bootstrap-table .table.table_data:not(.table-condensed) > tbody > tr > td,
    .bootstrap-table .table.table_data:not(.table-condensed) > thead > tr > th { padding: 0; }
    table.table_data .cell { padding: 8px 2px; }
    table.table_data > thead > tr > th .th-inner { padding: .75rem 1.25rem .75rem .5rem; }

    /* Строки рендерятся на сервере, а страницы режет bootstrap-table: до её
       инициализации на экране вся простыня сделок. Прячем, пока таблицу не обернули. */
    table.table_data:not(.bootstrap-table *) { display: none; }
</style>

<table class="table table_data"
       id="{{ $table_id }}"
       @if($toolbar) data-toolbar="#{{ $prefix }}_toolbar" @endif
       data-search="true"
       data-search-text="{{ $params['q'] }}"
       data-search-on-enter-key="true"
       data-pagination="true"
       data-page="1"
       data-page-size="50"
       data-page-list="[10, 25, 50, 100]"
       data-locale="ru-RU"
       data-sort-name="id"
       data-sort-order="desc">
    <thead>
    <tr>
        <th data-field="id" data-align="center" data-width="70"
            data-sortable="true" data-sorter="dealNumberSort">ID</th>

        <th data-field="title" data-align="left"
            data-sortable="true" data-sorter="dealTextSort">Название</th>

        <th data-field="stage" data-align="center" data-width="130"
            data-sortable="true" data-sorter="dealTextSort">Стадия</th>

        <th data-field="date" data-align="center" data-width="100"
            data-sortable="true" data-sorter="dealDateSort">Дата</th>

        <th data-field="manager" data-align="left" data-width="160"
            data-sortable="true" data-sorter="dealTextSort"
            >Менеджер</th>

        <th data-field="partner" data-align="left" data-width="250"
            data-sortable="true" data-sorter="dealTextSort">Партнёр и заказчик</th>

        <th data-field="country" data-align="center" data-width="90"
            data-sortable="true" data-sorter="dealTextSort">Страна</th>

        <th data-field="amount" data-align="right" data-width="120"
            data-sortable="true" data-sorter="dealNumberSort">Сумма</th>

        <th data-field="project" data-align="center" data-width="120"
            data-sortable="true" data-sorter="dealDateSort">Проект</th>

        <th data-field="proposal" data-align="center" data-width="120"
            data-sortable="true" data-sorter="dealTextSort">КП</th>
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        @php
            $proposal = $row->proposal;
            $project = $row->project;
            $currency = $symbols[$row->currency_id] ?? $row->currency_id;
        @endphp
        <tr>
            <td>
                <div class="cell">
                    <a href="{{ $row->deal_url }}" target="_blank" title="Открыть сделку в Битрикс24">
                        {{ $row->id }}
                    </a>
                </div>
            </td>

            <td>
                <div class="cell">
                    <div class="fw-bolder fs-7 align-center d-flex justify-content-start">
                        <a href="{{ $row->deal_url }}" target="_blank" title="Открыть сделку в Битрикс24">
                            {{ $row->title ?: 'без названия' }}
                            <i class="fa-light fa-arrow-up-right-from-square fs-8 ms-1 text-muted"></i>
                        </a>
                    </div>
                </div>
            </td>

            <td>
                <div class="cell">
                    <div class="d-flex flex-column align-items-center gap-1">
                        <span class="badge badge-light-{{ $semantic[$row->stage_semantic_id] ?? 'dark' }} d-inline-flex align-items-center">
                            <span class="fs-7">{{ $row->stage_name ?: '—' }}</span>
                        </span>
                    </div>
                </div>
            </td>

            <td>
                <div class="cell fs-7 text-nowrap">
                    {{ $row->date_create ? \Carbon\Carbon::parse($row->date_create)->format('d.m.Y') : '—' }}
                </div>
            </td>

            <td>
                <div class="cell fs-7 text-nowrap ps-2">{{ $row->manager ?: '—' }}</div>
            </td>

            <td>
                <div class="cell">
                    @if($row->company_name)
                        <x-ui.badge.light type="info" class="text-info-700 bg-hover-info text-hover-white">
                            {{ $row->company_name }}
                        </x-ui.badge.light>
                    @else
                        <span class="text-muted fs-8">партнёр не указан</span>
                    @endif

                    @if($row->customer_name)
                        <span class="px-1 text-dark-800">--></span>
                        <x-ui.badge.light type="primary" class="text-primary-700 bg-hover-primary text-hover-white">
                            {{ $row->customer_name }}
                        </x-ui.badge.light>
                    @endif
                </div>
            </td>

            <td>
                <div class="cell fs-7">{{ $row->country }}</div>
            </td>

            <td>
                <div class="cell">
                    @if((float) $row->opportunity > 0)
                        <span @class(['text-nowrap', 'text-success' => $row->currency_id !== 'RUB'])>
                            {{ tools()->cost_normalize(round($row->opportunity)) }} {{ $currency }}
                        </span>
                    @else
                        -
                    @endif
                </div>
            </td>

            <td>
                <div class="cell">
                    <div class="d-flex justify-content-center fs-7">
                        @if($project)
                            <a href="javascript:void(0)"
                               onclick="javascript:box({href:'{{ route('deal_project.box_info', $project) }}'})"
                               @class(["fs-7 badge d-inline-flex align-items-center text-decoration-none",
                                    "badge-light-secondary" => $project->is_archived,
                                    "badge-light-warning bg-hover-warning text-hover-white" => !$project->is_archived && $project->is_pilot,
                                    "badge-light-info bg-hover-info text-hover-white" => !$project->is_archived && !$project->is_pilot,
                                ])
                               title="{{ $project->is_archived ? 'Проект в архиве' : 'Открыть карточку проекта' }}">
                                <i class="fa-light fa-diagram-project me-2"></i>
                                @if($project->is_pilot)
                                    {{ $project->date_start?->format('d.m.Y') ?? '' }}
                                @else
                                    проект
                                @endif
                            </a>
                        @else
                            {{-- партнёра проекту попап определит сам — по сопоставлению
                                 компании сделки с партнёром портала (patch v23) --}}
                            <a href="javascript:void(0)"
                               onclick="javascript:box({href:'{{ route('deal_project.box_form', $row->id) }}'})"
                               class="fs-7 d-flex align-items-center badge badge-light-primary d-inline-flex align-items-center text-decoration-none bg-hover-primary text-hover-white"
                               title="Создать проект или прикрепить сделку к существующему">
                                <i class="fa-regular fa-plus fs-8 me-1"></i>
                                проект
                            </a>
                        @endif
                    </div>
                </div>
            </td>

            <td>
                <div class="cell">
                    <div class="d-flex justify-content-center">
                            @if($proposal)
                                <a href="{{ route('proposal.detail', [$proposal, $proposal->iteration]) }}"
                                   class="fs-7 badge badge-light-success d-inline-flex align-items-center text-decoration-none bg-hover-success-700 text-hover-white-900"
                                   title="{{ $proposal->name }}">
                                    <i class="fa-light fa-link me-2"></i>
                                    {{ $proposal->number ?: 'КП' }}
                                </a>

                                {{-- сменить или снять привязку, не уходя из реестра --}}
                                <a href="javascript:void(0)" class="text-muted ms-2 text-hover-primary"
                                   onclick="javascript:box({href:'{{ route('crm-deal.box.proposal', $row->id) }}'})"
                                   title="Изменить привязку к КП">
                                    <i class="fa-light fa-pen fs-8"></i>
                                </a>
                            @else
                                <a href="javascript:box({href:'{{ route('crm-deal.box.proposal', $row->id) }}'})"
                                   class="fs-7 badge badge-light-secondary d-inline-flex align-items-center text-decoration-none bg-hover-light-primary text-hover-primary"
                                   title="Привязать КП к сделке">
                                    <i class="fa-light fa-link-slash fs-8"></i>
                                </a>
                            @endif
                    </div>
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

<script>
    // Числовая сортировка: в ячейке лежит разметка и разделители разрядов
    window.dealNumberSort = function (a, b) {
        var num = function (value) {
            var text = String(value === null || value === undefined ? '' : value)
                .replace(/<[^>]*>/g, ' ')
                .replace(/&nbsp;/g, ' ')
                .replace(/[^\d\-,.]/g, '')
                .replace(',', '.');

            var parsed = parseFloat(text);
            return isNaN(parsed) ? -Infinity : parsed;
        };

        var x = num(a), y = num(b);
        return x === y ? 0 : (x > y ? 1 : -1);
    };

    // Текстовая сортировка: сравниваем видимый текст, а не разметку ячейки
    /** Дата в ячейке — dd.mm.yyyy, сравниваем как число yyyymmdd */
    window.dealDateSort = function (a, b) {
        var num = function (value) {
            var m = String(value === null || value === undefined ? '' : value)
                .replace(/<[^>]*>/g, ' ')
                .match(/(\d{2})\.(\d{2})\.(\d{4})/);

            return m ? parseInt(m[3] + m[2] + m[1], 10) : -Infinity;
        };

        var x = num(a), y = num(b);
        return x === y ? 0 : (x > y ? 1 : -1);
    };

    window.dealTextSort = function (a, b) {
        var text = function (value) {
            return String(value === null || value === undefined ? '' : value)
                .replace(/<[^>]*>/g, ' ')
                .replace(/&nbsp;/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .toLowerCase();
        };

        return text(a).localeCompare(text(b), 'ru');
    };

    // Таблица приезжает и вместе со страницей, и ajax'ом во вкладку
    // партнёра, поэтому инициализацию делаем сами и только один раз
    (function () {
        var tries = 0;

        var init = function () {
            // библиотеки может не оказаться вовсе — не ждём её вечно
            if (typeof jQuery === 'undefined' || !jQuery.fn.bootstrapTable) {
                return (++tries < 50) ? setTimeout(init, 100) : null;
            }

            jQuery(function ($) {
                var $table = $('#{{ $table_id }}');
                if (!$table.length || $table.closest('.bootstrap-table').length) return;

                $table.bootstrapTable();

                // строка поиска bootstrap-table работает на сервер: Enter
                // перезагружает страницу, и отбор виден в адресе
                $table.closest('.bootstrap-table').find('.fixed-table-toolbar .search input')
                    .on('keydown', function (event) {
                        if (event.which !== 13) return;

                        event.preventDefault();
                        var url = @json($search_base) + encodeURIComponent($.trim($(this).val()));

@if($ajax)
                        // во вкладке партнёра перерисовываем только вкладку
                        window.dealTabLoad(url, '{{ $prefix }}');
@else
                        location.href = url;
@endif
                    });
            });
        };

        init();
    })();
</script>
