@extends('components.sidebar.offcanvas-right')

@section('body')
    <form method="post" id="staff" class="needs-validation" novalidate>

        <div class="card">
            <div class="card-body p-0">

                <div>
                    <x-ui.select.multiple class="select2" :selected="$selected" :items="$users" name="agreementer[]" value-name="fullName" id="id" placeholder="Выберите согласовантов"></x-ui.select.multiple>
                </div>

                <x-ui.button.outline type="button" btn_type="primary" class="mt-3 w-100 " id="staff_submit">
                    Сохранить
                </x-ui.button.outline>
            </div>
        </div>
    </form>

    <script>
        $(document).ready(function() {
            $("#staff_submit").on("click", function() {
                staff_submit();
            });
            $(".select2").select2({
                'placeholder' : 'Выберите согласовантов'
            });
        });

        function staff_submit(event) {
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
                url: "{{ route('api.user_department.agreement_save', [$row, '_token' => _token()]) }}",
                type: "POST",
                data: $("form#staff").serialize(),
                dataType: "json",
                success: function (json) {
                    $(block_elem).unblock();
                    $("#agreementers_count").html(json.count);
                    sidebar_close();
                },
                error: function () {
                    toastr.error("Не получилось сохранить согласовантов", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });
        }


    </script>
@endsection
