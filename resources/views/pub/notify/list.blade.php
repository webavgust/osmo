@extends('layouts.layout')

@section('styles')
    @parent
    <link href="/dist/modules/daterangepicker/daterangepicker.css" rel="stylesheet"/>
    <style>

    </style>
@endsection

@section('content')
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if($actual->count() > 0)
                        <h3>Текущие уведомления</h3>
                        <div class="notifies">
                            @foreach($actual as $notify)
                                <x-notify.list.row :notify="$notify"></x-notify.list.row>
                            @endforeach
                        </div>
                    @endif

                        @if($trashed->count() > 0)
                        <h3 class="mt-4">Прошлые уведомления ({{ $trashed->count() }})</h3>
                        <div class="notifies">
                            @foreach($trashed as $notify)
                                <x-notify.list.row :notify="$notify"></x-notify.list.row>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
@endsection



@section('js')
    @parent
    <script src="/dist/modules/daterangepicker/daterangepicker.js"></script>
@endsection


@section('breadcrumb_right')
    @include('components.notify.date-select')
@endsection
