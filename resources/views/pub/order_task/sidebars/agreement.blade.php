@extends('components.sidebar.offcanvas-right')


@section('body')
    <style>
        .select2-results__message {
            display: none;
        }
    </style>
    <div class="card">
        <div class="card-body">
            <form method="post" id="agreement">
                @forelse($users as $user)
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="user_{{ $user->id }}" name="user[]" value="{{ $user->id }}">
                        <label class="form-check-label" for="user_{{ $user->id }}">
                            {{ $user->full_name }}
                        </label>
                    </div>
                @empty
                    <div class=" alert customize-alert alert-dismissible alert-light-danger text-danger fade show remove-close-icon" role="alert">
                        <div class=" d-flex align-items-center font-weight-medium me-3 me-md-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-info text-danger fill-white feather-sm me-2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="8"></line></svg>
                            Нет подходящих пользователей
                        </div>
                    </div>
                @endforelse
            </form>

            <x-ui.button.default disabled btn_type="primary" class="mt-3" id="agree_confirm">Отправить</x-ui.button.default>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $("form#agreement input").on("change", function() {
               if($("form#agreement input:checked").length > 0) {
                   $("#agree_confirm").removeAttr("disabled");
               } else {
                   $("#agree_confirm").attr("disabled", 1);
               }
            });
            $("#agree_confirm").on("click", function() {
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
                        agree_submit();
                    }
                });
            });
        });


        function agree_submit() {
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
                url: "{{ route('api.order_task.agree', [$order_task, '_token' => auth()->user()->ajax_token ]) }}",
                type: "POST",
                dataType: "html",
                data: $("form#agreement").serialize(),
                success: function (result) {
                    $(block_elem).unblock();

                    // если детальная, то перезагружаем
                    if($("[page='order_task.detail']").length > 0)
                    {
                        location.reload();
                        return true;
                    }

                    // рабочий стол
                    $(".agree-table tr[task_id='{{ $order_task->id }}']").replaceWith(result);

                    // таблица со всеми заявками
                    $("#table_orders tr[id='{{$order_task->id}}'] .dropdown-item.agree").remove();
                    $("#table_orders tr[id='{{$order_task->id}}'] .dropdown-item.agree_view").removeClass('d-none');
                    $("#table_orders tr[id='{{$order_task->id}}'] .badge").removeClass('bg-success').addClass('bg-warning').addClass('text-dark').html('На согласовании');



                    toastr.success("ТЗ отпралвено на согласование", "Это успех!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    sidebar_close();
                },
                error: function () {
                    $("#attach_confirm span").addClass("d-none");
                    toastr.error("Не получилось отправить ТЗ на согласование", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });

        }
    </script>
@endsection
