@extends('components.sidebar.offcanvas-right')

@section('body')
    <div class="card">
        <div class="card-body">
            <form method="post" id="set_time">
                <div class="input-group">
                    <input class="form-control" name="from" type="time" value="{{ !empty($has) ? tools()->time_convert($has->from) : ''}}" >
                    <span class="input-group-text">&ndash;</span>
                    <input class="form-control" name="to" type="time" value="{{ !empty($has) ? tools()->time_convert($has->to) : ''}}" >
                </div>
            </form>

            <div class="row">
                <div class="col-7">
                    <x-ui.button.default btn_type="primary" class="mt-3" id="agree_confirm">Сохранить</x-ui.button.default>
                </div>
                <div class="col-5 text-end">
                    @if(!empty($has))
                        <x-ui.button.outline btn_type="danger" class="mt-3" id="delete_confirm">Удалить</x-ui.button.outline>
                    @endif
                </div>
            </div>

        </div>
    </div>
    <script>
        $(document).ready(function() {

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
            $("#delete_confirm").on("click", function() {
                Swal.fire({
                    title: "Вы уверены?",
                    type: "danger",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Да",
                    cancelButtonText: "Нет",
                }).then((result) => {
                    if (result.value) {
                        delete_submit();
                    }
                });
            });
        });


        function delete_submit() {
            $("#set_time input").val('');
            agree_submit();
        }
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
                url: "{{ route('api.users.work_calendar.set_time', [$user, $date, '_token' => _token()]) }}",
                type: "POST",
                dataType: "json",
                data: $("form#set_time").serialize(),
                success: function (result) {
                    if(result.status == 'blank') {
                        $(".control[date='{{$date}}'] .fa-circle").addClass('d-none');
                    } else {
                        $(".control[date='{{$date}}'] .fa-circle").removeClass('d-none');
                    }
                    $(block_elem).unblock();
                    sidebar_close();

                },
                error: function () {

                    toastr.error("Ошибка!", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });

        }
    </script>
@endsection
