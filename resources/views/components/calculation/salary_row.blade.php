<tr>
    <td>
        @if(!empty($link))
            <a href="{{ $link }}" target="_blank">
        @endif
            {{ $target_name }}
        @if(!empty($link))
            </a>
        @endif


        @if(!empty($salary->calculated) && $salary->calculated)
            <x-ui.icon.regular class="text-warning ms-2" icon="fa-clock"></x-ui.icon.regular>
        @endif

    </td>
    <td class="text-center">
        {{ !empty($target->finished_at) ? $target->finished_at->format('d.m.Y') : "-" }}
    </td>
    <td class="text-right">
        @if(!$salary->is_correction)
            {{ tools()->cost_normalize($salary->amount) }} ₽
        @else
            -
        @endif
    </td>
    <td class="text-right">
        @if($salary->is_correction)
            @if($salary->amount > 0)
                <span class="text-success">{{ tools()->cost_normalize($salary->amount) }} ₽</span>
            @else
                <span class="text-danger">{{ tools()->cost_normalize($salary->amount) }} ₽</span>
            @endif
        @else

        @endif
    </td>

    <td class="text-center">
        {{ !empty($salary->calculation) ? $salary->calculation->created_at->format('d.m.Y H:i:s') : "Предварительный" }}
    </td>
    <td>
        @switch($salary->type)
            @case(\App\Modules\Pub\Salary\Models\Salary::TYPE_SUPERVISOR)
                <a href="{{ route('calculation.supervisor', $salary->target_id) }}">
                    <x-ui.icon.regular icon="fa-magnifying-glass"></x-ui.icon.regular>
                </a>
            @break
            @case(\App\Modules\Pub\Salary\Models\Salary::TYPE_TENDER)
                <a href="{{ route('calculation.tender', $salary->target_id) }}">
                    <x-ui.icon.regular icon="fa-magnifying-glass"></x-ui.icon.regular>
                </a>
            @break
            @case(\App\Modules\Pub\Salary\Models\Salary::TYPE_METHODIST)
                @if(!empty($salary->id))
                    <x-ui.a.box href="{{ route('calculation.box_methodist', $salary) }}">
                        <x-ui.icon.regular icon="fa-magnifying-glass"></x-ui.icon.regular>
                    </x-ui.a.box>
                @else
                    <x-ui.a.box href="{{ route('calculation.box_methodist_pre', $salary->target_id) }}">
                        <x-ui.icon.regular icon="fa-magnifying-glass"></x-ui.icon.regular>
                    </x-ui.a.box>
                @endif
            @break
        @endswitch
    </td>
</tr>
