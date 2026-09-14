@extends('components.box.box-static-extralarge')

@section('body')
    <div>
        <table class="table table-bordered table-slim  fs-7">
            <thead>
            <tr class="bg-light-secondary text-dark">
                <th class="text-center">ID</th>
                <th class="">Название</th>
                <th class="">Статус</th>
                <th class="text-end text-nowrap">Услуги</th>
                <th class="text-end text-nowrap">Разработка</th>
                <th class="text-end text-nowrap">Платф. дораб.</th>
                <th class="text-nowrap text-end">Итого</th>
            </tr>
            </thead>
            @foreach($data['deals'] as $deal)
                @continue(!$deal->uf_crm_1718977763677_RUB && !$deal->uf_crm_1723814702122_RUB && !$deal->uf_crm_1725019324602_RUB && !$deal->service_raw)
                <tr @class(["bg-light-warning" => $deal->currency_id !== $currency_slug])>
                    <td class="text-center">{{ $deal->id }}</td>
                    <td class=""><a href="https://osmoview.bitrix24.ru/crm/deal/details/{{ $deal->id }}/" target="_blank">{{ $deal->title }}</a></td>
                    <td class=" text-nowrap">{{ $deal->stage_name }}</td>

                    <td @class([" text-end text-nowrap monospace", "text-warning" => $deal->currency_id !== $currency_slug])>
                        {{ tools()->cost_normalize($deal->uf_crm_1718977763677_RUB) }} {{ $currencies[$currency_slug]->symbol }}
                    </td>
                    <td @class([" text-end text-nowrap monospace", "text-danger" => $deal->uf_crm_1723814702122_RUB > 0])>
                        @if(!$deal->uf_crm_1723814702122_RUB)
                            -
                        @else
                            {{ tools()->cost_normalize($deal->uf_crm_1723814702122_RUB) }} {{ $currencies[$currency_slug]->symbol }}
                        @endif
                    </td>
                    <td @class([" text-end text-nowrap monospace", "text-danger" => $deal->uf_crm_1725019324602_RUB > 0])>
                        @if(!$deal->uf_crm_1725019324602_RUB)
                            -
                        @else
                            {{ tools()->cost_normalize($deal->uf_crm_1725019324602_RUB) }} {{ $currencies[$currency_slug]->symbol }}
                        @endif
                    </td>
                    <td @class([" text-end text-nowrap monospace", "text-warning" => $deal->currency_id !== $currency_slug])>
                        {{ tools()->cost_normalize($deal->service_raw) }} {{ $currencies[$currency_slug]->symbol }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="6"></td>
                <td class=" text-end fw-bold monospace text-nowrap fs-5 py-2 ps-4">
                    {{ tools()->cost_normalize($data['deals']->sum('service_raw')) }} {{ $currencies[$currency_slug]->symbol }}
                </td>
            </tr>
        </table>
    </div>
@endsection

@section('footer') &nbsp; @endsection
