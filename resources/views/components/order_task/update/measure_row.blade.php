@php
    $uid = $measure->id;
    if(empty($num)) $num = 1;
@endphp
<div class="col-12 measure_row" uid="{{$uid}}">
    <div class="del" onclick="javascript:row_del(this)">
        <i class="fa-solid fa-delete-left"></i>
    </div>
    <div class="input-group flex-grow-1">
                                                            <span class="input-group-text row_icon ">  <i
                                                                    class="fa-regular fa-flask"></i></span>
        @php
            $first = array_shift($measures);
        @endphp

        <select class="form-select mr-sm-2 flex-grow-1 select2" name="point[{{$measure->point->id}}][{{$uid}}][measure_id]">
            <option selected="" value="0">Выберите исследование</option>

            @if(!empty($first))
                <optgroup label="{{ implode(" / ", $first->chain_name) }} ">
                    @foreach($measures as $path => $item)
                        @if(!$item->is_last)
                </optgroup>
                <optgroup label="{{$path}}">
                    @else
                        <option value="{{$item->id}}" @if($measure->measure->id == $item->id) selected @endif>{{ $item->name }}</option>
                    @endif
                    @endforeach
                </optgroup>
            @endif
        </select>
        <span class="input-group-text" title="Количество">
                                                                <i class="fa-solid fa-xmark"></i>
                                                            </span>
        <input type="number" min="0" value="{{$measure->count}}"
               class="form-control text-start inp_count" name="point[{{$measure->point->id}}][{{$uid}}][count]">
        <span class="input-group-text">
            <i class="fa-solid fa-ruble-sign"></i>
        </span>
        <input type="text" class="form-control inp_cost"  name="point[{{$measure->point->id}}][{{$uid}}][cost]" value="{{$measure->cost}}">
        <span class="input-group-text row_total_field d-flex">
           <i class="fa-solid fa-equals"></i>
           <span class="row_total flex-grow-1 text-end"><span
                   class="amount">{{$measure->cost_total}}</span></span>
       </span>
        <span class="input-group-text">
                                                                       <i class="fa-light fa-comment"></i>
                                                                   </span>
        <input type="text" class="form-control inp_comment flex-grow-1" name="point[{{$measure->point->id}}][{{$uid}}][comment]" placeholder="Комментарий" value="{{$measure->comment}}">
    </div>
</div>
