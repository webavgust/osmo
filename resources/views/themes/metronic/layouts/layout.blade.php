@extends('layouts.layout_short')

@section('body')
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">

            {{-- Мобильная полоса: только бургер и логотип (на десктопе скрыта) --}}
            <div id="kt_app_header_mobile" class="app-header-mobile d-flex d-lg-none align-items-center">
                <div class="btn btn-icon btn-active-color-primary w-40px h-40px ms-2" id="kt_app_sidebar_mobile_toggle">
                    <i class="fa-light fa-bars fs-2"></i>
                </div>
                <a href="{{ route('desktop.index') }}" class="ms-2">
                    <img alt="OSMO" src="/images/logo/logo_letter.svg" class="h-30px" />
                </a>
            </div>

            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">

                @include('layouts.sidebar')

                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">

                        @if(!empty($log_state))
                            {{-- patch v29: просмотр исторического состояния (?at=) --}}
                            <div class="bg-warning bg-opacity-25 border-bottom border-warning px-8 py-5 d-flex align-items-center gap-5 flex-wrap">
                                <i class="fa-light fa-clock-rotate-left fs-2x text-warning"></i>
                                <div class="flex-grow-1">
                                    <div class="fs-2 fw-bold text-gray-900">Просмотр состояния на {{ \App\Modules\Pub\EntityLog\Services\EntityLogViewService::dateWords($log_state['at']) }}, {{ $log_state['at']->format('H:i') }}</div>
                                    <div class="fs-6 text-gray-700">{{ $log_state['log']->event_label }} · внёс {{ trim((string) ($log_state['log']->user->full_name ?? '')) ?: 'система' }} · это исторический слепок, редактирование недоступно@if(empty($log_state['exact'])) · {{ $log_state['note'] }}@endif</div>
                                </div>
                                <a href="{{ $log_state['timeline_url'] }}" class="btn btn-light-warning"><i class="fa-light fa-timeline fs-4 me-2"></i>Журнал изменений</a>
                                <a href="{{ $log_state['current_url'] }}" class="btn btn-icon btn-light-warning btn-lg" title="Вернуться к текущему состоянию"><i class="fa-light fa-xmark fs-1"></i></a>
                            </div>
                        @endif

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
