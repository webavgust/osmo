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
        .object_name.odd {
            background: #dfdfdf!important;
        }
        .object_name a {
            transform:rotate(180deg);
            writing-mode: tb-rl;
            display: inline-block;
            padding: 10px 0!important;
            font-size: 13px;
            font-size: 15px !important;
            font-weight: 400 !important;
            text-wrap: pretty;
            max-height: 250px !important;
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
        <div>
            <ul class="nav nav-tabs" role="tablist">
                @forelse($objects as $object_nav)
                    <li class="nav-item">
                        <a class="nav-link @if($object_nav->id == $object->id)active @endif"  href="{{ route('lab-object.bind', $object_nav) }}" role="tab">
                            @if(!empty($object_nav->icon))
                                <span class="me-1"><i class="{{$object_nav->icon}}"></i></span>
                            @endif
                            <span>{{$object_nav->name}}</span>
                        </a>
                    </li>
                @empty
                    NO TABS
                @endforelse
            </ul>

            @if(!empty($measures[$object->id]))
                <x-lab-object.bind.pane :object="$object" :measure="$measures[$object->id] " :saved="$saved"></x-lab-object.bind.pane>

                <x-ui.button.default btn_type="primary" id="save" class="mt-3" data-bs-toggle="modal"
                                     data-bs-target="#submit-modal">Сохранить</x-ui.button.default>

            @else
                <div class="m-2 fs-6 fw-bold">
                    Нет прикрепленных измерений
                </div>
            @endif
        </div>

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
        $(document).ready(function() {
           $("#search").on("keyup", function() {
               var group_show = [];
               var rex = new RegExp($(this).val(), 'i');
               $('tr.searchable').hide();
               $('tr.searchable').filter(function () {
                   if(rex.test($(this).text())) {
                      if($(this).attr("parent")) {
                          $('tr.searchable[id=' + $(this).attr("parent") + ']').show();
                      } else {
                          group_show.push($(this).attr("id"));
                      }
                       return true;
                   }
               }).show();

               $(group_show).each(function(index, id) {
                  $('tr[parent=' + id + ']').show();
               });
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
    </script>
@endsection

