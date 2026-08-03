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
                <th class="p-1">Менеджер</th>
                <th class="p-1">Проблема</th>
            </tr>
            </thead>
            @foreach($deals as $deal)
                @php
                    $loop_deal = $loop;
                @endphp
                @foreach($deal->issues as $issue)

                    <tr @class(["bg-issue-odd" => $loop_deal->odd])>
                        @if($loop->first)
                            <td class="p-1 px-2 text-center" rowspan="{{ $loop->count }}">{{ $deal->id }}</td>
                            <td class="p-1" rowspan="{{ $loop->count }}"><a href="https://osmoview.bitrix24.ru/crm/deal/details/{{ $deal->id }}/" target="_blank">{{ $deal->title }}</a></td>
                            <td class="p-1 text-nowrap" rowspan="{{ $loop->count }}">{{ $deal->assigned_by }}</td>
                        @endif

                        <td class="p-1 text-nowrap px-2">{{ $issue->data()['label']  }}</td>
                    </tr>
                @endforeach
            @endforeach
        </table>
    </div>

    <style>
        tr.bg-issue-odd { background: #EEE; }
    </style>
@endsection

