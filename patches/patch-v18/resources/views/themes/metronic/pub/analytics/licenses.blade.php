@extends('layouts.layout')

@section('content')
    <div class="container-fluid">

        {{-- Отбор --}}
        <div class="card mb-4">
            <div class="card-body py-4">
                <form method="get" action="{{ route('analytics.licenses') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-auto">
                            <label class="form-label fs-8 text-muted mb-1">Горизонт</label>
                            <select name="horizon" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="expired" @selected($params['horizon'] === 'expired')>только истекшие</option>
                                @foreach($horizons as $horizon)
                                    <option value="{{ $horizon }}" @selected((string) $params['horizon'] === (string) $horizon)>
                                        истекает в течение {{ $horizon }} дней
                                    </option>
                                @endforeach
                                <option value="" @selected(empty($params['horizon']))>все лицензии</option>
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

                        <div class="col-3">
                            <label class="form-label fs-8 text-muted mb-1">Поиск</label>
                            <input type="text" name="q" value="{{ $params['q'] }}" class="form-control form-control-sm"
                                   placeholder="ключ, компания, спецификация">
                        </div>

                        <div class="col-auto">
                            <label class="form-check form-check-sm form-check-custom">
                                <input type="checkbox" name="only_active" value="1" class="form-check-input"
                                       @checked($params['only_active']) onchange="this.form.submit()">
                                <span class="form-check-label fs-7">только активные ключи</span>
                            </label>
                        </div>

                        <div class="col-auto ms-auto">
                            <button type="submit" class="btn btn-sm btn-primary">Показать</button>
                            <a href="{{ route('analytics.licenses') }}" class="btn btn-sm btn-light ms-1">Сбросить</a>
                        </div>
                    </div>
                </form>
            </div>
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
                <table class="table table-row-bordered align-middle m-0">
                    <thead>
                        <tr class="fw-bold fs-7 text-muted text-uppercase">
                            <th class="ps-4">Ключ</th>
                            <th>Компания</th>
                            <th>Партнёр</th>
                            <th>Договор и спецификация</th>
                            <th class="text-center">Период</th>
                            <th class="text-center">Осталось</th>
                            <th class="text-end pe-4">Сумма спецификации</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr @class(['bg-light-danger' => in_array($row['bucket'], ['expired', 'soon30'])])>
                                <td class="ps-4">
                                    <code class="fs-6">{{ $row['code'] }}</code>
                                    @if(!$row['active'])
                                        <div class="fs-8 text-muted">ключ неактивен</div>
                                    @endif
                                </td>

                                <td>
                                    @if(!empty($row['company']['id']))
                                        <a href="{{ route('company.detail', $row['company']['id']) }}" class="fw-bold">
                                            {{ $row['company']['name'] }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td>
                                    @if(!empty($row['partner']['id']))
                                        @php $grade = \App\Modules\Pub\Partner\Models\PartnerGrade::tryFrom((string) $row['partner']['grade'])?->data(); @endphp
                                        <a href="{{ route('partner.detail', $row['partner']['id']) }}"
                                           style="color: {{ $grade['color']['medal'] ?? '#7e8299' }}">
                                            <x-ui.icon.solid icon="fa-medal" class="me-1"/>{{ $row['partner']['name'] }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td>
                                    @if(!empty($row['contract']['id']))
                                        @php $type = \App\Modules\Pub\Contract\Models\ContractType::tryFrom((string) $row['contract']['type'])?->data(); @endphp
                                        <span class="text-{{ $type['color'] ?? 'dark' }} fw-bold">
                                            {{ $type['label'] ?? '' }}
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
                                    @endif
                                    <x-ui.icon.regular icon="fa-dash"/>
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
                                        {{ tools()->cost_normalize(round($row['spec']['amount'])) }}
                                        <span class="fs-8 text-muted">{{ $row['spec']['currency'] }}</span>
                                        <div class="fs-8 text-muted">
                                            {{ tools()->cost_normalize(round($row['amount_rub'])) }} ₽
                                        </div>
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

            <div class="card-footer py-3 fs-8 text-muted">
                Горизонт включающий: «в течение 60 дней» показывает и то, что уже истекло. Сумма
                спецификации — не счёт на продление, а порядок денег, который стоит за этим ключом;
                к рублям приведена по текущему курсу. Ключи без даты окончания в горизонты не попадают.
            </div>
        </div>
    </div>
@endsection
