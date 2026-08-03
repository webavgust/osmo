<li  uid="{{$uid}}" @class(['needbind', 'object'])>
    <div class="content bg-info">
        <div class="del" onclick="javascript:li_del(this)">
            <i class="fa-solid fa-delete-left"></i>
        </div>
        <div class="row align-items-center">
            <div class="col-12 col-sm-2 d-flex align-items-center mb-1 mb-sm-0">
                <h5 class="font-weight-medium fs-4 todo-header m-0 text-white">Объект&nbsp;{{ $num }}</h5>
            </div>
            <div class="col-12 col-sm-4 mb-1 mb-sm-0">
                <div class="form-group">
                    @if(empty($object))
                        <input type="text" class="form-control object_name" aria-describedby="name" placeholder="Название"
                               value="Объект {{ $num }}" name="object[{{$uid}}][name]">
                    @else
                        <input type="text" class="form-control object_name" aria-describedby="name" placeholder="Название"
                               value="{{ $object->name }}" name="object[{{$uid}}][name]">
                    @endif
                </div>
            </div>
            <div class="col-12 col-sm-5">
                <div class="form-group">
                    <select class="form-select object_type select2 lab_object" name="object[{{$uid}}][type]">
                        <option selected="" value="0">Выберите тип объекта...</option>
                        @foreach($lab_objects as $lab_object)
                            <option value="{{ $lab_object?->id }}" @selected($object?->lab_object?->id == $lab_object->id)>{{ $lab_object->chain_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
    <ul class="addresses">
        @forelse($object->addresses ?? [] as $i => $address)
            <x-evaluation.create.address_row parent="{{$uid}}" :address="$address" :num="$i + 1"></x-evaluation.create.address_row>
        @empty
            <x-evaluation.create.address_row parent="{{$uid}}"></x-evaluation.create.address_row>
        @endforelse
    </ul>

    <x-ui.button.outline btn_type="danger" onclick="javascript:add_address('{{$uid}}')" class="add_address"><i class="fa-thin fa-circle-plus me-1"></i> адрес</x-ui.button.outline>
</li>
