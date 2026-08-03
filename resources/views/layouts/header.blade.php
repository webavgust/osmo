<?php
?>
<header class="topbar"  data-navbarbg="skin1">
    <nav class="navbar top-navbar navbar-expand-md navbar-dark">
        <div class="navbar-header">
            <!-- This is for the sidebar toggle which is visible on mobile only -->
            <a
                class="nav-toggler waves-effect waves-light d-block d-md-none"
                href="javascript:void(0)"
            ><i class="ti-menu ti-close"></i
                ></a>
            <!-- ============================================================== -->
            <!-- Logo -->
            <!-- ============================================================== -->
            <a class="navbar-brand" href="/">
                <!-- Logo icon -->
                <b class="logo-icon">
                    <!--You can put here icon as well // <i class="wi wi-sunset"></i> //-->
                    <!-- Dark Logo icon -->
                </b>
                <div>
                    <img src="/images/logo/logo_letter_white.svg" style="height: 50px" class="w-100">
                </div>
{{--                @env('development')--}}
{{--                    <x-ui.badge.light type="danger" class="mx-1">DEV</x-ui.badge.light>--}}
{{--                @endenv--}}
            </a>
            <!-- ============================================================== -->
            <!-- End Logo -->
            <!-- ============================================================== -->
            <!-- ============================================================== -->
            <!-- Toggle which is visible on mobile only -->
            <!-- ============================================================== -->
            <a class="topbartoggler d-block d-md-none waves-effect waves-light" href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><i class="ti-more"></i></a>
        </div>
        <!-- ============================================================== -->
        <!-- End Logo -->
        <!-- ============================================================== -->
        <div class="navbar-collapse collapse" id="navbarSupportedContent">
            <!-- ============================================================== -->
            <!-- toggle and nav items -->
            <!-- ============================================================== -->
            <ul class="navbar-nav me-auto">
                <!-- This is  -->
                <li class="nav-item">
                    <a
                        class="
                    nav-link
                    sidebartoggler
                    d-none d-md-block
                    waves-effect waves-dark
                  "
                        href="javascript:void(0)"
                    ><i class="ti-menu"></i
                        ></a>
                </li>
                <!-- ============================================================== -->
                <!-- Search -->
                <!-- ============================================================== -->
                {{--                <li class="nav-item d-none d-md-block search-box">--}}
                {{--                    <a--}}
                {{--                        class="nav-link d-none d-md-block waves-effect waves-dark"--}}
                {{--                        href="javascript:void(0)"--}}
                {{--                    ><i class="ti-search"></i--}}
                {{--                        ></a>--}}
                {{--                    <form class="app-search">--}}
                {{--                        <input--}}
                {{--                            type="text"--}}
                {{--                            class="form-control"--}}
                {{--                            placeholder="{{ __('header.search_placeholder') }}"--}}
                {{--                        />--}}
                {{--                        <a class="srh-btn"><i class="ti-close"></i></a>--}}
                {{--                    </form>--}}
                {{--                </li>--}}
            </ul>
            <!-- ============================================================== -->
            <!-- Right side toggle and nav items -->
            <!-- ============================================================== -->
            <ul class="navbar-nav">
{{--                    @if(!\Illuminate\Support\Facades\Session::has('mask_admin'))--}}
{{--                        @can('super_user')--}}
{{--                            <li class="nav-item dropdown">--}}
{{--                                <x-ui.a.box href="{{ route('users.box_mask') }}" class="ps-3 pe-3 nav-link dropdown-toggle waves-effect waves-dark">--}}
{{--                                    <x-ui.icon.duotone icon="fa-masks-theater"></x-ui.icon.duotone>--}}
{{--                                </x-ui.a.box>--}}
{{--                            </li>--}}
{{--                        @endif--}}
{{--                    @else--}}
{{--                        <li class="nav-item dropdown">--}}
{{--                            <a href="{{ route('users.unmask', [\Illuminate\Support\Facades\Session::get('mask_admin'), 'url' => $_SERVER['HTTP_REFERER'] ?? '']) }}" class="ps-3 pe-3 nav-link dropdown-toggle waves-effect waves-dark">--}}
{{--                                <x-ui.icon.duotone icon="fa-user-slash"></x-ui.icon.duotone>--}}
{{--                            </a>--}}
{{--                        </li>--}}
{{--    --}}
{{--                        <li class="nav-item dropdown">--}}
{{--                            <x-ui.a.box href="{{ route('users.box_mask') }}" class="ps-3 pe-3 nav-link dropdown-toggle waves-effect waves-dark">--}}
{{--                                <x-ui.icon.duotone icon="fa-masks-theater" style="color:#4fed84"></x-ui.icon.duotone>--}}
{{--                            </x-ui.a.box>--}}
{{--                        </li>--}}
{{--                    @endif--}}

                <li class="nav-item dropdown">
                    <a href="{{ route('reminder.index') }}" class="ps-3 pe-3 nav-link dropdown-toggle waves-effect waves-dark" >
                        <x-ui.icon.duotone icon="fa-alarm-exclamation"></x-ui.icon.duotone>
                    </a>
                </li>


                <li class="nav-item dropdown">
                    <a href="{{ route('calendar.index') }}" class="ps-3 pe-3 nav-link dropdown-toggle waves-effect waves-dark" >
                        <x-ui.icon.duotone icon="fa-calendar-days"></x-ui.icon.duotone>
                    </a>
                </li>
                <li class="nav-item dropdown @if(!$global['notifies']['count']) d-none @endif" id="notifies">
                    <a class="ps-3 pe-3 nav-link dropdown-toggle waves-effect waves-dark" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <x-ui.icon.duotone icon="fa-message"></x-ui.icon.duotone>
                        <div class="notify">
                            <span class="heartbit @if(empty($global['notifies']['new'])) d-none @endif"></span>
                            <span class="point"></span>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end mailbox dropdown-menu-animate-up pb-0">
                        <div class="loader text-center">
                            <div class="spinner-border text-success mt-4 mb-3" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                        <div class="notices_shell"></div>
                    </div>
                </li>
                <!-- ============================================================== -->
                <!-- Profile -->
                <!-- ============================================================== -->
                <li class="nav-item dropdown ms-3">
                    <a
                        class="nav-link dropdown-toggle waves-effect waves-dark"
                        href="#"
                        data-bs-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >
                        <img
                            src="{{ asset(auth()->user()->avatar()) }}"
                            alt="user"
                            width="30"
                            class="profile-pic rounded-circle"
                        />
                    </a>
                    <div class=" dropdown-menu dropdown-menu-end user-dd animated flipInY">
                        <div class=" d-flex no-block align-items-center p-3 bg-info text-white mb-2">
                            <div class="">
                                <img
                                    src="{{ asset(auth()->user()->avatar()) }}"
                                    alt="user"
                                    class="rounded-circle"
                                    width="60"
                                />
                            </div>
                            <div class="ms-2">
                                <h4 class="mb-0 text-white">{{ auth()->user()->name }}</h4>
                                <p class="mb-0">
                                    {{ auth()->user()->email }}
                                </p>

                                @if(auth()->user()->isAdmin() || auth()->user()->silentAdmin   ())
                                    ID: {{ auth()->id() }}
                                @endif
                            </div>
                        </div>
                        {{--                        <a class="dropdown-item" href="#"--}}
                        {{--                        ><i--}}
                        {{--                                data-feather="user"--}}
                        {{--                                class="feather-sm text-info me-1 ms-1"--}}
                        {{--                            ></i>--}}
                        {{--                            {{ __('header.my_profile') }}</a--}}
                        {{--                        >--}}

                        <div class="dropdown-divider"></div>

                        <a class="dropdown-item" href="{{ route('notify.list') }}">
                            <i class="fa-light fa-message text-warning me-1 ms-1"></i> История уведомлений
                        </a>

                        <div class="dropdown-divider"></div>

                        @if(auth()->user()->isAdmin() || auth()->user()->silentAdmin())
                            <x-ui.a.ajax class="dropdown-item" url="{{ route('api.access.refresh') }}" method="post" :data="['a' => 1]" reload="1">
                                <x-ui.icon.light icon="fa-retweet" class=" text-info me-1 ms-1"></x-ui.icon.light>
                                Обновить доступы
                            </x-ui.a.ajax>
                        @endif
                        <a class="dropdown-item" href="{{ route('logout') }}">
                            <i data-feather="log-out" class="feather-sm text-danger me-1 ms-1"></i>
                            {{ __('header.logout') }}
                        </a>

                        <div class="dropdown-divider"></div>
                        <div class="pl-4 p-2">
                            <a href="{{ route('users.view', auth()->user()) }}" class="btn d-block w-100 btn-info rounded-pill"
                            >{{ __('header.my_profile') }}</a
                            >
                        </div>
                    </div>
                </li>

            </ul>
        </div>
    </nav>
</header>

