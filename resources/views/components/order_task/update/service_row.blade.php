@php
    $uid = $objservice->id;
@endphp
<div class="col-12 service_row" uid="{{$uid}}">
    <div class="del" onclick="javascript:row_del(this)">
        <i class="fa-solid fa-delete-left"></i>
    </div>
    <div class="input-group flex-grow-1">
        <span class="row_icon input-group-text"><i class="fa-light fa-coin"></i></span>

        <select class="form-select mr-sm-2 flex-grow-1 select2 service_name" name="service[{{$object->id}}][{{$uid}}][service_id]"
            @if(!empty($objservice->pivot->link_object_id)) disabled @endif
        >
            <option selected="" value="0">Выберите услуги</option>
            @foreach($services as $service)
                <option value="{{$service->id}}"
                        @if($service->id == ($objservice->pivot->service_id ?? 0)) selected @endif>{{ $service->name }}</option>
            @endforeach
        </select>
        <span class="input-group-text" title="Количество">
            <i class="fa-solid fa-xmark"></i>
        </span>
        <input type="number" min="0" value="{{ $objservice->pivot->count ?? 1}}"
               class="form-control text-start inp_count" name="service[{{$object->id}}][{{$uid}}][count]" @if(!empty($objservice->pivot->link_object_id)) readonly @endif>
        <span class="input-group-text">
            <i class="fa-solid fa-ruble-sign"></i>
        </span>
        <input type="text" class="form-control inp_cost " name="service[{{$object->id}}][{{$uid}}][cost]"
               value="{{ $objservice->pivot->cost ?? 0 }}"  @if(!empty($objservice->pivot->link_object_id)) readonly @endif>

        <span class="input-group-text row_total_field d-flex">
           <i class="fa-solid fa-equals"></i>
           <span
               class="row_total flex-grow-1 text-end"><span
                   class="amount">{{ !empty($objservice->pivot->count) ? $objservice->pivot->count * $objservice->pivot->cost : 0 }}</span></span>
       </span>

        <span class="input-group-text">
           или <i class="fa-solid fa-link ms-2"></i>
       </span>
        <select class="form-select mr-sm-2 flex-grow-1 service_link" name="service[{{$object->id}}][{{$uid}}][link_object_id]" >
            <option value="0">...</option>
            @if(!empty($objservice->pivot->link_object_id))
                @php $object_ref = \App\Modules\Pub\OrderTaskObject\Models\OrderTaskObject::find($objservice->pivot->link_object_id)->load('task.order'); @endphp

                <option selected="" value="{{ $objservice->pivot->link_object_id }}"
                        object_task_id="{{ $object_ref->task->id }}"
                        object_name="{{ $object_ref->name }}"
                        object_task_order_id="{{ $object_ref->task->order->id ?? null}}"
                ></option>
            @endif
        </select>

        <span class="input-group-text">
           <i class="fa-light fa-comment"></i>
       </span>
        <input type="text" class="form-control inp_comment flex-grow-1"
               name="service[{{$object->id}}][{{$uid}}][comment]" value="{{ $objservice->pivot->comment ?? '' }}"
               placeholder="Комментарий">
    </div>
</div>
