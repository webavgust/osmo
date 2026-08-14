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
        .score-bar { height: 6px; border-radius: 3px; background: #f1f1f4; overflow: hidden; }
        .score-bar > span { display: block; height: 100%; }
        .num-link { border-bottom: 1px dashed currentColor; cursor: pointer; text-decoration: none; }
        .num-link:hover { border-bottom-style: solid; }
    </style>
@endsection

@section('content')
    <div class="container-fluid">

        {{-- Отбор --}}
        <div class="card mb-4">
            <div class="card-body py-4">
                <form method="get" action="{{ route('analytics.partners') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-auto">
                            <label class="form-label fs-8 text-muted mb-1">Год</label>
                            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">все годы</option>
                                @foreach($years as $year)
                                    <option value="{{ $year }}" @selected($params['year'] == $year)>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-2">
                            <label class="form-label fs-8 text-muted mb-1">Грейд</label>
                            <select name="grade" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">любой</option>
                                @foreach($grades as $grade)
                                    <option value="{{ $grade->value }}" @selected($params['grade'] === $grade->value)>
                                        {{ $grade->data()['label'] ?? $grade->value }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-3">
                            <label class="form-label fs-8 text-muted mb-1">Поиск</label>
                            <input type="text" name="q" value="{{ $params['q'] }}" class="form-control form-control-sm"
                                   placeholder="название партнёра">
                        </div>

                        <div class="col-auto ms-auto">
                            <button type="submit" class="btn btn-sm btn-primary">Показать</button>
                            <a href="{{ route('analytics.partners', ['year' => '']) }}" class="btn btn-sm btn-light ms-1">Сбросить</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Показатели --}}
        <div class="row g-4 mb-4">
            @php
                $cards = [
                    ['label' => 'Партнёров', 'value' => $totals['count'], 'color' => 'dark'],
                    ['label' => 'Подписано спецификаций', 'value' => $totals['specs_signed'], 'color' => 'success'],
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
                            <div class="fs-8 text-muted text-uppercase">{{ $card['label'] }}</div>
                            <div class="fs-2 fw-bold text-{{ $card['color'] }} mt-1">{{ $card['value'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Легенда — под кнопкой: нужна один раз, потом только занимает место --}}
        <div class="mb-4">
            <button class="btn btn-sm btn-light-primary" type="button" data-bs-toggle="collapse"
                    data-bs-target="#scoring_legend" aria-expanded="false">
                <i class="fas fa-circle-question me-1"></i>Как считается балл и что значат буквы
            </button>
        </div>

        <div class="collapse" id="scoring_legend">
            <div class="card mb-4">
            <div class="card-body py-4">
                <div class="row g-4">
                    <div class="col-12 col-xl-5">
                        <div class="fw-bold mb-2">Балл: 0–100, у лучшего партнёра выборки всегда 100</div>
                        <div class="fs-7 text-muted">
                            Складывается из четырёх частей, каждая считается относительно лучшего
                            результата в выборке, а итог нормируется на лидера.
                        </div>

                        <div class="mt-3 d-flex flex-column gap-2">
                            @php
                                $weights = [
                                    ['label' => 'Сумма подписанных спецификаций', 'weight' => \App\Modules\Pub\Analytics\Services\PartnerScoringService::WEIGHT_SPECS, 'color' => 'primary', 'hint' => 'одна спецификация на миллион весит больше десяти по пятьдесят тысяч'],
                                    ['label' => 'Конверсия решённых КП', 'weight' => \App\Modules\Pub\Analytics\Services\PartnerScoringService::WEIGHT_CONVERSION, 'color' => 'success', 'hint' => 'выиграно к сумме выигранных и проигранных; КП в работе не считаются'],
                                    ['label' => 'Доля просроченных платежей', 'weight' => \App\Modules\Pub\Analytics\Services\PartnerScoringService::WEIGHT_OVERDUE, 'color' => 'warning', 'hint' => 'чем меньше просрочки, тем выше балл; без платежей — нейтрально'],
                                    ['label' => 'Количество КП за год', 'weight' => \App\Modules\Pub\Analytics\Services\PartnerScoringService::WEIGHT_PROPOSALS, 'color' => 'info', 'hint' => 'активность сейчас, а не накопленная история'],
                                ];
                            @endphp

                            @foreach($weights as $weight)
                                <div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fs-7">{{ $weight['label'] }}</span>
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

                    <div class="col-12 col-xl-7">
                        <div class="fw-bold mb-2">Буква — это балл словами</div>
                        <table class="table table-row-bordered align-middle m-0">
                            @foreach($legend as $item)
                                <tr>
                                    <td width="60" class="ps-0">
                                        <span class="badge badge-{{ $item['color'] }} fs-4 fw-bold" style="width: 32px">{{ $item['letter'] }}</span>
                                    </td>
                                    <td width="90" class="fw-bold text-nowrap">{{ $item['range'] }}</td>
                                    <td width="150" class="text-{{ $item['color'] }} fw-bold">{{ $item['label'] }}</td>
                                    <td class="fs-7 text-muted">{{ $item['hint'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
            </div>
        </div>

        {{-- Таблица --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="m-0">Партнёры</h3>
                <span class="text-muted fs-7">
                    Сортировка по баллу. Цифры кликабельны, при наведении на балл — место в рейтинге по годам.
                </span>
            </div>

            <div style="overflow: visible">
                <table class="table table-row-bordered align-middle m-0">
                    <thead>
                        <tr class="fw-bold fs-7 text-muted text-uppercase">
                            <th class="ps-4" width="110">Балл</th>
                            <th>Партнёр</th>
                            <th class="text-center">КП</th>
                            <th class="text-center">Конверсия</th>
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
                                        <span class="badge badge-{{ $row['rank']['color'] }} fs-4 fw-bold" style="width: 32px">
                                            {{ $row['rank']['letter'] }}
                                        </span>
                                        <div>
                                            <div class="fw-bold fs-4">{{ $row['score'] }}</div>
                                            <div class="fs-8 text-muted text-nowrap">{{ $row['rank']['label'] }}</div>
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
                                                    <circle cx="{{ $c['x'] }}" cy="{{ $c['y'] }}" r="{{ $c['point']['current'] ? 4 : 3 }}"
                                                            fill="{{ $c['point']['current'] ? '#f1416c' : '#009ef7' }}"/>
                                                    <text x="{{ $c['x'] }}" y="{{ max(10, $c['y'] - 7) }}" font-size="9" fill="#3f4254"
                                                          text-anchor="middle">{{ $c['point']['place'] }}</text>
                                                    <text x="{{ $c['x'] }}" y="{{ $h + 12 }}" font-size="9" fill="#a1a5b7"
                                                          text-anchor="middle">{{ $c['point']['year'] }}</text>
                                                @endforeach
                                            </svg>
                                            <div class="fs-8 text-muted">
                                                Выше — лучше. Всего партнёров в рейтинге: {{ $max_place }}.
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

                                        <div class="fw-bold fs-7 mt-3 mb-1">Из чего собран балл</div>
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
                                        <div class="fs-8 text-muted">
                                            Сумма частей — {{ round($row['score_raw'], 1) }}; после нормировки на лидера
                                            выборки — {{ $row['score'] }}.
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <a href="{{ route('partner.detail', $partner) }}" class="fw-bold"
                                       style="color: {{ $row['grade']['color']['medal'] ?? '#7e8299' }}">
                                        <x-ui.icon.solid icon="fa-medal" class="me-1"/>{{ $partner->name }}
                                    </a>
                                    <div class="fs-8 text-muted">{{ $row['grade']['label'] ?? '—' }}</div>
                                </td>

                                {{-- КП --}}
                                <td class="text-center text-nowrap">
                                    <a href="javascript:void(0)" class="num-link fw-bold fs-4 text-dark"
                                       onclick="javascript:box({href:'{{ $box('proposals') }}'})">{{ $row['proposals'] }}</a>
                                    <div class="fs-8 text-muted">
                                        <span class="text-success">{{ $row['won'] }}</span> /
                                        <span class="text-danger">{{ $row['lost'] }}</span> /
                                        {{ $row['in_work'] }}
                                    </div>
                                </td>

                                <td class="text-center">
                                    @if($row['conversion'] !== null)
                                        <span class="fs-4 fw-bold">{{ round($row['conversion']) }}%</span>
                                        <div class="fs-8 text-muted">из решённых</div>
                                    @else
                                        <span class="text-muted">—</span>
                                        <div class="fs-8 text-muted">нет решённых</div>
                                    @endif
                                </td>

                                {{-- Объём выигранных КП --}}
                                <td class="text-end text-nowrap">
                                    <a href="javascript:void(0)" class="num-link fw-bold text-dark"
                                       onclick="javascript:box({href:'{{ $box('volume') }}'})">
                                        {{ tools()->cost_normalize(round($row['amount_won'])) }} ₽
                                    </a>
                                    <div class="fs-8 text-muted">выигранные КП</div>
                                </td>

                                {{-- Договор: спецификации и деньги, а не штуки договоров --}}
                                <td class="text-center text-nowrap">
                                    <a href="javascript:void(0)" class="num-link fw-bold text-dark"
                                       onclick="javascript:box({href:'{{ $box('contracts') }}'})">
                                        {{ tools()->cost_normalize(round($row['specs_sum'])) }} ₽
                                    </a>
                                    <div class="fs-8 text-muted">
                                        подписано <span class="text-success fw-bold">{{ $row['specs_signed'] }}</span>
                                        из {{ $row['specs'] }} спец.
                                    </div>
                                    <div class="fs-8 text-muted">
                                        договоров {{ $row['contracts'] }}, с КП {{ $row['links'] }}
                                    </div>
                                </td>

                                {{-- Платежи --}}
                                <td class="text-center text-nowrap">
                                    <a href="javascript:void(0)" class="num-link text-dark"
                                       onclick="javascript:box({href:'{{ $box('payments') }}'})">
                                        <span class="text-success fw-bold">{{ $row['payments_paid'] }}</span>
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
                                    <a href="javascript:void(0)" class="btn btn-sm btn-light-primary"
                                       onclick="javascript:box({href:'{{ $box('stats') }}'})">
                                        <i class="fas fa-chart-column me-1"></i>По годам
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-10">
                                    По этому отбору партнёров нет
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer py-3 fs-8 text-muted">
                Сумма подписанных спецификаций (вес {{ \App\Modules\Pub\Analytics\Services\PartnerScoringService::WEIGHT_SPECS }})
                + конверсия решённых КП ({{ \App\Modules\Pub\Analytics\Services\PartnerScoringService::WEIGHT_CONVERSION }})
                + платежи без просрочки ({{ \App\Modules\Pub\Analytics\Services\PartnerScoringService::WEIGHT_OVERDUE }})
                + количество КП за год ({{ \App\Modules\Pub\Analytics\Services\PartnerScoringService::WEIGHT_PROPOSALS }}),
                нормировано так, что у лидера выборки 100. «КП → договор» — средний срок от даты
                последнего выставленного КП до даты спецификации, к которой его прикрепили.
                Суммы приведены к рублям по текущему курсу.
            </div>
        </div>
    </div>
@endsection
