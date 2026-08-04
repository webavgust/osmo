@extends('layouts.layout_short')

@section('body')
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">

            @include('layouts.header')

            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">

                @include('layouts.sidebar')

                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">

                        @include('layouts.breadcrumbs')

                        <div id="kt_app_content" class="app-content flex-column-fluid">
                            <div id="kt_app_content_container" class="app-container container-fluid">
                                @yield('content')
                            </div>
                        </div>
                    </div>

                    <div id="kt_app_footer" class="app-footer">
                        <div class="app-container container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack py-3">
                            <div class="text-gray-900 order-2 order-md-1">
                                <span class="text-muted fw-semibold me-1">OSMO AVG</span>
                                <span class="text-gray-700">Портал руководителя проектов</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="offcanvas"></div>
    <div id="box"></div>
    <div id="toasts"></div>
@endsection

@section("js")
<script>
    function spider_tick()
    {
        is_active = 'visible' == Visibility.state();
        $.ajax({
            url: "{{ route('spider.tick', ['_token' => auth()->user()->ajax_token]) }}",
            type: "POST",
            data: {
                is_active: is_active ? 1 : 0,
                page: window.location.href,
                toasts: $("#toasts .toast").length
            },
            dataType: "json",
            success: function (response) {
                // переадресация
                if(response.error == 'auth')
                    location.replace('{{ route('auth.form') }}');

                if(response.redirect)
                    location.replace(response.redirect);

                if(response.toast) {
                    notify_out(response.toast);
                }

                if(response.notifies && response.notifies.count > 0) {
                    $("#notifies").removeClass("d-none");
                    if(response.notifies.new > 0) {
                        $("#notifies .notify .heartbit").removeClass("d-none");
                    } else {
                        $("#notifies .notify .heartbit").addClass("d-none");
                    }
                } else {
                    $("#notifies").addClass("d-none");
                }
            },
        });
    }


    $(document).ready(function() {
        Visibility.every(3000, 60000, () => {
            spider_tick();
        });
    });
</script>
@endsection
