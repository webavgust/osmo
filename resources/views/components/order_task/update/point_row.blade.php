@php
    $uid = $point->id;
    if(empty($num)) $num = 1;

@endphp
<li>
    <div class="content">
        <div class="del" onclick="javascript:li_del(this)">
            <i class="fa-solid fa-delete-left"></i>
        </div>
        <div class="row align-items-center">
            <div class="col-12 col-sm-3 col-md-3 d-flex align-items-center mb-2 mb-sm-0">
                <i class="fa-solid fa-chevron-left me-2"></i>
                <h5 class="font-weight-medium fs-4 todo-header m-0">Точка&nbsp;{{ $num }}</h5>
            </div>
            <div class="col-10 col-sm-3 d-flex align-items-center offset-sm-0 offset-2 mb-1 mb-sm-0">
                <div class="form-group">
                    <input type="text" class="form-control" aria-describedby="name"
                           placeholder="Номер ист." name="point[{{$parent}}][{{$uid}}][number]" value="{{ $point->number }}">
                </div>
            </div>

            <div class="col-10 col-sm-5 offset-sm-0 offset-2">
                <div class="form-group">
                    <input type="text" class="form-control point_name" aria-describedby="name"
                           placeholder="Укажите название" name="point[{{$parent}}][{{$uid}}][name]" value="{{ $point->name }}">
                </div>
            </div>
            <div class="col-1">
            </div>
        </div>
    </div>
</li>
