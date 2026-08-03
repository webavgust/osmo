<div class="card card-body p-0" id="hardware_table" variant="{{ $variant->id }}">
    <div class="
                                                  invoice-header
                                                  d-flex
                                                  align-items-center
                                                  border-bottom
                                                  px-3 py-3
                                                " style="padding-bottom: 12px!important">
        <h3 class="font-weight-medium text-uppercase mb-0">
            Вычислительные ресурсы и оборудование
        </h3>

        <x-ui.a.box href="{{ route('hardware.box_add', $variant) }}">
            <i class="fas fa-add text-warning"></i>
        </x-ui.a.box>

    </div>

    @if($variant->hardware->isNotEmpty())
        <div class="m-3">
            <table id="table-summary" class="table no-wrap w-100">
                <tr class="caption">
                    <th class="text-center text-dark fw-bold" valign="top">Наименование</th>
                    <th class="text-center text-dark fw-bold" valign="top">Количество</th>
                    <th class="text-center text-dark fw-bold" valign="top">Параметры</th>
                    <th class="text-center text-dark fw-bold" valign="top" width="1"></th>
                </tr>

                @foreach($variant->hardware as $once)
                    <tr id="{{ $once->id }}">
                        <td class="align-top">{!! $once->name !!}</td>
                        <td class="align-top text-center">{!! $once->count !!}</td>
                        <td class="align-top text-wrap">{!! html_entity_decode($once->params) !!}</td>
                        <td>
                            <div class="d-flex justify-content-between align-items-center">
                                <x-ui.a.box href="{{ route('hardware.box_edit', $once) }}">
                                    <x-ui.icon.regular icon="fa-edit"/>
                                </x-ui.a.box>

                                <a href="javascript:void(0)" onclick="javascript:hardware_delete({{ $once->id }});" class="p-2">
                                    <x-ui.icon.regular icon="fa-xmark" class="text-danger" />
                                </a>
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
