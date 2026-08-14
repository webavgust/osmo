@extends('components.box.box-static-large')

@section('body')
    <form method="POST" id="generate_excel" action="{{ route('proposal_tools.excel', [$proposal, $proposal->iteration]) }}" target="_blank">
        @csrf

        <div class="row">
            <div class="col-12">
                <div class="fs-4 fw-bold">Выберите варианты для выгрузки:</div>
            </div>

            <div class="px-3 mt-2">
                @foreach($proposal->variants as $variant)
                    <div class="input-group mt-1">
                        <div class="input-group-text">
                            <div class="form-check">
                                <input class="form-check-input" name="active[]" type="checkbox"
                                       value="{{ $variant->id }}" id="excel_variant_{{ $loop->iteration }}" checked="">
                            </div>
                        </div>

                        <label class="form-control d-flex justify-content-between align-items-center"
                               for="excel_variant_{{ $loop->iteration }}">
                            <span>
                                Вариант {{ $loop->iteration }}
                                @if($variant->is_main)
                                    <span class="badge badge-light-primary ms-1">основной</span>
                                @endif
                            </span>
                            <span class="fw-bold text-nowrap">
                                {{ tools()->cost_normalize(round($variant->cost_total)) }} {{ $proposal->currency->symbol }}
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="col-6 mt-3">
                <div class="fs-4 fw-bold">Шаблон:</div>

                <div class="mt-2">
                    <select name="template" class="form-select">
                        @foreach($templates as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-6 mt-3">
                <div class="fs-4 fw-bold">Позиции:</div>

                <div class="mt-2">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="excel_unprocessed"
                               checked="" name="show_unprocessed" value="1">
                        <label class="form-check-label" for="excel_unprocessed">
                            Выводить неактивные записи
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-4">
                <div class="fs-8 text-muted">
                    Каждый вариант уходит на свой лист. Шаблон «Со скидкой клиента» показывает цену
                    сразу со скидкой заказчика и не раскрывает партнёрскую — его можно отдавать наружу.
                </div>
            </div>
        </div>
    </form>

    <script>
        function save() {
            if (!box_check_form()) return;

            $("#generate_excel").submit();
        }

        function box_check_form() {
            let cb_count = $('#generate_excel input[name="active[]"]:checked').length;
            if (!cb_count) {
                $("#btn_submit").attr("disabled", "1");
                return false;
            } else {
                $("#btn_submit").removeAttr("disabled");
                return true;
            }
        }

        $(document).ready(function () {
            $("form#generate_excel input").on("change", function () {
                box_check_form();
            });
        });
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>

        <x-ui.button.default id="btn_submit" btn_type="success" onclick="javascript:save();">
            <x-ui.icon.solid icon="fa-file-excel"></x-ui.icon.solid>
            <span>Создать</span>
        </x-ui.button.default>
    </div>
@endsection
