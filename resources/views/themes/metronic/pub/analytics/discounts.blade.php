@extends('layouts.layout')

@php
    // «Фильтр (n)»: год не считаем — он вынесен отдельным селектором и стоит
    // в тулбаре всегда, это главный отбор страницы
    $rules_count = collect(['partner', 'status', 'q'])
            ->filter(fn($key) => !empty($params[$key]))
            ->count()
        + ($params['only_alert'] ? 1 : 0);

    // год уезжает в адрес как есть: пустая строка — «все годы»
    $year = $params['year'] ?? '';
@endphp

{{-- Тулбар страницы: год + «Фильтр», как на дашборде Битрикса --}}
@section('breadcrumb_right')

    {{-- Год всегда на виду; остальной отбор уезжает вместе с ним скрытыми полями --}}
    <form method="get" action="{{ route('analytics.discounts') }}" class="d-flex align-items-center">
        <input type="hidden" name="partner" value="{{ $params['partner'] }}"/>
        <input type="hidden" name="status" value="{{ $params['status'] }}"/>
        <input type="hidden" name="q" value="{{ $params['q'] }}"/>
        @if($params['only_alert'])
            <input type="hidden" name="only_alert" value="1"/>
        @endif

        <select name="year" class="form-select w-auto fw-bold" onchange="this.form.submit()">
            <option value="">все годы</option>
            @foreach($years as $item)
                <option value="{{ $item }}" @selected($params['year'] == $item)>{{ $item }}</option>
            @endforeach
        </select>
    </form>

    <button type="button" data-bs-toggle="modal" data-bs-target="#discounts_filter_modal"
            class="btn btn-light-info fw-bold d-flex align-items-center">
        <i class="fa-light fa-filter"></i>
        Фильтр
        @if($rules_count)
            <span class="count filter-count">{{ $rules_count }}</span>
        @endif
    </button>

    @if($rules_count)
        {{-- год остаётся: убираем только то, что стоит в модалке --}}
        <a href="{{ route('analytics.discounts', ['year' => $year]) }}"
           class="me-2 text-dark-500 text-hover-dark">
            <i class="fa-light fa-xmark fs-5 me-2" aria-hidden="true"></i> Убрать
        </a>
    @endif

@endsection

