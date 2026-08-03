@extends('components.box.box-static-extralarge')

@section('body')
    <style>
    </style>
    <div class="card">
        <table class="table table-bordered">
            <thead>
            <tr class="bg-light-secondary fs-5 text-dark">
                <th class="p-1 text-center">ID</th>
                <th class="p-1">Название</th>
                <th class="p-1">Статус</th>
                <th class="p-1 text-end text-nowrap">Услуги</th>
                <th class="p-1 text-end text-nowrap">Разработка</th>
                <th class="p-1 text-end text-nowrap">Платф. дораб.</th>
                <th class="p-1 text-nowrap text-end">Итого</th>
            </tr>
            </thead>
            @foreach($data['deals'] as $deal)
                @continue(!$deal->uf_crm_1718977763677_RUB && !$deal->uf_crm_1723814702122_RUB && !$deal->uf_crm_1725019324602_RUB && !$deal->service_raw)
                <tr @class(["bg-light-warning" => $deal->currency_id !== $currency_slug])>
                    <td class="p-1 px-2  text-center">{{ $deal->id }}</td>
                    <td class="p-1"><a href="https://osmoview.bitrix24.ru/crm/deal/details/{{ $deal->id }}/" target="_blank">{{ $deal->title }}</a></td>
                    <td class="p-1 text-nowrap">{{ $deal->stage_name }}</td>

                    <td @class(["p-1 text-end text-nowrap monospace", "text-warning" => $deal->currency_id !== $currency_slug])>
                        {{ tools()->cost_normalize($deal->uf_crm_1718977763677_RUB) }} {{ $currencies[$currency_slug]->symbol }}
                    </td>
                    <td @class(["p-1 text-end text-nowrap monospace", "text-danger" => $deal->uf_crm_1723814702122_RUB > 0])>
                        @if(!$deal->uf_crm_1723814702122_RUB)
                            -
                        @else
                            {{ tools()->cost_normalize($deal->uf_crm_1723814702122_RUB) }} {{ $currencies[$currency_slug]->symbol }}
                        @endif
                    </td>
                    <td @class(["p-1 text-end text-nowrap monospace", "text-danger" => $deal->uf_crm_1725019324602_RUB > 0])>
                        @if(!$deal->uf_crm_1725019324602_RUB)
                            -
                        @else
                            {{ tools()->cost_normalize($deal->uf_crm_1725019324602_RUB) }} {{ $currencies[$currency_slug]->symbol }}
                        @endif
                    </td>
                    <td @class(["p-1 text-end text-nowrap monospace", "text-warning" => $deal->currency_id !== $currency_slug])>
                        {{ tools()->cost_normalize($deal->service_raw) }} {{ $currencies[$currency_slug]->symbol }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="6"></td>
                <td class="p-1 text-end fw-bold monospace text-nowrap">
                    {{ tools()->cost_normalize($data['deals']->sum('service_raw')) }} {{ $currencies[$currency_slug]->symbol }}
                </td>
            </tr>
        </table>
    </div>
@endsection

