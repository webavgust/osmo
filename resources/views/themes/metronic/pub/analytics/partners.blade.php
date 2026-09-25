@extends('layouts.layout')

@section('styles')
    @parent
    <style>
        /* Подсказка к баллу: место в рейтинге по годам и состав балла */
        .score-cell { position: relative; }
        .score-pop {
            display: none;
            position: absolute;
            z-index: 1060;
            left: 0;
            top: calc(100% + 4px);
            width: 320px;
            background: #fff;
            border: 1px solid #e4e6ef;
            border-radius: 6px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
            padding: 12px;
        }
        .score-cell:hover .score-pop { display: block; }
        /* таблица в прокручиваемом контейнере: у последних строк подсказка открывается вверх,
           иначе её обрежет нижний край контейнера */
        tbody tr:nth-last-child(-n+3) .score-pop { top: auto; bottom: calc(100% + 4px); }
        .score-bar { height: 6px; border-radius: 3px; background: #f1f1f4; overflow: hidden; }
        .score-bar > span { display: block; height: 100%; }
        .num-link { border-bottom: 1px dashed currentColor; cursor: pointer; text-decoration: none; }
        .num-link:hover { border-bottom-style: solid; }
    </style>
@endsection

@php
    // «Фильтр (n)»: год не считаем — он вынесен отдельным селектором и стоит
    // в тулбаре всегда, это главный отбор страницы
    $rules_count = collect(['grade', 'q'])
        ->filter(fn($key) => !empty($params[$key]))
        ->count();

    // год уезжает в адрес как есть: пустая строка — «все годы»
    $year = $params['year'] ?? '';
@endphp

{{-- Тулбар страницы: год + «Фильтр», как на дашборде Битрикса --}}
@section('breadcrumb_right')
    {{-- как считается балл — по иконке, в модалке; раньше легенда раскрывалась над таблицей --}}
    <button type="button" class="btn btn-icon btn-active-light-primary"
            data-bs-toggle="modal" data-bs-target="#scoring_legend_modal"
            title="Как считается балл и что значат буквы">
        <i class="fas fa-circle-question text-primary fs-1"></i>
    </button>


    {{-- Год всегда на виду; остальной отбор уезжает вместе с ним скрытыми полями --}}
    <form method="get" action="{{ route('analytics.partners') }}" class="d-flex align-items-center">
        <input type="hidden" name="grade" value="{{ $params['grade'] }}"/>
        <input type="hidden" name="q" value="{{ $params['q'] }}"/>

        <select name="year" class="form-select w-auto fw-bold" onchange="this.form.submit()">
            <option value="">все годы</option>
            @foreach($years as $item)
                <option value="{{ $item }}" @selected($params['year'] == $item)>{{ $item }}</option>
            @endforeach
        </select>
    </form>

    <button type="button" data-bs-toggle="modal" data-bs-target="#partners_filter_modal"
            class="btn btn-light-info fw-bold d-flex align-items-center">
        <i class="fa-light fa-filter"></i>
        Фильтр
        @if($rules_count)
            <span class="count filter-count">{{ $rules_count }}</span>
        @endif
    </button>

    @if($rules_count)
        {{-- год остаётся: убираем только то, что стоит в модалке --}}
        <a href="{{ route('analytics.partners', ['year' => $year]) }}"
           class="me-2 text-dark-500 text-hover-dark">
            <i class="fa-light fa-xmark fs-5 me-2" aria-hidden="true"></i> Убрать
        </a>
    @endif

@endsection

@section('content')
    <div class="container-fluid">

        {{-- Отбор: живёт в модалке, в адресе остаётся обычной GET-строкой,
             поэтому ссылку с отбором можно передать --}}
        <div id="partners_filter_modal" class="modal fade" tabindex="-1" aria-hidden="true">
            <form method="get" action="{{ route('analytics.partners') }}">
                <input type="hidden" name="year" value="{{ $year }}"/>

                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title fw-bold">Фильтр</h3>
                            <button type="button" class="btn btn-icon btn-sm btn-active-light-primary"
                                    data-bs-dismiss="modal" aria-label="Закрыть">
                                <i class="fa-light fa-xmark fs-2"></i>
                            </button>
                        </div>

                        <div class="modal-body py-8">
                            <div class="row mb-5">
                                <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Грейд</label>
                                <div class="col-sm-9">
                                    <select name="grade" class="form-select partners_select" data-placeholder="любой">
                                        <option value="">любой</option>
                                        @foreach($grades as $grade)
                                            <option value="{{ $grade->value }}" @selected($params['grade'] === $grade->value)>
                                                {{ $grade->data()['label'] ?? $grade->value }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Поиск</label>
                                <div class="col-sm-9">
                                    <input type="text" name="q" value="{{ $params['q'] }}" class="form-control"
                                           placeholder="название партнёра"/>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отменить</button>
                            <button type="submit" class="btn btn-primary">Применить</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>


        {{-- Показатели --}}
        <div class="row g-4 mb-4">
            @php
                $cards = [
                    ['label' => 'Партнёров', 'value' => $totals['count'], 'color' => 'dark'],
                    ['label' => 'Подписано специфик.', 'value' => $totals['specs_signed'], 'color' => 'success'],
                    ['label' => 'Сумма подписанного', 'value' => tools()->cost_normalize(round($totals['specs_sum'])) . ' ₽', 'color' => 'primary'],
                    ['label' => 'КП', 'value' => $totals['proposals'], 'color' => 'dark'],
                    ['label' => 'Выиграно КП', 'value' => $totals['won'], 'color' => 'success'],
                    ['label' => 'Просроченных платежей', 'value' => $totals['overdue'], 'color' => 'danger'],
                ];
            @endphp

            @foreach($cards as $card)
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body p-4">
                            <div class="fs-6 text-muted text-uppercase">{{ $card['label'] }}</div>
                            <div class="fs-2x fw-bold text-{{ $card['color'] }} mt-1">{{ $card['value'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Легенда — в модалке по иконке «?» в тулбаре страницы --}}
        <div id="scoring_legend_modal" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title fw-bold">Как считается балл и что значат буквы</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary"
                                data-bs-dismiss="modal" aria-label="Закрыть">
                            <i class="fa-light fa-xmark fs-2"></i>
                        </button>
                    </div>

                    <div class="modal-body py-6">
                <div class="row g-4">
                    <div class="col-12 col-xl-5 fs-5">
                        <div class="fw-bold mb-2">Балл: 0–100, у лучшего партнёра выборки всегда 100</div>
                        <div class="fs-7">
                            Складывается из пяти частей, каждая считается относительно лучшего
                            результата в выборке, а итог нормируется на лидера.
                        </div>

                        <div class="mt-3 d-flex flex-column gap-2">
                            @php
                                // веса настраиваются в админ-панели (consts.scoring_weight_*)
                                $score_weights = \App\Modules\Pub\Analytics\Services\PartnerScoringService::weights();
                                $weights = [
                                    ['label' => 'Сумма подписанных спецификаций', 'weight' => $score_weights['specs'], 'color' => 'primary', 'hint' => 'одна спецификация на миллион весит больше десяти по пятьдесят тысяч'],
                                    ['label' => 'Количество проектов', 'weight' => $score_weights['projects'], 'color' => 'dark', 'hint' => 'проекты партнёра за год по дате начала; архивные тоже считаются'],
                                    ['label' => 'Конверсия решённых КП', 'weight' => $score_weights['conversion'], 'color' => 'success', 'hint' => 'выиграно к сумме выигранных и проигранных; КП в работе не считаются'],
                                    ['label' => 'Кол-во сделок Битрикс24', 'weight' => $score_weights['deals'], 'color' => 'info', 'hint' => 'все сделки партнёра за год, любых стадий; считаются по сопоставлению партнёра с Битрикс24'],
                                    ['label' => 'Доля просроченных платежей', 'weight' => $score_weights['overdue'], 'color' => 'warning', 'hint' => 'чем меньше просрочки, тем выше балл; без платежей — нейтрально'],
                                ];
                            @endphp

                            @foreach($weights as $weight)
                                <div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fs-6 fw-bold">{{ $weight['label'] }}</span>
                                        <span class="fw-bold text-{{ $weight['color'] }}">{{ $weight['weight'] }}</span>
                                    </div>
                                    <div class="score-bar mt-1">
                                        <span class="bg-{{ $weight['color'] }}" style="width: {{ $weight['weight'] }}%"></span>
                                    </div>
                                    <div class="fs-8 text-muted mt-1">{{ $weight['hint'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-12 col-xl-7 fs-5 ps-9">
                        <div class="fw-bold mb-2">Буква — это балл словами</div>
                        <table class="table table-row-bordered align-middle m-0">
                            @foreach($legend as $item)
                                <tr>
                                    <td width="60" class="ps-0">
                                        <span class="badge badge-{{ $item['color'] }} fs-4 fw-bold justify-content-center" style="width: 32px">{{ $item['letter'] }}</span>
                                    </td>
                                    <td width="90" class="fw-bold text-nowrap">{{ $item['range'] }}</td>
                                    <td width="200" class="text-{{ $item['color'] }} fw-bold">{{ $item['label'] }}</td>
                                    <td class="fs-7">{{ $item['hint'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Таблица --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="m-0">Партнёры</h3>
            </div>

            {{-- таблица широкая: прокручивается внутри карточки, страница за контейнер не уходит --}}
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle m-0">
                    <thead>
                        <tr class="fw-bold fs-7 text-muted text-uppercase">
                            <th class="ps-4" width="110">Балл</th>
                            <th>Партнёр</th>
                            <th class="text-center">КП</th>
                            <th class="text-center">Конверсия</th>
                            <th class="text-center">Сделки</th>
                            <th class="text-center">Проекты</th>
                            <th class="text-end">Объём</th>
                            <th class="text-center">Договор</th>
                            <th class="text-center">Платежи</th>
                            <th class="text-center">КП → договор</th>
                            <th class="text-end pe-4" width="120">Статистика</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $partner = $row['partner'];
                                $box = fn($tab) => route('analytics.box_partner', array_filter([
                                    'partner' => $partner->id, 'tab' => $tab, 'year' => $params['year'],
                                ]));
                                $points = $history->get((int) $partner->id, []);
                            @endphp
                            <tr>
                                {{-- Балл: буква, число и подсказка с графиком места --}}
                                <td class="ps-4 score-cell">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge badge-{{ $row['rank']['color'] }} fs-1 justify-content-center fw-bold" style="width: 32px">
                                            {{ $row['rank']['letter'] }}
                                        </span>
                                        <div class="ms-1">
                                            <div class="fw-bold fs-3">{{ $row['score'] }}</div>
                                            <div class="fs-9 text-muted text-nowrap">{{ $row['rank']['label'] }}</div>
                                        </div>
                                    </div>

                                    <div class="score-pop">
                                        <div class="fw-bold fs-7 mb-1">Место в рейтинге по годам</div>

                                        @php
                                            $known = collect($points)->filter(fn($point) => !empty($point['place']))->values();
                                            $w = 292; $h = 78; $pad = 16;
                                            $max_place = max(2, (int) collect($points)->max('total'));
                                        @endphp

                                        @if($known->count() > 1)
                                            @php
                                                $step = $known->count() > 1 ? ($w - 2 * $pad) / ($known->count() - 1) : 0;
                                                $coords = $known->map(function ($point, $i) use ($pad, $step, $h, $max_place) {
                                                    $x = $pad + $i * $step;
                                                    $y = $pad + ($point['place'] - 1) / max(1, $max_place - 1) * ($h - 2 * $pad);
                                                    return ['x' => round($x, 1), 'y' => round($y, 1), 'point' => $point];
                                                });
                                            @endphp

                                            <svg width="{{ $w }}" height="{{ $h + 18 }}" style="display: block">
                                                <polyline fill="none" stroke="#009ef7" stroke-width="2"
                                                          points="{{ $coords->map(fn($c) => $c['x'] . ',' . $c['y'])->implode(' ') }}"/>
                                                @foreach($coords as $c)
                                                    <circle cx="{{ $c['x'] }}" cy="{{ $c['y'] }}" r="{{ $c['point']['current'] ? 5 : 4 }}"
                                                            fill="{{ $c['point']['current'] ? '#f1416c' : '#009ef7' }}"/>
                                                    <text x="{{ $c['x'] }}" y="{{ max(10, $c['y'] - 10) }}" font-size="12" fill="#3f4254"
                                                          text-anchor="middle">{{ $c['point']['place'] }}</text>
                                                    <text x="{{ $c['x'] }}" y="{{ $h + 12 }}" font-size="11" fill="#a1a5b7"
                                                          text-anchor="middle">{{ $c['point']['year'] }}</text>
                                                @endforeach
                                            </svg>
                                            <div class="fs-8 text-muted mt-2">
                                                Выше — лучше. Всего партнёров в рейтинге: <span class="fw-bold">{{ $max_place }}</span>.
                                                Текущий год ({{ now()->year }}) посчитан на сегодня и потому неполный.
                                            </div>
                                        @else
                                            <div class="fs-8 text-muted">
                                                Данных меньше чем за два года — график строить не на чем.
                                                @if($known->count() === 1)
                                                    В {{ $known->first()['year'] }} году — {{ $known->first()['place'] }} место
                                                    из {{ $known->first()['total'] }}.
                                                @endif
                                            </div>
                                        @endif

                                        <div class="fw-bold fs-7 mt-3 mb-1">Из чего собран балл:</div>
                                        @foreach($row['parts'] as $part)
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between fs-8">
                                                    <span>{{ $part['label'] }}</span>
                                                    <span class="fw-bold text-nowrap ms-2">
                                                        {{ round($part['points'], 1) }} из {{ $part['weight'] }}
                                                    </span>
                                                </div>
                                                <div class="score-bar mt-1">
                                                    <span class="bg-primary" style="width: {{ round(min(100, $part['value'])) }}%"></span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                <td>
                                    {{-- цвет статуса — только у медали: серебряное имя на белом не читалось --}}
                                    <a href="{{ route('partner.detail', $partner) }}" class="fw-bold fs-3 text-gray-900 text-hover-primary">
                                        <span style="color: {{ $row['grade']['color']['medal'] ?? '#7e8299' }}"><x-ui.icon.solid icon="fa-medal" class="me-1"/></span>{{ $partner->name }}
                                    </a>
                                    <div class="fs-9 text-muted">{{ $row['grade']['label'] ?? '—' }}</div>
                                </td>

                                {{-- КП --}}
                                <td class="text-center text-nowrap">
                                    <a href="javascript:void(0)" class="num-link fw-bold fs-3 text-dark"
                                       onclick="javascript:box({href:'{{ $box('proposals') }}'})">{{ $row['proposals'] }}</a>
                                    <div class="fs-7 text-muted">
                                        <span class="text-success">{{ $row['won'] }}</span> /
                                        <span class="text-danger">{{ $row['lost'] }}</span> /
                                        {{ $row['in_work'] }}
                                    </div>
                                </td>

                                <td class="text-center">
                                    @if($row['conversion'] !== null)
                                        <span class="fs-4 fw-bold">{{ round($row['conversion']) }}%</span>
                                        <div class="fs-7 text-muted">из решённых</div>
                                    @else
                                        <span class="text-muted">—</span>
                                        <div class="fs-7 text-muted">&nbsp;</div>
                                    @endif
                                </td>

                                {{-- Сделки Битрикса: считаются по сопоставлению партнёра с Битрикс24 --}}
                                <td class="text-center text-nowrap">
                                    <a href="javascript:void(0)" class="num-link fw-bold fs-3 text-dark"
                                       onclick="javascript:box({href:'{{ $box('deals') }}'})">{{ $row['deals'] }}</a>
                                    <div class="fs-8 text-muted">
                                        @if(!$row['crm_linked'])
                                            нет сопоставления
                                        @elseif($row['deals_sum'] > 0)
                                            {{ tools()->cost_normalize(round($row['deals_sum'])) }} ₽
                                        @else
                                            &nbsp;
                                        @endif
                                    </div>
                                </td>

                                {{-- Проекты по сделкам --}}
                                <td class="text-center text-nowrap">
                                    <a href="javascript:void(0)" class="num-link fw-bold fs-3 text-dark"
                                       onclick="javascript:box({href:'{{ $box('projects') }}'})">{{ $row['projects'] }}</a>
                                    <div class="fs-8 text-muted">
                                        @if($row['projects_archived'] > 0)
                                            в архиве {{ $row['projects_archived'] }}
                                        @elseif($row['projects_pilot'] > 0)
                                            пилотов {{ $row['projects_pilot'] }}
                                        @else
                                            &nbsp;
                                        @endif
                                    </div>
                                </td>

                                {{-- Объём выигранных КП --}}
                                <td class="text-end text-nowrap">
                                    <a href="javascript:void(0)" class="num-link fw-bold text-dark fs-3"
                                       onclick="javascript:box({href:'{{ $box('volume') }}'})">
                                        {{ tools()->cost_normalize(round($row['amount_won'])) }} ₽
                                    </a>
                                    <div class="fs-8 text-muted">выигранные КП</div>
                                </td>

                                {{-- Договор: спецификации и деньги, а не штуки договоров --}}
                                <td class="text-center text-nowrap">
                                    <a href="javascript:void(0)" class="num-link fw-bold text-dark fs-3"
                                       onclick="javascript:box({href:'{{ $box('contracts') }}'})">
                                        {{ tools()->cost_normalize(round($row['specs_sum'])) }} ₽
                                    </a>
                                    @if($row['contracts'] > 1)
                                        <sup>{{ $row['contracts'] }} шт.</sup>
                                    @endif
                                    <div class="fs-8 text-muted">
                                        @if($row['specs'] > 0)
                                            <span class="text-success fw-bold">{{ $row['specs_signed'] }}</span>
                                            из {{ $row['specs'] }} спец.
                                        @else
                                            &nbsp;
                                        @endif
                                    </div>
                                </td>

                                {{-- Платежи --}}
                                <td class="text-center text-nowrap">
                                    <a href="javascript:void(0)" class="num-link text-success fs-3"
                                       onclick="javascript:box({href:'{{ $box('payments') }}'})">
                                        <span class="fw-bold">{{ $row['payments_paid'] }}</span>
                                        @if($row['payments_overdue'])
                                            / <span class="text-danger fw-bold">{{ $row['payments_overdue'] }}</span>
                                        @endif
                                    </a>
                                    <div class="fs-8 text-muted">
                                        @if($row['overdue_share'] !== null)
                                            просрочка {{ round($row['overdue_share']) }}%
                                        @else
                                            платежей нет
                                        @endif
                                    </div>
                                </td>

                                {{-- Срок от последнего выставленного КП до даты спецификации --}}
                                <td class="text-center text-nowrap">
                                    @if($row['days_to_spec'] !== null)
                                        <span @class(['fw-bold', 'text-warning' => $row['days_to_spec'] < 0])>
                                            {{ \App\Modules\Pub\Analytics\Services\PartnerScoringService::humanPeriod($row['days_to_spec']) }}
                                        </span>
                                        <div class="fs-8 text-muted">
                                            @if($row['days_to_spec'] < 0)
                                                КП позже спецификации
                                            @else
                                                в среднем по {{ $row['days_known'] }} КП
                                            @endif
                                        </div>
                                    @elseif($row['links'] > 0)
                                        <span class="text-muted">—</span>
                                        <div class="fs-8 text-muted">нет даты КП или спецификации</div>
                                    @else
                                        <span class="text-muted">—</span>
                                        <div class="fs-8 text-muted">нет прикреплённых КП</div>
                                    @endif
                                </td>

                                <td class="text-end pe-4">
                                    <a href="javascript:void(0)" class="btn btn-sm btn-light-primary text-nowrap"
                                       onclick="javascript:box({href:'{{ $box('stats') }}'})">
                                        <i class="fas fa-chart-column me-1"></i>По годам
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-10">
                                    По этому отбору партнёров нет
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        // грейдов немного, но список в модалке держим таким же, как на «Анализе скидок»
        $(document).ready(function () {
            // модалки живут в контенте — уводим в body, чтобы их не обрезал контекст наложения
            $('#scoring_legend_modal').appendTo('body');

            var $modal = $('#partners_filter_modal');

            $modal.find('select.partners_select').each(function () {
                $(this).select2({
                    width: '100%',
                    dropdownParent: $modal,
                    placeholder: $(this).data('placeholder'),
                    allowClear: true
                });
            });
        });
    </script>
@endsection
