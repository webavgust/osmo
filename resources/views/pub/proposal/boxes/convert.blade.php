@extends('components.box.box-static-large')

@section('body')
    <form method="POST" id="generate" >
        <div class="row">
            <div class="px-3 mt-2">
                <div class="form-floating">
                    <input name="name_alt" id="tb-name-alt" placeholder="" type="text" class="form-control">
                    <label for="tb-name-alt">Название (альт.)</label>
                </div>
            </div>

            <div class="mt-3">
                <div class="input-group mt-1">
                    <select class="form-select mr-sm-2" name="currency">
                        <option selected="">Выберите валюту</option>
                        @foreach($currencies as $currency)
                            <option value="{{ $currency->slug }}">{{ $currency->name }} ({{ $currency->symbol }})</option>
                        @endforeach
                    </select>

                    <div class="form-floating">
                        <input name="rate" id="tb-rate" placeholder="" type="text" class="form-control">
                        <label for="tb-rate">Курс</label>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        function save() {
            if (!box_check_form()) return;

            if(!confirm("Вы действительно хотите создать копию КП и пересчитать её по указанному курсу?"))
                return;

            $("body").block(block_default);
            $.ajax({
                url: "{{ route('api.proposal.convert', [$proposal, $proposal->iteration, '_token' => _token() ]) }}",
                type: "POST",
                dataType: "json",
                data: $("form#generate").serialize(),
                success: function (response) {
                    if (response.result == 'success') {
                        location.replace(response.url);
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


        function box_check_form() {
            error = 0;

            if(!$("[name='currency']").val()) error++;
            if(!$("[name='rate']").val()) error++;



            if(error) {
                $("#btn_submit").attr("disabled", "1");
                return false;
            } else {
                $("#btn_submit").removeAttr("disabled");
                return true;
            }
        }

        $(document).ready(function() {
            $("form#generate input").on("change keyup", function() {
                box_check_form();
            }) ;
        });
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>

        <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:save();" disabled >
            <x-ui.icon.solid icon="fa-file-pdf"></x-ui.icon.solid>
            <span>Создать</span>
        </x-ui.button.default>
    </div>
@endsection
