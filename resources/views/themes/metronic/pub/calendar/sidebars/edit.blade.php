{{-- Правка события (тема Metronic); форма — pub.calendar.sidebars._form --}}
@extends('components.sidebar.offcanvas-right')

@section('title')
    <div class="d-flex justify-content-between align-items-center">
        <span>{{ $title }}</span>
        <x-ui.a.sidebar href="{{ route('calendar.sidebar_show', $event) }}" class="ms-4 d-flex align-items-center">
            <x-ui.icon.regular icon="fa-eye" class="me-1"></x-ui.icon.regular>
            Просмотр
        </x-ui.a.sidebar>
    </div>
@endsection

@section('body')
    @include('pub.calendar.sidebars._form', [
        'form_id' => 'calendar_edit',
        'action' => route('api.calendar.edit', [$event, '_token' => _token()]),
        'event' => $event,
        'date' => null,
        'submit' => 'Сохранить событие',
        'error' => 'Не получилось сохранить событие',
    ])
@endsection
