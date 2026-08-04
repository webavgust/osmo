<div id="kt_app_toolbar" class="app-toolbar align-items-center">
    <div id="kt_app_toolbar_container" class="app-container container-xxl">
        <div class="d-flex flex-stack flex-row-fluid flex-wrap gap-3">

            <div class="d-flex flex-column flex-row-fluid">
                @if(!empty($breadcrumbs))
                    <x-breadcrumb :data="$breadcrumbs"></x-breadcrumb>

                    <div class="page-title d-flex align-items-center flex-wrap gap-3 me-3">
                        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2 flex-column justify-content-center my-0">
                            {{ $breadcrumbs->getLastName() }}
                        </h1>
                        @yield('breadcrumb_add')
                    </div>
                @endif
            </div>

            <div class="d-flex align-items-center flex-wrap gap-2">
                @yield('breadcrumb_right')

                @if(!empty($reminder))
                    @include('components.reminder.header', ['reminder' => $reminder])
                @endif
            </div>

        </div>
    </div>
</div>
