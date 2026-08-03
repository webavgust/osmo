@extends('components.sidebar.offcanvas-right')


@section('body')
    <table class="table mb-0">
        <thead>
        <tr>
            <th class="px-1 text-center">#</th>
            <th class="px-1 text-center">Создано</th>
            <th class="px-1 text-center">Отправлено</th>
            <th class="px-1 text-center"><x-ui.icon.regular icon="fa-copy"/> </th>
            <th class="px-1 text-end">Сумма</th>
        </tr>
        </thead>
        <tbody>
           @foreach($iterations as $iteration)
                <tr>
                    <th class="px-1 fs-3 text-center">{{ $iteration->id }}</th>
                    <td class="px-1 fs-3 text-center">
                        <a href="{{ route('proposal.detail', $iteration) }}">
                            {{ $iteration->created_at->format("d.m.Y") }}
                        </a>
                    </td>
                    <td class="px-1 fs-3 text-center">{{ $iteration->sended_at->format("d.m.Y") }}</td>
                    <td class="px-1 fs-3 text-center">{{ $iteration->variants->count() }}</td>
                    <td class="px-1 fs-3 text-end">{{ tools()->cost_normalize($iteration->variants[0]->cost_total) }} ₽</td>
                </tr>
           @endforeach
        </tbody>
    </table>
@endsection
