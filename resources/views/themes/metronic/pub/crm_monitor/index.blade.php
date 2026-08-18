@extends('layouts.layout')

@section('content')
    @php
        $link = function (array $extra = []) use ($params) {
            $query = array_merge([
                'issue' => $params['issue'],
                'status' => $params['status'],
                'manager' => $params['manager'],
                'q' => $params['q'],
                'all' => $params['only_issues'] ? null : 1,
            ], $extra);

            return route('crm_monitor.index', array_filter($query, fn($value) => $value !== null && $value !== ''));
        };
    @endphp

    <div class="d-flex flex-column gap-6">

        {{-- Виды расхождений --}}
        <div class="row g-4">
            @foreach($issues as $code => $issue)
                <div class="col-6 col-lg-4 col-xxl-2">
                    <a href="{{ $link(['issue' => $params['issue'] === $code ? null : $code]) }}"
                       class="card h-100 border-0 text-decoration-none {{ $params['issue'] === $code ? 'bg-light-primary' : 'bg-light' }}"
                       title="{{ $issue['hint'] }}">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <i class="fa-light {{ $issue['icon'] }} fs-2 text-{{ $issue['color'] }}"></i>
                                <span class="fs-2hx fw-bold text-gray-900">{{ $counters[$code] }}</span>
                            </div>
                            <div class="fs-5 fw-bold text-gray-800">{{ $issue['label'] }}</div>
                            <div class="fs-7 text-muted mt-auto pt-2">{{ $issue['hint'] }}</div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        @if($params['issue'] == 'amount' && $money['count'])
            <x-ui.notification.regular type="danger" class="fs-6 mb-0">
                <i class="fa-light fa-scale-unbalanced fs-2 text-danger me-4"></i>
                По {{ $money['count'] }} КП суммы в Битриксе и на портале расходятся на
                <b>{{ $money['diff'] > 0 ? '+' : '' }}{{ tools()->cost_normalize(round($money['diff'])) }}</b>
                (в Битриксе {{ tools()->cost_normalize(round($money['deals_total'])) }},
                в КП {{ tools()->cost_normalize(round($money['proposal_total'])) }}).
            </x-ui.notification.regular>
        @endif

        {{-- Список --}}
        <div class="card">
            <div class="card-header min-h-auto py-5 border-bottom">
                <div class="card-title flex-column align-items-start">
                    <h4 class="fw-bold mb-1">
                        {{ $params['issue'] ? $issues[$params['issue']]['label'] : 'Все расхождения' }}
                    </h4>
                    <span class="text-muted fs-7">
                        Найдено: {{ $rows->count() }}. Сверяется последний созданный вариант последней редакции КП.
                    </span>
                </div>

                <div class="card-toolbar">
                    <form method="get" class="d-flex flex-wrap ">
                        <div class="d-flex justify-content-start gap-3 align-items-center">
                            @if($params['issue'])
                                <input type="hidden" name="issue" value="{{ $params['issue'] }}" />
                            @endif

                            <div class="position-relative">
                                <i class="fa-light fa-magnifying-glass position-absolute top-50 translate-middle-y ms-4 text-gray-500"></i>
                                <input type="text" name="q" value="{{ $params['q'] }}"
                                       class="form-control form-control-sm form-control-solid ps-11 w-225px"
                                       placeholder="КП, номер, компания" />
                            </div>

                            <select name="status" class="form-select form-select-sm form-select-solid w-160px">
                                    <option value="">Все статусы</option>
                                    @foreach($statuses as $code => $status)
                                        <option value="{{ $code }}" @selected($params['status'] === $code)>{{ $status['label'] }}</option>
                                    @endforeach
                            </select>

                            <select name="manager" class="form-select form-select-sm form-select-solid w-175px">
                                <option value="">Все менеджеры</option>
                                @foreach($managers as $manager)
                                    <option value="{{ $manager->id }}" @selected($params['manager'] == $manager->id)>
                                        {{ $manager->name }}
                                    </option>
                                @endforeach
                            </select>

                            <label class="form-check form-check-custom form-check-solid form-check-sm"
                                   title="Показать и те КП, где всё сходится">
                                <input class="form-check-input" type="checkbox" name="all" value="1"
                                       @checked(!$params['only_issues']) />
                                <span class="form-check-label fs-8 text-nowrap">все КП</span>
                            </label>

                            <button type="submit" class="btn btn-sm btn-primary text-nowrap">
                                <i class="fa-light fa-filter fs-6 me-2"></i> Применить
                            </button>

                            @if($params['issue'] || $params['status'] || $params['manager'] || $params['q'] || !$params['only_issues'])
                                <a href="{{ route('crm_monitor.index') }}" class="btn btn-sm btn-light text-nowrap">
                                    <i class="fa-light fa-xmark fs-6 me-2"></i>
                                    Сбросить
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-body p-0">
                @if($rows->isEmpty())
                    <div class="text-center text-muted py-10 fs-4">
                        Расхождений нет — портал и Битрикс сходятся
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle mb-0">
                            <thead>
                            <tr class="fw-bold text-muted bg-light fs-7">
                                <th class="ps-5">КП</th>
                                <th width="140">Статус</th>
                                <th>Сделки Битрикс24</th>
                                <th class="text-end" width="150">В КП</th>
                                <th class="text-end" width="150">В Битриксе</th>
                                <th class="text-end" width="140">Расхождение</th>
                                <th class="pe-5" width="260">Что не так</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rows as $row)
                                @php $proposal = $row['proposal']; @endphp
                                <tr>
                                    <td class="ps-5">
                                        <a href="{{ route('deal_card.index', $proposal) }}"
                                           class="fw-semibold text-gray-900 text-hover-primary d-block fs-5"
                                           title="Сводная информация по сделке">
                                            {{ $proposal->name }}
                                        </a>
                                        <div class="fs-8 text-muted">
                                            @if($proposal->number)
                                                № {{ $proposal->number }} ·
                                            @endif
                                            {{ $proposal->company?->name ?: 'без компании' }}
                                        </div>
                                        @if($proposal->manager?->full_name)
                                            <div class="fs-8 text-muted">{{ $proposal->manager->full_name }}</div>
                                        @endif
                                    </td>

                                    <td>
                                        @if($row['status'])
                                            <span class="fs-7 badge badge-light-{{ $row['status']->data()['color'] }}">
                                                <i class="fa-light {{ $row['status']->data()['icon'] }} fs- me-2"></i>
                                                {{ $row['status']->data()['label'] }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($row['links']->isEmpty())
                                            <span class="text-muted fs-7">не привязаны</span>
                                        @else
                                            @foreach($row['links'] as $link_row)
                                                <div class="fs-7 mb-1">
                                                    @if($link_row->is_main)
                                                        <span class="badge badge-light-danger fs-7" title="Главная сделка">#{{ $link_row->crm_deal_id }}</span>
                                                    @else
                                                        <span class="fw-semibold ms-2">#{{ $link_row->crm_deal_id }}</span>
                                                    @endif
                                                    <span class="text-muted ms-1">
                                                        {{ \Illuminate\Support\Str::limit($link_row->deal?->title ?: '—', 45) }}
                                                    </span>
                                                    @if($link_row->error)
                                                        <div class="fs-8 text-danger">
                                                            <i class="fa-light fa-triangle-exclamation fs-8 me-1"></i>{{ $link_row->error }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endif
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <span class="fw-bold fs-5">{{ tools()->cost_normalize(round($row['proposal_total'])) }}</span>
                                        <span class="text-muted fs-7 ms-1">{{ $row['currency'] }}</span>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        @if($row['links']->isEmpty())
                                            <span class="text-muted">—</span>
                                        @else
                                            <span class="fw-bold fs-5">{{ tools()->cost_normalize(round($row['deals_total'])) }}</span>
                                        @endif
                                    </td>

                                    <td class="text-end text-nowrap fs-5">
                                        @if($row['links']->isEmpty() || abs($row['diff']) < 1)
                                            <span class="text-muted">—</span>
                                        @else
                                            <span class="fw-bold text-danger">
                                                {{ $row['diff'] > 0 ? '+' : '' }}{{ tools()->cost_normalize(round($row['diff'])) }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="pe-5">
                                        <div class="d-flex flex-column gap-1">
                                            @foreach($row['issues'] as $code => $message)
                                                <div>
                                                    <span class="badge badge-light-{{ $issues[$code]['color'] }} fs-7">
                                                        {{ $issues[$code]['label'] }}
                                                    </span>
                                                </div>
                                            @endforeach

                                            @if(empty($row['issues']))
                                                <span class="badge badge-light-success fs-9">всё сходится</span>
                                            @endif
                                        </div>
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
