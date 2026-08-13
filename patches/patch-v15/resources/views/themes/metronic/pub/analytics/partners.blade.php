@extends('layouts.layout')

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
                    ['label' => 'КП', 'value' => $totals['proposals'], 'color' => 'dark'],
                    ['label' => 'Выиграно', 'value' => $totals['won'], 'color' => 'success'],
                    ['label' => 'Объём выигранных', 'value' => tools()->cost_normalize(round($totals['amount_won'])) . ' ₽', 'color' => 'primary'],
                    ['label' => 'Просроченных платежей', 'value' => $totals['overdue'], 'color' => 'danger'],
                    ['label' => 'Средний балл', 'value' => $totals['score'], 'color' => 'info'],
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

        {{-- Таблица --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="m-0">Партнёры</h3>
                <span class="text-muted fs-7">Сортировка по баллу</span>
            </div>

            <div class="table-responsive">
                <table class="table table-row-bordered align-middle m-0">
                    <thead>
                        <tr class="fw-bold fs-7 text-muted text-uppercase">
                            <th class="ps-4" width="90">Балл</th>
                            <th>Партнёр</th>
                            <th class="text-center">КП</th>
                            <th class="text-center">Конверсия</th>
                            <th class="text-end">Объём</th>
                            <th class="text-center">Договоры</th>
                            <th class="text-center">Платежи</th>
                            <th class="text-center">КП → договор</th>
                            <th class="text-center pe-4">Скидка</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge badge-{{ $row['rank']['color'] }} fs-4 fw-bold" style="width: 32px">
                                            {{ $row['rank']['letter'] }}
                                        </span>
                                        <div>
                                            <div class="fw-bold">{{ $row['score'] }}</div>
                                            <div class="fs-8 text-muted text-nowrap">{{ $row['rank']['label'] }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <a href="{{ route('partner.detail', $row['partner']) }}" class="fw-bold"
                                       style="color: {{ $row['grade']['color']['medal'] ?? '#7e8299' }}">
                                        <x-ui.icon.solid icon="fa-medal" class="me-1"/>{{ $row['partner']->name }}
                                    </a>
                                    <div class="fs-8 text-muted">{{ $row['grade']['label'] ?? '—' }}</div>
                                </td>

                                <td class="text-center text-nowrap">
                                    <span class="fw-bold">{{ $row['proposals'] }}</span>
                                    <div class="fs-8 text-muted">
                                        <span class="text-success">{{ $row['won'] }}</span> /
                                        <span class="text-danger">{{ $row['lost'] }}</span> /
                                        {{ $row['in_work'] }}
                                    </div>
                                </td>

                                <td class="text-center">
                                    @if($row['won'] + $row['lost'] > 0)
                                        <span class="fs-4 fw-bold">{{ round($row['conversion']) }}%</span>
                                        <div class="fs-8 text-muted">из решённых</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-end text-nowrap">
                                    {{ tools()->cost_normalize(round($row['amount_won'])) }} ₽
                                    <div class="fs-8 text-muted">выигранные КП</div>
                                </td>

                                <td class="text-center text-nowrap">
                                    {{ $row['contracts_signed'] }} / {{ $row['contracts'] }}
                                    <div class="fs-8 text-muted">подписано</div>
                                </td>

                                <td class="text-center text-nowrap">
                                    <span class="text-success">{{ $row['payments_paid'] }}</span>
                                    @if($row['payments_overdue'])
                                        / <span class="text-danger fw-bold">{{ $row['payments_overdue'] }}</span>
                                    @endif
                                    <div class="fs-8 text-muted">
                                        @if($row['payment_discipline'] !== null)
                                            дисциплина {{ round($row['payment_discipline']) }}%
                                        @else
                                            платежей нет
                                        @endif
                                    </div>
                                </td>

                                <td class="text-center text-nowrap">
                                    @if($row['days_to_contract'] !== null)
                                        {{ tools()->num_rus($row['days_to_contract'], ['дня', 'день', 'дней'], true) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-center text-nowrap pe-4">
                                    <span class="text-danger">{{ round($row['discount_partner_p'], 1) }}%</span>
                                    <div class="fs-8 text-muted">заказчику {{ round($row['discount_customer_p'], 1) }}%</div>
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
                Балл: конверсия решённых КП (вес 40) + объём выигранного относительно лучшего
                партнёра выборки (25) + доля вовремя прошедших платежей (25) − штраф за скидку
                партнёру (10). Партнёру без платежей дисциплина ставится нейтральной. Суммы
                приведены к рублям по текущему курсу.
            </div>
        </div>
    </div>
@endsection
