@extends('components.sidebar.offcanvas-right')

@section('body')
    <div class="card">
        <div class="card-body">
            <form method="post" id="agreement">
                    <select class="form-control select2" multiple name="user[]">
                        @foreach($users as $parent)
                            <option value="{{ $parent->id }}" @if($user->parent_users->contains($parent->id)) selected @endif>{{ $parent->fullName }}</option>
                        @endforeach
                    </select>
            </form>

            <x-ui.button.default btn_type="primary" class="mt-3" id="agree_confirm">Сохранить</x-ui.button.default>
        </div>
    </div>
    <script>

        $(document).ready(function() {
            $(".select2").select2({
                multiple: true
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
                url: "{{ route('users.parent_users_set', [$user, '_token' => _token()]) }}",
                type: "POST",
                dataType: "html",
                data: $("form#agreement").serialize(),
                success: function (result) {
                    $(block_elem).unblock();
                    $(".card.users [block='parent']").html(result);
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
