@php
    $currency = \App\Modules\Pub\Currency\Repository\CurrencyRepository::get($row->currency_slug);
@endphp
<div>
    @if(empty($row->variants[0]->cost_total))
        -
    @else
        <span @class(["text-nowrap", "text-success" => $currency->slug !== \App\Modules\Pub\Currency\Models\Currency::CURRENCY_DEFAULT])>
            {{ tools()->cost_normalize($row->variants[0]->cost_total) }} {{ $currency->symbol }}
        </span>
    @endif
</div>
