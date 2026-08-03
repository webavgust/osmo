
<!-- ============================================================== -->
<!-- Left Sidebar - style you can find in sidebar.scss  -->
<!-- ============================================================== -->
<aside class="left-sidebar">
    <!-- Sidebar scroll-->
    <div class="scroll-sidebar">
        <!-- User profile -->
        <div
            class="user-profile position-relative"
            style="
              background: url(/assets/images/background/user-info.jpg)
                no-repeat;
                background-position-y: -16px;
            "
        >
            <!-- User profile image -->
            <a href="{{ route('dashboard.index') }}">
                <div style="height: 84px"></div>
            </a>

        </div>
        <!-- End User profile text-->
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav">
            <x-sidebar.menu :tree="$menu_tree"></x-sidebar.menu>
        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
    <!-- Bottom points-->
    <div class="sidebar-footer">
        <!-- item-->
        <a
            href="{{ route('logout') }}"
            class="link"
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            title="{{ __('sidebar.logout') }}"
        ><i class="mdi mdi-power"></i
            ></a>
    </div>
    <!-- End Bottom points-->
</aside>
<!-- ============================================================== -->
<!-- End Left Sidebar - style you can find in sidebar.scss  -->
<!-- ============================================================== -->
