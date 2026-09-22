@extends('components.box.box-static-extralarge')

@section('body')
    @if(empty($preview))
        <div class="alert alert-warning py-3 fs-7 mb-0">
            У записи нет детальных данных — загрузите detail из API или импортируйте ответ <code>detail</code> через «Импорт JSON».
        </div>
    @else
        @php
            $symbol = match($preview['currency']) { 'USD' => '$', 'EUR' => '€', default => '₽' };
            // варианты КП: у каждой позиции своя ячейка в каждом варианте
            $vlist = $preview['variants'];
            $multi = !empty($preview['multi']);
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
            @if($multi)
                /* с колонками вариантов выбор сценария не должен растягивать таблицу */
                #transfer_form .scenario_pick { width: 300px; }
            @endif
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
                    <label class="form-label fw-semibold">Компания</label>
                    <select name="company" id="transfer_company" class="form-select" data-placeholder="Заказчик не указан">
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
                            @if($multi)
                                <tr>
                                    <td class="text-muted align-top">Варианты</td>
                                    <td>
                                        @foreach($vlist as $variant)
                                            <div @class(['mt-2' => !$loop->first])>
                                                <b>{{ $variant['title'] }}</b> — {{ $variant['period'] === 'unlimited' ? 'бессрочные лицензии' : 'годовые лицензии, 1 год' }}@if($variant['delivery_weeks']) <span class="text-muted">· срок {{ $variant['delivery_weeks'] }} нед.</span>@endif
                                                @if($variant['name'])<div class="fs-8 text-muted">{{ $variant['name'] }}</div>@endif
                                            </div>
                                        @endforeach
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <td class="text-muted">Вариант</td>
                                    <td>{{ $preview['period'] === 'unlimited' ? 'бессрочные лицензии' : 'годовые лицензии, 1 год' }}</td>
                                </tr>
                            @endif
                            @if($preview['partner_discount'] || collect($vlist)->pluck('partner_discount')->unique()->count() > 1)
                                <tr>
                                    <td class="text-muted">Скидка партнёра</td>
                                    <td>
                                        @if($multi && collect($vlist)->pluck('partner_discount')->unique()->count() > 1)
                                            {{ collect($vlist)->map(fn($variant) => $variant['title'] . ' — ' . $variant['partner_discount'] . '%')->implode(', ') }}
                                            <span class="text-muted">(платформа и нейросервисы)</span>
                                        @else
                                            {{ $preview['partner_discount'] }}% (платформа и нейросервисы)
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="col-lg-5">
                    @foreach($vlist as $variant)
                        <div @class(['bg-light rounded p-3 fs-7', 'mt-3' => !$loop->first])>
                            @if($multi)
                                <div class="fw-bold mb-2">{{ $variant['title'] }} <span class="fs-9 fw-normal text-muted">· {{ $variant['period'] === 'unlimited' ? 'бессрочные' : 'годовые' }}</span></div>
                            @endif
                            <div class="d-flex justify-content-between"><span>Платформа: {{ tools()->cost_normalize($variant['platform']['count']) }} × {{ tools()->cost_normalize($variant['platform']['cost'], '.', false, ' ', false, 2) }} {{ $symbol }} @if($variant['platform']['discount'])− {{ $variant['platform']['discount'] }}%@endif <span class="text-muted fs-9">({{ $variant['platform']['cost_source'] === 'manual' ? 'platformManualPrice' : 'наш прайс' }})</span></span><b class="text-nowrap">{{ tools()->cost_normalize($variant['totals']['platform'], '.', false, ' ', false, 2) }} {{ $symbol }}</b></div>
                            <div class="d-flex justify-content-between"><span>Нейросервисы</span><b class="text-nowrap" @if($variant['is_main']) id="total_neuro" @endif>{{ tools()->cost_normalize($variant['totals']['neuro'], '.', false, ' ', false, 2) }} {{ $symbol }}</b></div>
                            <div class="d-flex justify-content-between"><span>Работы</span><b class="text-nowrap">{{ tools()->cost_normalize($variant['totals']['works'], '.', false, ' ', false, 2) }} {{ $symbol }}</b></div>
                            <div class="separator my-2"></div>
                            <div class="d-flex justify-content-between"><span>Итого без НДС</span><b class="text-nowrap">{{ tools()->cost_normalize($variant['totals']['total'], '.', false, ' ', false, 2) }} {{ $symbol }}</b></div>
                            @if($preview['vat'])
                                <div class="d-flex justify-content-between text-muted"><span>НДС {{ $preview['nds'] }}%</span><span class="text-nowrap">{{ tools()->cost_normalize($variant['totals']['nds'], '.', false, ' ', false, 2) }} {{ $symbol }}</span></div>
                            @endif
                            @if(!$multi)
                                <div class="fs-9 text-muted mt-2">Оценка по прайсу до переноса; точный расчёт сделает форма КП.</div>
                            @endif
                        </div>
                    @endforeach
                    @if($multi)
                        <div class="fs-9 text-muted mt-2">Оценка по прайсу до переноса; точный расчёт сделает форма КП.</div>
                    @endif
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
                            @unless($multi)
                                <th class="text-end" style="width: 70px">Камер</th>
                            @endunless
                            <th style="width: {{ $multi ? '30%' : '40%' }}">Наш сценарий</th>
                            @if($multi)
                                @foreach($vlist as $variant)
                                    <th class="text-end text-nowrap" style="width: 140px">{{ $variant['title'] }}</th>
                                @endforeach
                            @else
                                <th class="text-end text-nowrap" style="width: 120px">Цена / скидка</th>
                            @endif
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
                                @unless($multi)
                                    <td class="text-end">{{ $row['count'] }}</td>
                                @endunless
                                <td>
                                    <div class="scenario_pick">
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
                                    </div>
                                </td>
                                @if($multi)
                                    @foreach($vlist as $variant)
                                        @php $cell = $row['cells'][$variant['v']] ?? null; @endphp
                                        <td class="text-end text-nowrap @if(empty($cell)) text-muted @endif">
                                            @if(empty($cell))
                                                — <div class="fs-9">нет в варианте</div>
                                            @else
                                                <b>{{ $cell['count'] }}</b> × {{ tools()->cost_normalize($cell['cost'], '.', false, ' ', false, 2) }} {{ $symbol }}
                                                @if($cell['discount'])<div class="fs-9 text-muted">− {{ $cell['discount'] }}%</div>@endif
                                                <div class="fs-9 text-muted">{{ tools()->cost_normalize($cell['total'], '.', false, ' ', false, 2) }} {{ $symbol }} · {{ $cell['cost_source'] === 'manual' ? 'manualPrice' : ($cell['cost_source'] === 'price' ? 'прайс' : 'цены нет') }}</div>
                                            @endif
                                        </td>
                                    @endforeach
                                @else
                                    <td class="text-end text-nowrap">
                                        {{ tools()->cost_normalize($row['cost'], '.', false, ' ', false, 2) }} {{ $symbol }}
                                        @if($row['discount'])<div class="fs-9 text-muted">− {{ $row['discount'] }}%</div>@endif
                                        <div class="fs-9 text-muted">{{ $row['cost_source'] === 'manual' ? 'manualPrice' : ($row['cost_source'] === 'price' ? 'прайс' : 'цены нет') }}</div>
                                    </td>
                                @endif
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
                            @if($multi)
                                @foreach($vlist as $variant)
                                    <th class="text-end text-nowrap" style="width: 140px">{{ $variant['title'] }}</th>
                                @endforeach
                            @else
                                <th class="text-end text-nowrap">Кол-во × цена</th>
                                <th class="text-end" style="width: 70px">Скидка</th>
                                <th class="text-end text-nowrap">Сумма</th>
                            @endif
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
                                @if($multi)
                                    @foreach($vlist as $variant)
                                        @php $cell = $row['cells'][$variant['v']] ?? null; @endphp
                                        <td class="text-end text-nowrap @if(empty($cell)) text-muted @endif">
                                            @if(empty($cell))
                                                — <div class="fs-9">нет в варианте</div>
                                            @else
                                                {{ $cell['count'] + 0 }} × {{ tools()->cost_normalize($cell['cost'], '.', false, ' ', false, 2) }} {{ $symbol }}
                                                @if($cell['discount'])<div class="fs-9 text-muted">− {{ $cell['discount'] }}%</div>@endif
                                                <div class="fs-9 text-muted fw-semibold">{{ tools()->cost_normalize($cell['total'], '.', false, ' ', false, 2) }} {{ $symbol }}</div>
                                            @endif
                                        </td>
                                    @endforeach
                                @else
                                    <td class="text-end text-nowrap">{{ $row['count'] + 0 }} × {{ tools()->cost_normalize($row['cost'], '.', false, ' ', false, 2) }} {{ $symbol }}</td>
                                    <td class="text-end">{{ $row['discount'] }}%</td>
                                    <td class="text-end text-nowrap fw-semibold">{{ tools()->cost_normalize($row['total'], '.', false, ' ', false, 2) }} {{ $symbol }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Дополнительные начисления --}}
            @php $charges = $preview['charges']; @endphp
            @if(!empty($charges['transfer']) || !empty($charges['skipped']))
                <div class="fs-6 fw-bold mb-2 mt-5">Дополнительные начисления <span class="badge badge-light ms-1">{{ count($charges['transfer']) + count($charges['skipped']) }}</span></div>
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle fs-7 m-0">
                        <thead>
                            <tr class="fw-bold fs-8 text-muted text-uppercase">
                                <th>Наименование</th>
                                <th class="text-end" style="width: 90px">Процент</th>
                                <th class="text-end" style="width: 200px">Перенос</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($charges['transfer'] as $charge)
                                <tr>
                                    <td class="fw-semibold">{{ $charge['name'] }}</td>
                                    <td class="text-end">{{ $charge['percent'] + 0 }}%</td>
                                    <td class="text-end"><span class="badge badge-light-success fs-9">перенесётся во все варианты</span></td>
                                </tr>
                            @endforeach
                            @foreach($charges['skipped'] as $charge)
                                <tr class="text-muted">
                                    <td>{{ $charge['name'] }}</td>
                                    <td class="text-end">{{ $charge['percent'] + 0 }}%</td>
                                    <td class="text-end"><span class="badge badge-light-warning fs-9">в источнике отключён</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if(!empty($charges['transfer']))
                    <div class="fs-8 text-muted mt-1">Начисление ставится на весь проект (блок «на всё»), процент считается от суммы варианта каскадом.</div>
                @endif
            @endif

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
                @if($multi)
                    Вариантов КП будет создано: <b>{{ count($vlist) }}</b>, основной — {{ $vlist[$preview['main']]['title'] }}; позиция, которой в варианте нет, встанет выключенной ячейкой.<br>
                @endif
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
                $('#transfer_company').select2({ width: '100%', allowClear: true })
                    // очистили — «заказчик не указан»: при смене партнёра найденную компанию не возвращаем
                    .on('select2:clear', function () { transfer_company_selected = 0; });
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
