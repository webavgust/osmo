{{-- patch v46: менеджер и номер договора с генератором (формы создания и редактирования) --}}
<div class="form-group mb-3 row pb-3">
    <label for="inp_manager" class="col-sm-3 text-end control-label col-form-label">Менеджер</label>
    <div class="col-sm-9">
        <x-ui.select.single name="manager" :items="$users" id="id" value-name="full_name" class="select2" :value="$manager_id"/>
    </div>
</div>
<div class="form-group mb-3 row pb-3">
    <label for="inp_number" class="col-sm-3 text-end control-label col-form-label">Номер договора</label>
    <div class="col-sm-9">
        <div class="d-flex align-items-center gap-2">
            <input name="number" type="text" min="0" class="form-control w-50 text-start" id="inp_number" value="{{ $number }}">
            <button type="button" class="btn btn-icon btn-light-primary flex-shrink-0" id="btn_generate_number"
                    title="Сгенерировать номер" aria-label="Сгенерировать номер" onclick="javascript:contract_generate_number();">
                <i class="fas fa-wand-magic-sparkles"></i>
            </button>
        </div>
    </div>
</div>

<script>
    /**
     * Подставить в поле следующий номер договора; записывается он при сохранении формы
     */
    function contract_generate_number() {
        var contract_number_error = function (message) {
            toastr.error(message, "Это провал!", {
                progressBar: true,
                "timeOut": 3000,
            });
        };

        var type = $("select[name='type']").val();
        var manager = $("select[name='manager']").val();
        if (!type) return contract_number_error("Сначала выберите тип договора");
        if (!manager) return contract_number_error("Сначала выберите менеджера");

        var button = $("#btn_generate_number").prop("disabled", true);
        $.ajax({
            url: "{{ route('api.contract.generate_number', ['_token' => _token()]) }}",
            type: "GET",
            dataType: "json",
            data: {type: type, manager: manager, date: $("#inp_date").val()},
            success: function (response) {
                if (response.result == 'success') {
                    $("#inp_number").val(response.number).trigger("change");
                } else {
                    contract_number_error(response.message || "Не получилось сгенерировать номер");
                }
            },
            error: function () {
                contract_number_error("Не получилось сгенерировать номер");
            },
            complete: function () {
                button.prop("disabled", false);
            }
        });
    }

    $(document).ready(function() {
        $("select[name='manager']").select2({
            width: '100%',
            dropdownParent: $("#staticBackdrop"),
        });
    });
</script>
