@php
    if(empty($uid)) $uid = Str::uuid()->toString();
@endphp

<div class="col-12 service_row" uid="{{$uid}}">
    <div class="del" onclick="javascript:row_del(this)">
        <i class="fa-solid fa-delete-left"></i>
    </div>
    <div class="input-group flex-grow-1">
        <span class="row_icon input-group-text"><i class="fa-light fa-coin"></i></span>

        <select class="form-select mr-sm-2 flex-grow-1 service_name" name="service[{{$object->id}}][{{$uid}}][service_id]">
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
               class="form-control text-start inp_count" name="service[{{$object->id}}][{{$uid}}][count]">
        <span class="input-group-text">
            <i class="fa-solid fa-ruble-sign"></i>
        </span>
        <input type="text" class="form-control inp_cost " name="service[{{$object->id}}][{{$uid}}][cost]"
               value="{{ $objservice->pivot->cost ?? 0 }}">
        <span class="input-group-text row_total_field d-flex">
           <i class="fa-solid fa-equals"></i>
           <span
               class="row_total flex-grow-1 text-end"><span
                   class="amount">{{ !empty($objservice->pivot->count) ? $objservice->pivot->count *  $objservice->pivot->cost : 0 }}</span></span>
           </span>

        <span class="input-group-text">
           или <i class="fa-solid fa-link ms-2"></i>
       </span>
        <select class="form-select mr-sm-2 flex-grow-1 service_link" name="service[{{$object->id}}][{{$uid}}][link_object_id]" >
            <option value="0">...</option>
        </select>

        <span class="input-group-text">
           <i class="fa-light fa-comment"></i>
       </span>
        <input type="text" class="form-control inp_comment flex-grow-1"
               name="service[{{$object->id}}][{{$uid}}][comment]" value="{{ $objservice->pivot->comment ?? '' }}"
               placeholder="Комментарий">
    </div>
</div>


<script>
    $(document).ready(function () {

        $("[name='service[{{$object->id}}][{{$uid}}][service_id]']").on('change', function() {
            var row = $(this).parents('.service_row');
            cost = services_costs[$(this).val()].cost;
            count = row.find(".inp_count").val() - 0;

            $("[name='service[{{$object->id}}][{{$uid}}][cost]']").val(cost);
            $(".service_row[uid='{{$uid}}'] .row_total span").html(cost * count);

        });
    })
</script>
