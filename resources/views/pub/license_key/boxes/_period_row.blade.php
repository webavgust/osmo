{{-- Строка периода ключа в попапе «Редактирование ключа» (patch v49).
     $uid — индекс строки в periods[...]; $period — LicenseKeyPeriod или null (шаблон новой строки) --}}
<tr class="key_period_row">
    <td>
        @if(!empty($period))
            <input type="hidden" name="periods[{{ $uid }}][id]" value="{{ $period->id }}">
        @endif
        @if($specs->isNotEmpty())
            <select name="periods[{{ $uid }}][specification]" class="form-select form-select-sm">
                <option value=""></option>
                @foreach($specs as $spec)
                    <option value="{{ $spec->id }}" @selected(!empty($period) && $period->contract_specification_id == $spec->id)>{{ $spec->name_full }}</option>
                @endforeach
            </select>
        @else
            <span class="text-muted fs-7">Нет созданных спецификаций</span>
        @endif
    </td>
    <td>
        <input name="periods[{{ $uid }}][active_from]" type="date" class="form-control form-control-sm key_period_from"
               value="{{ $period?->active_from?->format('Y-m-d') }}" required>
    </td>
    <td>
        <input name="periods[{{ $uid }}][active_to]" type="date" class="form-control form-control-sm key_period_to"
               value="{{ $period?->active_to?->format('Y-m-d') }}" required>
    </td>
    <td class="text-end">
        <a href="javascript:void(0)" onclick="javascript:key_period_remove(this);" class="text-danger" title="Удалить период">
            <x-ui.icon.regular icon="fa-trash"/>
        </a>
    </td>
</tr>
