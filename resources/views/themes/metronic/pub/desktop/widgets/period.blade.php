{{-- Виджет «Период» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\PeriodWidget --}}
<div class="desk-center">
    {{-- одна строка, когда блок широкий, и две, когда узкий: переносом занимается сам flex --}}
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <select class="form-select form-select-sm form-select-solid flex-grow-1" style="min-width: 0"
                data-desk-context="period" @disabled($preview)>
            @foreach($data['periods'] as $key => $label)
                <option value="{{ $key }}" @selected($key === $data['current'])>{{ $label }}</option>
            @endforeach
        </select>

        <span class="desk-muted fs-8 text-nowrap desk-hide-short">{{ $data['dates'] }}</span>
    </div>
</div>
