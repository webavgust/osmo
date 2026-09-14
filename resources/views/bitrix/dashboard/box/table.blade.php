@extends('components.box.box-static-extralarge')

@section('body')
    <div>
        <table class="table table-bordered table-slim  fs-7">
            <thead>
            <tr class="bg-light-secondary text-dark">
                <th class=" text-center">ID</th>
                <th class="">Название</th>
                <th class="">Статус</th>
                <th class=" text-nowrap text-end">{{ $field }}</th>
            </tr>
            </thead>
            @foreach($data['deals'] as $deal)
                @continue(!($deal->{$data['field'] . '_RUB'}))
                <tr @class(["bg-light-warning" => $deal->currency_id !== $currency_slug])>
                    <td class=" px-2  text-center">{{ $deal->id }}</td>
                    <td class=""><a href="https://osmoview.bitrix24.ru/crm/deal/details/{{ $deal->id }}/" target="_blank">{{ $deal->title }}</a></td>
                    <td class=" text-nowrap">{{ $deal->stage_name }}</td>

                    <td @class([" text-end text-nowrap monospace", "text-warning" => $deal->currency_id !== $currency_slug])>
                        {{ tools()->cost_normalize($deal->{$data['field'] . '_RUB'}) }} {{ $currencies[$currency_slug]->symbol }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3"></td>
                <td class=" text-end fw-bold monospace text-nowrap fs-5 py-2 ps-4">
                    {{ tools()->cost_normalize($data['deals']->sum($data['field'] . '_RUB')) }} {{ $currencies[$currency_slug]->symbol }}
                </td>
            </tr>
        </table>
    </div>
@endsection

@section('footer') &nbsp; @endsection
