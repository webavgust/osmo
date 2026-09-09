@extends('components.box.box-static-extralarge')

@section('body')
    @if(empty($preview))
        <div class="alert alert-warning py-3 fs-7 mb-0">
            У записи нет детальных данных — загрузите detail из API или импортируйте ответ <code>detail</code> через «Импорт JSON».
        </div>
    @else
        @php
            $symbol = match($preview['currency']) { 'USD' => '$', 'EUR' => '€', default => '₽' };
            $match_labels = [
                'map' => ['success', 'по карте соответствий'],
                'exact' => ['success', 'по названию'],
                'similar' => ['info', 'похожее название — проверьте'],
                'custom' => ['dark', 'кастомный → служебный сценарий'],
                'choice' => ['primary', 'выбрано'],
                'fuzzy' => ['info', 'похожее название — проверьте'],
                'company' => ['info', 'партнёр по компании-заказчику'],
            ];
        @endphp

        <style>
            #transfer_form select.scenario_select { min-width: 280px; }
            #transfer_form .match-hint { font-size: 11px; }
        </style>

        <form id="transfer_form" onsubmit="return false;">
            @if($force)
                <div class="alert alert-warning d-flex align-items-center py-3 mb-4">
                    <i class="fa-light fa-triangle-exclamation fs-2 me-3"></i>
                    <div>
                        Запись уже перенесена в КП
                        @if($external->proposal)
                            <a href="{{ route('proposal.detail', [$external->proposal, $external->proposal->iteration]) }}" class="fw-bold" target="_blank">{{ $external->proposal->number }}</a>
                        @endif
                        {{ $external->transferred_at?->format('d.m.Y H:i') }}.
                        Кнопка ниже создаст <b>ещё одно, новое КП</b>; связь записи переключится на него.
                    </div>
                </div>
            @endif

            @foreach($preview['warnings'] as $warning)
                <div class="alert alert-light-warning text-warning border border-warning py-2 fs-8 mb-2">
                    <i class="fa-light fa-circle-exclamation me-1"></i> {{ $warning }}
                </div>
            @endforeach

            {{-- Шапка КП --}}
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <label class="form-label fw-semibold">Партнёр <span class="text-danger">*</span></label>
                    <select name="partner" id="transfer_partner" class="form-select" data-placeholder="Выберите партнёра">
                        <option value=""></option>
                        @foreach($partners as $partner)
                            <option value="{{ $partner->id }}" @selected($preview['partner']?->id === $partner->id)>{{ $partner->name }}@if(!$partner->active) (неактивен)@endif</option>
                        @endforeach
                    </select>
                    <div class="match-hint mt-1">
                        <span class="text-muted">Адресат у Алексея:</span> <b>{{ $preview['partner_source'] ?: '—' }}</b>
                        @if($preview['partner_match'])
                            <span class="badge badge-light-{{ $match_labels[$preview['partner_match']][0] ?? 'light' }} fs-9 ms-1">{{ $match_labels[$preview['partner_match']][1] ?? $preview['partner_match'] }}</span>
                        @else
                            <span class="badge badge-light-danger fs-9 ms-1">не найден — выберите</span>
                        @endif
                    </div>
                </div>

                <div class="col-lg-4">
                    <label class="form-label fw-semibold">Компания <span class="text-danger">*</span></label>
                    <select name="company" id="transfer_company" class="form-select" data-placeholder="Выберите компанию">
                        <option value=""></option>
                    </select>
                    <div class="match-hint mt-1">
                        <span class="text-muted">Заказчик у Алексея:</span> <b>{{ $preview['company_source'] ?: '—' }}</b>
                        @if($preview['company_match'])
                            <span class="badge badge-light-{{ $match_labels[$preview['company_match']][0] ?? 'light' }} fs-9 ms-1">{{ $match_labels[$preview['company_match']][1] ?? $preview['company_match'] }}</span>
                        @else
                            <span class="badge badge-light-danger fs-9 ms-1">не найдена</span>
                        @endif
                    </div>
                </div>

                <div class="col-lg-2">
                    <label class="form-label fw-semibold">Менеджер <span class="text-danger">*</span></label>
                    <select name="manager" id="transfer_manager" class="form-select">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((int) $preview['manager'] === (int) $user->id)>{{ $user->full_name ?: $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2">
                    <label class="form-label fw-semibold">Номер КП <span class="text-danger">*</span></label>
                    <input type="text" name="number" id="transfer_number" class="form-control" value="{{ $preview['number_default'] }}" maxlength="32">
                    <div class="match-hint text-muted mt-1">его <b>{{ $preview['number_external'] }}</b> → в альт. название</div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-7">
                    <table class="table table-row-bordered table-row-dashed align-middle fs-7 m-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 150px">Название</td>
                                <td class="fw-bold">{{ $preview['name'] }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Дата КП</td>
                                <td>{{ \Carbon\Carbon::parse($preview['date'])->format('d.m.Y') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Язык</td>
                                <td>{{ $preview['lang'] === 'en' ? 'английский' : 'русский' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Валюта</td>
                                <td>{{ $preview['currency'] }}@if($preview['currency'] !== 'RUB') <span class="text-muted">· курс {{ $preview['rate'] }} ₽, прайс пересчитан по нему</span>@endif</td>
                            </tr>
                            <tr>
                                <td class="text-muted">НДС</td>
                                <td>{{ $preview['nds'] }}% {{ $preview['vat'] ? '(включён во все позиции)' : '' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Вариант</td>
                                <td>{{ $preview['period'] === 'unlimited' ? 'бессрочные лицензии' : 'годовые лицензии, 1 год' }}</td>
                            </tr>
                            @if($preview['partner_discount'])
                                <tr>
                                    <td class="text-muted">Скидка партнёра</td>
                                    <td>{{ $preview['partner_discount'] }}% (платформа и нейросервисы)</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="col-lg-5">
                    <div class="bg-light rounded p-3 fs-7">
                        <div class="d-flex justify-content-between"><span>Платформа: {{ tools()->cost_normalize($preview['platform']['count']) }} × {{ tools()->cost_normalize($preview['platform']['cost'], '.', false, ' ', false, 2) }} {{ $symbol }} @if($preview['platform']['discount'])− {{ $preview['platform']['discount'] }}%@endif <span class="text-muted fs-9">({{ $preview['platform']['cost_source'] === 'manual' ? 'platformManualPrice' : 'наш прайс' }})</span></span><b class="text-nowrap">{{ tools()->cost_normalize($preview['totals']['platform'], '.', false, ' ', false, 2) }} {{ $symbol }}</b></div>
                        <div class="d-flex justify-content-between"><span>Нейросервисы</span><b class="text-nowrap" id="total_neuro">{{ tools()->cost_normalize($preview['totals']['neuro'], '.', false, ' ', false, 2) }} {{ $symbol }}</b></div>
                        <div class="d-flex justify-content-between"><span>Работы</span><b class="text-nowrap">{{ tools()->cost_normalize($preview['totals']['works'], '.', false, ' ', false, 2) }} {{ $symbol }}</b></div>
                        <div class="separator my-2"></div>
                        <div class="d-flex justify-content-between"><span>Итого без НДС</span><b class="text-nowrap">{{ tools()->cost_normalize($preview['totals']['total'], '.', false, ' ', false, 2) }} {{ $symbol }}</b></div>
                        @if($preview['vat'])
                            <div class="d-flex justify-content-between text-muted"><span>НДС {{ $preview['nds'] }}%</span><span class="text-nowrap">{{ tools()->cost_normalize($preview['totals']['nds'], '.', false, ' ', false, 2) }} {{ $symbol }}</span></div>
                        @endif
                        <div class="fs-9 text-muted mt-2">Оценка по прайсу до переноса; точный расчёт сделает форма КП.</div>
                    </div>
                </div>
            </div>

            {{-- Сценарии --}}
            <div class="fs-6 fw-bold mb-2">Сценарии <span class="badge badge-light ms-1">{{ count($preview['scenarios']) }}</span></div>
            <div class="table-responsive mb-4">
                <table class="table table-row-bordered align-middle fs-7 m-0">
                    <thead>
                        <tr class="fw-bold fs-8 text-muted text-uppercase">
                            <th style="width: 30px">#</th>
                            <th>У Алексея</th>
                            <th class="text-end" style="width: 70px">Камер</th>
                            <th style="width: 40%">Наш сценарий</th>
                            <th class="text-end text-nowrap" style="width: 120px">Цена / скидка</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preview['scenarios'] as $row)
                            <tr>
                                <td>{{ $row['i'] }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $row['name'] }}</div>
                                    <div class="fs-9 text-muted">id {{ $row['external_id'] }}@if($row['retrain']) · дообучение @endif @if($row['comment']) · {{ \Illuminate\Support\Str::limit($row['comment'], 90) }} @endif</div>
                                </td>
                                <td class="text-end">{{ $row['count'] }}</td>
                                <td>
                                    <select name="scenario[{{ $row['i'] }}]" class="form-select form-select-sm scenario_select" data-placeholder="Выберите сценарий" data-i="{{ $row['i'] }}">
                                        <option value=""></option>
                                        @foreach($scenarios as $scenario)
                                            <option value="{{ $scenario->id }}" @selected((int) $row['scenario_id'] === (int) $scenario->id)>{{ $scenario->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="match-hint mt-1">
                                        @if($row['match'])
                                            <span class="badge badge-light-{{ $match_labels[$row['match']][0] ?? 'light' }} fs-9">{{ $match_labels[$row['match']][1] ?? $row['match'] }}</span>
                                        @else
                                            <span class="badge badge-light-danger fs-9">не сопоставлен — выберите</span>
                                        @endif
                                        @if($row['external_id'] !== '' && $row['external_id'] !== '0')
                                            <span class="text-muted">выбор запомнится для id {{ $row['external_id'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-end text-nowrap">
                                    {{ tools()->cost_normalize($row['cost'], '.', false, ' ', false, 2) }} {{ $symbol }}
                                    @if($row['discount'])<div class="fs-9 text-muted">− {{ $row['discount'] }}%</div>@endif
                                    <div class="fs-9 text-muted">{{ $row['cost_source'] === 'manual' ? 'manualPrice' : ($row['cost_source'] === 'price' ? 'прайс' : 'цены нет') }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Работы --}}
            <div class="fs-6 fw-bold mb-2">Работы <span class="badge badge-light ms-1">{{ count($preview['works']) }}</span></div>
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle fs-7 m-0">
                    <thead>
                        <tr class="fw-bold fs-8 text-muted text-uppercase">
                            <th style="width: 50px">id</th>
                            <th>Работа</th>
                            <th>Группа</th>
                            <th class="text-end text-nowrap">Кол-во × цена</th>
                            <th class="text-end" style="width: 70px">Скидка</th>
                            <th class="text-end text-nowrap">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preview['works'] as $row)
                            <tr>
                                <td><code>{{ $row['id'] }}</code></td>
                                <td>
                                    <div class="fw-semibold">{{ $row['name'] }}</div>
                                    @if($row['notice'])<div class="fs-9 text-muted">{!! $row['notice'] !!}</div>@endif
                                </td>
                                <td class="fs-8">{{ $row['group'] ?? '—' }}</td>
                                <td class="text-end text-nowrap">{{ $row['count'] + 0 }} × {{ tools()->cost_normalize($row['cost'], '.', false, ' ', false, 2) }} {{ $symbol }}</td>
                                <td class="text-end">{{ $row['discount'] }}%</td>
                                <td class="text-end text-nowrap fw-semibold">{{ tools()->cost_normalize($row['total'], '.', false, ' ', false, 2) }} {{ $symbol }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Оборудование --}}
            @if(!empty($preview['hardware']))
                <div class="fs-6 fw-bold mb-2 mt-5">Оборудование <span class="badge badge-light ms-1">{{ count($preview['hardware']) }}</span></div>
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle fs-7 m-0">
                        <thead>
                            <tr class="fw-bold fs-8 text-muted text-uppercase">
                                <th style="width: 220px">Наименование</th>
                                <th style="width: 90px">Кол-во</th>
                                <th>Параметры</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preview['hardware'] as $row)
                                <tr>
                                    <td class="fw-semibold">{!! $row['name'] !!}</td>
                                    <td>{!! $row['count'] ?: '—' !!}</td>
                                    <td class="fs-8">{!! $row['params'] !!}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="fs-8 text-muted mt-3">
                Исходный запрос, описание, цели и результат попадут в блок «Задачи» варианта КП,
                требования к серверам и камерам — в блок «Вычислительные ресурсы и оборудование».
            </div>
        </form>

        @php
            $companies_json = [];
            foreach ($companies as $c) {
                $companies_json[] = ['id' => $c->id, 'name' => $c->name, 'partner_id' => $c->partner_id, 'active' => (bool) $c->active];
            }
            $companies_json = json_encode($companies_json, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        @endphp
        <script>
            var transfer_companies = {!! $companies_json !!};
            var transfer_company_selected = {{ (int) ($preview['company']?->id ?? 0) }};

            /** Компании: сначала компании выбранного партнёра, потом остальные */
            function transfer_company_render() {
                var partner_id = parseInt($('#transfer_partner').val()) || 0;
                var select = $('#transfer_company');
                var current = parseInt(select.val()) || transfer_company_selected;

                select.empty().append('<option value=""></option>');

                var own = $('<optgroup label="Компании партнёра">');
                var others = $('<optgroup label="Остальные">');
                transfer_companies.forEach(function (c) {
                    var option = $('<option>').val(c.id).text(c.name + (c.active ? '' : ' (неактивна)'));
                    if (partner_id && c.partner_id === partner_id) own.append(option); else others.append(option);
                });
                if (own.children().length) select.append(own);
                select.append(others);

                select.val(current ? String(current) : '').trigger('change.select2');
            }

            function transfer_save() {
                var missing = [];
                if (!$('#transfer_partner').val()) missing.push('партнёр');
                if (!$('#transfer_company').val()) missing.push('компания');
                if (!$('#transfer_number').val().trim()) missing.push('номер КП');
                $('#transfer_form select.scenario_select').each(function () {
                    if (!$(this).val()) missing.push('сценарий ' + $(this).data('i'));
                });
                if (missing.length) {
                    toastr.error('Заполните: ' + missing.join(', '), 'Это провал!', { progressBar: true, timeOut: 4000 });
                    return;
                }

                var data = $('#transfer_form').serialize() + '&_token=' + csrf_token() + '&force={{ $force ? 1 : 0 }}';

                $('#btn_transfer').prop('disabled', true);
                body_block();

                $.ajax({
                    url: '{{ route('api.external_proposal.transfer', $external) }}',
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function (response) {
                        body_unblock();
                        $('#btn_transfer').prop('disabled', false);

                        if (response.result !== 'success') {
                            toastr.error(response.message ?? 'Не получилось перенести', 'Это провал!', { progressBar: true, timeOut: 8000 });
                            return;
                        }

                        toastr.success(
                            response.message + ' — <a href="' + response.url + '" class="fw-bold text-white text-decoration-underline">открыть</a>',
                            'Это успех!',
                            { progressBar: true, timeOut: 10000, escapeHtml: false }
                        );
                        box_close();
                        if (typeof external_table_refresh === 'function') external_table_refresh();
                    },
                    error: function (xhr) {
                        body_unblock();
                        $('#btn_transfer').prop('disabled', false);
                        var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Ошибка запроса (' + xhr.status + ')';
                        toastr.error(message, 'Это провал!', { progressBar: true, timeOut: 6000 });
                    }
                });
            }

            $(document).ready(function () {
                $('#transfer_partner, #transfer_manager').select2({ width: '100%' });
                $('#transfer_company').select2({ width: '100%', allowClear: true });
                $('#transfer_form select.scenario_select').select2({ width: '100%' });

                transfer_company_render();

                $('#transfer_partner').on('change', function () {
                    transfer_company_render();
                });
            });
        </script>
    @endif
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="light" onclick="javascript:box_close();">
            <span>Закрыть</span>
        </x-ui.button.default>

        @if(!empty($preview))
            <x-ui.button.default id="btn_transfer" btn_type="{{ $force ? 'warning' : 'success' }}" onclick="javascript:transfer_save();">
                <i class="fa-light fa-arrow-right-to-bracket me-2"></i>
                <span>{{ $force ? 'Перенести ещё раз как новое КП' : 'Перенести в КП' }}</span>
            </x-ui.button.default>
        @endif
    </div>
@endsection
