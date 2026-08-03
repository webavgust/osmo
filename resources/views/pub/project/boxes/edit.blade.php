@extends('components.box.box-static-large')

@section('body')
    <form method="POST" id="form">
        <div class="card-body">
            <div class="form-group mb-3 row pb-3">
                <label for="inp_number" class="col-sm-3 text-end control-label col-form-label">Префикс</label>
                <div class="col-sm-9 pt-2">
                    <mark>{{ $project->prefix }}.</mark>
                </div>
            </div>
            <div class="form-group mb-3 row pb-3">
                <label for="inp_number" class="col-sm-3 text-end control-label col-form-label">Название проекта</label>
                <div class="col-sm-9">
                    <input name="name" type="text" min="0" class="form-control w-50 text-start" id="inp_number" value="{{ $project->name }}">
                </div>
            </div>
        </div>
    </form>

    <script>
        function box_check_form() {
            var err = false;
            if($("#inp_number").val() - 0 < 1) {
                err = true;
            }

            if(err) {
                $("#btn_submit").attr("disabled", "1");
            } else {
                $("#btn_submit").removeAttr("disabled");
            }

            return !err;
        }



        function save() {
            if (!box_check_form()) return;

            $("body").block(block_default);
            $.ajax({
                url: "{{ route('api.project.update', [$project, '_token' => _token() ]) }}",
                type: "POST",
                dataType: "json",
                data: $("form#form").serialize(),
                success: function (response) {
                    if (response.result == 'success') {
                        location.reload();
                    } else {
                        toastr.error("Не получилось сохранить данные", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        $("body").unblock();
                    }
                },
                error: function () {
                    toastr.error("Не получилось сохранить данные", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $("body").unblock();
                }
            });
        }

        function project_delete() {
            if(!confirm("Вы действительно хотите удалить этот проект?")) return;
            $.ajax({
                url: "{{ route('api.project.delete', [$project, '_token' => _token() ]) }}",
                type: "DELETE",
                dataType: "json",
                success: function (response) {
                    if (response.result == 'success') {
                        location.reload();
                    } else {
                        toastr.error("Не получилось удалить запись", "Это провал!", {progressBar: true, "timeOut": 3000,});
                        $("body").unblock();
                    }
                },
                error: function () {
                    toastr.error("Не получилось удалить запись", "Это провал!", {progressBar: true, "timeOut": 3000,});
                    $("body").unblock();
                }
            });
        }



        $(document).ready(function() {
            $("form#form input, form#form select").on("keyup change", function() {
                box_check_form();
            }) ;
        });
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        @if($project->canDelete())
            <x-ui.button.default btn_type="danger" onclick="javascript:project_delete();">
                <x-ui.icon.solid icon="fa-trash"></x-ui.icon.solid>
                <span>УДАЛИТЬ</span>
            </x-ui.button.default>
        @else
            <div/>
        @endif

        <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:save();">
            <x-ui.icon.solid icon="fa-file-pdf"></x-ui.icon.solid>
            <span>Cохранить</span>
        </x-ui.button.default>
    </div>
@endsection
