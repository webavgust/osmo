<div class="card">
    <div class="card-body p-0" id="hardware_table" variant="{{ $variant->id }}">
        <div class="
                                                      invoice-header
                                                      d-flex
                                                      align-items-center
                                                      border-bottom
                                                      px-3 py-3
                                                    " style="padding-bottom: 12px!important">
            <h3 class="fw-semibold text-uppercase mb-0">
                Дополнительные начисления.
            </h3>

            <x-ui.a.box href="{{ route('proposal-variant-extra-pay.box_edit', $variant) }}">
                <i class="fas fa-edit text-warning"></i>
            </x-ui.a.box>

        </div>

        @if($variant->extra_pays->isNotEmpty())
            <div class="m-3">
                <table id="table-summary" class="table no-wrap w-100">
                    <tr class="caption">
                        <th width="1" class="text-center text-dark fw-bold" valign="top">Цель</th>
                        <th class="text-start text-dark fw-bold" valign="top">Наименование</th>
                        <th width="1" class="text-center text-dark fw-bold" valign="top">Процент</th>

                        <th width="1" class="text-center text-dark fw-bold" valign="top">ПО</th>
                        <th width="1" class="text-center text-dark fw-bold" valign="top">Работы</th>

                        <th width="1" class="text-center text-dark fw-bold" valign="top" width="1">Итого КП</th>
                    </tr>

                    @foreach($variant->extra_pays as $once)
                        <tr id="{{ $once->id }}">
                            <td class="px-3 align-top text-center text-nowrap">
                                @switch($once->block)
                                    @case("all")
                                        <strong class="text-primary">Всё КП</strong>
                                        @break
                                    @case("software")
                                        <span class="text-secondary">
                                            <x-ui.icon.regular icon="fa-desktop" class="me-1"/>
                                            ПО
                                        </span>
                                        @break
                                    @case("work")
                                        <span class="text-warning">
                                            <x-ui.icon.regular icon="fa-person-digging" class="me-1"/>
                                            Работы
                                        </span>
                                        @break
                                @endswitch
                            </td>
                            <td class="align-top">{!! $once->name ?? 'Без названия' !!}</td>
                            <td class="px-3 align-top text-wrap">
                                <div class="text-nowrap d-flex justify-content-between">
                                    <span class="fw-bold">{{ $once->percent }} %</span>
                                    <span>
                                        <x-ui.icon.regular icon="fa-equals" class="mx-2"/>
                                        <span>
                                            {{ tools()->cost_normalize($once->value) }}
                                            {{ $variant->proposal->currency->symbol }}
                                        </span>
                                        </span>
                                </div>
                            </td>

                            <td class="px-3 text-center text-nowrap">
                                @if($once->software_start !== $once->software_end)
                                    <x-ui.badge.light type="danger">
                                        {{ tools()->cost_normalize($once->software_start) }}
                                        {{ $variant->proposal->currency->symbol }}
                                    </x-ui.badge.light>

                                    <x-ui.icon.regular icon="fa-arrow-right" class="mx-2"/>

                                    <x-ui.badge.light type="success">
                                        {{ tools()->cost_normalize($once->software_end) }}
                                        {{ $variant->proposal->currency->symbol }}
                                    </x-ui.badge.light>
                                @else
                                    <x-ui.badge.light type="secondary">
                                        {{ tools()->cost_normalize($once->software_start) }}
                                        {{ $variant->proposal->currency->symbol }}
                                    </x-ui.badge.light>
                                @endif
                            </td>

                            <td class="px-3 text-center text-nowrap">
                                @if($once->work_start !== $once->work_end)
                                    <x-ui.badge.light type="danger">
                                        {{ tools()->cost_normalize($once->work_start) }}
                                        {{ $variant->proposal->currency->symbol }}
                                    </x-ui.badge.light>

                                    <x-ui.icon.regular icon="fa-arrow-right" class="mx-2"/>

                                    <x-ui.badge.light type="success">
                                        {{ tools()->cost_normalize($once->work_end) }}
                                        {{ $variant->proposal->currency->symbol }}
                                    </x-ui.badge.light>
                                @else
                                    <x-ui.badge.light type="secondary">
                                        {{ tools()->cost_normalize($once->work_start) }}
                                        {{ $variant->proposal->currency->symbol }}
                                    </x-ui.badge.light>
                                @endif
                            </td>
                            <td class="px-3">
                                <div class="text-nowrap text-end">
                                    {{ tools()->cost_normalize($once->total) }}
                                    {{ $variant->proposal->currency->symbol }}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @else
            <div class="px-4 py-3">Тут пока нет записей</div>
        @endif
    </div>
</div>
