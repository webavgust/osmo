@extends('layouts.layout')

{{-- Отчёт «Движение сделок за год» (patch v50) --}}

@php
    // плитки когорты: ключ группы, подпись, иконка, цвет
    $tiles = [
        ['total', 'Всего', 'fa-inbox-in', 'primary'],
        ['work', 'В работе', 'fa-person-running', 'info'],
        ['lost', 'Отвалились', 'fa-circle-xmark', 'danger'],
        ['done', 'Закончились', 'fa-circle-check', 'success'],
    ];
    $moves = [
        ['lost', 'Отвалились', 'fa-circle-xmark', 'danger'],
        ['done', 'Закончились', 'fa-circle-check', 'success'],
    ];
@endphp

@section('styles')
    @parent
    <style>
        #deal-movement .table > :not(caption) > * > * { padding: .4rem .5rem; }
        #deal-movement .table thead th { background: var(--bs-gray-100); font-weight: 600; }
        /* вкладки-острова: отдельные плашки фона карточки; активная — основной цвет */
        #mv-tabs .mv-island {
            display: flex; align-items: center; padding: .65rem 1.25rem;
            background: var(--bs-body-bg); color: var(--bs-gray-700);
            border-radius: .75rem; box-shadow: var(--bs-card-box-shadow);
            transition: color .15s, background-color .15s;
        }
        #mv-tabs .mv-island i { color: inherit; }
        #mv-tabs .mv-island:hover { color: var(--bs-primary); }
        #mv-tabs .mv-island.active { background: var(--bs-primary); color: var(--bs-primary-inverse, #fff); }
    </style>
@endsection

