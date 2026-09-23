{{-- Виджет «Период» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\PeriodWidget --}}
@php
    $current = $data['periods'][$data['current']] ?? null;
    // узкий блок: в выборе короткие подписи («III квартал», «30 дней») — полные не помещаются
    $short = in_array($dw, ['xs', 'sm'], true);
    // все периоды списком — в высоком блоке
    $with_list = in_array($dh, ['lg', 'xl'], true);
    // имя группы переключателей: на столе может стоять несколько таких виджетов
    $group = 'desk-period-' . substr(md5(uniqid('', true)), 0, 8);
    $share = $current && $current['days'] > 0 ? round($current['passed'] / $current['days'] * 100) : 0;
@endphp
<div class="desk-stack per-body">
    <div class="d-flex align-items-center gap-2 per-pick">
        <select class="form-select form-select-sm form-select-solid flex-grow-1" style="min-width: 0"
                data-desk-context="period" title="{{ $current ? $current['label'] . ': ' . $current['dates'] : '' }}" @disabled($preview)>
            @foreach($data['periods'] as $key => $period)
                <option value="{{ $key }}" @selected($key === $data['current'])>{{ $short ? $period['short'] : $period['label'] }}</option>
            @endforeach
        </select>

        {{-- в низком блоке даты в строку с выбором (шире 340 px — иначе выбор режет подпись);
             в высоком они в карточке ниже --}}
        <span class="desk-muted text-nowrap flex-shrink-0 per-dates-inline">{{ $data['dates'] }}</span>
    </div>

    {{-- карточка периода: даты крупно и сколько дней прошло --}}
    @if($current)
        <div class="per-now desk-only-h-md">
            <div class="per-dates fw-bold">
                {{-- в узком блоке даты друг под другом без тире, в широком — через тире --}}
                @foreach(explode(' – ', $current['dates']) as $index => $date)
                    <span class="text-nowrap">{{ $date }}</span>@if(!$index)<span class="desk-hide-narrow">&nbsp;–</span>@endif
                @endforeach
            </div>
            <div class="per-meta desk-muted">
                @if($current['passed'] >= $current['days'])
                    {{ $current['days'] }} {{ \App\Facades\Tools::morph($current['days'], 'день', 'дня', 'дней') }}<span class="desk-hide-narrow">, завершён</span>
                @else
                    <span class="desk-hide-narrow">прошло</span> {{ $current['passed'] }} из {{ $current['days'] }} {{ \App\Facades\Tools::morph($current['days'], 'дня', 'дней', 'дней') }}
                @endif
            </div>
            <div class="desk-bar per-bar" title="Прошло {{ $share }} % периода">
                <i class="bg-primary" style="width: {{ $share }}%"></i>
            </div>
        </div>
    @endif

    {{-- высокий блок: все периоды списком с датами, переключение одним нажатием --}}
    @if($with_list)
        <ul class="desk-list desk-fit desk-stack-grow desk-only-h-lg per-list">
            @foreach($data['periods'] as $key => $period)
                <li @class(['per-item', 'is-current' => $key === $data['current']])>
                    <label class="per-option" title="{{ $period['label'] }}: {{ $period['dates'] }}">
                        <input type="radio" class="per-radio" name="{{ $group }}" value="{{ $key }}"
                               data-desk-context="period" @checked($key === $data['current']) @disabled($preview)>
                        <span class="fw-semibold desk-grow">{{ $short ? $period['short'] : $period['label'] }}</span>
                        <span class="desk-muted text-nowrap desk-only-w-lg">{{ $period['dates'] }}</span>
                    </label>
                </li>
            @endforeach
        </ul>
    @endif
</div>