@section('content')
    <div class="container-fluid">

        {{-- Отбор: живёт в модалке, в адресе остаётся обычной GET-строкой,
             поэтому ссылку с отбором можно передать --}}
        <div id="discounts_filter_modal" class="modal fade" tabindex="-1" aria-hidden="true">
            <form method="get" action="{{ route('analytics.discounts') }}">
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
                                <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Партнёр</label>
                                <div class="col-sm-9">
                                    <select name="partner" class="form-select discounts_select" data-placeholder="все партнёры">
                                        <option value="">все партнёры</option>
                                        @foreach($partners as $partner)
                                            <option value="{{ $partner->id }}" @selected($params['partner'] == $partner->id)>{{ $partner->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-5">
                                <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Статус КП</label>
                                <div class="col-sm-9">
                                    <select name="status" class="form-select discounts_select" data-placeholder="любой">
                                        <option value="">любой</option>
                                        @foreach($statuses as $code => $status)
                                            <option value="{{ $code }}" @selected($params['status'] === $code)>{{ $status['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-5">
                                <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Поиск</label>
                                <div class="col-sm-9">
                                    <input type="text" name="q" value="{{ $params['q'] }}" class="form-control"
                                           placeholder="номер, название, компания"/>
                                </div>
                            </div>

                            <div class="row">
                                <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Что показываем</label>
                                <div class="col-sm-9">
                                    <label class="form-check form-check-custom mt-3">
                                        <input type="checkbox" name="only_alert" value="1" class="form-check-input"
                                               @checked($params['only_alert'])>
                                        <span class="form-check-label">только выделенные</span>
                                    </label>

                                    <div class="form-text mt-3">
                                        Выделенные — КП со скидкой выше нормы: их видно красной строкой в таблице.
                                    </div>
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
                            <div class="fs-6 text-muted text-uppercase">{{ $card['label'] }}</div>
                            <div class="fs-1 fw-bold text-{{ $card['color'] }} mt-1">{{ $card['value'] }}</div>
                            <div class="fs-7 text-muted mt-1">{{ $card['sub'] }}</div>
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
            <div class="card-header">
                <div class="card-title flex-column align-items-start">
                    <h3 class="fw-bold mb-1">Скидки по КП</h3>
                    <span class="text-muted fs-7">
                        Сортировка по совокупной скидке. Суммы — в валюте своего КП,
                        проценты сравнимы между собой.
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-row-bordered align-middle m-0">
                    <thead>
                        <tr class="fw-bold fs-7 text-muted text-uppercase">
                            <th class="ps-4">КП</th>
                            <th class="text-center">Статус</th>
                            <th class="text-end">Прайс</th>
                            <th class="text-end">Заказчику</th>
                            <th class="text-end">Партнёру</th>
                            <th class="text-end">Итог</th>
                            <th class="text-center" title="З — скидка заказчику, П — скидка партнёру">Скидки</th>
                            <th class="text-center">Всего</th>
                            {{-- пометки — значком с балуном --}}
                            <th class="pe-4" width="1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php $proposal = $row['proposal']; @endphp
                            <tr @class(['fs-5', 'bg-light-danger border-bottom-danger' => !empty($row['alerts'])])>
                                <td class="ps-4">
                                    {{-- главное — название КП (как в списке КП), номер и редакция — вторичной строкой --}}
                                    <a href="{{ route('proposal.detail', [$proposal, $proposal->iteration]) }}" class="fw-bold">
                                        {{ $proposal->name ?: 'без названия' }}
                                    </a>
                                    <div class="fs-7 text-muted">
                                        № {{ $proposal->number ?: 'б/н' }}@if((int) $proposal->iteration > 1) · редакция {{ $proposal->iteration }}@endif
                                    </div>
                                    {{-- партнёр → компания бейджами, как в списке КП (components/proposal/table/main/partner);
                                         компонент не берём: у части КП партнёра нет, а он строит ссылку на обоих --}}
                                    @if(!empty($row['partner']) || !empty($row['company']))
                                        <div class="mt-1">
                                            @if(!empty($row['partner']))
                                                <a href="{{ route('partner.detail', $row['partner']) }}">
                                                    <x-ui.badge.light type="info" class="text-info-700 bg-hover-info text-hover-white">
                                                        {{ $row['partner']->name }}
                                                    </x-ui.badge.light>
                                                </a>
                                            @endif
                                            @if(!empty($row['partner']) && !empty($row['company']))
                                                <span class="px-1 text-dark-800">--></span>
                                            @endif
                                            @if(!empty($row['company']))
                                                <a href="{{ route('company.detail', $row['company']) }}">
                                                    <x-ui.badge.light type="primary" class="text-primary-700 bg-hover-primary text-hover-white">
                                                        {{ $row['company']->name }}
                                                    </x-ui.badge.light>
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <x-proposal.status :proposal="$proposal" stacked="1"/>
                                </td>

                                <td class="text-end text-nowrap">
                                    <span class="fw-semibold text-gray-900 text-hover-primary">{{ tools()->cost_normalize(round($row['list'])) }}</span>
                                    <div class="fs-7 text-muted">{{ $row['currency'] }}</div>
                                </td>

                                <td class="text-end text-nowrap">
                                    @if($row['customer'] > 0)
                                        <span class="text-warning fw-semibold ">&ndash; {{ tools()->cost_normalize(round($row['customer'])) }}</span>

                                        <div class="fs-7 text-muted">{{ round($row['customer_p'], 1) }}%</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-end text-nowrap">
                                    @if($row['partner_amount'] > 0)
                                        <span class="text-danger fw-semibold ">&ndash; {{ tools()->cost_normalize(round($row['partner_amount'])) }}</span>

                                        <div class="fs-7 text-muted">{{ round($row['partner_p'], 1) }}%</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-end text-nowrap fw-bold">
                                    <span class="text-dark fw-semibold ">{{ tools()->cost_normalize(round($row['total'])) }}</span>

                                    <div class="fs-7 text-muted">&nbsp;</div>

                                </td>


                                <td class="text-center">
                                    {{-- скидки по блокам мини-таблицей: иконка блока, «З» и «П» ровными колонками
                                         (ширины заданы, чтобы колонки совпадали во всех строках);
                                         название блока и суммы — в подсказке строки. Класс .table не ставим:
                                         иначе правила table-row-bordered внешней таблицы дорисуют рамки --}}
                                    <table class="m-0 lh-sm" style="table-layout: fixed; width: 110px">
                                        @foreach($row['blocks'] as $code => $block)
                                            @continue($block['list'] <= 0)
                                            @php
                                                // процент заказчика — от прайса, процент партнёра — от уже
                                                // уменьшенной цены: так же, как в карточке КП
                                                $customer_p = $block['list'] > 0 ? $block['customer'] / $block['list'] * 100 : 0;
                                                $base = $block['list'] - $block['customer'];
                                                $partner_p = $base > 0 ? $block['partner'] / $base * 100 : 0;
                                            @endphp
                                            <tr class="border-0" title="{{ $block['label'] }}: прайс {{ tools()->cost_normalize(round($block['list'])) }}, заказчику −{{ tools()->cost_normalize(round($block['customer'])) }}, партнёру −{{ tools()->cost_normalize(round($block['partner'])) }}, итог {{ tools()->cost_normalize(round($block['total'])) }}">
                                                <td class="p-0 pe-2 pb-1 w-20px text-{{ $block['color'] }}">
                                                    <x-ui.icon.regular :icon="$block['icon']" class="fs-7"/>
                                                </td>
                                                <td class="p-0 pe-3 pb-1 w-50px fs-8 fw-bold text-nowrap text-end @if($customer_p > 0) text-warning @else text-muted @endif"
                                                    title="Скидка заказчику">{{ round($customer_p, 1) }}%</td>
                                                <td class="p-0 pb-1 w-40px fs-8 fw-bold text-nowrap text-end @if($partner_p > 0) text-danger @else text-muted @endif"
                                                    title="Скидка партнёру">{{ round($partner_p, 1) }}%</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </td>

                                <td class="text-center text-nowrap">
                                    <span @class(['fs-4 fw-bold', 'text-danger' => !empty($row['alerts']), 'text-dark' => empty($row['alerts'])])>
                                        {{ round($row['total_p'], 1) }}%
                                    </span>
                                    @if(!empty($row['grade_average']))
                                        <div class="fs-8 text-muted">
                                            {{ round($row['grade_average'], 1) }}%
                                            @if($row['grade_diff'] > 0)
                                                <span class="text-danger">+{{ round($row['grade_diff'], 1) }}</span>
                                            @else
                                                <span class="text-success">{{ round($row['grade_diff'], 1) }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <td class="pe-4 text-center">
                                    @if(!empty($row['alerts']))
                                        @php
                                            // текст пометок экранируем: балун выводит html
                                            $alerts_html = '<ol class="ps-4 mb-0">'
                                                . collect($row['alerts'])->map(fn($alert) => '<li class="mb-1">' . e($alert) . '</li>')->implode('')
                                                . '</ol>';
                                        @endphp
                                        <span role="button" tabindex="0" class="d-inline-block cursor-pointer"
                                              data-bs-toggle="popover" data-bs-trigger="focus" data-bs-container="body"
                                              data-bs-placement="left" data-bs-html="true" title="Пометки"
                                              data-bs-content="{{ $alerts_html }}">
                                            <i class="fas fa-triangle-exclamation text-danger fs-3"></i>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-10">
                                    По этому отбору КП со скидками нет
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer py-3 fs-7 text-muted">
                Скидка считается по последнему созданному варианту последней редакции КП, по той же
                формуле, что в карточке: процент заказчику снимается с прайса, процент партнёру —
                с уже уменьшенной цены. В блоках «З» — скидка заказчику, «П» — партнёру, каждая
                от своего основания. Выделяются КП, где совокупная скидка выше
                {{ \App\Modules\Pub\Analytics\Services\DiscountAnalysisService::hardLimitP() }}%
                либо превышает средний уровень своего грейда более чем на
                {{ \App\Modules\Pub\Analytics\Services\DiscountAnalysisService::gradeAlertPp() }} п.п.
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        // партнёров много, поэтому в модалке оба списка с поиском
        $(document).ready(function () {
            var $modal = $('#discounts_filter_modal');

            $modal.find('select.discounts_select').each(function () {
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
