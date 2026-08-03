@php
    $rows = $parent->children()->orderBy('is_last', 'desc')->orderBy('name', 'asc')->get();
@endphp


@foreach($rows as $row)
    @if(!$row->is_last)
        <tr id="{{ $row->id }}" class="searchable">
            <th style="padding-left: {{ 20 + ($depth - 1) * 50 }}px" class="fs-4" colspan="2">
                <x-ui.icon.solid icon="fa-arrow-turn-down-right" class="me-2"/>
                <strong>{{ $row->name }}</strong>
            </th>
        </tr>

        <x-lab-object.bind.measures_out :object="$object" :parent="$row" :depth="$depth+1"/>
    @else
        <tr id="{{ $row->id }}" class="searchable" parent="{{ $row->parent->id }}">
            <td width="1" class="py-1">
                <div class="form-check form-switch" style="scale: 1.1">
                    <input class="form-check-input" type="checkbox" id="switch{{ $row->id }}"
                           name="set[{{$row->id}}]" id="cb_{{$row->id}}" value="1" @if($object?->lab_measures?->contains($row->id) ?? false) checked @endif
                    >
                </div>
            </td>
            <td class="py-1" style="padding-left: {{ 0 + ($depth - 2) * 50 }}px">
                <label for="switch{{ $row->id }}">{{ $row->name }}</label>
            </td>
        </tr>
    @endif
@endforeach


