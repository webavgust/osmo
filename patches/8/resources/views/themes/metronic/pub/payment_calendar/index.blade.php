@extends('layouts.layout')

@section('content')
    <div class="d-flex flex-column gap-6">

        {{-- Показатели --}}
        <div class="row g-4">
            <div class="col-6 col-xl-3">
                <div class="card h-100 border-0 bg-light-danger">
                    <div class="card-body p-5">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-semibold text-gray-700">Просрочено</span>
                            <i class="fa-light fa-triangle-exclamation fs-2 text-danger"></i>
                        </div>
                        <div class="fs-2hx fw-bold text-gray-900">
                            {{ tools()->cost_normalize(round($summary['overdue']['amount']['main'])) }} ₽
                        </div>
                        <div class="fs-7 text-gray-700">
                            {{ $summary['overdue']['count'] }} платеж(ей)
                            @if($summary['overdue']['max_days'])
                                · до {{ $summary['overdue']['max_days'] }} дн
                            @endif
                        </div>
                        @foreach($summary['overdue']['amount']['other'] as $slug => $sum)
                            <div class="fs-8 text-gray-600">+ {{ tools()->cost_normalize(round($sum)) }} {{ $slug }}</div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="card h-100 border-0 bg-light-warning">
                    <div class="card-body p-5">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-semibold text-gray-700">Ждём в ближайшие 14 дней</span>
                            <i class="fa-light fa-hourglass-half fs-2 text-warning"></i>
                        </div>
                        <div class="fs-2hx fw-bold text-gray-900">
                            {{ tools()->cost_normalize(round($summary['soon']['amount']['main'])) }} ₽
                        </div>
                        <div class="fs-7 text-gray-700">{{ $summary['soon']['count'] }} платеж(ей)</div>
                        @foreach($summary['soon']['amount']['other'] as $slug => $sum)
                            <div class="fs-8 text-gray-600">+ {{ tools()->cost_normalize(round($sum)) }} {{ $slug }}</div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="card h-100 border-0 bg-light-success">
                    <div class="card-body p-5">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-semibold text-gray-700">Поступило в этом месяце</span>
                            <i class="fa-light fa-circle-check fs-2 text-success"></i>
                        </div>
                        <div class="fs-2hx fw-bold text-gray-900">
                            {{ tools()->cost_normalize(round($summary['paid_month']['amount']['main'])) }} ₽
                        </div>
                        <div class="fs-7 text-gray-700">{{ $summary['paid_month']['count'] }} платеж(ей)</div>
                        @foreach($summary['paid_month']['amount']['other'] as $slug => $sum)
                            <div class="fs-8 text-gray-600">+ {{ tools()->cost_normalize(round($sum)) }} {{ $slug }}</div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="card h-100 border-0 bg-light">
                    <div class="card-body p-5">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-semibold text-gray-700">Без даты</span>
                            <i class="fa-light fa-circle-question fs-2 text-gray-600"></i>
                        </div>
                        <div class="fs-2hx fw-bold text-gray-900">
                            {{ tools()->cost_normalize(round($summary['unknown']['amount']['main'])) }} ₽
                        </div>
                        <div class="fs-7 text-gray-700">
                            {{ $summary['unknown']['count'] }} платеж(ей) — срок не определён
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Год: план против факта --}}
        <div class="card">
            <div class="card-header min-h-auto py-5 border-bottom">
                <div class="card-title flex-column align-items-start">
                    <h4 class="fw-bold mb-1">План и факт по месяцам</h4>
                    <span class="text-muted fs-7">
                        Суммы в основной валюте (₽). Платежи в других валютах вынесены отдельно в показателях выше.
                    </span>
                </div>

                <div class="card-toolbar">
                    <form method="get" class="d-flex align-items-center gap-2">
                        @foreach(['state', 'q', 'partner'] as $keep)
                            @if(!empty($params[$keep]))
                                <input type="hidden" name="{{ $keep }}" value="{{ $params[$keep] }}" />
                            @endif
                        @endforeach

                        <select name="year" class="form-select form-select-sm form-select-solid w-125px"
                                onchange="this.form.submit()">
                            @foreach($years as $item)
                                <option value="{{ $item }}" @selected($item == $year)>{{ $item }} год</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 align-middle mb-0">
                        <thead>
                        <tr class="fw-bold text-muted bg-light">
                            <th class="ps-5" width="160">Месяц</th>
                            <th class="text-end">План</th>
                            <th class="text-end">Факт</th>
                            <th class="text-end">Просрочено</th>
                            <th class="text-end pe-5" width="160">Разница</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($months as $month)
                            <tr @class(['bg-light-primary' => $month['is_current']])>
                                <td class="ps-5">
                                    <span class="fw-semibold text-capitalize">{{ $month['label'] }}</span>
                                    @if($month['is_current'])
                                        <span class="badge badge-light-primary ms-2 fs-9">сейчас</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    @if($month['plan'])
                                        <span class="fw-semibold">{{ tools()->cost_normalize(round($month['plan'])) }}</span>
                                        <span class="text-muted fs-8 ms-1">/ {{ $month['plan_count'] }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    @if($month['fact'])
                                        <span class="fw-semibold text-success">{{ tools()->cost_normalize(round($month['fact'])) }}</span>
                                        <span class="text-muted fs-8 ms-1">/ {{ $month['fact_count'] }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    @if($month['overdue'])
                                        <span class="badge badge-light-danger">
                                            {{ tools()->cost_normalize(round($month['overdue'])) }}
                                            · {{ $month['overdue_count'] }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-end pe-5">
                                    @if($month['plan'] || $month['fact'])
                                        <span @class([
                                            'fw-semibold',
                                            'text-success' => $month['diff'] >= 0,
                                            'text-danger' => $month['diff'] < 0,
                                        ])>
                                            {{ $month['diff'] >= 0 ? '+' : '' }}{{ tools()->cost_normalize(round($month['diff'])) }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Платежи --}}
        <div class="card">
            <div class="card-header min-h-auto py-5 border-bottom">
                <div class="card-title flex-column align-items-start">
                    <h4 class="fw-bold mb-1">Платежи</h4>
                    <span class="text-muted fs-7">Найдено: {{ $rows->count() }}</span>
                </div>

                <div class="card-toolbar">
                    <form method="get" class="d-flex flex-wrap align-items-center gap-2">
                        <input type="hidden" name="year" value="{{ $year }}" />

                        <div class="position-relative">
                            <i class="fa-light fa-magnifying-glass position-absolute top-50 translate-middle-y ms-4 text-gray-500"></i>
                            <input type="text" name="q" value="{{ $params['q'] }}"
                                   class="form-control form-control-sm form-control-solid ps-11 w-250px"
                                   placeholder="Компания, партнёр, спецификация, договор" />
                        </div>

                        <select name="partner" class="form-select form-select-sm form-select-solid w-200px">
                            <option value="">Все партнёры</option>
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" @selected($params['partner'] == $partner->id)>
                                    {{ $partner->name }}
                                </option>
                            @endforeach
                        </select>

                        <select name="state" class="form-select form-select-sm form-select-solid w-150px">
                            <option value="">Все состояния</option>
                            @foreach($states as $code => $state)
                                <option value="{{ $code }}" @selected($params['state'] === $code)>{{ $state['label'] }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fa-light fa-filter fs-6 me-2"></i>Применить
                        </button>

                        @if($params['q'] || $params['state'] || $params['partner'])
                            <a href="{{ route('payment_calendar.index', ['year' => $year]) }}" class="btn btn-sm btn-light">
                                Сбросить
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            <div class="card-body p-0">
                @if($rows->isEmpty())
                    <div class="text-center text-muted py-10">
                        Платежей по заданным условиям нет
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle mb-0">
                            <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-5" width="130">Состояние</th>
                                <th>Компания и спецификация</th>
                                <th width="110">План</th>
                                <th width="110">Факт</th>
                                <th class="text-end">Сумма</th>
                                <th class="text-end pe-5" width="90">Цепочка</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td class="ps-5">
                                        <span class="badge badge-light-{{ $row->state_decorate['color'] }}">
                                            <i class="fa-light {{ $row->state_decorate['icon'] }} fs-8 me-2"></i>
                                            {{ $row->state_decorate['label'] }}
                                        </span>
                                        @if($row->state === 'overdue')
                                            <div class="fs-8 text-danger mt-1">
                                                {{ abs($row->days_left) }} дн назад
                                            </div>
                                        @elseif($row->state === 'soon')
                                            <div class="fs-8 text-warning mt-1">через {{ $row->days_left }} дн</div>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="fw-semibold">{{ $row->company_name ?: '—' }}</div>
                                        <div class="fs-8 text-muted">
                                            {{ collect([
                                                $row->spec_name,
                                                $row->contract_number ? 'договор ' . $row->contract_number : null,
                                                $row->partner_name,
                                            ])->filter()->implode(' · ') }}
                                        </div>
                                        @if(!$row->is_signed && $row->spec_id)
                                            <span class="badge badge-light-warning fs-9 mt-1">спецификация не подписана</span>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        {{ $row->date_plan?->format('d.m.Y') ?: '—' }}
                                        @if($row->delay)
                                            <div class="fs-8 text-muted">отсрочка {{ $row->delay }} дн</div>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        @if($row->date_fact)
                                            <span class="text-success">{{ $row->date_fact->format('d.m.Y') }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <span class="fw-bold">{{ tools()->cost_normalize(round($row->amount)) }}</span>
                                        <span class="text-muted fs-8 ms-1">{{ $row->currency_slug }}</span>
                                        @if($row->amount_plan && $row->amount_fact && abs($row->amount_plan - $row->amount_fact) > 1)
                                            <div class="fs-8 text-muted">
                                                план {{ tools()->cost_normalize(round($row->amount_plan)) }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-end pe-5">
                                        @if($row->proposal_id)
                                            @php $chain_group = \App\Modules\Pub\Proposal\Models\Proposal::find($row->proposal_id)?->group; @endphp
                                            @if($chain_group)
                                                <a href="{{ route('deal_card.index', $chain_group) }}"
                                                   class="btn btn-sm btn-icon btn-light-primary"
                                                   title="Сквозная карточка сделки">
                                                    <i class="fa-light fa-diagram-project fs-5"></i>
                                                </a>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection
