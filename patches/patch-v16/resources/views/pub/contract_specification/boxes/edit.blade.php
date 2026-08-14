@extends('components.box.box-static-large')

@section('body')
    <form method="POST" id="form">
        <div class="card-body">
            <div class="form-group mb-3 row pb-3">
                <label  class="col-sm-3 text-end control-label col-form-label">Статус</label>
                <div class="col-sm-9">
                    <x-ui.select.single :value="$spec->status" name="status" :items="$statuses" class="select2" blank-ignore="1"/>
                </div>
            </div>
            <div class="form-group mb-3 row pb-3">
                <label for="inp_name" class="col-sm-3 text-end control-label col-form-label">Договор
                    <span class="text-danger">*</span>
                </label>
                <div class="col-sm-9">
                    <x-ui.select.single :value="$spec->contract->id" value-name="number-check" name="contract" :items="$spec->contract->partner->contracts()->where('type', $spec->contract->type)->get()" id="id" class="select2 contract-select" blank-ignore="1"/>
                </div>
            </div>
            <div class="form-group mb-3 row pb-3">
                <label for="inp_name" class="col-sm-3 text-end control-label col-form-label">Компания
                    <span class="text-danger">*</span>
                </label>
                <div class="col-sm-9">
                    <x-ui.select.single :value="$spec->company->id" value-name="number" name="company" :items="$spec->company->partner->companies" id="id" class="select2 company-select" blank-ignore="1"/>
                </div>
            </div>

            <div class="form-group mb-3 row pb-3">
                <label for="inp_name" class="col-sm-3 text-end control-label col-form-label">Название
                    <span class="text-danger">*</span>
                </label>
                <div class="col-sm-9">
                    <input value="{{ $spec->name }}" name="name" type="text" min="0" class="form-control w-50 text-start" id="inp_name" required>
                </div>
            </div>

            {{-- дата спецификации (patch v16): от неё считается срок сделки и разбивка по годам --}}
            <div class="form-group mb-3 row pb-3">
                <label for="inp_date_create" class="col-sm-3 text-end control-label col-form-label">Дата</label>
                <div class="col-sm-9">
                    <input name="date_create" type="date" class="form-control w-50 text-start" id="inp_date_create"
                           value="{{ $spec->date_create?->format('Y-m-d') }}">
                    <div class="form-text">Если оставить пустым, встанет дата рамочного договора</div>
                </div>
            </div>

            <div class="form-group mb-3 row pb-3">
                <label for="inp_name" class="col-sm-3 text-end control-label col-form-label">Валюта
                    <span class="text-danger">*</span>
                </label>
                <div class="col-sm-9">
                        <x-ui.select.single name="currency" :items="$currencies" :value="$spec->currency->slug" blank-ignore="1"></x-ui.select.single>
                </div>
            </div>
{{--            <div class="form-group mb-3 row pb-3">--}}
{{--                <label for="inp_amount" class="col-sm-3 text-end control-label col-form-label">Сумма--}}
{{--                    <span class="text-danger">*</span>--}}
{{--                </label>--}}
{{--                <div class="col-sm-9">--}}
{{--                    <input value="{{ $spec->amount }}" name="amount" type="text" min="0" class="form-control w-50 text-start" id="inp_amount">--}}
{{--                </div>--}}
{{--            </div>--}}
            <div class="form-group mb-3 row pb-3">
                <label for="inp_signed" class="col-sm-3 text-end control-label col-form-label">Подписан</label>
                <div class="col-sm-9">
                    <div class="form-check pt-2">
                        <input class="form-check-input" type="checkbox" value="1" id="inp_signed" name="cb_signed" @checked($spec->is_signed)>
                        <label class="form-check-label" for="flexCheckChecked">

                        </label>
                    </div>
                </div>
            </div>


            <h4>Сценарии</h4>
            <x-contract_specification.scenarios_table :specification="$spec"/>
        </div>
    </form>

    <script>
        function box_check_form() {
            var err = false;
            if($("#inp_amount").val() - 0 < 1) {
                err = true;
            }

            $("#spec_scenarios .once").each(function() {
               if($(this).find("select").val() == -1 && !$(this).find("input.manual").val()) err = true;
            });

            if(err) {
                $("#btn_submit").attr("disabled", "1");
            } else {
                $("#btn_submit").removeAttr("disabled");
            }

            return !err;
        }

        function contract_delete() {
            if(!confirm("Вы действительно хотите удалить эту спецификацию?")) return;
            $.ajax({
                url: "{{ route('api.contract_spec.delete', [$spec, '_token' => _token() ]) }}",
                type: "DELETE",
                dataType: "json",
                success: function (response) {
                    if (response.result == 'success') {
                        location.reload();
                    } else {
                        toastr.error("Не получилось удалить запись", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        $("body").unblock();
                    }
                },
                error: function () {
                    toastr.error("Не получилось удалить запись", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $("body").unblock();
                }
            });
        }

        function save() {
            if (!box_check_form()) return;

            $("body").block(block_default);
            $.ajax({
                url: "{{ route('api.contract_spec.update', [$spec, '_token' => _token() ]) }}",
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


        $(document).ready(function() {
            $("form#form input, form#form select").on("keyup change", function() {
                box_check_form();
            }) ;

            $("select[name='contract']").select2({
                dropdownParent: $(".modal  .modal-content"),
                width: '100%',
            });

            $("select[name='company']").select2({
                dropdownParent: $(".modal  .modal-content"),
                width: '100%',
            });

            $("select[name='status']").select2({
                dropdownParent: $(".modal  .modal-content"),
                width: '100%',
            });
        });
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        @if($spec->canDelete())
            <x-ui.button.default btn_type="danger" onclick="javascript:contract_delete();">
                <x-ui.icon.solid icon="fa-trash" class="me-1"></x-ui.icon.solid>
                <span>Удалить</span>
            </x-ui.button.default>
        @else
            <div/>
        @endif

        <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:save();" >
            <x-ui.icon.solid icon="fa-file-pdf"></x-ui.icon.solid>
            <span>Сохранить</span>
        </x-ui.button.default>
    </div>
@endsection
