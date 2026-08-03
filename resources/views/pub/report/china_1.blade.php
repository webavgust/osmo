@extends('layouts.layout')

@section('content')
    <div class="container-fluid">
        <div>
                <x-ui.a.default btn_type="primary" href="{{ route('report-download.china1') }}">
                    <x-ui.icon.regular icon="fa-file-excel" class="me-1"/>
                    Скачать отчёт 1
                </x-ui.a.default>
        </div>

    </div>
@endsection

