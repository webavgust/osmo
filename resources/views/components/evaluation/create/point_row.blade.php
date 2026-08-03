<li class="point" uid="{{$uid}}">
    <div class="content">
        <div class="del" onclick="javascript:li_del(this)">
            <i class="fa-solid fa-delete-left"></i>
        </div>
        <div class="row align-items-center">
            <div class="col-12 col-sm-3 col-md-3 d-flex align-items-center mb-2 mb-sm-0">
                <i class="fa-solid fa-chevron-left me-2"></i>
                <h5 class="font-weight-medium fs-4 todo-header m-0 text-warning">Точка&nbsp;{{ $num }}</h5>
            </div>
            <div class="col-10 col-sm-3 d-flex align-items-center offset-sm-0 offset-2 mb-1 mb-sm-0">
                <div class="form-group w-100">
                    @if(!empty($point))
                        <input type="text" class="form-control" aria-describedby="name"
                               placeholder="Номер ист." value="{{ $point->number }}" name="point[{{$parent}}][{{$uid}}][number]">
                    @else
                        <input type="text" class="form-control" aria-describedby="name"
                               placeholder="Номер ист." value="" name="point[{{$parent}}][{{$uid}}][number]">
                    @endif
                </div>
            </div>

            <div class="col-10 col-sm-6 offset-sm-0 offset-2">
                <div class="form-group">

                    @if(!empty($point))
                        <input type="text" class="form-control point_name" aria-describedby="name"
                                   placeholder="Укажите название" value="{{ $point->name }}" name="point[{{$parent}}][{{$uid}}][name]">
                    @else
                        <input type="text" class="form-control point_name" aria-describedby="name"
                               placeholder="Укажите название" value="Точка {{ $num }}" name="point[{{$parent}}][{{$uid}}][name]">
                    @endif
                </div>
            </div>
            <div class="col-1">
            </div>
        </div>
    </div>

    <ul class="measures">
        @forelse($point->measures ?? [] as $i => $measure)
            <x-evaluation.create.measure_row parent="{{$uid}}" :measure="$measure" :num="$i + 1"></x-evaluation.create.measure_row>
        @empty
            <x-evaluation.create.measure_row parent="{{$uid}}"></x-evaluation.create.measure_row>
        @endforelse
    </ul>

    <div class="d-flex justify-content-between align-items-center">
        <div>
            <x-ui.button.outline btn_type="secondary" onclick="javascript:add_measure('{{$uid}}')" class="add_measure"><i class="fa-thin fa-circle-plus me-1"></i> измерение</x-ui.button.outline>

            <x-ui.button.outline btn_type="primary" onclick="javascript:copy_selected_measure('{{$uid}}')" class="copy_measure d-none"><i class="fa-regular fa-arrow-down-to-line me-1"></i> вставить</x-ui.button.outline>
            <x-ui.button.outline btn_type="success" onclick="javascript:paste_measure('{{$uid}}')" class="paste_measure d-none"><i class="fa-regular fa-arrow-down-to-line me-1"></i> вставить</x-ui.button.outline>
        </div>
        <x-ui.button.outline class="me-4" btn_type="secondary" onclick="javascript:clone_point('{{$uid}}')"><i class="fa-thin fa-copy me-1"></i> дублировать точку</x-ui.button.outline>

    </div>
</li>
