@extends('components.box.box-static-large')

@section('body')
    <div>
        <form class="form-horizontal r-separator border-top" id="correct_count">
            <div class="card-body">
                <div class="form-group mb-3 row pb-3">
                    <label for="inputEmail3" class="col-sm-3 text-end control-label col-form-label">Указанное кол-во</label>
                    <div class="col-sm-9 fs-7 text-warning ps-4">
                        {{ $sampleWork->count }}
                    </div>
                </div>
                <div class="form-group mb-3 row pb-3">
                    <label class="col-sm-3 text-end control-label col-form-label">Реальное количество</label>
                    <div class="col-sm-9">
                        <input name="count_real" type="number" min="0" max="{{ $sampleWork->count - 1 }}" class="form-control" value="0" style="width: 60px">
                    </div>
                </div>
                <div class="form-group mb-3 row pb-3">
                    <label for="inputEmail3" class="col-sm-3 text-end control-label col-form-label">Комментарий</label>
                    <div class="col-sm-9">
                        <input name="comment" type="text" class="form-control" id="inputEmail3" placeholder="Укажите комментарий">
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function correct_save() {
            if (!confirm('Вы действительно хотите внести корректировку?'))
                return false;

            $("body").block(block_default);
            $.ajax({
                url: '{{ route('api.sample-works.correct', $sampleWork) }}?_token={{ _token() }}',
                data: $("form#correct_count").serialize(),
                method: "POST",
                dataType: 'json',
                success: function (answer) {
                    if(answer.result == 'success') {
                        location.reload();
                    } else {
                        $("body").unblock();
                        toastr.error("Не получилось внести корректировку", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                },
                error: function () {
                    $("body").unblock();
                    toastr.error("Не получилось внести корректировку", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                }
            })
        }
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>

        <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:correct_save();">
            <x-ui.icon.solid icon="fa-save"></x-ui.icon.solid>
            <span>Сохранить</span>
        </x-ui.button.default>
    </div>
@endsection


