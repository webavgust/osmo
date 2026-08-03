@extends('layouts.layout')

@section('styles')
    <link rel="stylesheet" href="/assets/libs/bootstrap-table/dist/bootstrap-table.min.css">
@endsection

@section('content')
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                            <div id="filter">
                                <h6 class="card-subtitle lh-base">
                                    Для назначения доступа для пользователя перейдите на его детальную страницу
                                </h6>
                            </div>
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body pt-2">
                                        <table class="table"
                                               id="table_users"
                                               data-search="true"
                                               {{--                                data-search-text="Баба"--}}
                                               data-toolbar="#filter"
                                               data-page="2"
                                               data-pagination="true"
                                               data-page-size="10"
                                               data-page-list="[10, 25, 50, 100]"
                                               data-side-pagination="server"
                                               data-locale="ru-RU"
                                               data-responsible="true  "
                                               data-url="{{ route("api.users.list", ['_token' => auth()->user()->ajax_token]) }}"
                                        ></table>

                                    </div>
                                </div>
                    </div>
                </div>
            </div>
        </div>



@endsection

@section('js')
    @parent
    <script src="/assets/libs/bootstrap-table/dist/bootstrap-table.min.js"></script>
    <script src="/assets/libs/bootstrap-table/dist/bootstrap-table-locale-all.min.js"></script>
    <script src="/assets/libs/bootstrap-table/dist/extensions/export/bootstrap-table-export.min.js"></script>
    <script src="/dist/modules/daterangepicker/moment.min.js"></script>
    <script src="/dist/modules/daterangepicker/daterangepicker.js"></script>

    <script>
        $(document).ready(function() {
            $table = $('#table_orders');
            $table.bootstrapTable('resetView', {height: false});
        });



        function dateFormatter(value)
        {
            if(!value) return '';
            return '<span class="mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark">' + value + '</span>';
        }

        function idFormatter(value)
        {
            @can('order_view_detail')
                return '<a href="#' + value + '">' + value + '</a>';
            @else
                return value;
            @endcan

        }


        function personFormatter(value, row, index)
        {
            if(row) {

                thumb = row.personal_photo && row.personal_photo['45'] ? '/storage/' + row.personal_photo['45'] : '{{ config('settings.user_avatar_default') }}';
                if(row.work_department) row.work_department += ' > ';
                return `
                        @can('users_view_profile') <a href="{{ route('users.view') }}/` + row.id + `"> @endcan
                            <div class="d-flex align-items-center">
                                <img src="` + thumb + `" class="rounded-circle" alt="user" width="32">
                                <div class="ms-2">
                                    <div class="user-meta-info">
                                        <h6 class="user-name mb-0 font-weight-medium ` + (!row.active ? 'text-danger' : '') + `">
                                            ` + row.last_name + ' ' + row.name + ' ' + row.second_name + `
                                        </h6>
                                        <div class="fs-2 mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark">` + row.work_department + row.work_position + `</div>
                                    </div>
                                </div>
                            </div>
                        @can('users.view')</a> @endcan

                `;
            }
            return '';
        }

        function orderFormatter(value, row, index)
        {
            str = `<strong>` + value + `</strong>`;

            @can('order_info_company')
                str += `<br/>
                <a href="{{ env('PORTAL_URL') }}/projects/clients/` + row.customer_id + `/" class="mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark" target="_blank">` + row.customer_name + `</a>
                `
            @endcan
                return str;
        }


        function emailFormatter(value)
        {
            str = `<a href="mailto:` + value + `">` + value + `</a>`;
            return str;
        }

        function controlFormatter(value, row, index) {
            str = `
                <div class="dropdown dropstart">
                    <a href="#" class="link" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal feather-sm"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <li>
                            <a class="dropdown-item" href="{{ route('access_set.user') }}/` + row.id+ `"><i class="fas fa-edit text-info me-1"></i> Назначить доступы</a>
                        </li>
                    </ul>
                </div>
            `;
            return str;
        }

        function groupFormatter(value, row, index) {
            if(!value) return '-';
            str = '';
            $.each(row.groups, function(index, item) {
                if(str) str += '  ';
                str += "[" + item.id + "] " + item.name
            });

            return `
                    <a type="button" class="
                        btn btn-light-danger
                        text-danger
                        font-weight-medium
                        w-100
                      " onclick='javascript:sidebar({href: \"{{ route('access_show.groups') }}/` + row.id + `\"})'>
                      ` + value + `
                    </a>
            `;
        }

        function depFormatter(value, row, index) {
            if(!value) return '-';
            str = '';
            $.each(row.departments, function(index, item) {
                if(str) str += '  ';
                str += "[" + item.id + "] " + item.name
            });

            return `
                    <a type="button" class="
                        btn btn-light-danger
                        text-danger
                        font-weight-medium
                        w-100
                      " onclick='javascript:sidebar({href: \"{{ route('access_show.departments') }}/` + row.id + `\"})'>
                      ` + value + `
                    </a>
            `;
        }
        $("#table_users").bootstrapTable("destroy").bootstrapTable({
            height: 800,
            icons: {
                paginationSwitchDown: "far fa-caret-square-down",
                paginationSwitchUp: "far fa-caret-square-up",
                refresh: "fas fa-history",
                toggleOff: "fas fa-toggle-off",
                toggleOn: "fas fa-toggle-on",
                columns: "fas fa-list",
                fullscreen: "fas fa-expand",
                detailOpen: "fas fa-plus-circle",
                detailClose: "far fa-times-circle",
                export: "fas fa-share-alt",
            },
            columns: [
                [
                    {
                        title: "ID",
                        field: "id",
                        align: "center",
                        valign: "middle",
                        sortable: true,
                        width: 1
                    },
                    {
                        title: "Ф.И.О.",
                        field: "last_name",
                        align: "left",
                        sortable: true,
                        valign: "middle",
                        formatter: personFormatter
                    },
                    {
                        title: "Группы",
                        field: "groups_count",
                        align: "center",
                        valign: "middle",
                        width: 70,
                        formatter: groupFormatter
                    },
                    {
                        title: "Отделы",
                        field: "departments_count",
                        align: "center",
                        valign: "middle",
                        width: 70,
                        formatter: depFormatter
                    },
                    {
                        title: "",
                        field: "",
                        align: "center",
                        valign: "middle",
                        width: 1,
                        formatter: controlFormatter

                    }
                ]
            ]
        });

    </script>
@endsection
