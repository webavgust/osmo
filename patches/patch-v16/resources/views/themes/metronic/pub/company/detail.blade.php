@extends('layouts.layout')

@section('breadcrumb_right')
    <x-ui.a.default btn_type="info" href="{{ route('company.edit', $company) }}">
        Редактировать
    </x-ui.a.default>
@endsection

@section('content')
    <style>
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
                                                        <th class="text-center">Вар.</th>
                                                        <th class="text-center">Статус</th>
                                                        <th class="text-center">Сделка</th>
                                                        <th class="text-end">Сумма</th>
                                                        <th width="1"></th>
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
                                                            <td class="text-center"><x-proposal.status :proposal="$proposal"/></td>
                                                            <td class="text-center"><x-proposal.deal :proposal="$proposal"/></td>
                                                            <td class="text-end text-nowrap">{{ tools()->cost_normalize($proposal->cost_total) }} {{ $proposal->currency->symbol }}</td>
                                                            <td class="pe-3">
                                                                {{-- сквозная карточка: сделки, договоры, спецификации, платежи --}}
                                                                <a href="{{ route('deal_card.index', $proposal->group) }}"
                                                                   class="btn btn-sm btn-icon btn-primary"
                                                                   title="Сводная информация">
                                                                    <i class="fas fa-sitemap"></i>
                                                                </a>
                                                            </td>
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
            <div class="col-8">

                @if($company->specifications->isNotEmpty())
                    @php
                        // сверка сумм и привязанные КП — одним заходом на всю таблицу (patch v16)
                        $reconcile = \App\Modules\Pub\ContractSpecification\Services\SpecReconcileService::map($company->specifications);
                        $reconcile_alerts = \App\Modules\Pub\ContractSpecification\Services\SpecReconcileService::alerts($reconcile);

                        $spec_links = \App\Modules\Pub\ContractSpecification\Models\ContractSpecificationProposal::whereIn('contract_specification_id', $company->specifications->pluck('id'))->get();
                        $spec_proposals = \App\Modules\Pub\Proposal\Models\Proposal::latestIteration()->whereIn('group', $spec_links->pluck('proposal_group'))->get()->keyBy('group');
                    @endphp

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center pe-2">
                            <h3 class="m-0">
                                Спецификации
                                @if($reconcile_alerts)
                                    <a href="{{ route('payment_calendar.index', ['company' => $company->id]) }}"
                                       class="badge badge-light-danger ms-2 fs-8 text-decoration-none"
                                       title="Сумма спецификации не сходится с графиком платежей или с КП">
                                        <i class="fas fa-triangle-exclamation me-1"></i>{{ $reconcile_alerts }} с расхождением
                                    </a>
                                @endif
                            </h3>

                            {{-- платежи компании в общем календаре --}}
                            <a href="{{ route('payment_calendar.index', ['company' => $company->id]) }}"
                               class="btn btn-sm btn-light-primary">
                                <i class="fas fa-calendar-days me-2"></i>Платёжный календарь
                            </a>
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
                                <th width="210">КП</th>
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
                                            <td @class(['position-relative', 'bg-light-danger' => $spec->status == \App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus::CANCELED->value])>
                                                @php
                                                    $links_row = $spec_links->where('contract_specification_id', $spec->id);
                                                @endphp

                                                @if($links_row->isNotEmpty())
                                                    @foreach($links_row as $link)
                                                        @php $linked = $spec_proposals->get($link->proposal_group); @endphp
                                                        @continue(empty($linked))
                                                        <div class="d-flex align-items-center gap-1">
                                                            <a href="{{ route('proposal.detail', [$linked, $linked->iteration]) }}" class="fw-bold">
                                                                {{ $linked->number ?: 'б/н' }}
                                                            </a>
                                                            <x-proposal.status :proposal="$linked"/>
                                                        </div>
                                                        <div class="fs-1 text-muted">
                                                            {{ tools()->cost_normalize(round($linked->cost_total)) }} {{ $linked->currency->symbol ?? '' }}
                                                            @if(!empty($link->attached_at))
                                                                · {{ $link->attached_at->format('d.m.Y') }}
                                                            @endif
                                                        </div>
                                                    @endforeach

                                                    <a href="javascript:void(0)"
                                                       onclick="javascript:box({href:'{{ route('contract_spec.box_proposal', ['spec' => $spec]) }}'})"
                                                       class="key_edit always ms-2 position-absolute"
                                                       style="right: 5px; top: 5px;">
                                                        <x-ui.icon.regular icon="fa-edit"/>
                                                    </a>
                                                @else
                                                    <x-ui.a.box_clear href="{{ route('contract_spec.box_proposal', $spec) }}">
                                                        Прикрепить
                                                    </x-ui.a.box_clear>
                                                @endif
                                            </td>
                                            <td @class(['text-end', 'bg-light-danger' => $spec->status == \App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus::CANCELED->value])>
                                                {{ tools()->cost_normalize($spec->amount) }} ₽

                                                @php $check = $reconcile->get($spec->id); @endphp
                                                @if(!empty($check) && empty($check['skip']) && empty($check['ok']))
                                                    <div @class(['fs-1', 'text-danger' => $check['hard'], 'text-warning' => !$check['hard']])>
                                                        <i class="fas fa-triangle-exclamation me-1"></i>
                                                        @foreach($check['reasons'] as $reason)
                                                            <div>{{ $reason }}</div>
                                                        @endforeach
                                                    </div>
                                                    <div class="fs-1 text-muted">
                                                        платежи: {{ tools()->cost_normalize(round($check['payments'])) }}
                                                        @if($check['proposals'] !== null)
                                                            · КП: {{ tools()->cost_normalize(round($check['proposals'])) }}
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            @endforeach
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection
