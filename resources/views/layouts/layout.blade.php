@extends('layouts.layout_short')


@section('body')
    <!-- ============================================================== -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- ============================================================== -->

    <div id="main-wrapper" data-sidebartype="{{ auth()->user()->setting?->read('sidebar_mode') ?? 'full' }}" class="" data-theme="light" data-layout="vertical" data-navbarbg="skin1" data-sidebar-position="fixed" data-header-position="fixed" data-boxed-layout="full">
    @include('layouts.header')

    @include('layouts.sidebar')

    <!-- ============================================================== -->
        <!-- Page wrapper  -->
        <!-- ============================================================== -->
        <div class="page-wrapper">

        @include('layouts.breadcrumbs')

        <!-- ============================================================== -->
            <!-- Container fluid  -->
            <!-- ============================================================== -->
                    @yield('content')
            <!-- ============================================================== -->
            <!-- End Container fluid  -->
            <!-- ============================================================== -->
            <!-- ============================================================== -->
            <!-- footer -->
            <!-- ============================================================== -->
            <footer class="footer">
                OSMO AVG. Портал
            </footer>
            <!-- ============================================================== -->
            <!-- End footer -->
            <!-- ============================================================== -->
        </div>
        <!-- ============================================================== -->
        <!-- End Page wrapper  -->
        <!-- ============================================================== -->
    </div>
    <!-- ============================================================== -->
    <!-- End Wrapper -->
    <!-- ============================================================== -->

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

