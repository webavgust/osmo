@if(!empty($breadcrumbs))
    <div class="row page-titles">
        <div class="col-sm-12 col-md-6 align-self-center">
            <div class="d-flex align-items-center ">
                <h3 class="text-themecolor mb-0">{{ $breadcrumbs->getLastName() }}</h3>
                @yield('breadcrumb_add')
            </div>
            <x-breadcrumb :data="$breadcrumbs"></x-breadcrumb>
        </div>
        <div class="col-sm-12 col-md-6 align-items-center justify-content-end d-flex">
            @yield('breadcrumb_right')

            @if(!empty($reminder))
                @include('components.reminder.header', ['reminder' => $reminder])
            @endif
        </div>
    </div>
@endif
