@extends('layouts.layout')

@section('content')
    @php
        $first = $rows->first();
        $last = $rows->last();
        $total_diff = (float) $last['total'] - (float) $first['total'];
        $total_diff_p = $first['total'] > 0 ? round($total_diff / $first['total'] * 100, 1) : null;
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
                        <span class="badge badge-light">редакций: {{ $rows->count() }}</span>
                    </div>
                </div>

                <div class="card-toolbar gap-2">
                    <a href="{{ route('deal_card.index', $proposal) }}" class="btn btn-sm btn-light">
                        <i class="fa-light fa-diagram-project fs-5 me-2"></i>Цепочка
                    </a>
                    <a href="{{ route('proposal.detail', [$proposal, $proposal->iteration]) }}" class="btn btn-sm btn-light-primary">
                        <i class="fa-light fa-file-invoice fs-5 me-2"></i>Открыть КП
                    </a>
                </div>
            </div>

            <div class="card-body py-5">
                <div class="row g-5">
                    <div class="col-6 col-lg-3">
                        <div class="text-muted fs-8 text-uppercase mb-1">Первая редакция</div>
                        <div class="fs-3 fw-bold">{{ tools()->cost_normalize(round($first['total'])) }}</div>
                        <div class="fs-8 text-muted">
                            #{{ $first['iteration'] }} · {{ $first['sended_at']?->format('d.m.Y') ?: '—' }}
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="text-muted fs-8 text-uppercase mb-1">Последняя редакция</div>
                        <div class="fs-3 fw-bold">{{ tools()->cost_normalize(round($last['total'])) }}</div>
                        <div class="fs-8 text-muted">
                            #{{ $last['iteration'] }} · {{ $last['sended_at']?->format('d.m.Y') ?: '—' }}
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="text-muted fs-8 text-uppercase mb-1">Изменение</div>
                        <div class="fs-3 fw-bold text-{{ $total_diff > 0 ? 'success' : ($total_diff < 0 ? 'danger' : 'muted') }}">
                            {{ $total_diff > 0 ? '+' : '' }}{{ tools()->cost_normalize(round($total_diff)) }}
                        </div>
                        @if($total_diff_p !== null)
                            <div class="fs-8 text-muted">{{ $total_diff_p > 0 ? '+' : '' }}{{ $total_diff_p }}%</div>
                        @endif
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="text-muted fs-8 text-uppercase mb-1">Валюта</div>
                        <div class="fs-3 fw-bold">{{ $last['currency'] }}</div>
                        <div class="fs-8 text-muted">{{ $last['period'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Итерации --}}
        <div class="card">
            <div class="card-header min-h-auto py-5 border-bottom">
                <div class="card-title flex-column align-items-start">
                    <h4 class="fw-bold mb-1">Цена по редакциям</h4>
                    <span class="text-muted fs-7">
                        Суммы основного варианта каждой редакции. Δ — изменение итога к предыдущей редакции.
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 align-middle mb-0">
                        <thead>
                        <tr class="fw-bold text-muted bg-light">
                            <th class="ps-5" width="90">Редакция</th>
                            <th width="120">Отправлено</th>
                            <th width="140">Период</th>
                            @foreach($blocks as $block)
                                <th class="text-end">{{ $block['label'] }}</th>
                            @endforeach
                            <th class="text-end">НДС</th>
                            <th class="text-end">Итого</th>
                            <th class="text-end pe-5" width="140">Δ</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($rows as $row)
                            <tr @class(['bg-light-primary' => $row['iteration'] === $last['iteration']])>
                                <td class="ps-5">
                                    <a href="{{ route('proposal.detail', [$row['proposal'], $row['iteration']]) }}"
                                       class="fw-bold">#{{ $row['iteration'] }}</a>
                                </td>
                                <td class="text-nowrap">{{ $row['sended_at']?->format('d.m.Y') ?: '—' }}</td>
                                <td class="fs-7">{{ $row['period'] }}</td>

                                @foreach($blocks as $code => $block)
                                    <td class="text-end">
                                        @if($row['blocks'][$code])
                                            {{ tools()->cost_normalize(round($row['blocks'][$code])) }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach

                                <td class="text-end">
                                    @if($row['nds'])
                                        {{ tools()->cost_normalize(round($row['nds'])) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-end fw-bold">{{ tools()->cost_normalize(round($row['total'])) }}</td>

                                <td class="text-end pe-5 text-nowrap">
                                    @if($row['diff'] === null)
                                        <span class="text-muted">—</span>
                                    @elseif(abs($row['diff']) < 1)
                                        <span class="badge badge-light">без изменений</span>
                                    @else
                                        <span class="badge badge-light-{{ $row['diff'] > 0 ? 'success' : 'danger' }}">
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
                        Позиции сопоставлены по названию внутри блока. Считаем по основному варианту.
                    </span>
                </div>

                <div class="card-toolbar">
                    <form method="get" class="d-flex align-items-center gap-2">
                        <select name="from" class="form-select form-select-sm form-select-solid w-125px">
                            @foreach($rows as $row)
                                <option value="{{ $row['iteration'] }}" @selected($row['iteration'] == $from)>
                                    редакция {{ $row['iteration'] }}
                                </option>
                            @endforeach
                        </select>

                        <i class="fa-light fa-arrow-right text-muted"></i>

                        <select name="to" class="form-select form-select-sm form-select-solid w-125px">
                            @foreach($rows as $row)
                                <option value="{{ $row['iteration'] }}" @selected($row['iteration'] == $to)>
                                    редакция {{ $row['iteration'] }}
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
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-5" width="140">Блок</th>
                                <th>Позиция</th>
                                <th class="text-end" width="180">Редакция {{ $from }}</th>
                                <th class="text-end" width="180">Редакция {{ $to }}</th>
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
                                <tr @class(['opacity-75' => $item['state'] === 'same'])>
                                    <td class="ps-5 fs-7 text-muted">{{ $blocks[$item['block']]['label'] ?? $item['block'] }}</td>

                                    <td>
                                        <div class="fw-semibold" style="word-break: break-word;">{{ $item['label'] }}</div>
                                    </td>

                                    <td class="text-end">
                                        @if($item['from'])
                                            <div class="fw-semibold">{{ tools()->cost_normalize(round($item['from']['total'])) }}</div>
                                            <div class="fs-8 text-muted">
                                                {{ (int) $item['from']['count'] }} × {{ tools()->cost_normalize(round($item['from']['cost'])) }}
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
                                            <div class="fw-semibold">{{ tools()->cost_normalize(round($item['to']['total'])) }}</div>
                                            <div class="fs-8 text-muted">
                                                {{ (int) $item['to']['count'] }} × {{ tools()->cost_normalize(round($item['to']['cost'])) }}
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
                                            <span class="fw-semibold text-{{ $item['diff'] > 0 ? 'success' : 'danger' }}">
                                                {{ $item['diff'] > 0 ? '+' : '' }}{{ tools()->cost_normalize(round($item['diff'])) }}
                                            </span>
                                            @if($item['diff_p'] !== null)
                                                <div class="fs-8 text-muted">
                                                    {{ $item['diff_p'] > 0 ? '+' : '' }}{{ $item['diff_p'] }}%
                                                </div>
                                            @endif
                                        @endif
                                    </td>

                                    <td class="pe-5">
                                        <span class="badge badge-light-{{ $state['color'] }}">{{ $state['label'] }}</span>
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
