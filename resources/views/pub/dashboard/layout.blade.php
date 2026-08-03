@extends('layouts.layout')

@section('styles')
    @parent
    <link href="/dist/modules/daterangepicker/daterangepicker.css" rel="stylesheet"/>
@endsection

@section('content')
    <div class="container-fluid">
        @if($available->count() > 1)
            <ul class="nav nav-pills p-3 bg-white mb-3 align-items-center">
                @foreach($available as $chr => $dashboard)
                    <li class="nav-item">
                        <a href="{{ route('dashboard.index', $chr) }}" class="nav-link rounded-pill note-link d-flex align-items-center justify-content-center px-3 px-md-3 me-0 me-md-2 @if($chr == $mode) active @endif" id="all-category">
                            <span class="d-md-block font-weight-medium">{{ $dashboard->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        @yield('dashboard_content')
    </div>
@endsection

@section('js')
    @parent
    <script src="/dist/modules/daterangepicker/daterangepicker.js"></script>
    <script src="/assets/extra-libs/treeview/dist/bootstrap-treeview.min.js"></script>
    <script src="/assets/libs/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="/assets/libs/bootstrap-datepicker/dist/locales/bootstrap-datepicker.ru.min.js"></script>
    <script src="/dist/modules/daterangepicker/moment.min.js"></script>

@endsection
