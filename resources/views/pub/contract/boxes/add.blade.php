@extends('components.box.box-static-large')

@section('body')
    <form method="POST" id="form">
        <div class="card-body">
            <div class="form-group mb-3 row pb-3">
                <label for="inp_organization" class="col-sm-3 text-end control-label col-form-label">Организация</label>
                <div class="col-sm-9">
                    <x-ui.select.single name="organization" :items="$organizations" id="id" class="select2" blank-ignore="1"/>
                </div>
            </div>

{{--            <div class="form-group mb-3 row pb-3">--}}
{{--                <label for="int_proposal" class="col-sm-3 text-end control-label col-form-label">КП</label>--}}
{{--                <div class="col-sm-9">--}}
{{--                    @if($company->proposals->isEmpty())--}}
{{--                        <div class="col-form-label fs-4">Неизвестно</div>--}}
{{--                    @else--}}
{{--                        <select name="proposal" class="form-select select2" id="int_proposal">--}}
{{--                            <option value="0">Неизвестно</option>--}}
{{--                            @foreach($company->proposals as $proposal)--}}
{{--                                <option value="{{ $proposal->id }}" <?if(!empty($proposal_default) && $proposal_default->id == $proposal->id) :?> selected <?endif;?> >{{ $proposal->name }} ({{ $proposal->iteration }})</option>--}}
{{--                            @endforeach--}}
{{--                        </select>--}}
{{--                    @endif--}}

{{--                    <div id="proposal_name" class="mt-1">--}}
{{--                        <input name="proposal_name" type="text" min="0" class="form-control w-50 text-start" placeholder="Название вместо КП">--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}

            <div class="form-group mb-3 row pb-3">
                <label for="inp_type" class="col-sm-3 text-end control-label col-form-label">Тип</label>
                <div class="col-sm-9">
                    <select name="type" class="form-select select2 w-50" id="inp_type"></select>
                </div>
            </div>
            <div class="form-group mb-3 row pb-3">
                <label for="inp_number" class="col-sm-3 text-end control-label col-form-label">Номер договора</label>
                <div class="col-sm-9">
                    <input name="number" type="text" min="0" class="form-control w-50 text-start" id="inp_number">
                </div>
            </div>
            <div class="form-group mb-3 row pb-3">
                <label for="inp_date" class="col-sm-3 text-end control-label col-form-label">Дата договора</label>
                <div class="col-sm-9">
                    <input name="date" type="date" min="0" class="form-control w-25" id="inp_date">
                </div>
            </div>
            <div class="form-group mb-3 row pb-3">
                <label for="inp_signed" class="col-sm-3 text-end control-label col-form-label">Подписан</label>
                <div class="col-sm-9">
                    <div class="form-check pt-2">
                        <input class="form-check-input" type="checkbox" value="1" id="inp_signed" name="cb_signed">
                        <label class="form-check-label" for="flexCheckChecked">

                        </label>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        prefixes = @json($prefixes);

        function box_check_form() {
            var err = false;
            if($("#inp_amount").val() - 0 < 1) {
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
                url: "{{ route('api.contract.create', [$partner, '_token' => _token() ]) }}",
                type: "PUT",
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

            var types = @json(\App\Modules\Pub\Contract\Models\ContractType::getActualDecorated());
            var formattedData = $.map(types, function(value, key) {
                return {
                    id: key,
                    text: '<span class="text-center text-' + value.color + '"><i class="fa-regular ' + value.icon + ' me-2"></i>' + value.label + '</span>',
                };
            });

            $("select[name='proposal']").select2({
                dropdownParent: $(".modal  .modal-content"),
                width: '100%',
            }).on("change", function() {
                if($(this).val() == 0) {
                    $("#proposal_name").show();
                } else {
                    $("#proposal_name").hide();
                }
            });

            $("select[name='organization']").select2({
                dropdownParent: $(".modal  .modal-content"),
                width: '100%',
            });


            $("select[name='type']").select2({
                width: '50%',
                dropdownParent: $(".modal  .modal-content"),
                data: formattedData, // Передаем отформатированные данные
                escapeMarkup: function(markup) {
                    return markup; // Не экранировать HTML
                }
            }).val("unknown").trigger('change');
        });
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>

        <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:save();" disabled>
            <x-ui.icon.solid icon="fa-file-pdf"></x-ui.icon.solid>
            <span>Создать</span>
        </x-ui.button.default>
    </div>
@endsection
