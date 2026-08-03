@extends('components.sidebar.offcanvas-right')

@section('title')
    <div class="d-flex justify-content-between align-items-center">
        <span>{{ $title }}</span>
        <x-ui.a.sidebar href="{{ route('calendar.sidebar_edit', $event) }}" class="ms-4 d-flex align-items-center">
            <x-ui.icon.regular icon="fa-edit" class="me-1"></x-ui.icon.regular>
            Редактировать
        </x-ui.a.sidebar>
    </div>
@endsection

@section('body')
    <div class="card">
        <div class="card-body p-0">
            <h2>{{ $event->title }}</h2>
            @if(!empty($event->text))
                <div>
                    {!! ($event->text) !!}
                </div>
            @endif

            <h4 class="mt-5">Продолжительность</h4>
            @switch($event->mode)
                @case('day')
                {{ $event->start->format('d.m.Y') }}
                @break
                @case('dates')
                <x-ui.badge.default type="secondary">
                    {{ $event->start->format('d.m.Y') }}
                </x-ui.badge.default>
                &ndash;
                <x-ui.badge.default type="secondary">
                    {{ $event->end->format('d.m.Y') }}
                </x-ui.badge.default>
                @break
                @case('time')
                <x-ui.badge.default type="secondary">
                    {{ $event->start->format('d.m.Y H:i') }}
                </x-ui.badge.default>
                &ndash;
                <x-ui.badge.default type="secondary">
                    {{ $event->end->format('d.m.Y H:i') }}
                </x-ui.badge.default>
                @break
            @endswitch

            <h4 class="mt-4">Доп.сведения</h4>
            <div class="card-table">
                <x-ui.card.card_table_tr field="Дата создания" value="{{ $event->created_at->format('d.m.Y H:i')}}"></x-ui.card.card_table_tr>
            </div>

            <h4 class="mt-4">Напоминания</h4>
            @include('components.reminder.header', ['reminder' => $reminder])

        </div>
    </div>
@endsection
