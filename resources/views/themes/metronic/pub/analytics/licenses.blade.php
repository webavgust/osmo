@extends('layouts.layout')

@section('styles')
    @parent
    <style>
        /* подряд подсвеченные строки сливаются в пятно — разделяем их линией цвета danger,
           как tr.transferred на /external-proposals */
        #licenses_table tr.bg-light-danger:has(+ tr.bg-light-danger) td { border-bottom-color: var(--bs-danger-300); }
    </style>
@endsection

@php
    // «Фильтр (n)»: горизонт не считаем — он стоит в тулбаре всегда, это главный
    // отбор страницы. «Только активные ключи» включено по умолчанию, поэтому
    // условием считается его выключение
    $rules_count = collect(['partner', 'q', 'hide_expired'])->filter(fn($key) => !empty($params[$key]))->count()
        + ($params['only_active'] ? 0 : 1);

    // горизонт уезжает в адрес как есть: пустая строка — «все лицензии»
    $horizon_value = (string) $params['horizon'];
@endphp

{{-- Тулбар страницы: горизонт + «Фильтр», как на «Скоринге партнёров» --}}
@section('breadcrumb_right')
    {{-- Горизонт всегда на виду; остальной отбор уезжает вместе с ним скрытыми полями --}}
    <form method="get" action="{{ route('analytics.licenses') }}" class="d-flex align-items-center">
        <input type="hidden" name="partner" value="{{ $params['partner'] }}"/>
        <input type="hidden" name="q" value="{{ $params['q'] }}"/>
        @if($params['only_active'])
            <input type="hidden" name="only_active" value="1"/>
        @endif
        @if($params['hide_expired'])
            <input type="hidden" name="hide_expired" value="1"/>
        @endif

        <select name="horizon" class="form-select w-auto fw-bold" onchange="this.form.submit()">
            <option value="expired" @selected($horizon_value === 'expired')>только истекшие</option>
            @foreach($horizons as $horizon)
                <option value="{{ $horizon }}" @selected($horizon_value === (string) $horizon)>
                    истекает в течение {{ $horizon }} дней
                </option>
            @endforeach
            <option value="" @selected($horizon_value === '')>все лицензии</option>
        </select>
    </form>

    <button type="button" data-bs-toggle="modal" data-bs-target="#licenses_filter_modal"
            class="btn btn-light-info fw-bold d-flex align-items-center">
        <i class="fa-light fa-filter"></i>
        Фильтр
        @if($rules_count)
            <span class="count filter-count">{{ $rules_count }}</span>
        @endif
    </button>

    @if($rules_count)
        {{-- горизонт остаётся, «только активные» возвращается к умолчанию --}}
        <a href="{{ route('analytics.licenses', ['horizon' => $horizon_value, 'only_active' => 1]) }}"
           class="me-2 text-dark-500 text-hover-dark">
            <i class="fa-light fa-xmark fs-5 me-2" aria-hidden="true"></i> Убрать
        </a>
    @endif
@endsection