@section('content')
    <div id="deal-movement">

        {{-- Вкладки: таблица / диаграмма; активная — в ?view, переключение без перезагрузки --}}
        <ul class="nav fs-6 fw-semibold mb-6 gap-3" id="mv-tabs">
            <li class="nav-item">
                <a href="{{ route('bitrix.movement', ['year' => $year, 'at' => $at, 'view' => 'table']) }}" data-mv-view="table"
                   class="nav-link mv-island @if($view === 'table') active @endif">
                    <i class="fa-light fa-table me-2"></i>Таблица
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('bitrix.movement', ['year' => $year, 'at' => $at, 'view' => 'gantt']) }}" data-mv-view="gantt"
                   class="nav-link mv-island @if($view === 'gantt') active @endif">
                    <i class="fa-light fa-chart-gantt me-2"></i>Диаграмма
                </a>
            </li>
        </ul>

        <div id="mv-pane-table" @class(['d-none' => $view !== 'table'])>

        {{-- Когорта года --}}
        <h3 class="fw-bold text-gray-900 mb-4">Новые запросы {{ $year }}</h3>
        <div class="row g-4 mb-8">
            @foreach($tiles as [$key, $label, $icon, $color])
                <div class="col-6 col-xl-3">
                    {{-- плитки групп фильтруют диаграмму Ганта (см. _movement_gantt) --}}
                    <div @class(['card h-100', 'mg-tile' => $key !== 'total'])
                         @if($key !== 'total') data-gantt-group="{{ $key }}" role="button" title="Показать на диаграмме только «{{ $label }}»" @endif>
                        <div class="card-body d-flex align-items-center gap-4 py-5">
                            <i class="fa-light {{ $icon }} fs-2x text-{{ $color }}"></i>
                            <div>
                                <div class="text-gray-600 fs-6">{{ $label }}</div>
                                <div class="fw-bolder fs-2x text-gray-900 lh-1">
                                    {{ $cohort[$key] }}
                                    @if($key !== 'total')
                                        <span class="fs-5 fw-semibold text-{{ $color }}">{{ $cohort[$key . '_pct'] }}%</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Движения за год --}}
        <h3 class="fw-bold text-gray-900 mb-1">Движения за {{ $year }}</h3>
        <div class="text-muted fs-7 mb-4">
            Сделки, которые в {{ $year }} году перешли в группу (по первому переходу), — включая запросы прошлых лет.
        </div>
        <div class="row g-4 mb-8">
            @foreach($moves as [$key, $label, $icon, $color])
                <div class="col-12 col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center gap-4 py-5">
                            <i class="fa-light {{ $icon }} fs-2x text-{{ $color }}"></i>
                            <div>
                                <div class="text-gray-600 fs-6">{{ $label }}</div>
                                <div class="fw-bolder fs-2x text-gray-900 lh-1">{{ $movements[$key]['all'] }}</div>
                                <div class="text-gray-600 fs-7 mt-1">
                                    из запросов {{ $year }}: {{ $movements[$key]['this'] }},
                                    прошлых лет: {{ $movements[$key]['past'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- По менеджерам — только администратору --}}
        @if($by_manager)
            <div class="card">
                <div class="card-header min-h-auto pt-5 pb-0 border-bottom">
                    <h4 class="mb-5">
                        <span class="card-label fw-bold text-gray-900">По менеджерам</span>
                    </h4>
                </div>
                <div class="card-body p-0 overflow-auto">
                    <table class="table table-row-bordered align-middle mb-0 text-nowrap">
                        <thead>
                            <tr>
                                <th rowspan="2">Менеджер</th>
                                <th colspan="4" class="text-center">Новые запросы {{ $year }}</th>
                                <th colspan="2" class="text-center">Движения за {{ $year }}</th>
                            </tr>
                            <tr>
                                <th class="text-end">Всего</th>
                                <th class="text-end">В работе</th>
                                <th class="text-end">Отвалились</th>
                                <th class="text-end">Закончились</th>
                                <th class="text-end">Отвалились <span class="text-muted fw-normal">(прошлых лет)</span></th>
                                <th class="text-end">Закончились <span class="text-muted fw-normal">(прошлых лет)</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($managers as $name => $row)
                                @include('bitrix.dashboard._movement_row', ['name' => $name, 'c' => $row['cohort'], 'm' => $row['movements'], 'bold' => false])
                            @endforeach
                            @include('bitrix.dashboard._movement_row', ['name' => 'Итого', 'c' => $cohort, 'm' => $movements, 'bold' => true])
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        </div>{{-- /mv-pane-table --}}

        {{-- Диаграмма Ганта по сделкам года --}}
        <div id="mv-pane-gantt" @class(['d-none' => $view !== 'gantt'])>
            @include('bitrix.dashboard._movement_gantt')
        </div>

    </div>
@endsection

{{-- текущий год на срезе «на конец года» — бейдж у заголовка --}}
@section('breadcrumb_add')
    @if($is_current_year && $at === 'year_end')
        <span class="badge badge-light-warning fs-8 fw-semibold align-self-start mt-1" title="Год ещё не закончился — снимок на сегодня">
            снимок на сегодня
        </span>
    @endif
@endsection

{{-- Год и момент среза — в шапке страницы; ?view сохраняется (JS обновляет при смене вкладки) --}}
@section('breadcrumb_right')
    <div class="d-flex align-items-center flex-wrap gap-2">
        <form method="get" action="{{ route('bitrix.movement') }}" class="d-flex align-items-center gap-2 m-0">
            <input type="hidden" name="at" value="{{ $at }}">
            <input type="hidden" name="view" value="{{ $view }}" data-mv-view-input>
            <label for="movement-year" class="visually-hidden">Год</label>
            <select id="movement-year" name="year" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                @foreach(array_reverse($years) as $y)
                    <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                @endforeach
            </select>
        </form>

        <div class="btn-group" role="group" aria-label="Момент среза">
            <a href="{{ route('bitrix.movement', ['year' => $year, 'at' => 'now', 'view' => $view]) }}" data-mv-keep-view
               class="btn btn-sm {{ $at === 'now' ? 'btn-primary' : 'btn-light' }}">
                <i class="fa-light fa-clock"></i> Сейчас
            </a>
            <a href="{{ route('bitrix.movement', ['year' => $year, 'at' => 'year_end', 'view' => $view]) }}" data-mv-keep-view
               class="btn btn-sm {{ $at === 'year_end' ? 'btn-primary' : 'btn-light' }}">
                <i class="fa-light fa-calendar-check"></i> На конец года
            </a>
        </div>

        <i class="fa-light fa-circle-info fs-4 text-gray-500" role="img" aria-label="Пояснение к срезу"
           data-bs-toggle="tooltip" data-bs-placement="bottom"
           title="Сейчас — текущий статус сделок, цифры прошлых лет ещё меняются; на конец года — снимок на 31 декабря.{{ $is_current_year ? ' Год ещё не закончился — для него «на конец года» = снимок на сегодня.' : '' }}"></i>
    </div>
@endsection
