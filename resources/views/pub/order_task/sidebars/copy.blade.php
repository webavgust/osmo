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
                    <span class="th"><strong>Копируем из заявки</strong></span>
                    <span class="td">
                        <strong>{{ $order->id }}</strong>
                     </span>
                </div>
                <div class="tr">
                    <span class="th">Объектов</span>
                    <span class="td">
                        {{ $order->order_task->objects_all->count() }}
                     </span>
                </div>
                <div class="tr">
                    <span class="th">Адресов</span>
                    <span class="td">
                      {{ $order->order_task->addresses_all->count() }}
                     </span>
                </div>
                <div class="tr">
                    <span class="th">Точек</span>
                        <span class="td">
                          {{ $order->order_task->points_all->count() }}
                     </span>
                </div>
                <div class="tr">
                    <span class="th">Измерений</span>
                    <span class="td">
                          {{ $order->order_task->measures_all->count() }}
                     </span>
                </div>
                <form method="post" id="copy">
                    <h4 class="mt-5">Куда копируем</h4>
                    <div class="input-group justify-content-start">
                        <span class="input-group-text"><i class="fa-light fa-briefcase fs-4 me-1"></i></span>
                        <select id="copy_to" name="copy_to">
                            <option></option>
{{--                            @foreach($orders as $order_id)--}}
{{--                                <option value="{{$order_id}}">{{$order_id}}</option>--}}
{{--                            @endforeach--}}
                        </select>
                    </div>


                    <button class="mt-3 btn btn-primary d-none" type="button" id="copy_confirm" >
                        <span class="d-none spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Скопировать
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $("#copy_confirm").on("click", function() {
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
                        copy_submit();
                    }
                });
            });


           $("#copy_to").select2({
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
                   $("#copy_confirm").removeClass("d-none");
               } else {
                   $("#copy_confirm").addClass("d-none");
               }
           });
        });

        function copy_submit() {
            $("#copy_confirm span").removeClass("d-none");
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
                url: "{{ route('api.order_task.copy', [$order, '_token' => auth()->user()->ajax_token ]) }}",
                type: "POST",
                dataType: "json",
                data: $("form#copy").serialize(),
                success: function (result) {
                    location.replace(result.url);
                },
                error: function () {
                    $("#copy_confirm span").addClass("d-none");
                    toastr.error("Не получилось скопировать ТЗ", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });

        }
    </script>
@endsection
