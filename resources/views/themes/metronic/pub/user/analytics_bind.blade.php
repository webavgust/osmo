@extends('layouts.layout')

@section('styles')
    <style>

        .table-wrapper {
            max-height: 680px;
            overflow: auto;
            background: white;
            display: inline-block;
        }
        .measure-table {
            width: auto;
        }
        .measure-table thead td,
        .measure-table thead th {
            box-shadow: 0px -5px 5px -5px rgba(34, 60, 80, 0.6) inset;
        }
        .measure-table thead td,
        .measure-table thead th {
            position: sticky;
            top: 0px;
        }
        h1{
            color: green;
        }

        .measure-table td,
        .measure-table th
        {
            padding: 0!important;
            font-size: 12px;
        }
        .measure-table thead td,
        .measure-table thead th
        {
            padding: 10px!important;
            font-size: 17px;
            background:#FDFDFD;
        }


        .object_name {
            text-align: center;
        }
        .object_name a {
            transform:rotate(180deg);
            writing-mode: tb-rl;
            display: inline-block;
            padding: 10px 0!important;
            font-size: 14px;
            font-weight: 300;;
        }
        .object_name.main a {
            font-weight: bold;
            font-size: 17px;
        }
        .object_name.main {
            background: #EEE;
        }
        .object_name {
            width: 20px;
        }

        .measure-table .caption {
            width: 250px;
            padding: 7px 10px!important;
            font-size: 13px;
            background: #EEE;
        }

        .measure-table td:first-of-type {
            padding: 3px 10px!important;
            font-size: 14px;
        }
        .measure-table td.value {
            text-align: center;
        }

        .measure-table tbody td {
            border-bottom: 0;
        }
        .measure-table tbody tr:hover td {
            background: #F7FFFF;
        }
    </style>
@endsection


@section('content')
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <span class="text-nowrap me-2">Аналитик для привязки:</span>
            <div class="d-flex w-100">
                <x-ui.select.single name="user_selector" id="id" :value="$user?->id ?? null" :items="$users" value-name="full_name" class="flex-grow"/>
                <x-ui.button.default btn_type="info" class="ms-2" onclick="javascript:set_user();">ОК</x-ui.button.default>
            </div>
        </div>

            @if(!empty($user))
                <form id="bind">
                    <div class="mt-3">
                        {{-- TABS --}}
                        <div class="tab-content">
                            <div class="p-3 px-5 bg-white">

                                @foreach($objects as $object)
                                    <x-lab-object.tree_block :object="$object" :user="$user"/>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </form>


                <x-ui.button.default btn_type="primary" id="save" class="mt-3" data-bs-toggle="modal"
                                     data-bs-target="#submit-modal">Сохранить</x-ui.button.default>
            @endif
    </div>



    <div
        id="massive-fill-modal"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="danger-header-modalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class=" modal-header modal-colored-header bg-danger text-white">
                    <h4 class="modal-title" id="danger-header-modalLabel">Проставление галочек</h4>
                </div>
                <div class="modal-body">
                    <h5 class="mt-0">Внимание!</h5>
                    <p>
                        Все проставленные галочки в колонке будут стёрты!
                    </p>

                    <textarea class="form-control" id="massive" rows="20"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Не добавлять</button>
                    <button type="button" onclick="javascript:fill_add();" data-bs-dismiss="modal" class=" btn btn-light-danger text-danger fw-semibold">Проставить</button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>

    <div
        id="submit-modal"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="danger-header-modalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class=" modal-header modal-colored-header bg-danger text-white">
                    <h4 class="modal-title" id="danger-header-modalLabel">Сохранение привязок</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Не сохранять"></button>
                </div>
                <div class="modal-body">
                    <h5 class="mt-0">Внимание!</h5>
                    <p>
                        Сохранение привязок необратимо!
                    </p>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Не сохранять
                    </button>
                    <button
                        type="button"
                        onclick="javascript:save();"
                        data-bs-dismiss="modal"
                        class="
                                btn btn-light-danger
                                text-danger
                                fw-semibold
                              "
                    >
                        СОХРАНИТЬ
                    </button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>

