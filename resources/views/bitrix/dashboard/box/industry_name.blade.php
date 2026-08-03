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
                <th class="p-1">Сфера</th>
                <th class="p-1">Менеджер</th>
                <th class="p-1">Статус</th>
                <th class="p-1 text-nowrap text-end">Сумма</th>
            </tr>
            </thead>
            @foreach($deals as $deal)
                @continue(!$deal->opportunity_RUB)
                <tr @class(["bg-light-warning" => $deal->currency_id !== $currency_slug])>
                    <td class="p-1 px-2  text-center">{{ $deal->id }}</td>
                    <td class="p-1"><a href="https://osmoview.bitrix24.ru/crm/deal/details/{{ $deal->id }}/" target="_blank">{{ $deal->title }}</a></td>
                    <td class="p-1 text-nowrap">{{ $deal->customer?->industry_name ?? 'Неизвестно'  }}</td>
                    <td class="p-1 text-nowrap">{{ $deal->assigned_by }}</td>
                    <td class="p-1 text-nowrap">{{ $deal->stage_name }}</td>
                    <td @class(["p-1 text-end text-nowrap monospace", "text-warning" => $deal->currency_id !== $currency_slug])>
                        {{ tools()->cost_normalize($deal->opportunity_RUB) }} {{ $currencies[$currency_slug]->symbol }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="5"></td>
                <td class="p-1 text-end fw-bold monospace text-nowrap">
                    {{ tools()->cost_normalize($deals->sum('opportunity_RUB')) }} {{ $currencies[$currency_slug]->symbol }}
                </td>
            </tr>
        </table>
    </div>
@endsection

