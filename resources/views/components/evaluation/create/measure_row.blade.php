<li class="measure_once needbind" uid="{{ $uid }}">
{{--    @dump($measure->point->address->object->lab_object);--}}
    <div class="content">
        <div class="del" onclick="javascript:li_del(this)">
            <i class="fa-solid fa-delete-left"></i>
        </div>
        <div class="row align-items-center">
            <div class="col-12 col-sm-1 col-md-1 d-flex align-items-center mb-2 mb-sm-0">
                <div class="form-check">
                    <input class="form-check-input primary cb_copy" type="checkbox" value="" id="flexCheckDefault">
                </div>
            </div>

            <div class="col-12 col-sm-2 col-md-2 d-flex align-items-center mb-2 mb-sm-0">
                <i class="fa-solid fa-chevron-left me-2"></i>
                <h5 class="font-weight-medium fs-4 todo-header m-0">Измерение&nbsp;{{ $num }}</h5>
            </div>
            <div class="col-10 col-sm-9 col-md-9 d-flex align-items-center offset-sm-0 offset-2 mb-1 mb-sm-0">
                <div class="form-group w-100">
                    <div class="input-group">
                        <span class="input-group-text row_icon "><i class="fa-regular fa-flask"></i></span>

                        <select name="measure[{{$parent}}][{{$uid}}][measure_id]" class="form-select mr-sm-2 flex-grow-1 select2 measure_item init">
                            <option></option>
                            @foreach($measures as $id => $item)
                                @if($item->lab_objects->isEmpty())
                                    @continue
                                @endif
                                <option value="{{ $item?->id }}" base=";{{ implode(";", $item?->lab_objects?->pluck('id')->toArray()) }};" @selected($item->id == $measure?->measure?->id || (!empty($data) && $data['measure'] == $item->id))>{{ $item->name }}</option>
                            @endforeach
                        </select>


                        <span class="input-group-text" title="Количество">
                            <i class="fa-solid fa-xmark"></i>
                        </span>

                        <input type="number" min="1" value="{{ $data['count'] ?? $measure->count ?? 1 }}" class="form-control text-start inp_count flex-grow-0 measure_count" name="measure[{{$parent}}][{{$uid}}][count]" style="width: 60px">

                            <span class="input-group-text">
                              <x-ui.icon.solid icon="fa-ruble-sign" title="₽" class="cursor-help cost_real"></x-ui.icon.solid>
                            </span>

                        <input type="number" min="0" value="{{ $data['cost'] ?? $measure->cost ?? 0 }}" class="form-control text-start inp_count flex-grow-0 measure_cost" name="measure[{{$parent}}][{{$uid}}][cost]" style="width: 120px">

                        <span class="input-group-text fs-2 px-2 justify-content-end" title="Сумма" style="width: 85px">
                            = <span class="row_amount mx-1">{{ tools()->cost_normalize(($data['cost'] ?? $measure->cost ?? 0) * ($data['count'] ?? $measure->count ?? 1)) }}</span>  ₽
                        </span>

                        <div class="ms-2 d-flex align-items-center">
                            <a href="javascript:void(0);" onclick="javascript:measure_copy('{{ $uid }}')" class="copy" title="Дублировать измерение">
                                <x-ui.icon.regular icon="fa-copy"></x-ui.icon.regular>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div @class(["col-12  col-sm-9 col-md-9 offset-sm-3 mt-1 measure_comment", "d-none" => ($measure->count ?? 0) < 2])>
                <input type="text" value="{{ $measure->comment ?? ''}}" class="form-control text-start inp_comment flex-grow-0" name="measure[{{$parent}}][{{$uid}}][comment]" placeholder="Укажите комментарий" autocomplete="off">
            </div>
        </div>
    </div>
</li>
