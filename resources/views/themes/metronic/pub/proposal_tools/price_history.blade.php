@extends('layouts.layout')

@section('content')
    @php
        $first = $rows->first();
        $last = $rows->last();
        $total_diff = (float) $last['value'] - (float) $first['value'];
        $total_diff_p = $first['value'] > 0 ? round($total_diff / $first['value'] * 100, 1) : null;

        /** Курс редакции строкой: «1 200 000 USD × 92,45» */
        $source = function ($row) {
            return tools()->cost_normalize(round($row['total'])) . ' ' . $row['currency']
                . ' × ' . number_format($row['rate'], 2, ',', ' ');
        };
    @endphp

    <div class="d-flex flex-column gap-6">

        {{-- Шапка --}}
        <div class="card">
            <div class="card-header min-h-auto py-5 border-bottom">
                <div class="card-title flex-column align-items-start">
                    <h2 class="fw-bold mb-2">{{ $proposal->name }}</h2>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <x-proposal.status :proposal="$proposal" editable="1"/>
                        @if($proposal->number)
                            <span class="badge badge-light">№ {{ $proposal->number }}</span>
                        @endif
                        <span class="badge badge-light fs-8">РЕДАКЦИЙ: {{ $rows->count() }}</span>

                        @if($mode['convert'])
                            <span class="badge badge-light-warning"
                                  title="Редакции в разных валютах — суммы приведены к рублям по курсу на сегодня">
                                {{ implode(' / ', $mode['currencies']) }} → ₽ по курсу на сегодня
                            </span>
                        @else
                            <span class="badge badge-light-primary fs-8">ВСЕ СУММЫ В {{ $mode['currency'] }}</span>
                        @endif
                    </div>
                </div>

                <div class="card-toolbar gap-2">
                    <a href="{{ route('deal_card.index', $proposal) }}" class="btn btn-sm btn-light">
                        <i class="fa-light fa-diagram-project fs-5 me-2"></i>Сводная информация
                    </a>
                    <a href="{{ route('proposal.detail', [$proposal, $proposal->iteration]) }}" class="btn btn-sm btn-light-primary">
                        <i class="fa-light fa-file-invoice fs-5 me-2"></i>Открыть КП
                    </a>
                </div>
            </div>

            <div class="card-body py-5">
                <div class="row g-5">
                    <div class="col-6 col-lg-3">
                        <div class="text-muted fs-7 fw-bold text-uppercase mb-1">Первая редакция</div>
                        <div class="fs-3">
                            <span class="fw-bold">{{ tools()->cost_normalize(round($first['value'])) }}</span>
                            <span class="fs-6 text-muted">{{ $mode['currency'] }}</span>
                        </div>
                        <div class="fs-7 text-muted">
                            #{{ $first['iteration'] }} · {{ $first['sended_at']?->format('d.m.Y') ?: '—' }}
                            @if($first['show_source'])
                                · {{ $source($first) }}
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="text-muted fs-7 fw-bold text-uppercase mb-1">Последняя редакция</div>
                        <div class="fs-3">
                            <span class="fw-bold">{{ tools()->cost_normalize(round($last['value'])) }}</span>
                            <span class="fs-6 text-muted">{{ $mode['currency'] }}</span>
                        </div>
                        <div class="fs-7 text-muted">
                            #{{ $last['iteration'] }} · {{ $last['sended_at']?->format('d.m.Y') ?: '—' }}
                            @if($last['show_source'])
                                · {{ $source($last) }}
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="text-muted fs-7 fw-bold text-uppercase mb-1">Изменение</div>
                        <div class="fs-3 text-{{ $total_diff > 0 ? 'success' : ($total_diff < 0 ? 'danger' : 'muted') }}">
                            <span class="fw-bold">{{ $total_diff > 0 ? '+' : '' }}{{ tools()->cost_normalize(round($total_diff)) }}</span>
                            <span class="fs-6 text-muted">{{ $mode['currency'] }}</span>
                        </div>
                        @if($total_diff_p !== null)
                            <div class="fs-7 fw-bold text-{{ $total_diff > 0 ? 'success' : ($total_diff < 0 ? 'danger' : 'muted') }}">{{ $total_diff_p > 0 ? '+' : '' }}{{ $total_diff_p }}%</div>
                        @endif
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="text-muted fs-7 fw-bold text-uppercase mb-1">Валюта расчёта</div>
                        <div class="fs-3 fw-bold">{{ $last['currency'] }}</div>
                    </div>
                </div>

                @if($mode['rate_unknown'])
                    <div class="alert alert-warning d-flex align-items-center mt-5 mb-0">
                        <i class="fa-light fa-triangle-exclamation fs-2 me-4"></i>
                        <div class="fs-7">
                            Для части редакций нет курса валюты — они посчитаны один к одному.
                            Обновите курсы (<span class="text-muted">CurrencyService::updateRates</span>).
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Итерации --}}
        <div class="card">
            <div class="card-header min-h-auto py-5 border-bottom">
                <div class="card-title flex-column align-items-start">
                    <h4 class="fw-bold mb-1">Цена по редакциям</h4>
                    <span class="text-muted fs-7">
                        Суммы основного варианта каждой редакции
                        @if($mode['convert'])
                            приведены к рублям по курсу на сегодня — иначе Δ считалась бы
                            между рублями и валютой. Под каждой пересчитанной суммой серым — исходная.
                        @else
                            в валюте расчёта ({{ $mode['currency'] }}): все редакции в одной валюте, пересчёт не нужен.
                        @endif
                        Δ — изменение итога к предыдущей редакции.
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 align-middle mb-0">
                        <thead>
                        <tr class="fw-bold text-muted text-uppercase fs-7 bg-light">
                            <th class="ps-5" width="90">Редакция</th>
                            <th width="120" class=" text-center">Отправлено</th>
                            <th width="130">Период</th>
                            @foreach($blocks as $block)
                                <th class="text-end">{{ $block['label'] }}</th>
                            @endforeach
                            <th class="text-end">НДС</th>
                            <th class="text-end">Итого, {{ $mode['currency'] }}</th>
                            <th width="150">Валюта и курс</th>
                            <th class="text-end pe-5" width="140">Δ</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($rows as $row)
                            <tr @class(['fs-6', 'bg-light-primary' => $row['iteration'] === $last['iteration']])>
                                <td class="ps-5 text-center">
                                    <a href="{{ route('proposal.detail', [$row['proposal'], $row['iteration']]) }}"
                                       class="fw-bold">#{{ $row['iteration'] }}</a>
                                </td>
                                <td class="text-nowrap text-center">{{ $row['sended_at']?->format('d.m.Y') ?: '—' }}</td>
                                <td>{{ $row['period'] }}</td>

                                @foreach($blocks as $code => $block)
                                    <td class="text-end">
                                        @if($row['blocks_value'][$code])
                                            {{ tools()->cost_normalize(round($row['blocks_value'][$code])) }}
                                            {{-- сумма до пересчёта: видно, из чего получился рублёвый итог --}}
                                            @if($row['show_source'])
                                                <div class="fs-7 text-muted">
                                                    {{ tools()->cost_normalize(round($row['blocks'][$code])) }} {{ $row['currency'] }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach

                                <td class="text-end">
                                    @if($row['nds_value'])
                                        {{ tools()->cost_normalize(round($row['nds_value'])) }}
                                        @if($row['show_source'])
                                            <div class="fs-7 text-muted">
                                                {{ tools()->cost_normalize(round($row['nds'])) }} {{ $row['currency'] }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <span class="fw-bold">{{ tools()->cost_normalize(round($row['value'])) }}</span>
                                    @if($row['show_source'])
                                        <div class="fs-7 text-muted">
                                            {{ tools()->cost_normalize(round($row['total'])) }} {{ $row['currency'] }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <span class="fs-7">{{ $row['currency'] }}</span>
                                    @if($row['show_source'])
                                        <div class="fs-7 text-muted text-nowrap">
                                            курс {{ number_format($row['rate'], 2, ',', ' ') }}
                                            @if($row['rate_unknown'])
                                                <span class="text-warning">курса нет</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <td class="text-end pe-5 text-nowrap">
                                    @if($row['diff'] === null)
                                        <span class="text-muted">—</span>
                                    @elseif(abs($row['diff']) < 1)
                                        <span class="badge badge-light fs-7">без изменений</span>
                                    @else
                                        <span class="badge badge-light-{{ $row['diff'] > 0 ? 'success' : 'danger' }} fs-7">
                                            {{ $row['diff'] > 0 ? '+' : '' }}{{ tools()->cost_normalize(round($row['diff'])) }}
                                            @if($row['diff_p'] !== null)
                                                <span class="ms-1">({{ $row['diff_p'] > 0 ? '+' : '' }}{{ $row['diff_p'] }}%)</span>
                                            @endif
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Что изменилось в позициях --}}
        <div class="card">
            <div class="card-header min-h-auto py-5 border-bottom">
                <div class="card-title flex-column align-items-start">
                    <h4 class="fw-bold mb-1">Что изменилось в позициях</h4>
                    <span class="text-muted fs-7">
                        Позиции сопоставлены по названию внутри блока, считаем по основному варианту.
                        @if($diff_convert)
                            Редакции в разных валютах
                            ({{ $from_row['currency'] }} и {{ $to_row['currency'] }}) —
                            суммы приведены к рублям по курсу на сегодня.
                        @else
                            Обе редакции в {{ $diff_currency }}, пересчёт не нужен.
                        @endif
                    </span>
                </div>

                <div class="card-toolbar">
                    <form method="get" class="d-flex align-items-center gap-2">
                        <select name="from" class="form-select form-select-sm form-select-solid w-150px">
                            @foreach($rows as $row)
                                <option value="{{ $row['iteration'] }}" @selected($row['iteration'] == $from)>
                                    редакция {{ $row['iteration'] }} ({{ $row['currency'] }})
                                </option>
                            @endforeach
                        </select>

                        <i class="fa-light fa-arrow-right text-muted"></i>

                        <select name="to" class="form-select form-select-sm form-select-solid w-150px">
                            @foreach($rows as $row)
                                <option value="{{ $row['iteration'] }}" @selected($row['iteration'] == $to)>
                                    редакция {{ $row['iteration'] }} ({{ $row['currency'] }})
                                </option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn btn-sm btn-primary">Сравнить</button>
                    </form>
                </div>
            </div>

            <div class="card-body p-0">
                @if($diff->isEmpty())
                    <div class="text-center text-muted py-10">
                        @if($from == $to)
                            Выберите две разные редакции
                        @else
                            Позиций для сравнения нет
                        @endif
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle mb-0">
                            <thead>
                            <tr class="fw-bold text-muted bg-light text-uppercase">
                                <th class="ps-5" width="130">Блок</th>
                                <th>Позиция</th>
                                <th class="text-end" width="190">
                                    Редакция {{ $from }}, {{ $diff_currency }}
                                    @if($diff_convert)
                                        <div class="fs-8 fw-normal text-gray-500">
                                            из {{ $from_row['currency'] }} × {{ number_format($from_row['rate'], 2, ',', ' ') }}
                                        </div>
                                    @endif
                                </th>
                                <th class="text-end" width="190">
                                    Редакция {{ $to }}, {{ $diff_currency }}
                                    @if($diff_convert)
                                        <div class="fs-8 fw-normal text-gray-500">
                                            из {{ $to_row['currency'] }} × {{ number_format($to_row['rate'], 2, ',', ' ') }}
                                        </div>
                                    @endif
                                </th>
                                <th class="text-end" width="150">Δ</th>
                                <th class="pe-5" width="150">Изменение</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($diff as $item)
                                @php
                                    $state = match($item['state']) {
                                        'added' => ['label' => 'новая позиция', 'color' => 'success'],
                                        'removed' => ['label' => 'убрана', 'color' => 'danger'],
                                        'changed' => ['label' => 'цена изменилась', 'color' => 'warning'],
                                        default => ['label' => 'без изменений', 'color' => 'light'],
                                    };
                                @endphp
                                <tr @class(['fs-6', 'opacity-75' => $item['state'] === 'same'])>
                                    <td class="ps-5 text-muted">{{ $blocks[$item['block']]['label'] ?? $item['block'] }}</td>

                                    <td>
                                        <div class="fw-semibold" style="word-break: break-word;">{{ $item['label'] }}</div>
                                    </td>

                                    <td class="text-end">
                                        @if($item['from'])
                                            <div class="fw-bold">
                                                {{ tools()->cost_normalize(round($item['from']['value'])) }}
                                            </div>
                                            <div class="fs-7 text-muted">
                                                {{ (int) $item['from']['count'] }} ×
                                                {{ tools()->cost_normalize(round($item['from']['cost'])) }}
                                                {{ $from_row['currency'] }}
                                                @if($item['from']['discount'])
                                                    · −{{ round($item['from']['discount']) }}%
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        @if($item['to'])
                                            <div class="fw-bold">
                                                {{ tools()->cost_normalize(round($item['to']['value'])) }}
                                            </div>
                                            <div class="fs-7 text-muted">
                                                {{ (int) $item['to']['count'] }} ×
                                                {{ tools()->cost_normalize(round($item['to']['cost'])) }}
                                                {{ $to_row['currency'] }}
                                                @if($item['to']['discount'])
                                                    · −{{ round($item['to']['discount']) }}%
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td class="text-end text-nowrap">
                                        @if(abs($item['diff']) < 1)
                                            <span class="text-muted">—</span>
                                        @else
                                            <span class="fw-bold text-{{ $item['diff'] > 0 ? 'success' : 'danger' }}">
                                                @if($item['diff'] > 0)
                                                    + {{ tools()->cost_normalize(round($item['diff'])) }}
                                                @else
                                                    &ndash; {{ tools()->cost_normalize(round(abs($item['diff']))) }}
                                                @endif
                                            </span>
                                            @if($item['diff_p'] !== null)
                                                <div class="fs-7 text-muted">
                                                    {{ $item['diff_p'] > 0 ? '+' : '' }}{{ $item['diff_p'] }}%
                                                </div>
                                            @endif
                                        @endif
                                    </td>

                                    <td class="pe-5">
                                        <span class="badge badge-light-{{ $state['color'] }} fs-7">{{ $state['label'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>

                            {{-- Итого по сравнению --}}
                            <tfoot>
                            <tr class="fw-bold border-top border-gray-300">
                                <td class="ps-5 fs-5" colspan="2">
                                    ИТОГО по позициям
                                    <div class="fs-7 fw-normal text-muted">
                                        новых {{ $diff_total['added'] }} ·
                                        убрано {{ $diff_total['removed'] }} ·
                                        изменено {{ $diff_total['changed'] }}
                                    </div>
                                </td>
                                <td class="text-end text-nowrap">
                                    <span class="fs-4">{{ tools()->cost_normalize(round($diff_total['from'])) }}</span>
                                    <span class="text-muted fs-6 fw-normal ms-1">{{ $diff_currency }}</span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <span class="fs-4">{{ tools()->cost_normalize(round($diff_total['to'])) }}</span>
                                    <span class="text-muted fs-6 fw-normal ms-1">{{ $diff_currency }}</span>
                                </td>
                                <td class="text-end text-nowrap text-{{ $diff_total['diff'] > 0 ? 'success' : ($diff_total['diff'] < 0 ? 'danger' : 'muted') }}">
                                    <span class="fs-4">{{ $diff_total['diff'] > 0 ? '+' : '' }}{{ tools()->cost_normalize(round($diff_total['diff'])) }}</span>
                                    @if($diff_total['diff_p'] !== null)
                                        <div class="fs-8 fw-normal text-muted">
                                            {{ $diff_total['diff_p'] > 0 ? '+' : '' }}{{ $diff_total['diff_p'] }}%
                                        </div>
                                    @endif
                                </td>
                                <td class="pe-5">
                                    @if($diff_convert)
                                        <span class="badge badge-light-warning">приведено к рублям</span>
                                    @endif
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection
