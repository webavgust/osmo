@extends('components.box.box-static-extralarge')

@php
    $payload = $external->payload ?? [];
    $symbol = match($external->currency) { 'USD' => '$', 'EUR' => '€', default => '₽' };
@endphp

@section('body')
    <style>
        #external_detail pre { max-height: 420px; overflow: auto; font-size: 11px; white-space: pre-wrap; word-break: break-word; }
    </style>

    <div id="external_detail">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
            <span class="badge badge-light-primary fs-7">{{ $external->source_label }}</span>
            @if($external->external_number)
                <span class="badge badge-light-dark fs-7">{{ $external->external_number }}</span>
            @endif
            <code class="fs-8 text-muted">{{ $external->external_id }}</code>

        </div>

        @if(!empty($external->proposal_group))
            <div class="alert alert-success d-flex align-items-center py-3 mb-4">
                <i class="fa-light fa-circle-check fs-2 me-3"></i>
                <div>
                    Перенесено в КП
                    @if($external->proposal)
                        <a href="{{ route('proposal.detail', [$external->proposal, $external->proposal->iteration]) }}" class="fw-bold">{{ $external->proposal->number }}</a>
                    @else
                        <span class="text-muted">(КП удалено)</span>
                    @endif
                    {{ $external->transferred_at?->format('d.m.Y H:i') }}
                    @if($external->transferred_user) · {{ $external->transferred_user->full_name ?? $external->transferred_user->name }} @endif
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header min-h-auto pt-5 pb-0 border-bottom d-flex justify-content-between align-items-center">
                    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#ext_tab_main" role="tab">Ключевые поля</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#ext_tab_items" role="tab">Сценарии @if($preview)<span class="badge badge-light ms-1">{{ count($preview['scenarios']) }}</span>@endif</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#ext_tab_works" role="tab">Работы @if($preview)<span class="badge badge-light ms-1">{{ count($preview['works']) }}</span>@endif</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#ext_tab_text" role="tab">Тексты</a></li>
{{--                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#ext_tab_json" role="tab">JSON</a></li>--}}
                    </ul>
            </div>
            <div class="card-body p-4">
                <div class="tab-content">
                    {{-- Ключевые поля --}}
                    <div class="tab-pane active" id="ext_tab_main" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <table class="table table-bordered align-middle fs-7 mb-0">
                                    <tr>
                                        <td class="text-muted" width="150">Название</td>
                                        <td class="fw-bold">{{ $external->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted" >Заказчик</td>
                                        <td>{{ $external->customer ?: '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted" >Адресат (партнёр)</td>
                                        <td>{{ $payload['recipientCompany'] ?? '—' }}
                                        @if(!empty($payload['recipientName'])) <span class="text-muted">· {{ $payload['recipientName'] }}, {{ $payload['recipientPosition'] ?? '' }}</span>@endif</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted" >Отправитель</td>
                                        <td>{{ $payload['senderName'] ?? '—' }} <span class="text-muted">{{ $payload['senderPosition'] ?? '' }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted" >Создано / изменено</td>
                                        <td>{{ $external->created_at_remote?->format('d.m.Y') ?? '—' }} / {{ $external->updated_at_remote?->format('d.m.Y H:i') ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted" >Язык / рынок</td>
                                        <td>{{ $payload['language'] ?? 'ru' }} / {{ $payload['marketType'] ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted" >Валюта / курс</td>
                                        <td>{{ $external->currency }} @if(!empty($payload['exchangeRate'])) · курс {{ $payload['exchangeRate'] }} @endif</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted" >НДС</td>
                                        <td>{{ !empty($payload['isVatIncluded']) ? 'да' : 'нет' }} <span class="text-muted">({{ $payload['taxMode'] ?? '—' }}{{ !empty($payload['taxPresetId']) ? ', ' . $payload['taxPresetId'] : '' }})</span></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-lg-6">
                                <table class="table table-bordered align-middle fs-7 mb-0">
                                    <tr>
                                        <td class="text-muted" width="210">Камер (лицензий платформы)</td>
                                        <td class="fw-bold">{{ tools()->cost_normalize((int) ($payload['totalCameras'] ?? $external->cameras ?? 0)) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted" >Цена лицензии платформы</td>
                                        <td>{{ isset($payload['platformManualPrice']) && $payload['platformManualPrice'] !== null && $payload['platformManualPrice'] !== '' ? tools()->cost_normalize($payload['platformManualPrice']) . ' ' . $symbol : 'по прайсу' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted" >Тип лицензий</td>
                                        <td>{{ match($external->license_type) { 'unlimited' => 'бессрочные', 'year' => 'годовые', 'mixed' => 'смешанные', default => '—' } }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted" >Скидки</td>
                                        <td>клиент {{ $payload['clientDiscount'] ?? 0 }}% · платформа {{ $payload['platformDiscount'] ?? 0 }}% · нейросервисы {{ $payload['neuroDiscount'] ?? 0 }}% · работы {{ $payload['workDiscount'] ?? 0 }}% · партнёр {{ $payload['partnerDiscount'] ?? 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted" >Ставка часа</td>
                                        <td>{{ isset($payload['globalWorkRate']) ? tools()->cost_normalize($payload['globalWorkRate']) : '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted" >Обучение</td>
                                        <td>{{ !empty($payload['includeTraining']) ? 'да, ' . tools()->cost_normalize($payload['trainingPrice'] ?? 0) : 'нет' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted" >Гарантия</td>
                                        <td>{{ !empty($payload['includeWarranty']) ? ($payload['warrantyMonths'] ?? '?') . ' мес.' . (!empty($payload['includeWarrantySecondYear']) ? ', со 2-го года ' . tools()->cost_normalize($payload['warrantyPrice'] ?? 0) : '') : 'нет' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted" >Оплата / срок</td>
                                        <td>аванс {{ $payload['paymentPrepaymentPercent'] ?? '—' }}% · остаток {{ $payload['paymentPostpaymentPercent'] ?? '—' }}% · {{ $payload['deliveryWeeks'] ?? '—' }} нед.</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted" >Техника</td>
                                        <td>{{ $payload['gpuModel'] ?? '—' }} · {{ $payload['fps'] ?? '—' }} FPS · {{ $payload['analyticsPerCamera'] ?? '—' }} аналитик/камеру · серверов {{ $payload['serverCount'] ?? '—' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        @if(!empty($payload['hardwareRequirements']) && is_array($payload['hardwareRequirements']))
                            <div class="separator separator-dashed my-4"></div>
                            <div class="fs-6 fw-bold mb-2">Оборудование</div>
                            <table class="table table-bordered align-middle fs-7 mb-0">
                                @foreach($payload['hardwareRequirements'] as $key => $value)
                                    <tr>
                                        <td class="text-muted" >{{ $key }}</td>
                                        <td>{{ $value }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif

                        @if(!empty($payload['additionalCharges']) && is_array($payload['additionalCharges']))
                            <div class="separator separator-dashed my-4"></div>
                            <div class="fs-6 fw-bold mb-2">Дополнительные сборы</div>
                            <ul class="fs-8 mb-0">
                                @foreach($payload['additionalCharges'] as $charge)
                                    <li>{{ $charge['description'] ?? '' }} — {{ $charge['ratePercent'] ?? '' }}%</li>
                                @endforeach
                            </ul>
                        @endif

                        @if(empty($preview))
                            <div class="alert alert-warning mt-4 py-3 fs-7 mb-0">
                                Детальные данные не загружены — есть только строка списка. Нажмите «Загрузить» в таблице
                                (нужен доступ к API) или импортируйте ответ <code>detail</code> через «Импорт JSON».
                            </div>
                        @endif
                    </div>

                    {{-- Сценарии --}}
                    <div class="tab-pane" id="ext_tab_items" role="tabpanel">
                        @if($preview && count($preview['scenarios']))
                            <div class="table-responsive">
                                <table class="table table-bordered table-row-bordered align-middle fs-7 m-0">
                                    <thead>
                                    <tr class="fw-bold fs-8 text-muted text-uppercase">
                                        <th>#</th>
{{--                                        <th>ID</th>--}}
                                        <th>Название</th>
                                        <th class="text-center">Камер</th>
                                        <th>Наш сценарий</th>
                                        <th class="text-end">Цена</th>
                                        <th class="text-end">Скидка</th>
                                        <th>Комментарий</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($preview['scenarios'] as $row)
                                        <tr>
                                            <td>{{ $row['i'] }}</td>
{{--                                            <td><code>{{ $row['external_id'] }}</code></td>--}}
                                            <td class="fw-semibold">{{ $row['name'] }} @if($row['retrain'])<span class="badge badge-light-warning fs-9 ms-1">дообучение</span>@endif</td>
                                            <td class="text-center">{{ $row['count'] }}</td>
                                            <td>
                                                @if($row['scenario_id'])
                                                    <span class="badge badge-light-{{ $row['match'] === 'map' ? 'success' : ($row['match'] === 'custom' ? 'dark' : 'info') }} fs-9 me-1">{{ $row['match'] }}</span>
                                                    {{ $row['scenario_name'] }} <span class="text-muted">#{{ $row['scenario_id'] }}</span>
                                                @else
                                                    <span class="badge badge-light-danger">не сопоставлен</span>
                                                @endif
                                            </td>
                                            <td class="text-end text-nowrap">{{ tools()->cost_normalize($row['cost'], '.', false, ' ', false, 2) }} {{ $symbol }}
                                                <div class="fs-9 text-muted">{{ $row['cost_source'] === 'manual' ? 'руками' : ($row['cost_source'] === 'price' ? 'наш прайс' : 'нет') }}</div>
                                            </td>
                                            <td class="text-end">{{ $row['discount'] }}%</td>
                                            <td class="fs-8 text-muted">{{ $row['comment'] }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-muted py-6 text-center">Сценариев нет</div>
                        @endif
                    </div>

                    {{-- Работы --}}
                    <div class="tab-pane" id="ext_tab_works" role="tabpanel">
                        @if($preview && count($preview['works']))
                            <div class="table-responsive">
                                <table class="table table-bordered table-row-bordered align-middle fs-7 m-0">
                                    <thead>
                                    <tr class="fw-bold fs-8 text-muted text-uppercase">
{{--                                        <th>id</th>--}}
                                        <th>Работа</th>
                                        <th class="text-center">Часы</th>
                                        <th class="text-end">Ставка</th>
                                        <th class="text-end">Сумма</th>
                                        <th class="text-center">Скидка</th>
                                        <th>Группа у нас</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($preview['works'] as $row)
                                        <tr>
{{--                                            <td><code>{{ $row['id'] }}</code></td>--}}
                                            <td>
                                                <div class="fw-semibold">{{ $row['name'] }}</div>
                                                @if($row['tasks'])
                                                    <ul class="fs-8 text-muted mb-0 ps-4">
                                                        @foreach($row['tasks'] as $task)<li>{{ $task }}</li>@endforeach
                                                    </ul>
                                                @endif
                                                @if($row['note'])<div class="fs-8 text-muted fst-italic">{{ $row['note'] }}</div>@endif
                                            </td>
                                            <td class="text-center">{{ $row['hours'] }}</td>
                                            <td class="text-end text-nowrap">{{ tools()->cost_normalize($row['rate']) }} {{ $symbol }}</td>
                                            <td class="text-end text-nowrap fw-semibold">{{ tools()->cost_normalize($row['cost_external']) }} {{ $symbol }}</td>
                                            <td class="text-center">{{ $row['discount'] }}%</td>
                                            <td class="fs-8">{{ $row['group'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-muted py-6 text-center">Работ нет</div>
                        @endif
                    </div>

                    {{-- Тексты --}}
                    <div class="tab-pane" id="ext_tab_text" role="tabpanel">
                        @php
                            $texts = [
                                'Исходный запрос' => $payload['projectRawInput'] ?? null,
                                'Описание проекта' => $payload['projectDescription'] ?? null,
                                'Цели проекта' => $payload['projectGoals'] ?? null,
                                'Ожидаемый результат' => $payload['projectResult'] ?? null,
                                'Вступление' => $payload['introText'] ?? null,
                                'Требования к камерам' => $payload['cameraRequirements'] ?? null,
                            ];
                        @endphp
                        @foreach($texts as $title => $text)
                            @continue(trim((string) $text) === '')
                            <div class="mb-4">
                                <div class="fs-7 fw-bold mb-1">{{ $title }}</div>
                                <div class="fs-8 text-gray-800" style="white-space: pre-wrap">{{ trim($text) }}</div>
                            </div>
                        @endforeach
                        @if(!empty($payload['cameraTypes']) && is_array($payload['cameraTypes']))
                            <div class="fs-7 fw-bold mb-1">Типы камер</div>
                            <ul class="fs-8">
                                @foreach($payload['cameraTypes'] as $type)
                                    <li>{{ $type['type'] ?? '' }} — {{ $type['count'] ?? 0 }} шт. <span class="text-muted">{{ $type['specs'] ?? '' }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- JSON --}}
{{--                    <div class="tab-pane" id="ext_tab_json" role="tabpanel">--}}
{{--                        <pre class="bg-light rounded p-3">{{ json_encode($external->payload ?: $external->list_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>--}}
{{--                    </div>--}}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="light" onclick="javascript:box_close();">
            <span>Закрыть</span>
        </x-ui.button.default>

        @if($preview)
            <x-ui.a.box href="{{ route('external_proposal.box_transfer', $external) }}" btn_type="{{ $external->proposal_group ? 'light-primary' : 'primary' }}">
                <i class="fa-light fa-arrow-right-to-bracket me-2"></i>
                <span>{{ $external->proposal_group ? 'Перенести ещё раз как новое КП' : 'Перенести в КП' }}</span>
            </x-ui.a.box>
        @endif
    </div>
@endsection
