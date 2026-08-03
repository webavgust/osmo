@php
    use App\Modules\Pub\LabObject\Models\LabObject;
    if(empty($uid)) $uid = Str::uuid()->toString();;
    if(empty($lab_objects))
        $lab_objects = LabObject::where('is_last', 1)->get();
    if(empty($num)) $num = 1;
@endphp

<li uid="{{$uid}}">
    <div class="content bg-info">
        <div class="del" onclick="javascript:li_del(this)">
            <i class="fa-solid fa-delete-left"></i>
        </div>
        <div class="row align-items-center">
            <div class="col-12 col-sm-3 d-flex align-items-center mb-1 mb-sm-0">
                <h5 class="font-weight-medium fs-4 todo-header m-0 text-white">Объект&nbsp;{{ $num }}</h5>
            </div>
            <div class="col-12 col-sm-3 mb-1 mb-sm-0">
                <div class="form-group">
                    <input type="text" class="form-control object_name" aria-describedby="name" placeholder="Название"
                           value="Объект {{ $num }}" name="object[{{$uid}}][name]">
                </div>
            </div>
            <div class="col-12 col-sm-6">
                <div class="form-group">
                    <select class="form-select object_type select2" name="object[{{$uid}}][type]">
                        <option selected="" value="0">Выберите тип объекта...</option>
                        @foreach($lab_objects as $object)
                            <option value="{{ $object->id }}">{{ $object->chain_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
    <ul class="addresses">
        <x-order_task.create.address_row parent="{{$uid}}"></x-order_task.create.address_row>
    </ul>

    <x-ui.button.outline btn_type="danger" onclick="javascript:add_address('{{$uid}}')" class="add_address"><i class="fa-thin fa-circle-plus me-1"></i> адрес</x-ui.button.outline>
</li>
