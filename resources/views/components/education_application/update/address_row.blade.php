@php
    $uid = $address->id;
    if(empty($num)) $num = 1;
@endphp

<li uid="{{$uid}}">
    <div class="content">
        <div class="del" onclick="javascript:li_del(this)">
            <i class="fa-solid fa-delete-left"></i>
        </div>
        <div class="row align-items-center">
            <div class="col-12 col-sm-3 d-flex align-items-center mb-2 mb-sm-0">

                <i class="fa-solid fa-chevron-left me-2" style="transform: rotate(-45deg)"></i>
                <h5 class="font-weight-medium fs-4 todo-header m-0 text-danger">Адрес&nbsp;{{ $num }}</h5>
            </div>
            <div class=" offset-1 col-11 col-sm-8 offset-sm-0">
                <div class="form-group">
                    <input type="text" class="form-control address"  aria-describedby="name"
                           placeholder="Укажите адрес" name="address[{{$parent}}][{{$uid}}][address]" value="{{ $address->address }}">
                </div>
            </div>
        </div>
    </div>
    <ul class="points">
        @foreach($address->points as $num => $point)
            <x-education_application.update.point_row parent="{{$uid}}" :point="$point" num="{{$num + 1}}"></x-education_application.update.point_row>
        @endforeach
    </ul>

    <x-ui.button.outline btn_type="secondary" onclick="javascript:add_point('{{$uid}}')" class="add_point"><i class="fa-thin fa-circle-plus me-1"></i> точку</x-ui.button.outline>

</li>
