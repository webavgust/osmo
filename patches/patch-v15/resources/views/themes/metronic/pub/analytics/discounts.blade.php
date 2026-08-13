@extends('layouts.layout')

@section('content')
    @php
        $link = function (array $extra = []) use ($params) {
            $query = array_merge($params, $extra);
            $query = array_filter($query, fn($value) => $value !== null && $value !== '' && $value !== false);

            return route('analytics.discounts', $query);
        };
    @endphp

    <div class="container-fluid">

        {{-- Отбор --}}
        <div class="card mb-4">
            <div class="card-body py-4">
                <form method="get" action="{{ route('analytics.discounts') }}">
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

                        <div class="col-3">
                            <label class="form-label fs-8 text-muted mb-1">Партнёр</label>
                            <select name="partner" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">все партнёры</option>
                                @foreach($partners as $partner)
                                    <option value="{{ $partner->id }}" @selected($params['partner'] == $partner->id)>{{ $partner->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-2">
                            <label class="form-label fs-8 text-muted mb-1">Статус КП</label>
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">любой</option>
                                @foreach($statuses as $code => $status)
                                    <option value="{{ $code }}" @selected($params['status'] === $code)>{{ $status['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-3">
                            <label class="form-label fs-8 text-muted mb-1">Поиск</label>
                            <input type="text" name="q" value="{{ $params['q'] }}" class="form-control form-control-sm"
                                   placeholder="номер, название, компания">
                        </div>

                        <div class="col-auto">
                            <label class="form-check form-check-sm form-check-custom">
                                <input type="checkbox" name="only_alert" value="1" class="form-check-input"
                                       @checked($params['only_alert']) onchange="this.form.submit()">
                                <span class="form-check-label fs-7">только выделенные</span>
                            </label>
                        </div>

                        <div class="col-auto ms-auto">
                            <button type="submit" class="btn btn-sm btn-primary">Показать</button>
                            <a href="{{ route('analytics.discounts', ['year' => '']) }}" class="btn btn-sm btn-light ms-1">Сбросить</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Показатели --}}
        <div class="row g-4 mb-4">
            @php
                $cards = [
                    ['label' => 'КП в выборке', 'value' => $totals['count'], 'sub' => 'последний вариант каждого', 'color' => 'dark'],
                    ['label' => 'Прайс', 'value' => tools()->cost_normalize(round($totals['list'])) . ' ₽', 'sub' => 'до скидок', 'color' => 'dark'],
                    ['label' => 'Скидка заказчику', 'value' => tools()->cost_normalize(round($totals['customer'])) . ' ₽', 'sub' => round($totals['customer_p'], 1) . '% прайса', 'color' => 'warning'],
                    ['label' => 'Скидка партнёру', 'value' => tools()->cost_normalize(round($totals['partner'])) . ' ₽', 'sub' => round($totals['partner_p'], 1) . '% от цены заказчика', 'color' => 'danger'],
                    ['label' => 'Итог', 'value' => tools()->cost_normalize(round($totals['total'])) . ' ₽', 'sub' => 'совокупная скидка ' . round($totals['total_p'], 1) . '%', 'color' => 'success'],
                    ['label' => 'Выделено', 'value' => $totals['alerts'], 'sub' => 'КП со скидкой выше нормы', 'color' => 'primary'],
                ];
            @endphp

            @foreach($cards as $card)
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body p-4">
                            <div class="fs-8 text-muted text-uppercase">{{ $card['label'] }}</div>
                            <div class="fs-2 fw-bold text-{{ $card['color'] }} mt-1">{{ $card['value'] }}</div>
                            <div class="fs-8 text-muted mt-1">{{ $card['sub'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($totals['rate_unknown'])
            <x-ui.notification.light type="warning" class="mb-4">
                У {{ $totals['rate_unknown'] }} КП не нашлось курса валюты — в итогах они посчитаны один к одному.
            </x-ui.notification.light>
        @endif

        {{-- Таблица --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="m-0">Скидки по КП</h3>
                <span class="text-muted fs-7">
                    Сортировка по совокупной скидке. Суммы — в валюте своего КП,
                    проценты сравнимы между собой.
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-row-bordered align-middle m-0">
                    <thead>
                        <tr class="fw-bold fs-7 text-muted text-uppercase">
                            <th class="ps-4">КП</th>
                            <th>Партнёр</th>
                            <th class="text-center">Статус</th>
                            <th class="text-end">Прайс</th>
                            <th class="text-end">Заказчику</th>
                            <th class="text-end">Партнёру</th>
                            <th class="text-end">Итог</th>
                            <th class="text-center">Совокупно</th>
                            <th>По блокам</th>
                            <th class="pe-4">Пометки</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php $proposal = $row['proposal']; @endphp
                            <tr @class(['bg-light-danger' => !empty($row['alerts'])])>
                                <td class="ps-4">
                                    <a href="{{ route('proposal.detail', [$proposal, $proposal->iteration]) }}" class="fw-bold">
                                        {{ $proposal->number ?: 'б/н' }}
                                    </a>
                                    <div class="fs-8 text-muted text-truncate" style="max-width: 240px">{{ $proposal->name }}</div>
                                    @if(!empty($row['company']))
                                        <a href="{{ route('company.detail', $row['company']) }}" class="fs-8">{{ $row['company']->name }}</a>
                                    @endif
                                </td>

                                <td>
                                    @if(!empty($row['partner']))
                                        <a href="{{ route('partner.detail', $row['partner']) }}"
                                           style="color: {{ $row['grade']['color']['medal'] ?? '#7e8299' }}">
                                            <x-ui.icon.solid icon="fa-medal" class="me-1"/>{{ $row['partner']->name }}
                                        </a>
                                        <div class="fs-8 text-muted">{{ $row['grade']['label'] ?? '—' }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <x-proposal.status :proposal="$proposal"/>
                                </td>

                                <td class="text-end text-nowrap">
                                    {{ tools()->cost_normalize(round($row['list'])) }}
                                    <div class="fs-8 text-muted">{{ $row['currency'] }}</div>
                                </td>

                                <td class="text-end text-nowrap">
                                    @if($row['customer'] > 0)
                                        <span class="text-warning">− {{ tools()->cost_normalize(round($row['customer'])) }}</span>
                                        <div class="fs-8 text-muted">{{ round($row['customer_p'], 1) }}%</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-end text-nowrap">
                                    @if($row['partner_amount'] > 0)
                                        <span class="text-danger">− {{ tools()->cost_normalize(round($row['partner_amount'])) }}</span>
                                        <div class="fs-8 text-muted">{{ round($row['partner_p'], 1) }}%</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-end text-nowrap fw-bold">{{ tools()->cost_normalize(round($row['total'])) }}</td>

                                <td class="text-center text-nowrap">
                                    <span @class(['fs-4 fw-bold', 'text-danger' => !empty($row['alerts']), 'text-dark' => empty($row['alerts'])])>
                                        {{ round($row['total_p'], 1) }}%
                                    </span>
                                    @if(!empty($row['grade_average']))
                                        <div class="fs-8 text-muted">
                                            грейд: {{ round($row['grade_average'], 1) }}%
                                            @if($row['grade_diff'] > 0)
                                                <span class="text-danger">+{{ round($row['grade_diff'], 1) }}</span>
                                            @else
                                                <span class="text-success">{{ round($row['grade_diff'], 1) }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($row['blocks'] as $code => $block)
                                            @continue($block['list'] <= 0)
                                            <span class="badge badge-light-dark fs-8"
                                                  title="{{ $block['label'] }}: прайс {{ tools()->cost_normalize(round($block['list'])) }}, итог {{ tools()->cost_normalize(round($block['total'])) }}">
                                                {{ $block['label'] }} {{ round($block['total_p'], 1) }}%
                                            </span>
                                        @endforeach
                                    </div>
                                </td>

                                <td class="pe-4">
                                    @foreach($row['alerts'] as $alert)
                                        <div class="fs-8 text-danger">{{ $alert }}</div>
                                    @endforeach
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-10">
                                    По этому отбору КП со скидками нет
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer py-3 fs-8 text-muted">
                Скидка считается по последнему созданному варианту последней редакции КП, по той же
                формуле, что в карточке: процент заказчику снимается с прайса, процент партнёру —
                с уже уменьшенной цены. Выделяются КП, где совокупная скидка выше
                {{ \App\Modules\Pub\Analytics\Services\DiscountAnalysisService::HARD_LIMIT_P }}%
                либо превышает средний уровень своего грейда более чем на
                {{ \App\Modules\Pub\Analytics\Services\DiscountAnalysisService::GRADE_ALERT_PP }} п.п.
            </div>
        </div>
    </div>
@endsection
