<x-ui.select.single name="data[{{ $uid }}][provider_id]" :items="$contractors ?? []" :value="$selected ?? null" id="id" class="form-select flex-grow-1 contractor" blank-name="Без подрядчика ({{$costs[0] ?? 0}})" blank-id="0" onclick="javascript:recalc($(this).parents('[uid]').attr('uid'));"></x-ui.select.single>
<script>
    {{-- Присваиваем ткущую стоимость работ --}}
    costs_{{ $uid }} = @json($costs);
    function recalc_{{ $uid }}(obj) {

        cost = costs_{{ $uid }}[obj.val()];
        obj.parents('.work_row').find('.cost').val(cost);

        recalc(obj.parents('.work_row[uid]').attr('uid'));
    }
    @if(!empty($costs) && empty($selected))
        setTimeout(function() {
            recalc_{{ $uid }}($("[name='data[{{ $uid }}][provider_id]']"));
        }, 100);
    @endif

    $("[name='data[{{ $uid }}][provider_id]']").on("change", function() {
        recalc_{{ $uid }}($(this));
    });
</script>
