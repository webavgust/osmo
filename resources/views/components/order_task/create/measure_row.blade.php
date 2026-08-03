@php
    if(empty($uid)) $uid = Str::uuid()->toString();
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

        <select class="form-select mr-sm-2 flex-grow-1 select2" name="point[{{$point->id}}][{{$uid}}][measure_id]">
            <option selected="" value="0">Выберите исследование</option>

            @if(!empty($first))
                <optgroup label="{{ implode(" / ", $first->chain_name) }} ">
                    @foreach($measures as $path => $item)
                        @if(!$item->is_last)
                            </optgroup>
                            <optgroup label="{{$path}}">
                        @else
                            <option value="{{$item->id}}">{{ $item->name }}</option>
                        @endif
                    @endforeach
                </optgroup>
            @endif
        </select>

            <span class="input-group-text" title="Количество">
                <i class="fa-solid fa-xmark"></i>
            </span>
            <input type="number" min="0" value="1"
               class="form-control text-start inp_count" name="point[{{$point->id}}][{{$uid}}][count]">


            <span class="input-group-text">
                <i class="fa-solid fa-ruble-sign"></i>
            </span>
            <input type="text" class="form-control inp_cost " disabled="1" name="point[{{$point->id}}][{{$uid}}][cost]">


        <span class="input-group-text row_total_field d-flex">
           <i class="fa-solid fa-equals"></i>
           <span class="row_total flex-grow-1 text-end"><span
                   class="amount">0</span></span>
       </span>
        <span class="input-group-text">
                                                                       <i class="fa-light fa-comment"></i>
                                                                   </span>
        <input type="text" class="form-control inp_comment flex-grow-1" name="point[{{$point->id}}][{{$uid}}][comment]" placeholder="Комментарий">
    </div>
</div>
