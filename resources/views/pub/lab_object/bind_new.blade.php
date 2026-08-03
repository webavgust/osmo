@extends('layouts.layout')

@section('styles')
    <style>
        tr:has(input.form-check-input:checked) {
            background: #eef7ff;
        }
    </style>
@endsection


@section('content')
    <div class="container-fluid">
        <div class="d-flex align-items-center">

            <span class="text-nowrap me-2">Объект для привязки:</span>
            <div class="d-flex w-100">
                <x-ui.select.single name="object_selector" id="id" :value="$object?->id ?? null" :items="$objects" value-name="chain_name" class="flex-grow"/>
                <x-ui.button.default btn_type="info" class="ms-2" onclick="javascript:set_object();">ОК</x-ui.button.default>
            </div>
        </div>

        @if(!empty($object))
            <form id="bind">
                <div class="mt-3">
                    {{-- TABS --}}
                    <ul class="nav nav-tabs" role="tablist">
                        @foreach($measure_cats as $cat)
                            <li class="nav-item">
                                <a @class(["nav-link", "active" => $loop->first]) data-bs-toggle="tab" href="#tab{{ $loop->iteration }}" role="tab" aria-selected="true">
                                    <span>{{ $cat->name }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content">
                        @foreach($measure_cats as $cat)
                            <div @class(["tab-pane", "active" => $loop->first]) id="tab{{ $loop->iteration }}" role="tabpanel">
                                <div class="p-3 px-0 bg-white">
                                    <div class="mx-3 mb-2">
                                        <input type="text" placeholder="Поиск" class="form-control search"/>
                                    </div>

                                    <table class="table customize-table mb-0 v-middle">
                                         <x-lab-object.bind.measures_out :object="$object" :parent="$cat" :depth="1"/>
                                    </table>
                                </div>
                            </div>
                        @endforeach
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
                    <button type="button" onclick="javascript:fill_add();" data-bs-dismiss="modal" class=" btn btn-light-danger text-danger font-weight-medium">Проставить</button>
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
                                font-weight-medium
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
        function set_object() {
            var object_id = $("select[name='object_selector']").val();
            if(object_id > 0) {
                location.replace('{{ route('lab-object.bind') }}/' + object_id);
            } else {
                location.replace('{{ route('lab-object.bind') }}');
            }
        }
        $(document).ready(function() {
            $(".tab-pane").each(function() {
               var pane = $(this);
               $(this).find(".search").on("keyup", function() {
                   console.log("!");
                   var group_show = [];
                   var rex = new RegExp($(this).val(), 'i');
                   pane.find('tr.searchable').hide();
                   pane.find('tr.searchable').filter(function () {
                       if(rex.test($(this).text())) {
                           if($(this).attr("parent")) {
                               pane.find('tr.searchable[id=' + $(this).attr("parent") + ']').show();
                           } else {
                               group_show.push($(this).attr("id"));
                           }
                           return true;
                       }
                   }).show();

                   $(group_show).each(function(index, id) {
                       $('tr[parent=' + id + ']').show();
                   });
               })
            });
        });

        function cb_fill(id)
        {
            window.fill_id = id;
            $('#massive-fill-modal').modal('show');
        }

        function fill_add() {
            $("input[object_id='" + window.fill_id  + "']").prop("checked", false);
            var text = $("#massive").val().split(String.fromCharCode(10));
            if(text.length > 0)
            {
                $.each(text, function( index, value ) {
                    if(value == '+')
                        $("tr.searchable:nth-child(" + index + ") input[object_id='" + window.fill_id + "']").prop("checked", true);
                });

            }
            $("#massive").val('');
        }

        @if(!empty($object))
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
                    url: "{{ route('api.lab-object.bind', [$object, '_token' => auth()->user()->ajax_token ]) }}",
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
        @endif
    </script>
@endsection

