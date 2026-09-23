{{-- Создание события (тема Metronic); форма — pub.calendar.sidebars._form, $date — день из виджета «Календарь» --}}
@extends('components.sidebar.offcanvas-right')

@section('body')
    @include('pub.calendar.sidebars._form', [
        'form_id' => 'calendar_add',
        'action' => route('api.calendar.add', ['_token' => _token()]),
        'event' => null,
        'date' => $date ?? null,
        'submit' => 'Добавить событие',
        'error' => 'Не получилось создать событие',
    ])
@endsection
