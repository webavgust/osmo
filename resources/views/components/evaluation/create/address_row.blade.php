<li class="address" uid="{{$uid}}">
    <div class="content">
        <div class="del" onclick="javascript:li_del(this)">
            <i class="fa-solid fa-delete-left"></i>
        </div>
        <div class="row align-items-center">
            <div class="col-12 col-sm-3 d-flex align-items-center mb-2 mb-sm-0">
                <i class="fa-solid fa-chevron-left me-2" style="transform: rotate(-45deg)"></i>
                <h5 class="font-weight-medium fs-4 todo-header m-0 text-danger">Адрес {{ $num }}</h5>
            </div>
            <div class=" offset-1 col-11 col-sm-9 offset-sm-0">
                <div class="form-group">
                    <div class="input-group">
                        @if(!empty($address))
                            <input type="text" class="form-control address flex-grow-1" aria-describedby="name"
                                   placeholder="Укажите адрес" name="address[{{$parent}}][{{$uid}}][address]"
                                   value="{{ $address->address }}">
                        @else
                            <input type="text" class="form-control address flex-grow-1" aria-describedby="name"
                                   placeholder="Укажите адрес" name="address[{{$parent}}][{{$uid}}][address]">
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="row align-items-center mt-1">
            <div class="col-12 col-sm-4 d-flex align-items-center mb-2 mb-sm-0">
                <i class="fa-solid fa-chevron-left me-2" style="transform: rotate(-45deg)"></i>
                <h5 class="font-weight-medium fs-4 todo-header m-0">Командировочные расходы</h5>
            </div>
            <div class=" offset-1 col-11 col-sm-8 offset-sm-0">
                <div class="input-group">
                    <input type="number" min="0" style="width: 100px" class="form-control inp_cost flex-grow-0"
                           name="address[{{$parent}}][{{$uid}}][expanses]" value="{{$address?->expanses ?? null}}" placeholder="Расходы">

                    <span class="input-group-text">
                        <x-ui.icon.solid icon="fa-ruble-sign"></x-ui.icon.solid>
                    </span>
                </div>
            </div>
        </div>
        <div class="row align-items-center mt-1">
            <div class="col-12 col-sm-4 d-flex align-items-center mb-2 mb-sm-0">
                <i class="fa-solid fa-chevron-left me-2" style="transform: rotate(-45deg)"></i>
                <h5 class="font-weight-medium fs-4 todo-header m-0">Транспортные расходы</h5>
            </div>
            <div class=" offset-1 col-11 col-sm-8 offset-sm-0">
                <div class="input-group">
                    <input type="number" min="0" style="width: 100px" class="form-control inp_cost flex-grow-0"
                           name="address[{{$parent}}][{{$uid}}][transport]" value="{{$address?->transport ?? null}}" placeholder="Расходы">

                    <span class="input-group-text">
                        <x-ui.icon.solid icon="fa-ruble-sign"></x-ui.icon.solid>
                    </span>
                </div>
            </div>
        </div>

        @php
            $specialist_cost = $address?->specialist['cost'] ?? \App\Modules\Pub\Constant\Models\Constant::get('address_visit_cost')

        @endphp
        <div class="row align-items-center mt-1">
            <div class="col-12 col-sm-4 d-flex align-items-center mb-2 mb-sm-0">
                <i class="fa-solid fa-chevron-left me-2" style="transform: rotate(-45deg)"></i>
                <h5 class="font-weight-medium fs-4 todo-header m-0">Выезд специалиста</h5>
            </div>
            <div class=" offset-1 col-11 col-sm-8 offset-sm-0">
                <div class="input-group">
                    <span class="input-group-text" title="Количество">
                        <i class="fa-solid fa-xmark"></i>
                    </span>
                    <input name="address[{{$parent}}][{{$uid}}][specialist]" type="number" min="0" value="{{ $address?->specialist['count'] ?? 1 }}" class="form-control text-start inp_count flex-grow-0 specialist_count" style="width: 60px" cost="{{ $specialist_cost  }}">
                    <span class="input-group-text">
                          * {{ tools()->cost_normalize($specialist_cost) }} ₽
                    </span>
                    <span class="input-group-text fs-2 px-2 justify-content-end" title="Сумма" style="width: 85px">
                        = <span class="specialist_amount mx-1">{{ tools()->cost_normalize(($address?->specialist['count'] ?? 1) * $specialist_cost) }}</span>  ₽
                    </span>
                </div>
            </div>
        </div>
    </div>
    <ul class="points">
        @forelse($address->points ?? [] as $i => $point)
            <x-evaluation.create.point_row parent="{{$uid}}" :point="$point"
                                           :num="$i + 1"></x-evaluation.create.point_row>
        @empty
            <x-evaluation.create.point_row parent="{{$uid}}"></x-evaluation.create.point_row>
        @endforelse

    </ul>
    <x-ui.button.outline btn_type="warning" onclick="javascript:add_point('{{$uid}}')" class="add_point"><i
            class="fa-thin fa-circle-plus me-1"></i> точку
    </x-ui.button.outline>

</li>
