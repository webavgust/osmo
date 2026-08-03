@php
    $id = uniqid();
    $mode = !empty($group->accesses) && !empty($group->accesses->find($access->id)) ? $group->accesses->find($access->id)->pivot->mode : 0;

@endphp
<div class="btn-group">
    <input type="radio" class=" btn-check" id="option1_{{$id}}" autocomplete="off" value="-1"  name="access[{{$access->id}}]" @if(!empty($mode) && $mode == -1) checked @endif/>
    <label class="fs-2 @if($checked == -1)hl @endif btn btn-outline-danger mb-0" for="option1_{{$id}}" title="Запретить">
        <i class="fa-solid fa-ban"></i>
    </label>

    <input type="radio" class="btn-check" id="option2_{{$id}}" autocomplete="off" value="0"  name="access[{{$access->id}}]" @if(empty($mode) || (!empty($mode) && $mode == 0)) checked @endif/>
    <label class="fs-2 btn btn-outline-secondary mb-0" for="option2_{{$id}}" title="Смотреть по иерархии">
        <i class="fa-solid fa-xmark"></i>
    </label>

    <input type="radio" class="btn-check" id="option3_{{$id}}" autocomplete="off" value="1"  name="access[{{$access->id}}]" @if(!empty($mode) && $mode == 1) checked @endif/>
    <label class="fs-2 @if($checked == 1)hl @endif btn btn-outline-success mb-0 " for="option3_{{$id}}" title="Разрешить">
        <i class="fa-solid fa-check"></i>
    </label>
</div>
