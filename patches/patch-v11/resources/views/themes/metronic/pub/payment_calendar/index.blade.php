@extends('layouts.layout')

@section('content')
    @php
        /**
         * Ссылка-детализация: сохраняем текущий отбор и меняем то, по чему кликнули.
         * null в $extra убирает параметр целиком.
         */
        $link = function (array $extra = []) use ($params, $year) {
            $query = array_merge($params, ['year' => $year], $extra);
            $query = array_filter($query, fn($value) => $value !== null && $value !== '' && $value !== false && $value !== []);

            return route('payment_calendar.index', $query) . '#payments';
        };

        /** Убрать одно значение из отбора (крестик на плашке) */
        $unlink = function (string $key, $value) use ($params, $year) {
            $query = array_merge($params, ['year' => $year]);

            if (is_array($query[$key] ?? null)) {
                $query[$key] = array_values(array_diff($query[$key], [(string) $value, (int) $value]));
                // сняли последний статус — показываем всё, а не значение по умолчанию
                if ($key === 'spec_status' && empty($query[$key])) $query['spec_status'] = ['all'];
            } else {
                $query[$key] = null;
            }

            $query = array_filter($query, fn($v) => $v !== null && $v !== '' && $v !== false && $v !== []);

            return route('payment_calendar.index', $query) . '#payments';
        };
    @endphp

    <div class="d-flex flex-column gap-6">

        {{-- Показатели: каждая цифра ведёт в таблицу платежей с тем же отбором --}}
        <div class="row g-4">
            <div class="col-6 col-xl-3">
                <div class="card h-100 border-0 bg-light-danger">
                    <div class="card-body p-5">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-semibold text-gray-700">Просрочено</span>
                            <i class="fa-light fa-triangle-exclamation fs-2 text-danger"></i>
                        </div>

                        <a href="{{ $link(['state' => ['overdue'], 'age' => null, 'month' => null, 'all_years' => 1]) }}"
                           class="fs-2hx fw-bold text-gray-900 text-hover-primary d-inline-block"
                           title="Показать эти платежи">
                            {{ tools()->cost_normalize(round($summary['overdue']['amount'])) }} ₽
                        </a>

                        <div class="fs-7 text-gray-700">
                            {{ $summary['overdue']['count'] }} платеж(ей) за все годы
                            @if($summary['overdue']['max_days'])
                                · до {{ $summary['overdue']['max_days'] }} дн
                            @endif
                        </div>

                        {{-- из чего сложилась сумма --}}
                        @if(!empty($summary['overdue']['buckets']))
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                @foreach($summary['overdue']['buckets'] as $bucket)
                                    <a href="{{ $link(['state' => ['overdue'], 'age' => [$bucket['code']], 'month' => null, 'all_years' => 1]) }}"
                                       class="badge badge-white text-gray-800 text-hover-primary"
                                       title="{{ $bucket['count'] }} платеж(ей)">
                                        {{ $bucket['label'] }}:
                                        <span class="fw-bold ms-1">
                                            {{ tools()->cost_normalize(round($bucket['amount'])) }}
                                        </span>
                                        <span class="text-muted ms-1">/ {{ $bucket['count'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="card h-100 border-0 bg-light-warning">
                    <div class="card-body p-5">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-semibold text-gray-700">Ждём в ближайшие {{ \App\Modules\Pub\PaymentCalendar\Services\PaymentCalendarService::SOON_DAYS }} дней</span>
                            <i class="fa-light fa-hourglass-half fs-2 text-warning"></i>
                        </div>

                        <a href="{{ $link(['state' => ['soon'], 'age' => null, 'month' => null, 'all_years' => 1]) }}"
                           class="fs-2hx fw-bold text-gray-900 text-hover-primary d-inline-block">
                            {{ tools()->cost_normalize(round($summary['soon']['amount'])) }} ₽
                        </a>

                        <div class="fs-7 text-gray-700">{{ $summary['soon']['count'] }} платеж(ей)</div>
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

                        <a href="{{ $link(['state' => ['paid'], 'age' => null, 'all_years' => null, 'year' => now()->year, 'month' => now()->month]) }}"
                           class="fs-2hx fw-bold text-gray-900 text-hover-primary d-inline-block">
                            {{ tools()->cost_normalize(round($summary['paid_month']['amount'])) }} ₽
                        </a>

                        <div class="fs-7 text-gray-700">{{ $summary['paid_month']['count'] }} платеж(ей)</div>
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

                        {{-- платежи без дат ни в один год не попадают: показываем за все годы --}}
                        <a href="{{ $link(['state' => ['unknown'], 'age' => null, 'month' => null, 'all_years' => 1]) }}"
                           class="fs-2hx fw-bold text-gray-900 text-hover-primary d-inline-block">
                            {{ tools()->cost_normalize(round($summary['unknown']['amount'])) }} ₽
                        </a>

                        <div class="fs-7 text-gray-700">
                            {{ $summary['unknown']['count'] }} платеж(ей) — срок не определён
                        </div>

                        @if($summary['canceled']['count'])
                            <a href="{{ $link(['state' => ['canceled'], 'spec_status' => ['canceled'], 'age' => null, 'month' => null, 'all_years' => 1]) }}"
                               class="badge badge-light-dark mt-3 text-hover-primary"
                               title="Отменённые спецификации не дают ни просрочки, ни плана">
                                отменённые спецификации:
                                {{ tools()->cost_normalize(round($summary['canceled']['amount'])) }}
                                <span class="text-muted ms-1">/ {{ $summary['canceled']['count'] }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($summary['rate_unknown'])
            <div class="alert alert-warning d-flex align-items-center mb-0">
                <i class="fa-light fa-triangle-exclamation fs-2 me-4"></i>
                <div class="fs-7">
                    Для {{ $summary['rate_unknown'] }} платеж(ей) в валюте нет курса на нужную дату —
                    они посчитаны один к одному и помечены в таблице. Обновите курсы валют
                    (<span class="text-muted">CurrencyService::updateRates</span>).
                </div>
            </div>
        @endif

        {{-- Год: план против факта --}}
        <div class="card">
            <div class="card-header min-h-auto py-5 border-bottom">
                <div class="card-title flex-column align-items-start">
                    <h4 class="fw-bold mb-1">План и факт по месяцам</h4>
                    <span class="text-muted fs-7">
                        Все суммы в рублях: факт — по курсу на дату поступления, план — по текущему курсу.
                        Любая цифра — ссылка на платежи, из которых она сложилась.
                    </span>
                </div>

                <div class="card-toolbar">
                    <form method="get" class="d-flex align-items-center gap-2">
                        @foreach(['q'] as $keep)
                            @if(!empty($params[$keep]))
                                <input type="hidden" name="{{ $keep }}" value="{{ $params[$keep] }}" />
                            @endif
                        @endforeach

                        @foreach(['state', 'age', 'partner', 'company', 'spec_status'] as $keep)
                            @foreach($params[$keep] as $value)
                                <input type="hidden" name="{{ $keep }}[]" value="{{ $value }}" />
                            @endforeach
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
                            <th class="ps-5" width="180">Месяц</th>
                            <th class="text-end">План, ₽</th>
                            <th class="text-end">Факт, ₽</th>
                            <th class="text-end">Просрочено, ₽</th>
                            <th class="text-end pe-5" width="160">Разница</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($months as $month)
                            {{-- выбранный месяц подсвечен: видно, по какому месяцу открыт список --}}
                            <tr @class([
                                'bg-light-primary' => $month['is_selected'],
                                'bg-light' => !$month['is_selected'] && $month['is_current'],
                            ])>
                                <td class="ps-5">
                                    @if($month['plan_count'] || $month['fact_count'])
                                        <a href="{{ $link(['month' => $month['month'], 'state' => null, 'age' => null, 'all_years' => null]) }}"
                                           class="fw-semibold text-capitalize text-gray-900 text-hover-primary">
                                            {{ $month['label'] }}
                                        </a>
                                    @else
                                        <span class="fw-semibold text-capitalize text-muted">{{ $month['label'] }}</span>
                                    @endif

                                    @if($month['is_selected'])
                                        <span class="badge badge-primary ms-2 fs-9">показан</span>
                                    @elseif($month['is_current'])
                                        <span class="badge badge-light-primary ms-2 fs-9">сейчас</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    @if($month['plan'])
                                        <a href="{{ $link(['month' => $month['month'], 'state' => null, 'age' => null, 'all_years' => null]) }}"
                                           class="fw-semibold text-gray-900 text-hover-primary">
                                            {{ tools()->cost_normalize(round($month['plan'])) }}
                                        </a>
                                        <span class="text-muted fs-8 ms-1">/ {{ $month['plan_count'] }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    @if($month['fact'])
                                        <a href="{{ $link(['month' => $month['month'], 'state' => ['paid'], 'age' => null, 'all_years' => null]) }}"
                                           class="fw-semibold text-success">
                                            {{ tools()->cost_normalize(round($month['fact'])) }}
                                        </a>
                                        <span class="text-muted fs-8 ms-1">/ {{ $month['fact_count'] }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    @if($month['overdue'])
                                        <a href="{{ $link(['month' => $month['month'], 'state' => ['overdue'], 'age' => null, 'all_years' => null]) }}"
                                           class="badge badge-light-danger">
                                            {{ tools()->cost_normalize(round($month['overdue'])) }}
                                            · {{ $month['overdue_count'] }}
                                        </a>
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

                        {{-- Итого за год --}}
                        <tfoot>
                        <tr class="fw-bold border-top border-gray-300">
                            <td class="ps-5">
                                ИТОГО за {{ $year }}
                                <div class="fs-8 fw-normal text-muted">
                                    оплачено {{ $year_total['progress'] }}% плана
                                </div>
                            </td>
                            <td class="text-end">
                                {{ tools()->cost_normalize(round($year_total['plan'])) }}
                                <span class="text-muted fs-8 fw-normal ms-1">/ {{ $year_total['plan_count'] }}</span>
                            </td>
                            <td class="text-end text-success">
                                {{ tools()->cost_normalize(round($year_total['fact'])) }}
                                <span class="text-muted fs-8 fw-normal ms-1">/ {{ $year_total['fact_count'] }}</span>
                            </td>
                            <td class="text-end text-danger">
                                @if($year_total['overdue'])
                                    {{ tools()->cost_normalize(round($year_total['overdue'])) }}
                                    <span class="text-muted fs-8 fw-normal ms-1">/ {{ $year_total['overdue_count'] }}</span>
                                @else
                                    <span class="text-muted fw-normal">—</span>
                                @endif
                            </td>
                            <td class="text-end pe-5 text-{{ $year_total['diff'] >= 0 ? 'success' : 'danger' }}">
                                {{ $year_total['diff'] >= 0 ? '+' : '' }}{{ tools()->cost_normalize(round($year_total['diff'])) }}
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Платежи --}}
        <div class="card" id="payments">
            <div class="card-header min-h-auto py-5 border-bottom flex-column align-items-stretch">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-2">
                    <div class="card-title flex-column align-items-start m-0">
                        <h4 class="fw-bold mb-1">Платежи</h4>
                        <span class="text-muted fs-7">Найдено: {{ $rows->count() }}</span>
                    </div>

                    @if(!empty($chips))
                        <a href="{{ route('payment_calendar.index', ['year' => $year, 'spec_status' => ['all']]) }}"
                           class="btn btn-sm btn-light">Сбросить всё</a>
                    @endif
                </div>

                {{-- Фильтр: в каждом поле можно выбрать несколько значений --}}
                <form method="get" class="row g-3 mt-1 pb-2">
                    <input type="hidden" name="year" value="{{ $year }}" />
                    @if($params['month'])
                        <input type="hidden" name="month" value="{{ $params['month'] }}" />
                    @endif
                    @if($params['all_years'])
                        <input type="hidden" name="all_years" value="1" />
                    @endif
                    @foreach($params['age'] as $value)
                        <input type="hidden" name="age[]" value="{{ $value }}" />
                    @endforeach

                    <div class="col-12 col-lg-3">
                        <div class="position-relative">
                            <i class="fa-light fa-magnifying-glass position-absolute top-50 translate-middle-y ms-4 text-gray-500"></i>
                            <input type="text" name="q" value="{{ $params['q'] }}"
                                   class="form-control form-control-sm form-control-solid ps-11"
                                   placeholder="КП, компания, партнёр, спецификация, договор" />
                        </div>
                    </div>

                    <div class="col-12 col-lg-3">
                        <select name="company[]" class="form-select form-select-sm form-select-solid calendar-select2"
                                multiple data-placeholder="Все компании">
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected(in_array($company->id, $params['company']))>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <select name="partner[]" class="form-select form-select-sm form-select-solid calendar-select2"
                                multiple data-placeholder="Все партнёры">
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" @selected(in_array($partner->id, $params['partner']))>
                                    {{ $partner->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <select name="state[]" class="form-select form-select-sm form-select-solid calendar-select2"
                                multiple data-placeholder="Все состояния">
                            @foreach($states as $code => $state)
                                <option value="{{ $code }}" @selected(in_array($code, $params['state']))>
                                    {{ $state['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-8 col-lg-1">
                        <select name="spec_status[]" class="form-select form-select-sm form-select-solid calendar-select2"
                                multiple data-placeholder="Статус спец."
                                title="По умолчанию — спецификации в работе. Оплаченные платежи видны при любом статусе.">
                            @foreach($spec_statuses as $code => $label)
                                <option value="{{ $code }}" @selected(in_array($code, $params['spec_status']))>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-4 col-lg-1 d-grid">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fa-light fa-filter fs-6 me-1"></i>Найти
                        </button>
                    </div>
                </form>
            </div>

            {{-- что сейчас на экране --}}
            @if(!empty($chips))
                <div class="card-body py-3 border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted fs-8 text-uppercase me-1">Отбор</span>
                        @foreach($chips as $chip)
                            <a href="{{ $unlink($chip['key'], $chip['value']) }}"
                               class="badge badge-light-primary d-inline-flex align-items-center"
                               title="Убрать это условие">
                                {{ $chip['label'] }}
                                <i class="fa-light fa-xmark fs-8 ms-2"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

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
                                <th width="170">КП</th>
                                <th>Компания</th>
                                <th>Договор и спецификация</th>
                                <th width="105">План</th>
                                <th width="105">Факт</th>
                                <th class="text-end" width="150">Оплата</th>
                                <th class="text-end" width="130">Курс</th>
                                <th class="text-end" width="140">Итого, ₽</th>
                                <th class="text-end pe-5" width="70">Сводная</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rows as $row)
                                <tr @class(['opacity-75' => $row->state === 'canceled'])>
                                    <td class="ps-5">
                                        <span class="badge badge-light-{{ $row->state_decorate['color'] }}">
                                            <i class="fa-light {{ $row->state_decorate['icon'] }} fs-8 me-2"></i>
                                            {{ $row->state_decorate['label'] }}
                                        </span>
                                        @if($row->state === 'overdue')
                                            <div class="fs-8 text-danger mt-1">
                                                {{ $row->overdue_days }} дн назад
                                            </div>
                                        @elseif($row->state === 'soon')
                                            <div class="fs-8 text-warning mt-1">через {{ $row->days_left }} дн</div>
                                        @endif
                                    </td>

                                    {{-- КП --}}
                                    <td>
                                        @if($row->proposal_group)
                                            <a href="{{ route('proposal.detail', [$row->proposal_group, $row->proposal_iteration]) }}"
                                               class="fw-semibold text-gray-900 text-hover-primary d-block text-truncate"
                                               style="max-width: 170px;"
                                               title="{{ $row->proposal_name }}">
                                                {{ $row->proposal_number ?: $row->proposal_name }}
                                            </a>
                                            @if($row->proposal_number && $row->proposal_name)
                                                <div class="fs-8 text-muted text-truncate" style="max-width: 170px;">
                                                    {{ $row->proposal_name }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted fs-8">КП не привязано</span>
                                        @endif
                                    </td>

                                    {{-- Компания --}}
                                    <td>
                                        @if($row->company_id)
                                            <a href="{{ route('company.detail', $row->company_id) }}"
                                               class="fw-semibold text-gray-900 text-hover-primary">
                                                {{ $row->company_name }}
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif

                                        @if($row->partner_name)
                                            <div class="fs-8 text-muted">{{ $row->partner_name }}</div>
                                        @endif
                                    </td>

                                    {{-- Договор и спецификация --}}
                                    <td>
                                        @if($row->contract_id)
                                            <span class="fw-semibold text-gray-900">
                                                Договор {{ $row->contract_number ?: '№ не указан' }}
                                            </span>
                                        @else
                                            <span class="text-muted">договора нет</span>
                                        @endif

                                        <div class="fs-8">
                                            <a href="javascript:box({href: '{{ route('contract_spec.box_edit', $row->spec_id) }}'})"
                                               class="text-muted text-hover-primary">
                                                {{ $row->spec_name ?: 'спецификация без названия' }}
                                            </a>
                                            @if($row->spec_status_label)
                                                <span class="badge badge-light fs-9 ms-1">{{ $row->spec_status_label }}</span>
                                            @endif
                                            @if($row->state !== 'canceled' && !$row->is_signed)
                                                <span class="badge badge-light-warning fs-9 ms-1">не подписана</span>
                                            @endif
                                        </div>
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

                                    {{-- Оплата в валюте спецификации --}}
                                    <td class="text-end text-nowrap">
                                        <span class="fw-bold">{{ tools()->cost_normalize(round($row->amount, 2)) }}</span>
                                        <span class="text-muted fs-8 ms-1">{{ $row->currency_slug }}</span>

                                        @if($row->spec_amount)
                                            <div class="fs-8 text-muted">
                                                спец. {{ tools()->cost_normalize(round((float) $row->spec_amount)) }}
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Курс --}}
                                    <td class="text-end text-nowrap">
                                        @if(!$row->is_currency)
                                            <span class="text-muted">—</span>
                                        @else
                                            <span class="fw-semibold">{{ number_format($row->rate, 2, ',', ' ') }}</span>
                                            <div class="fs-8 text-muted">
                                                @if($row->rate_unknown)
                                                    <span class="text-warning">курса нет, 1:1</span>
                                                @else
                                                    на {{ $row->rate_date->format('d.m.Y') }}
                                                    <span class="text-gray-500">
                                                        ({{ $row->date_fact ? 'факт' : 'текущий' }})
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Итого в рублях --}}
                                    <td class="text-end text-nowrap">
                                        <span class="fw-bold">{{ tools()->cost_normalize(round($row->amount_rub)) }}</span>
                                        <span class="text-muted fs-8 ms-1">₽</span>
                                    </td>

                                    <td class="text-end pe-5">
                                        @if($row->proposal_group)
                                            <a href="{{ route('deal_card.index', $row->proposal_group) }}"
                                               class="btn btn-sm btn-icon btn-light-primary"
                                               title="Сводная информация по сделке">
                                                <i class="fa-light fa-diagram-project fs-5"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>

                            <tfoot>
                            <tr class="fw-bold border-top border-gray-300">
                                <td class="ps-5" colspan="8">ИТОГО по выборке</td>
                                <td class="text-end text-nowrap">
                                    {{ tools()->cost_normalize(round($rows->sum(fn($row) => (float) $row->amount_rub))) }}
                                    <span class="text-muted fs-8 ms-1">₽</span>
                                </td>
                                <td class="pe-5"></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection

@section('scripts')
    @parent
    <script>
        $(".calendar-select2").select2({
            width: '100%',
            allowClear: true,
            closeOnSelect: false,
            placeholder: function () {
                return $(this).data('placeholder');
            }
        });
    </script>
@endsection
