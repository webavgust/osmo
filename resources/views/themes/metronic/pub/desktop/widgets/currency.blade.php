{{-- Виджет «Выбор валюты» (patch v30): App\Modules\Pub\Desktop\Widgets\Common\CurrencyWidget --}}
@php
    $current = $data['currencies'][$data['current']] ?? null;
    // название валюты в списке — только когда блок шире мелкого: в узком оно не помещается
    $with_name = in_array($dw, ['md', 'lg', 'xl'], true);
    $is_base = $data['current'] === \App\Modules\Pub\Currency\Models\Currency::CURRENCY_DEFAULT;
    $rate = ($current && !$is_base && $data['rate'] !== null) ? number_format($data['rate'], 2, ',', ' ') : null;
    // быстрый выбор списком — в высоком блоке, когда есть из чего выбирать
    $with_list = in_array($dh, ['lg', 'xl'], true) && count($data['currencies']) > 1;
    // имя группы переключателей: на столе может стоять несколько таких виджетов
    $group = 'desk-currency-' . substr(md5(uniqid('', true)), 0, 8);
@endphp
<div class="desk-stack cur-body">
    <div class="d-flex align-items-center gap-2 flex-wrap cur-pick">
        <select class="form-select form-select-sm form-select-solid flex-grow-1" style="min-width: 0"
                data-desk-context="currency" @disabled($preview)>
            @foreach($data['currencies'] as $currency)
                <option value="{{ $currency['slug'] }}" @selected($currency['slug'] === $data['current'])>
                    {{ $currency['symbol'] }} {{ $currency['slug'] }}@if($with_name) — {{ $currency['name'] }}@endif
                </option>
            @endforeach
        </select>

        {{-- в низком блоке курс в строку с выбором; в высоком он в карточке ниже --}}
        @if($rate !== null)
            <span class="desk-muted text-nowrap desk-only-w-md cur-rate-inline" title="Курс на {{ $data['rate_date'] }}">
                1 {{ $current['symbol'] }} = {{ $rate }} ₽
            </span>
        @endif
    </div>

    {{-- карточка текущей валюты: знак и код крупно, название, курс к рублю --}}
    @if($current)
        <div class="cur-now desk-only-h-md">
            <div class="cur-code fw-bold text-nowrap">{{ $current['symbol'] }} {{ $current['slug'] }}</div>
            <div class="cur-meta">
                <span class="desk-label desk-nowrap desk-hide-narrow cur-name" title="{{ $current['name'] }}">{{ $current['name'] }}</span>
                @if($is_base)
                    <span class="desk-muted desk-only-w-md cur-note">суммы без пересчёта</span>
                @elseif($rate !== null)
                    <span class="text-nowrap cur-rate" title="Курс на {{ $data['rate_date'] }}"><span class="desk-hide-narrow">1 {{ $current['symbol'] }} = </span>{{ $rate }} ₽</span>
                    <span class="desk-muted text-nowrap desk-only-w-md cur-date">на {{ $data['rate_date'] }}</span>
                @else
                    <span class="desk-muted desk-only-w-md cur-note">курса на сегодня нет</span>
                @endif
            </div>
        </div>
    @endif

    {{-- высокий блок: все валюты списком, переключение одним нажатием --}}
    @if($with_list)
        <ul class="desk-list desk-fit desk-stack-grow desk-only-h-lg cur-list">
            @foreach($data['currencies'] as $currency)
                <li @class(['cur-item', 'is-current' => $currency['slug'] === $data['current']])>
                    <label class="cur-option">
                        <input type="radio" class="cur-radio" name="{{ $group }}" value="{{ $currency['slug'] }}"
                               data-desk-context="currency" @checked($currency['slug'] === $data['current']) @disabled($preview)>
                        <span class="fw-semibold text-nowrap">{{ $currency['symbol'] }} {{ $currency['slug'] }}</span>
                        <span class="desk-grow desk-muted desk-only-w-md" title="{{ $currency['name'] }}">{{ $currency['name'] }}</span>
                    </label>
                </li>
            @endforeach
        </ul>
    @endif
</div>
