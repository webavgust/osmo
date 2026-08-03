@extends('layouts.layout')

@section('breadcrumb_right')
    <x-ui.a.default btn_type="info" href="{{ route('company.edit', $company) }}">
        Редактировать
    </x-ui.a.default>
@endsection

@section('content')
    <style>
        #payments[mode='summary'] .payment_pad[mode='table'],
        #payments[mode='table'] .payment_pad[mode='summary'] {
            display: none
        }

        .key_row {
            border-left: 7px solid rgba(0, 0, 0, 0);
            border-left-width: 4px !important;
        }

        .key_edit {
            opacity: 0
        }

        .key_row:hover .key_edit, .key_edit.always {
            opacity: .3
        }

        .key_row:hover {
            border-left-color: #1e88e5 !important
        }

        .key_edit:hover {
            opacity: 1 !important
        }
    </style>


    <div class="container-fluid">
        <div class="row">
            <div class="col-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="m-0">Общая информация</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="card-table m-4">
                            <x-ui.card.card_table_tr field="Название">{{ $company->name }}</x-ui.card.card_table_tr>
                            <x-ui.card.card_table_tr field="Партнёр">
                                @php $partner_data = \App\Modules\Pub\Partner\Models\PartnerGrade::from($company->partner->grade)->data(); @endphp
                                <a href="{{ route('partner.detail', $company->partner) }}"
                                   style="color: {{ $partner_data['color']['medal'] }}">
                                    <x-ui.icon.solid icon="fa-medal" class="me-1"/>
                                    {{ $company->partner->name }}
                                </a>
                            </x-ui.card.card_table_tr>
                            <x-ui.card.card_table_tr field="Сектор">
                                {{ $company->sector->name }}
                            </x-ui.card.card_table_tr>
                            <x-ui.card.card_table_tr field="Страна">
                                {{ $company->country?->name ?? '' }}
                            </x-ui.card.card_table_tr>

                            <div class="mt-4 mb-2 fs-4 fw-bold">Контактные данные</div>
                            <x-ui.card.card_table_tr field="Телефон">-</x-ui.card.card_table_tr>
                            <x-ui.card.card_table_tr field="Эл.почта">-</x-ui.card.card_table_tr>
                            <x-ui.card.card_table_tr field="Адрес">-</x-ui.card.card_table_tr>
                            <x-ui.card.card_table_tr field="Контактное лицо">-</x-ui.card.card_table_tr>


                            <div class="mt-4 mb-2 fs-4 fw-bold">Финансовые показатели</div>
                            <x-ui.card.card_table_tr field="Оплаты (полученные)">
                                @if($company->payments['past']->isNotEmpty())
                                    <a href="javascript:void(0)"
                                       onclick="javascript:box({href:'{{ route('payment.box_past', $company) }}'})"
                                       class="mt-1 ms-1">
                                        {{ $company->payments['past']->count() }} шт.
                                        на {{ tools()->cost_normalize($company->payments['past']->sum('amount_fact')) }}
                                        ₽</a>
                                @else
                                    нет
                                @endif
                            </x-ui.card.card_table_tr>
                            <x-ui.card.card_table_tr field="Оплаты (будущие)">
                                @if($company->payments['future']->isNotEmpty())
                                    <a href="javascript:void(0)"
                                       onclick="javascript:box({href:'{{ route('payment.box_future', $company) }}'})"
                                       class="mt-1 ms-1">
                                        {{ $company->payments['future']->count() }} шт.
                                        на {{ tools()->cost_normalize($company->payments['future']->sum('amount_plan')) }}
                                        ₽</a>
                                @else
                                    нет
                                @endif
                            </x-ui.card.card_table_tr>
                        </div>
                    </div>
                </div>


                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center pe-2">
                        <h3 class="m-0">Проекты</h3>

                        <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                            <x-ui.a.box btn_type="success" href="{{ route('project.box.add', $company) }}"
                                        class=" ms-2">
                                <x-ui.icon.regular icon="fa-plus-circle"/>
                                Добавить проект
                            </x-ui.a.box>
                        </div>
                    </div>

                    @if($company->projects->isNotEmpty())
                        <table class="table table-bordered">
                            <tr>
                                <th width="140">Номер</th>
                                <th width="110">Срок (мес)</th>
                                <th width="40">Потоков</th>
                                <th>Коммент</th>
                                <th width="1"/>
                            </tr>
                            @foreach($company->projects as $project)
                                <tr>
                                    <td colspan="6" class="fw-bold">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <mark>{{ $project->prefix }}</mark>
                                                <span class="fs-6 ms-2">{{ $project->name }}</span>

                                                <x-ui.a.box_clear href="{{ route('project.box.edit', $project) }}"
                                                                  class="ms-1">
                                                    <x-ui.icon.regular icon="fa-edit"/>
                                                </x-ui.a.box_clear>
                                            </div>

                                            <div class="btn-toolbar" role="toolbar"
                                                 aria-label="Toolbar with button groups">
                                                <x-ui.a.box btn_type="success"
                                                            href="{{ route('project_configuration.box.add', $project) }}"
                                                            class=" ms-2">
                                                    <x-ui.icon.regular icon="fa-plus-circle"/>
                                                </x-ui.a.box>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @forelse($project->configurations_available as $configuration)
                                    <tr>
                                        <td class="fs-5">
                                            <code>{{ $configuration->number }}</code>
                                            <div class="fs-2">{{ \App\Modules\Pub\ProjectConfiguration\Models\ProjectConfigurationPlatform::from($configuration->platform)->data()['label'] }}</div>
                                        </td>
                                        <td class="text-center">@if($configuration->duration > 0)
                                                {{ $configuration->duration }} мес.
                                            @else
                                                Бессрочно
                                            @endif</td>
                                        <td class="text-center">{{ $configuration->streams }}</td>
                                        <td>
                                            @if(!empty($configuration->comment))
                                                {{ $configuration->comment }}
                                            @endif
                                        </td>
                                        <td>
                                            <x-ui.a.box_clear href="{{ route('project_configuration.box.edit', $configuration) }}">
                                                <x-ui.icon.regular icon="fa-edit"/>
                                            </x-ui.a.box_clear>
                                        </td>
                                    </tr>
                                @empty
                                @endforelse
                            @endforeach
                        </table>
                    @else
                        <div class="p-2">
                            Ни одного проекта пока не создано!
                        </div>
                        @endforelse


                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3>Коммерческие предложения</h3>
                        <a href="{{ route('proposal.create', ['company' => $company->id]) }}">Создать</a>
                    </div>
                    <div class="card-body p-0">

                        @if(!empty($proposals))
                            @foreach($proposals as $group)
                                <div class="accordion accordion-flush" id="accordionFlushExample">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="flush-heading{{ $loop->iteration }}">
                                            <button class="accordion-button collapsed bg-white" type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#flush-collapse{{ $loop->iteration }}"
                                                    aria-expanded="false"
                                                    aria-controls="flush-collapse{{ $loop->iteration }}">
                                                {{ $group['name'] }}
                                            </button>
                                        </h2>
                                        <div id="flush-collapse{{ $loop->iteration }}"
                                             class="accordion-collapse collapse bg-white"
                                             aria-labelledby="flush-heading{{ $loop->iteration }}"
                                             data-bs-parent="#accordionFlushExample">
                                            <div class="accordion-body p-0">
                                                <table class="table m-0">
                                                    <tr>
                                                        <th>Номер</th>
                                                        <th>Название</th>
                                                        <th class="text-center">Вариантов</th>
                                                        <th class="text-end">Сумма</th>
                                                    </tr>
                                                    @foreach($group['rows'] as $proposal)
                                                        <tr>
                                                            <td>{{ $proposal->number }}</td>
                                                            <td>
                                                                <a href="{{ route('proposal.detail', [$proposal, $proposal->iteration]) }}">
                                                                    {{ $proposal->name }}
                                                                    <sup>{{ $proposal->iteration }}</sup>
                                                                </a>
                                                            </td>
                                                            <td class="text-center">{{ $proposal->variants->count() }}</td>
                                                            <td class="text-end">{{ tools()->cost_normalize($proposal->cost_total) }} {{ $proposal->currency->symbol }}</td>
                                                        </tr>
                                                    @endforeach
                                                </table>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                </table>
                                @else
                                    <div class="d-flex justify-content-between p-3">
                                        <span>Нет созданных КП</span>
                                    </div>
                                @endif
                    </div>
                </div>

                <div class="card mt-2">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3>Лицензионные ключи</h3>
                        <a href="javascript:void(0)"
                           onclick="javascript:box({href:'{{ route('license-keys.box_add', ['company' => $company->id]) }}'})"
                           class="ms-2">
                            Прикрепить
                        </a>
                    </div>
                    <div class="card-body p-0">
                        @if($company->license_keys->isNotEmpty())
                            @foreach($company->license_keys as $key)
                                <div class="border-top border-1 p-3 position-relative key_row">
                                    <div class="mb-2 fs-3 fw-bold d-flex">
                                        <span>{{ $key->key }}</span>

                                        <a href="javascript:void(0)"
                                           onclick="javascript:box({href:'{{ route('license-keys.box_edit', ['license_key' => $key]) }}'})"
                                           class="key_edit ms-2 position-absolute" style="right: 5px; top: 5px;">
                                            <x-ui.icon.regular icon="fa-edit"/>
                                        </a>
                                    </div>

                                    <div @class(["d-flex justify-content-between ", "text-muted" => !$key->active, "text-dark" => $key->active])>
                                        <div>
                                            @if(!empty($key->specification))
                                                <div class="d-flex flex-column align-items-start">
                                                    <div>
                                                        @php
                                                            $info = \App\Modules\Pub\Contract\Models\ContractType::from($key->specification->contract->type)->data();
                                                        @endphp
                                                        <x-ui.icon.regular :icon="$info['icon']" class="me-1 fs-5"/>
                                                        {{ $info['label'] }}

                                                        @if(!empty($key->specification->contract->number))
                                                            <x-ui.badge.light type="secondary"
                                                                              class="ms-1">{{ $key->specification->contract->number ?? '-'}}</x-ui.badge.light>
                                                        @endif
                                                    </div>

                                                    <mark class="fs-2 mt-1">{{ $key->specification->name }}</mark>
                                                </div>
                                            @else
                                                Без спецификации
                                            @endif
                                        </div>

                                        <div>
                                            @if($key->active && $key->active_to->lessThan(now()))
                                                <span style="color: #dc0404">
                                                {{ $key->active_from->format("d.m.Y") }}
                                                <x-ui.icon.regular icon="fa-dash"/>
                                                {{ $key->active_to->format("d.m.Y") }}
                                            </span>
                                            @elseif($key->active && $key->active_to->greaterThan(now()) && $key->active_to->subMonths(3)->lessThan(now()))
                                                <span style="color: #ff6c00">
                                                {{ $key->active_from->format("d.m.Y") }}
                                                <x-ui.icon.regular icon="fa-dash"/>
                                                {{ $key->active_to->format("d.m.Y") }}
                                            </span>
                                            @else
                                                <span>
                                                {{ $key->active_from->format("d.m.Y") }}
                                                <x-ui.icon.regular icon="fa-dash"/>
                                                {{ $key->active_to->format("d.m.Y") }}
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="d-flex justify-content-between p-3">
                                <span>Нет лицензионных ключей</span>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
            <div class="col-8" id="payments" mode="summary">

                @if($company->specifications->isNotEmpty())
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center pe-2">
                            <h3 class="m-0">Спецификации</h3>
                        </div>

                        <table class="table table-bordered">
                            <tr>
                                <th width="170">Тип</th>
                                <th width="100" class="text-center">Номер</th>
                                <th>Название</th>
                                <th>
                                    <div class="d-flex justify-content-between">
                                        <span>Ключи</span>

                                        <a href="javascript:void(0)"
                                           onclick="javascript:box({href:'{{ route('license-keys.box_add', ['company' => $company->id]) }}'})"
                                           class="ms-2">
                                            <x-ui.icon.regular icon="fa-circle-plus"/>
                                        </a>
                                    </div>
                                </th>
                                <th width="160">Конфигурации</th>
                                <th width="120">Сумма оплат</th>
                            </tr>

                            @foreach($specsGrouped as $type => $group)
                                @php
                                    $type_decorate = \App\Modules\Pub\Contract\Models\ContractType::from($type)->data();
                                    $loop_group = $loop;
                                @endphp

                                @foreach($group as $instance)
                                    @foreach($instance['specs'] as $spec)
                                        <tr>

                                            @if($loop->first)
                                                <td rowspan="{{ $group->sum(fn($group) => $group['specs']->count()) }}">
                                                 <span class="fw-bold text-{{ $type_decorate['color'] }} fs-5">
                                                    <x-ui.icon.regular :icon="$type_decorate['icon']" class="me-1 fs-5"/>
                                                    {{ $type_decorate['label'] }}
                                                </span>
                                                    <div class="fs-1  text-secondary"
                                                         style="margin-left: 29px;">{{ $spec->contract->organization->name }}</div>
                                                </td>

                                                <td class="text-center"  rowspan="{{ $instance['specs']->count() }}">
                                                    @if(!empty($spec->contract->number))
                                                        <x-ui.badge.light
                                                            type="secondary">{{ $spec->contract->number ?? '-'}}</x-ui.badge.light>
                                                        <div
                                                            class="fs-1">{{ $spec->contract->date?->format("d.m.Y") ?? '-' }}</div>
                                                    @endif
                                                </td>
                                            @endif

                                            <td @class(['bg-light-danger' => $spec->status == \App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus::CANCELED->value])>
                                                {{ $spec->name }}
                                            </td>
                                            <td width="400"  @class(['p-0', 'bg-light-danger' => $spec->status == \App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus::CANCELED->value])>
                                                @foreach($spec->license_keys as $key)
                                                    <div @class(["border-top border-1 p-1 position-relative key_row", "d-none" => !$key->active])>
                                                        <div @class(["d-flex justify-content-between", "text-danger" => !$key->active, "text-dark" => $key->active])>
                                                            <div class="fw-bold">
                                                                @if($key->active && $key->active_to->lessThan(now()))
                                                                    <span style="color: #dc0404">
                                                                    {{ $key->active_from->format("d.m.Y") }}
                                                                    <x-ui.icon.regular icon="fa-dash"/>
                                                                    {{ $key->active_to->format("d.m.Y") }}
                                                                </span>
                                                                @elseif($key->active && $key->active_to->greaterThan(now()) && $key->active_to->subMonths(3)->lessThan(now()))
                                                                    <span style="color: #ff6c00">
                                                                    {{ $key->active_from->format("d.m.Y") }}
                                                                    <x-ui.icon.regular icon="fa-dash"/>
                                                                    {{ $key->active_to->format("d.m.Y") }}
                                                                </span>
                                                                @else
                                                                    <span>
                                                                    {{ $key->active_from->format("d.m.Y") }}
                                                                    <x-ui.icon.regular icon="fa-dash"/>
                                                                    {{ $key->active_to->format("d.m.Y") }}
                                                                </span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="mb-2 fs-3 fw-bold d-flex">
                                                            <span>{{ $key->key }}</span>

                                                            <a href="javascript:void(0)"
                                                               onclick="javascript:box({href:'{{ route('license-keys.box_edit', ['license_key' => $key]) }}'})"
                                                               class="key_edit ms-2 position-absolute"
                                                               style="right: 5px; top: 5px;">
                                                                <x-ui.icon.regular icon="fa-edit"/>
                                                            </a>
                                                        </div>
                                                    </div>
                                                @endforeach

                                                @if($spec->license_keys()->where('active', 0)->count() > 0)
                                                    <div class="p-2 cursor-pointer text-info"
                                                         onclick="$(this).parents('td').find('.d-none').removeClass('d-none');$(this).remove();">
                                                        + {{ tools()->num_rus($spec->license_keys()->where('active', 0)->count(), ["неактивных ключа", "неактивный ключ", "неактивных ключей"], 1) }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td  @class(['position-relative', 'bg-light-danger' => $spec->status == \App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus::CANCELED->value])>
                                                @if($spec->project_configurations->isNotEmpty())
                                                    @foreach($spec->project_configurations as $config)
                                                        <code
                                                            @class(["fs-5", "cursor-help" => !empty($config->comment)])
                                                            @if(!empty($config->comment))
                                                                data-bs-toggle="tooltip" data-bs-placement="bottom" title=""
                                                            data-bs-original-title="{{ $config->comment }}"
                                                            @endif
                                                        >{{ $config->number }}</code>
                                                    @endforeach




                                                    <a href="javascript:void(0)"
                                                       onclick="javascript:box({href:'{{ route('contract_spec.box_project_configuration', ['spec' => $spec]) }}'})"
                                                       class="key_edit always ms-2 position-absolute"
                                                       style="right: 5px; top: 5px;">
                                                        <x-ui.icon.regular icon="fa-edit"/>
                                                    </a>
                                                @elseif($company->configurations_available->isNotEmpty())
                                                    <x-ui.a.box_clear
                                                        href="{{ route('contract_spec.box_project_configuration', $spec) }}">
                                                        Прикрепить
                                                    </x-ui.a.box_clear>
                                                @endif
                                            </td>
                                            <td @class(['text-end', 'bg-light-danger' => $spec->status == \App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus::CANCELED->value])>
                                                {{ tools()->cost_normalize($spec->amount) }} ₽
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            @endforeach
                        </table>
                    </div>
                @endif


                <div class="card" style="opacity: .3">
                    <div class="card-header d-flex justify-content-between align-items-center pe-2">
                        <h3 class="m-0">Оплаты (DEPRICATED)</h3>

                        <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                            <div class="btn-group me-2" role="group" aria-label="First group">
                                <button type="button" class="
                                    btn btn-info
                                " mode="summary">
                                    <x-ui.icon.regular icon="fa-list"/>
                                </button>
                                <button type="button" mode="table" class="
                                      btn btn-light-info
                                      text-info
                                      font-weight-medium">
                                    <x-ui.icon.regular icon="fa-table"/>
                                </button>
                            </div>


                            <x-ui.a.box btn_type="success" href="{{ route('contract.box_add', $company) }}"
                                        class=" ms-2">
                                <x-ui.icon.regular icon="fa-plus-circle"/>
                            </x-ui.a.box>
                        </div>
                    </div>

                    <div class="payment_pad" mode="summary">
                        @if($company->contracts->isEmpty())
                            <div class="p-3">
                                Пока нет добавленных договоров
                            </div>
                        @else
                            @foreach($contracts as $group)
                                <div class="proposal">
                                    <div
                                        class="px-3 card-header bg-light-secondary d-flex justify-content-between align-items-center">
                                        <div>
                                            @if(empty($group['proposal']))
                                                <b>КП: {{ $group['proposal_name'] ?? 'Неизвестно' }}</b>
                                                <a href="#" class="ms-1">(создать КП)</a>
                                            @else
                                                <b>КП:</b>
                                                <a href="{{ route('proposal.detail', [$group['proposal'], $group['proposal']->iteration]) }}">
                                                    {{ $group['proposal']->name }}
                                                    <sup>{{ $group['proposal']->iteration }}</sup>
                                                </a>
                                            @endif
                                        </div>

                                        <div>

                                            <x-ui.badge.default type="white" class="text-dark">
                                                {{ tools()->cost_normalize($group['rows']->flatMap->payments->sum('amount_fact') ?? 0) }}
                                                ₽
                                                /
                                                {{ tools()->cost_normalize($group['rows']->sum('amount') ?? 0) }} ₽
                                                /
                                                @if(!empty($group['proposal']))
                                                    @if($group['proposal']->isForeignCurrency)
                                                        {{ tools()->cost_normalize(round($group['proposal']->cost_total * $group['proposal']->currency_rate)) }}
                                                        ₽
                                                        ({{ $group['proposal']->cost_total }} {{$group['proposal']->currency->symbol}}
                                                        )
                                                    @else
                                                        {{ tools()->cost_normalize($group['proposal']->cost_total) }} ₽
                                                    @endif
                                                @else
                                                    ?
                                                @endif
                                            </x-ui.badge.default>

                                            <a href="javascript:void(0)"
                                               onclick="javascript:box({href:'{{ route('contract.box_add', [$company, 'proposal' => $group['proposal']->id ?? 0]) }}'})"
                                               class="mt-1 ms-1">
                                                <x-ui.icon.regular icon="fa-circle-plus"/>
                                            </a>
                                        </div>
                                    </div>

                                    {{--                                    <div class="progress" style="height: 14px">--}}
                                    {{--                                        <div class="progress-bar bg-secondary fs-1" role="progressbar" style="width: 100%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">10 000р. (57%)</div>--}}
                                    {{--                                    </div>--}}


                                    @foreach($group['rows'] as $contract)
                                        <div class="card-body p-0">
                                            <div class="p-3 pb-2 d-flex justify-content-between align-items-center">
                                                <div class="d-flex flex-grow-1">
                                                <span class="fw-bold text-{{ $contract->type_decorate['color'] }} fs-5">
                                                    <x-ui.icon.regular :icon="$contract->type_decorate['icon']"
                                                                       class="me-1 fs-5"/>
                                                    {{ $contract->type_decorate['label'] }}

                                                    @if($contract->cb_signed)
                                                        <x-ui.badge.default
                                                            type="success">Подписано</x-ui.badge.default>
                                                    @endif
                                                </span>

                                                    @if(!empty($contract->number))
                                                        <div class="d-flex justify-content-end ms-2">
                                                    <span class="text-center">
                                                        <x-ui.badge.light
                                                            type="secondary">{{ $contract->number ?? '-'}}</x-ui.badge.light>
                                                        <div
                                                            class="fs-1">{{ $contract->date?->format("d.m.Y") ?? '-' }}</div>
                                                    </span>
                                                        </div>
                                                    @endif
                                                </div>


                                                <div class="d-flex justify-content-end">
                                                <span class="text-center">
                                                    <x-ui.badge.default type="{{ $contract->type_decorate['color'] }}">{{ tools()->cost_normalize($contract->amount) }} ₽</x-ui.badge.default>
                                                    <div class="fs-1">{{ $contract->organization->name }}</div>
                                                </span>

                                                    <a href="javascript:void(0)"
                                                       onclick="javascript:box({href:'{{ route('contract.box_edit', $contract) }}'})"
                                                       class="ms-2">
                                                        <x-ui.icon.regular icon="fa-edit"/>
                                                    </a>
                                                </div>

                                            </div>


                                            @if(!$contract->contract_specifications->isEmpty())
                                                @foreach($contract->contract_specifications as $spec_i => $spec)
                                                    <div
                                                        class="ms-2 ps-5 pe-3 pb-2 d-flex justify-content-between align-items-center">
                                                        <span>
{{--                                                            <x-ui.icon.solid icon="fa-circle-dot" class="me-1"/>--}}
                                                            <span
                                                                class="fw-bold">{{ $spec_i + 1 }}) {{ $spec->name }}</span>



                                                            @if($spec->contract_specification_scenarios->isNotEmpty())
                                                                + {{ tools()->num_rus($spec->contract_specification_scenarios->count(), ['сценария', 'сценарий', 'сценариев'], true) }}
                                                            @endif
                                                        </span>

                                                        <div class="d-flex justify-content-end">
                                                            <span class="text-center">
                                                                <x-ui.badge.light type="secondary">
                                                                    {{ tools()->cost_normalize($spec->amount) }} ₽
                                                                </x-ui.badge.light>
                                                            </span>

                                                            <a href="javascript:void(0)"
                                                               onclick="javascript:box({href:'{{ route('contract_spec.box_edit', $spec) }}'})"
                                                               class="ms-2">
                                                                <x-ui.icon.regular icon="fa-edit"/>
                                                            </a>
                                                        </div>
                                                    </div>


                                                    @if($spec->payments->isNotEmpty())
                                                        <div class="card-table m-3 ms-5 ps-5 mt-0">
                                                            @foreach($spec->payments as $payment)
                                                                <div class="tr text-{{ $payment->status['color'] }}">
                                                                <span class="th align-items-center">
                                                                    <span class="me-1 text-center" style="width: 20px">
                                                                        <x-ui.icon.solid
                                                                            icon="{{ $payment->status['icon'] }}"
                                                                            class=""/>
                                                                    </span>

                                                                    @if(!empty($payment->date_plan))
                                                                        {{ $payment->date_plan?->format("d.m.Y") ?? '-'}}
                                                                    @endif
                                                                    @if(!empty($payment->date_fact) && !$payment->date_fact->isSameDay($payment->date_plan))

                                                                        @if(!empty($payment->date_plan))
                                                                            <x-ui.icon.regular icon="fa-arrow-right"
                                                                                               class="mx-2"/>
                                                                        @endif

                                                                        {{ $payment->date_fact->format("d.m.Y") }}

                                                                        @if(!empty($payment->delay))
                                                                            (+ {{ tools()->num_rus($payment->delay, ['дня', 'день', 'дней'], true) }}
                                                                            )
                                                                        @endif
                                                                    @endif
                                                                </span>
                                                                    <span class="td">
                                                                    @if(!empty($payment->amount_plan))
                                                                            {{ $payment->amount_plan ? tools()->cost_normalize($payment->amount_plan) : '?' }}
                                                                            ₽
                                                                        @endif

                                                                        @if(!empty($payment->amount_fact) && $payment->amount_plan !== $payment->amount_fact)
                                                                            @if(!empty($payment->amount_plan))
                                                                                <x-ui.icon.regular icon="fa-arrow-right"
                                                                                                   class="mx-2"/>
                                                                            @endif

                                                                            {{ tools()->cost_normalize($payment->amount_fact) }}
                                                                            ₽
                                                                        @endif
                                                                </span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    <div class="d-flex justify-content-end ps-5 pe-3 pb-4">
                                                        <a href="javascript:void(0)"
                                                           onclick="javascript:box({href:'{{ route('payment.box_control', $spec) }}'})"
                                                           class="ms-2">
                                                            <x-ui.icon.regular icon="fa-edit"/>
                                                            Управлять платежами
                                                        </a>
                                                    </div>
                                                @endforeach
                                            @endif


                                            <div class="ms-4 mb-3 px-2 ps-0">
                                                <a href="javascript:void(0)"
                                                   onclick="javascript:box({href:'{{ route('contract_spec.box_add', $contract) }}'})">
                                                    <x-ui.icon.regular icon="fa-plus"/>
                                                    Добавить спецификацию
                                                </a>
                                            </div>

                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        @endif


                    </div>

                    <div class="payment_pad" mode="table">
                        <table class="table table-bordered">
                            <tr>
                                <th rowspan="2">КП</th>
                                <th rowspan="2">Тип</th>
                                <th rowspan="2">Спецификация</th>
                                <th colspan="2" class="p-1 text-center">Дата</th>
                                <th colspan="2" class="p-1 text-center">Оплата</th>
                            </tr>
                            <tr>
                                <th class="p-1 text-center" width="100">План</th>
                                <th class="p-1 text-center" width="100">Факт</th>
                                <th class="p-1 text-center" width="100">План</th>
                                <th class="p-1 text-center" width="100">Факт</th>
                            </tr>
                            @php
                                $contract_count = 0;
                                $group_index = 0;
                                $amount_plan = $amount_fact = 0;
                            @endphp
                            @foreach($contracts as $group)
                                @php
                                    $group_index++;
                                @endphp
                                @foreach($group['rows'] as $contract_index => $contract)
                                    @if($contract->contract_specifications->isNotEmpty())
                                        @foreach($contract->contract_specifications as $spec)
                                            @if($spec->payments->isNotEmpty())
                                                @php
                                                    $contract_count++;
                                                @endphp
                                                @foreach($spec->payments as $payment_index => $payment)
                                                    @php
                                                        $amount_plan += $payment->amount_plan;
                                                        $amount_fact += $payment->amount_fact;
                                                    @endphp
                                                    <tr style="
                                                        @if($contract_count % 2 == 1) background: #F8F8F8; @endif
                                                        @if($group_index > 1 && $payment_index == 0) border-top: 2px solid; @endif
                                                    ">
                                                        <td>
                                                            @if(empty($group['proposal']))
                                                                Без КП
                                                            @else
                                                                <a href="{{ route('proposal.detail', [$group['proposal'], $group['proposal']->iteration]) }}">
                                                                    {{ $group['proposal']->name }}
                                                                    <sup>{{ $group['proposal']->iteration }}</sup>
                                                                </a>
                                                            @endif
                                                        </td>
                                                        <td class="text-{{ $contract->type_decorate['color'] }}">{{ $contract->type_decorate['label'] }}</td>
                                                        <td>{{ $spec->name }}</td>
                                                        <td class="text-center">{{ $payment->date_plan?->format("d.m.Y") ?? '-' }}</td>
                                                        <td class="text-center">{{ $payment->date_fact?->format("d.m.Y") ?? '-' }}</td>
                                                        <td class="text-center">
                                                            @if(!empty($payment->amount_plan))
                                                                {{ tools()->cost_normalize($payment->amount_plan) }} ₽
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if(!empty($payment->amount_fact))
                                                                {{ tools()->cost_normalize($payment->amount_fact) }} ₽
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    @endif
                                @endforeach
                            @endforeach


                            <tr>
                                <td colspan="5"/>
                                <td class="text-end fw-bold text-nowrap">= {{ tools()->cost_normalize($amount_plan) }}
                                    ₽
                                </td>
                                <td class="text-end fw-bold text-nowrap">= {{ tools()->cost_normalize($amount_fact) }}
                                    ₽
                                </td>
                            </tr>

                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            $("#payments button[mode]").on("click", function() {
                var mode = $(this).attr("mode");

                $("#payments").attr("mode", mode);
                $("#payments").find("button[mode]").addClass("btn-light-info text-info btn-info");
                $("#payments").find("button[mode='" + mode + "']").addClass("btn-info").removeClass("btn-light-info text-info");

            });
        });
    </script>
@endsection
