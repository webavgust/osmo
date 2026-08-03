@extends('components.box.box-static-extralarge')

@section('body')
    <style>
    </style>
    <div class="card">
        <table
            class="tablesaw table-bordered table-hover table no-wrap w-100 table-responsive"
            data-tablesaw-mode="swipe"
            data-tablesaw-sortable
            data-tablesaw-sortable-switch
            data-tablesaw-minimap
            data-tablesaw-mode-switch
        >
            <thead>
            <tr class="bg-light-secondary fs-5 text-dark">
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                    class="border"
                >
                    ID
                </th>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    data-tablesaw-sortable-default-col
                    data-tablesaw-priority="persist"
                    class="border"
                >
                    Название
                </th>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                    class="border"
                >
                    Менеджер
                </th>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                    class="border"
                >
                    Проблема
                </th>
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
                            <td class="p-1 text-wrap" rowspan="{{ $loop->count }}"><a href="https://osmoview.bitrix24.ru/crm/deal/details/{{ $deal->id }}/" target="_blank">{{ $deal->title }}</a></td>
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
        .tablesaw-bar { display: none; }
        .tablesaw { border: 1px solid #e4e1de; }
    </style>

    <script>
        $(document).ready(function() {
            Tablesaw.init();
            console.log("!!");
        });
    </script>
@endsection