@endsection


@section('js')
    @parent
    <script>
        $(document).ready(function() {
            $("input[type='checkbox'].form-check-input").on("change", function() {
                var inp_cb = $(this);
                var container = $(this).parents(".form-check");
                var children = container.attr("children").split(",").filter(item => item !== container.attr("id"));
                if(children.length > 0) {
                    // есть потомки, применим клик
                    $(children).each(function(key, children_id) {
                        $(".form-check[id='" + children_id + "'] input").prop("checked", inp_cb.prop("checked")).prop("indeterminate", false);
                    });
                }

                var parents = container.attr("chain").split(",").filter(item => item !== container.attr("id"));
                if(parents.length > 0) {
                    // есть предки, обработаем их
                    $(parents.reverse()).each(function(key, parent_id) {
                        check_parent(parent_id);
                    });
                }

            });


            $("input[type='checkbox'].form-check-input:checked").each(function() {
                var inp_cb = $(this);
                var container = $(this).parents(".form-check");
                var children = container.attr("children").split(",").filter(item => item !== container.attr("id"));

                var parents = container.attr("chain").split(",").filter(item => item !== container.attr("id"));
                if(parents.length > 0) {
                    // есть предки, обработаем их
                    $(parents.reverse()).each(function(key, parent_id) {
                        check_parent(parent_id);
                    });
                }
            });

        });

        function check_parent(parent_id)
        {
            var container = $(".form-check[id='" + parent_id + "']").attr('children').split(",").filter(item => item !== $(".form-check[id='" + parent_id + "']").attr("id"));
            var cb = $(".form-check[id='" + parent_id + "'] .form-check-input");
            var good = 0;
            $(container).each(function(key, value) {
                if($(".form-check[id='" + value + "'] .form-check-input").prop("checked")) good++;
            });

            if(good == 0) {
                cb.prop("checked", false);
                cb.prop("indeterminate", false);
            } else if (good == container.length) {
                cb.prop("checked", true);
                cb.prop("indeterminate", false);
            } else {
                cb.prop("indeterminate", true);
            }


            //
            // next_group = group.find(".group", 0);
            // console.log(next_group.find(".form-check-input"));
        }



        function set_user() {
            var user_id = $("select[name='user_selector']").val();
            if(user_id > 0) {
                location.replace('{{ route('users.lab_object_bind') }}/' + user_id);
            } else {
                location.replace('{{ route('users.lab_object_bind') }}');
            }
        }


        function cb_fill(id)
        {
            window.fill_id = id;
            $('#massive-fill-modal').modal('show');
        }

        function fill_add() {
            $("input[user_id='" + window.fill_id  + "']").prop("checked", false);
            var text = $("#massive").val().split(String.fromCharCode(10));
            if(text.length > 0)
            {
                $.each(text, function( index, value ) {
                    if(value == '+')
                    $("tr.searchable:nth-child(" + index + ") input[user_id='" + window.fill_id + "']").prop("checked", true);
                });

            }
            $("#massive").val('');
        }

        function save() {
            var block_elem = $("body");
            $(block_elem).block({
                message: '<i class="fas fa-spin fa-sync text-white"></i>',
                overlayCSS: {
                    backgroundColor: "#000",
                    opacity: 0.5,
                    cursor: "wait",
                },
                css: {
                    border: 0,
                    padding: 0,
                    backgroundColor: "transparent",
                },
            });


            $.ajax({
                url: "{{ route('api.users.analytic_bind', [$user, '_token' => auth()->user()->ajax_token ]) }}",
                type: "POST",
                data: $("form#bind").serialize(),
                dataType: "json",
                success: function (result) {
                    toastr.success("Привязки сохранены!", "Это успех!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                },
                error: function () {
                    toastr.error("Не получилось сохранить привязки", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });
        }
    </script>
@endsection