@section('content')
    <div class="container-fluid">

        {{-- Отбор: живёт в модалке, в адресе остаётся обычной GET-строкой,
             поэтому ссылку с отбором можно передать --}}
        <div id="licenses_filter_modal" class="modal fade" tabindex="-1" aria-hidden="true">
            <form method="get" action="{{ route('analytics.licenses') }}">
                <input type="hidden" name="horizon" value="{{ $horizon_value }}"/>

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
                                    <select name="partner" class="form-select licenses_select" data-placeholder="все партнёры">
                                        <option value="">все партнёры</option>
                                        @foreach($partners as $partner)
                                            <option value="{{ $partner->id }}" @selected($params['partner'] == $partner->id)>{{ $partner->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-5">
                                <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Поиск</label>
                                <div class="col-sm-9">
                                    <input type="text" name="q" value="{{ $params['q'] }}" class="form-control"
                                           placeholder="ключ, компания, спецификация"/>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-9 offset-sm-3">
                                    <label class="form-check form-switch form-check-custom form-check-solid">
                                        <input type="checkbox" name="only_active" value="1" class="form-check-input"
                                               @checked($params['only_active'])/>
                                        <span class="form-check-label fw-semibold">только активные ключи</span>
                                    </label>

                                    {{-- горизонт включающий: истёкшие попадают в любой, поэтому их скрывают отдельно --}}
                                    <label class="form-check form-switch form-check-custom form-check-solid mt-4">
                                        <input type="checkbox" name="hide_expired" value="1" class="form-check-input"
                                               @checked($params['hide_expired'])/>
                                        <span class="form-check-label fw-semibold">скрыть истёкшие</span>
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

        {{-- Показатели --}}
        <div class="row g-4 mb-4">
            @php
                $cards = [
                    ['label' => 'Лицензий в выборке', 'value' => $totals['count'], 'sub' => $totals['companies'] . ' компаний', 'color' => 'dark'],
                    ['label' => 'Истекли', 'value' => $totals['expired'], 'sub' => tools()->cost_normalize(round($totals['expired_sum'])) . ' ₽ по спецификациям', 'color' => 'danger'],
                ];

                foreach($horizons as $horizon) {
                    $cards[] = [
                        'label' => 'Истекает ≤ ' . $horizon . ' дн.',
                        'value' => $totals['soon'][$horizon]['count'],
                        'sub' => tools()->cost_normalize(round($totals['soon'][$horizon]['sum'])) . ' ₽',
                        'color' => $horizon <= 30 ? 'danger' : ($horizon <= 60 ? 'warning' : 'primary'),
                    ];
                }

                $cards[] = ['label' => 'Действуют дальше', 'value' => $totals['later'], 'sub' => $totals['unknown'] . ' без срока', 'color' => 'success'];
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

        {{-- Таблица --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="m-0">Лицензии</h3>
                <span class="text-muted fs-7">Сортировка по дате окончания: ближайшее сверху</span>
            </div>

            <div class="table-responsive">
                <table id="licenses_table" class="table table-row-bordered align-middle m-0">
                    <thead>
                        <tr class="fw-bold fs-7 text-muted text-uppercase">
                            <th class="ps-4">Ключ</th>
                            <th>Партнёр → компания</th>
                            <th>Договор и спецификация</th>
                            <th class="text-center">Начало</th>
                            <th class="text-center">Окончание</th>
                            <th class="text-center">Осталось</th>
                            <th class="text-end pe-4">Сумма спецификации</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr @class(['bg-light-danger' => in_array($row['bucket'], ['expired', 'soon' . min($horizons)])])>
                                <td class="ps-4">
                                    <span class="fs-7 text-gray-800 text-break">{{ $row['code'] }}</span>
                                    @if(!$row['active'])
                                        <div class="fs-8 text-muted">ключ неактивен</div>
                                    @endif
                                </td>

                                {{-- партнёр → компания бейджами, как в списке КП и на «Анализе скидок» --}}
                                <td>
                                    @if(!empty($row['partner']['id']))
                                        <a href="{{ route('partner.detail', $row['partner']['id']) }}">
                                            <x-ui.badge.light type="info" class="text-info-700 bg-hover-info text-hover-white">
                                                {{ $row['partner']['name'] }}
                                            </x-ui.badge.light>
                                        </a>
                                    @endif
                                    @if(!empty($row['partner']['id']) && !empty($row['company']['id']))
                                        <span class="px-1 text-dark-800">--></span>
                                    @endif
                                    @if(!empty($row['company']['id']))
                                        <a href="{{ route('company.detail', $row['company']['id']) }}">
                                            <x-ui.badge.light type="primary" class="text-primary-700 bg-hover-primary text-hover-white">
                                                {{ $row['company']['name'] }}
                                            </x-ui.badge.light>
                                        </a>
                                    @endif
                                    @if(empty($row['partner']['id']) && empty($row['company']['id']))
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td>
                                    @if(!empty($row['contract']['id']))
                                        @php $type = \App\Modules\Pub\Contract\Models\ContractType::tryFrom((string) $row['contract']['type'])?->data(); @endphp
                                        <span class="text-{{ $type['color'] ?? 'dark' }} fw-bold">
                                            @if(!empty($type))
                                                <x-ui.icon.regular :icon="$type['icon']" class="me-1"/>{{ $type['label'] }}
                                            @endif
                                        </span>
                                        <code class="ms-1">{{ $row['contract']['number'] ?: 'б/н' }}</code>
                                    @endif
                                    <div class="fs-8 text-muted">
                                        {{ $row['spec']['name'] ?? 'без спецификации' }}
                                        @if($row['spec']['canceled'])
                                            <span class="text-danger">(отменена)</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="text-center text-nowrap">
                                    @if($row['active_from'])
                                        {{ $row['active_from']->format('d.m.Y') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-center text-nowrap">
                                    @if($row['active_to'])
                                        <span class="fw-bold">{{ $row['active_to']->format('d.m.Y') }}</span>
                                    @else
                                        <span class="text-muted">без срока</span>
                                    @endif
                                </td>

                                <td class="text-center text-nowrap">
                                    @if($row['days'] === null)
                                        <span class="text-muted">—</span>
                                    @elseif($row['days'] < 0)
                                        <span class="fw-bold text-danger">
                                            {{ \App\Modules\Pub\Analytics\Services\PartnerScoringService::humanPeriod(abs($row['days'])) }} назад
                                        </span>
                                    @else
                                        <span class="fw-bold">
                                            {{ \App\Modules\Pub\Analytics\Services\PartnerScoringService::humanPeriod($row['days']) }}
                                        </span>
                                    @endif
                                    <div class="fs-8">
                                        <x-ui.badge.light :type="$row['state']['color']">{{ $row['state']['label'] }}</x-ui.badge.light>
                                    </div>
                                </td>

                                <td class="text-end text-nowrap pe-4">
                                    @if($row['spec']['amount'] > 0)
                                        {{-- крупно всегда рубли; сумма в валюте — серым под ней, только у валютных спецификаций --}}
                                        <span class="fw-bold">{{ tools()->cost_normalize(round($row['amount_rub'])) }} ₽</span>
                                        @if($row['spec']['keys'] > 1)
                                            {{-- по спецификации несколько ключей: у ключа — его доля --}}
                                            <div class="fs-8 text-muted" title="Спецификация на {{ $row['spec']['keys'] }} ключа(ей): {{ tools()->cost_normalize(round($row['spec']['total'])) }} {{ $row['spec']['currency'] }}, сумма поделена поровну">
                                                1/{{ $row['spec']['keys'] }} спецификации
                                            </div>
                                        @endif
                                        @if(strtoupper((string) $row['spec']['currency']) !== 'RUB')
                                            <div class="fs-8 text-muted">
                                                {{ tools()->cost_normalize(round($row['spec']['amount'])) }} {{ $row['spec']['currency'] }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-10">
                                    По этому отбору лицензий нет
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        // партнёров много — в модалке список с поиском, как на «Скоринге партнёров»
        $(document).ready(function () {
            // модалка живёт в контенте — уводим в body, чтобы её не обрезал контекст наложения
            var $modal = $('#licenses_filter_modal').appendTo('body');

            $modal.find('select.licenses_select').each(function () {
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
