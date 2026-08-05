@extends('components.box.box-static-large')

@section('body')
    <form id="filter">
        {{-- Статус сделки --}}
        <div class="row mb-5">
            <label class="col-sm-4 col-form-label fw-semibold text-sm-end">Статус</label>
            <div class="col-sm-8">
                <x-ui.select.multiple
                    :selected="$filter['stage_name'] ?? []"
                    name="filter[stage_name][]" class="select2" :items="$stage_name" blank-ignore="1" key-as-value="1"/>
            </div>
        </div>

        {{-- Ответственный менеджер --}}
        <div class="row mb-5">
            <label class="col-sm-4 col-form-label fw-semibold text-sm-end">Ответственный менеджер</label>
            <div class="col-sm-8">
                <x-ui.select.multiple
                    :selected="$filter['assigned_by'] ?? []"
                    name="filter[assigned_by][]" class="select2" :items="$assigned_by" blank-ignore="1" key-as-value="1"/>
            </div>
        </div>

        {{-- Вероятность --}}
        <div class="row mb-5">
            <label class="col-sm-4 col-form-label fw-semibold text-sm-end">Вероятность</label>
            <div class="col-sm-8">
                <x-ui.select.multiple
                    :selected="$filter['probability'] ?? []"
                    name="filter[probability][]" class="select2" :items="$probability" blank-ignore="1" key-as-value="1"/>
                <div class="form-text">Значение поля «Вероятность» в карточке сделки, %</div>
            </div>
        </div>

        {{-- Страна получения средств --}}
        <div class="row mb-5">
            <label class="col-sm-4 col-form-label fw-semibold text-sm-end">Страна получения средств</label>
            <div class="col-sm-8">
                <x-ui.select.multiple
                    :selected="$filter['country'] ?? []"
                    name="filter[country][]" class="select2" :items="$country" blank-ignore="1" key-as-value="1"/>
                <div class="form-text">Поле компании «Страна» из Битрикс24</div>
            </div>
        </div>
    </form>

    <script>
        function save() {
            $("body").block(block_default);

            $.ajax({
                url: "{{ route('api.bitrix.dashboard.set_filter', ['_token' => _token() ]) }}",
                type: "POST",
                data: $("form#filter").serialize(),
                dataType: "json",
                success: function (response) {
                    if (response.result == 'success') {
                        location.reload();
                    } else {
                        toastr.error(response.answer, "Это провал!", {
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

        $(document).ready(function() {
            $(".select2").select2({
                dropdownParent: $(".modal .modal-content"),
                width: '100%',
            });
        });
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <button type="button" class="btn btn-light" onclick="javascript:box_close();">
            <i class="fa-light fa-xmark fs-5 me-2"></i>
            <span>Закрыть</span>
        </button>

        <button type="button" id="btn_save" class="btn btn-success" onclick="javascript:save();">
            <i class="fa-light fa-floppy-disk fs-5 me-2"></i>
            <span>Сохранить</span>
        </button>
    </div>
@endsection
