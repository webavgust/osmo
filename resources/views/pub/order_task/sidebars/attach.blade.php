@extends('components.sidebar.offcanvas-right')


@section('body')
    <style>
        .select2-results__message {
            display: none;
        }
    </style>
    <div class="card">
        <div class="card-body">
            <div class="card-table">
                <div class="tr">
                    <span class="th">Объектов</span>
                    <span class="td">
                        {{ $order_task->objects_all->count() }}
                     </span>
                </div>
                <div class="tr">
                    <span class="th">Адресов</span>
                    <span class="td">
                      {{ $order_task->addresses_all->count() }}
                     </span>
                </div>
                <div class="tr">
                    <span class="th">Точек</span>
                        <span class="td">
                          {{ $order_task->points_all->count() }}
                     </span>
                </div>
                <div class="tr">
                    <span class="th">Измерений</span>
                    <span class="td">
                          {{ $order_task->measures_all->count() }}
                     </span>
                </div>
                <form method="post" id="copy">
                    <h4 class="mt-5">Заявка</h4>
                    <div class="input-group justify-content-start">
                        <span class="input-group-text"><i class="fa-light fa-briefcase fs-4 me-1"></i></span>
                        <select id="attach_to" name="attach_to">
                            <option></option>
{{--                            @foreach($orders as $order_id)--}}
{{--                                <option value="{{$order_id}}">{{$order_id}}</option>--}}
{{--                            @endforeach--}}
                        </select>
                    </div>


                    <button class="mt-3 btn btn-primary d-none" type="button" id="attach_confirm" >
                        <span class="d-none spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Присоединить
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $("#attach_confirm").on("click", function() {
                Swal.fire({
                    title: "Вы уверены?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Да",
                    cancelButtonText: "Нет",
                }).then((result) => {
                    if (result.value) {
                        attach_submit();
                    }
                });
            });


           $("#attach_to").select2({
               dropdownParent: $("#offcanvas .offcanvas-body"),
               minimum: 3,
               placeholder: 'Введите минимум 3 цифры',
               ajax: {
                   delay: 250,
                   url: '{{ route('api.order_task.list', ['_token' => auth()->user()->ajax_token]) }}',
                   data: function (params) {
                       return {
                           q: params.term // search term
                       };
                   },
                   processResults: function (response) {
                       return {
                           results: response
                       };
                   }
               }
           }).on('select2:select', function (e) {
               if($(this).val()) {
                   $("#attach_confirm").removeClass("d-none");
               } else {
                   $("#attach_confirm").addClass("d-none");
               }
           });
        });

        function attach_submit() {
            $("#attach_confirm span").removeClass("d-none");
            var block_elem = $("body");
            $(block_elem).block({
                message: '<i class="fas fa-spin fa-sync text-white"></i>',
                baseZ: 100000,
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
                url: "{{ route('api.order_task.attach', [$order_task, '_token' => auth()->user()->ajax_token ]) }}",
                type: "POST",
                dataType: "json",
                data: $("form#copy").serialize(),
                success: function (result) {
                    $(block_elem).unblock();

                    $("#attach_{{$order_task->id}}").html(`<a href="{{ route('order.detail') }}/` + result.number + `">` + result.number + `</a>`)
                    $("tr[id={{$order_task->id}}] .dropdown-item.attach").remove();

                    sidebar_close();
                },
                error: function () {
                    $("#attach_confirm span").addClass("d-none");
                    toastr.error("Не получилось присоединить ТЗ", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });

        }
    </script>
@endsection
