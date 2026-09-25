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
                    @if($actual->count() == 0 && $trashed->count() == 0)
                        {{-- пустой период: говорим, что делать, а не показываем пустоту --}}
                        <div class="card">
                            <div class="card-body text-center py-15">
                                <i class="fa-light fa-bell-slash fs-2x text-gray-400 mb-4"></i>
                                <div class="fs-5 fw-semibold text-gray-800 mb-1">За выбранный период уведомлений нет</div>
                                <div class="text-muted">Выберите другой период справа вверху.</div>
                            </div>
                        </div>
                    @endif
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
