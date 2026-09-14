<div id="kt_app_toolbar" class="app-toolbar pt-4 pb-2">
    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">

        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            @if(!empty($breadcrumbs))
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2 flex-column justify-content-center my-0">
                    {{ $breadcrumbs->getLastName() }}
                    @yield('breadcrumb_add')
                </h1>
                <x-breadcrumb :data="$breadcrumbs"></x-breadcrumb>
            @endif
        </div>

        <div class="d-flex align-items-center flex-wrap gap-2">
            @yield('breadcrumb_right')

            {{-- patch v29: кнопка журнала изменений — у корней агрегатов с правом просмотра --}}
            @if(!empty($log_root) && auth()->check() && auth()->user()->can('entity_log_view') && !request()->routeIs('entity_log.index'))
                <a href="{{ route('entity_log.index', [$log_root::logType(), $log_root->logGroupKey()]) }}"
                   class="btn btn-icon btn-light-primary" title="Журнал изменений">
                    <i class="fa-light fa-timeline fs-2"></i>
                </a>
            @endif

            @if(!empty($reminder))
                @include('components.reminder.header', ['reminder' => $reminder])
            @endif
        </div>

    </div>
</div>
