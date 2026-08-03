@php
    $uuid = \Illuminate\Support\Str::uuid();

@endphp
<div class="once input-group mb-1 position-relative d-flex align-items-center">
    <span class="me-2 fs-2"><span class="count">{{ $num }}</span>.</span>
    <x-ui.select.single value="{{ $selected->scenario_id ?? -1 }}" name="scenario[{{ $uuid }}]"  @class(["select2 flex-grow-0", "manual" => empty($selected->scenario_id)]) :items="$scenarios" blank-name="Вручную" blank-id="-1"/>
    <input type="text" name="scenario_manual[{{ $uuid }}]" class="form-control manual" value="{{ $selected->name ?? '' }}">

    <x-ui.icon.regular icon="fa-delete-left" class="del text-light-secondary position-absolute h2" onclick="javascript:scenario_row_delete($(this))"/>
</div>
