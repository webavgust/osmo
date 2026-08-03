<div class="input-group mb-1 position-relative add_row">
    <x-ui.select.single name="add[{{ $uid }}][document_id]" :items="$rows" id="id" class="form-select flex-grow-1" blank-name="Выберите документ для добавления"></x-ui.select.single>
    <input name="add[{{ $uid }}][count]" class="form-control flex-grow-0" type="number" min="1" style="width: 70px">

    <div class="del" onclick="javascript:row_del(this)">
        <i class="fa-solid fa-delete-left"></i>
    </div>
</div>
