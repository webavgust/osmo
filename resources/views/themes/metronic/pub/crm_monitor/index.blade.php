@extends('layouts.layout')

@php
    // «Фильтр (n)»: вид расхождения выбирается карточками, поиск стоит в шапке списка —
    // считаем только то, что в модалке. «Все КП» — отход от умолчания «только расхождения»
    $rules_count = collect(['status', 'manager'])->filter(fn($key) => !empty($params[$key]))->count()
        + ($params['only_issues'] ? 0 : 1);
@endphp

{{-- Тулбар страницы: «Фильтр» и «Убрать», как на остальных списках --}}
@section('breadcrumb_right')
    <button type="button" data-bs-toggle="modal" data-bs-target="#crm_monitor_filter_modal"
            class="btn btn-light-info fw-bold d-flex align-items-center">
        <i class="fa-light fa-filter"></i>
        Фильтр
        @if($rules_count)
            <span class="count filter-count">{{ $rules_count }}</span>
        @endif
    </button>

    @if($rules_count)
        {{-- вид расхождения и поиск остаются: убираем только то, что стоит в модалке --}}
        <a href="{{ route('crm_monitor.index', array_filter(['issue' => $params['issue'], 'q' => $params['q']])) }}"
           class="me-2 text-dark-500 text-hover-dark">
            <i class="fa-light fa-xmark fs-5 me-2" aria-hidden="true"></i> Убрать
        </a>
    @endif
@endsection

@section('content')
    {{-- Отбор: живёт в модалке, в адресе остаётся обычной GET-строкой --}}
    <div id="crm_monitor_filter_modal" class="modal fade" tabindex="-1" aria-hidden="true">
        <form method="get" action="{{ route('crm_monitor.index') }}">
            @if($params['issue'])
                <input type="hidden" name="issue" value="{{ $params['issue'] }}"/>
            @endif
            @if($params['q'] !== '')
                <input type="hidden" name="q" value="{{ $params['q'] }}"/>
            @endif

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
                            <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Статус КП</label>
                            <div class="col-sm-9">
                                <select name="status" class="form-select crm_monitor_select" data-placeholder="все статусы">
                                    <option value="">все статусы</option>
                                    @foreach($statuses as $code => $status)
                                        <option value="{{ $code }}" @selected($params['status'] === $code)>{{ $status['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mb-5">
                            <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Менеджер</label>
                            <div class="col-sm-9">
                                <select name="manager" class="form-select crm_monitor_select" data-placeholder="все менеджеры">
                                    <option value="">все менеджеры</option>
                                    @foreach($managers as $manager)
                                        <option value="{{ $manager->id }}" @selected($params['manager'] == $manager->id)>{{ $manager->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-9 offset-sm-3">
                                <label class="form-check form-switch form-check-custom form-check-solid">
                                    <input type="checkbox" name="all" value="1" class="form-check-input"
                                           @checked(!$params['only_issues'])/>
                                    <span class="form-check-label fw-semibold">все КП, в том числе где всё сходится</span>
                                </label>
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
                По {{ $money['count'] }} КП суммы в Битрикс24 и на портале расходятся на
                <b>{{ $money['diff'] > 0 ? '+' : '' }}{{ tools()->cost_normalize(round($money['diff'])) }}</b>
                (в Битрикс24 {{ tools()->cost_normalize(round($money['deals_total'])) }},
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

                {{-- Поиск — в шапке списка; остальной отбор уезжает с ним скрытыми полями --}}
                <div class="card-toolbar">
                    <form method="get" action="{{ route('crm_monitor.index') }}">
                        @if($params['issue'])
                            <input type="hidden" name="issue" value="{{ $params['issue'] }}"/>
                        @endif
                        @if($params['status'])
                            <input type="hidden" name="status" value="{{ $params['status'] }}"/>
                        @endif
                        @if($params['manager'])
                            <input type="hidden" name="manager" value="{{ $params['manager'] }}"/>
                        @endif
                        @if(!$params['only_issues'])
                            <input type="hidden" name="all" value="1"/>
                        @endif

                        <div class="position-relative">
                            <i class="fa-light fa-magnifying-glass position-absolute top-50 translate-middle-y ms-4 text-gray-500"></i>
                            <input type="text" name="q" value="{{ $params['q'] }}"
                                   class="form-control form-control-sm form-control-solid ps-11 w-225px"
                                   placeholder="КП, номер, компания" />
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-body p-0">
                @if($rows->isEmpty())
                    <div class="text-center text-muted py-10 fs-4">
                        Расхождений нет — портал и Битрикс24 сходятся
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle mb-0">
                            <thead>
                            <tr class="fw-bold text-muted bg-light fs-7">
                                {{-- место отдаём названию КП: служебные колонки сжимаются по содержимому
                                     (width="1%" + nowrap), сделкам — разумный минимум --}}
                                <th class="ps-5 min-w-250px">КП</th>
                                <th class="text-nowrap" width="1%">Статус</th>
                                <th class="min-w-200px">Сделки Битрикс24</th>
                                <th class="text-end text-nowrap" width="1%">В КП</th>
                                <th class="text-end text-nowrap" width="1%">В Битрикс24</th>
                                <th class="text-end text-nowrap" width="1%">Расхождение</th>
                                <th class="pe-5 text-nowrap" width="1%">Что не так</th>
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

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            // модалка живёт в контенте — уводим в body, чтобы её не обрезал контекст наложения
            var $modal = $('#crm_monitor_filter_modal').appendTo('body');

            $modal.find('select.crm_monitor_select').each(function () {
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
