@extends('layouts.layout')

@section('styles')
    <link rel="stylesheet" href="/assets/libs/bootstrap-table/dist/bootstrap-table.min.css">
@endsection

@section('content')
    <div class="container-fluid">
        <h2 class="mb-4">Список пользователей для подразделения "{{ $department->name }}"</h2>

        <div class="row">
            <div id="filter">
                @if(auth()->user()->isAdmin())
                    <x-ui.button.sidebar href="{{ route('user_department.sidebar_agreements', $department) }}" btn_type="warning">
                        Настроить согласовантов (<span id="agreementers_count">{{ $department->agreementers->count() }}</span>)
                    </x-ui.button.sidebar>
                @endif
            </div>
            <div class="col-12">
                    <div class="card">
                        <div class="card-body pt-2">
                            <table class="table"
                                   id="table_users"
                                   data-search="true"
                                   data-toolbar="#filter"
                                   data-page="2"
                                   data-pagination="true"
                                   data-page-size="25"
                                   data-page-list="[10, 25, 50, 100]"
                                   data-side-pagination="server"
                                   data-locale="ru-RU"
                                   data-responsible="true  "
                                   data-url="{{ route("api.users.list", ['department_id' => $department->id, '_token' => auth()->user()->ajax_token]) }}"
                            ></table>

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

                return `
                        @can('users_view_profile') <a href="{{ route('users.view') }}/` + row.id + `"> @endcan
                <div class="d-flex align-items-center">
                    <img src="` + thumb + `" class="rounded-circle" alt="user" width="32">
                                <div class="ms-2">
                                    <div class="user-meta-info">
                                        <h6 class="user-name mb-0 font-weight-medium ` + (!row.active ? 'text-danger' : '') + `">
                                            ` + row.last_name + ' ' + row.name + ' ' + row.second_name + `
                                        </h6>
                                    </div>
                                </div>
                            </div>
                        @can('users.view')</a> @endcan

                `;
            }
            return '';
        }

        function roleFormatter(value, row, index) {
            return `<div class="fs-3 mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark me-2">` + row.work_department + `</div>`
                + `<div class="fs-3 mb-1 badge font-weight-medium bg-light-primary text-primary ">` + row.work_position + `</div>`;
        }


        function emailFormatter(value)
        {
            str = `<a href="mailto:` + value + `">` + value + `</a>`;
            return str;
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
                        title: "Ф.И.О.",
                        field: "last_name",
                        align: "left",
                        sortable: true,
                        valign: "middle",
                        width: 400,
                        formatter: personFormatter
                    },
                    {
                        title: "Отдел и должность",
                        field: "deparment",
                        align: "left",
                        valign: "middle",
                        formatter: roleFormatter
                    },
                    {
                        title: "E-mail",
                        field: "email",
                        align: "right",
                        valign: "middle",
                        alt: 'asd',
                        sortable: true,
                        formatter: emailFormatter
                    },
                    {
                        title: "Телефон",
                        field: "personal_mobile",
                        align: "center",
                        valign: "middle",
                        width: 150,
                        sortable: true
                    },
                    {
                        title: "Вн.номер",
                        field: "work_phone",
                        align: "center",
                        valign: "middle",
                        width: 1,
                        sortable: true
                    },
                    {
                        title: "Дата рождения",
                        field: "personal_birthday",
                        align: "center",
                        valign: "middle",
                        width: 1,
                        sortable: true,
                        formatter: dateFormatter
                    }
                ]
            ]
        });
    </script>
@endsection
