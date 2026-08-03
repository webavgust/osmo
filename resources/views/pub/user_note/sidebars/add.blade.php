@extends('components.sidebar.offcanvas-right')

@section('body')
    <form method="post" id="note" class="needs-validation" novalidate>
        <div class="card">
            <div class="card-body p-0">
                <div class="mt-2">

                    <div class="mb-3">
                        <label>Заголовок <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label>Описание</label>
                        <textarea class="form-control" rows="5" name="text"></textarea>
                    </div>
                    <div class="mb-1">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input warning check-light-warning" type="checkbox" id="warning2-light-check" value="1" name="favorite">
                            <label class="form-check-label " for="warning2-light-check">Добавить в избранное</label>
                        </div>
                    </div>
                </div>


                <x-ui.button.outline type="button" btn_type="primary" class="mt-3 w-100 disabled" id="note_submit">
                    Создать заметку
                </x-ui.button.outline>
            </div>
        </div>
    </form>

    <script>
        $(document).ready(function() {
            rebind();
            $("#note_submit").on("click", function() {
                note_submit();
            });
        });

        function note_submit(event) {
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
                url: "{{ route('api.user-notes.create', ['_token' => _token()]) }}",
                type: "POST",
                data: $("form#note").serialize(),
                dataType: "html",
                success: function (html) {
                    $(block_elem).unblock();
                    $(".note-has-grid").html(html);
                    sidebar_close();
                },
                error: function () {
                    toastr.error("Не получилось добавить заметку", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });
        }


        function form_check() {
            var err = false;
            $("#note [required]").each(function() {
               if(!$(this).val()) err = true
            });
            $("#note [mode='time']").each(function() {
               if($(this).find("input:checked").length == 0) err = true;
            });
            if(err) {
                $("#note_submit").addClass('disabled');
            } else {
                $("#note_submit").removeClass('disabled');
            }
            return err;
        }
        function rebind()
        {
            $("#note input").on("change keyup", function() {
               form_check();
            });
        }
    </script>
@endsection
