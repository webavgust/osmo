@extends('components.box.box-static-large')

@section('body')
    <form method="POST" id="keys_form">
        <div class="card-body">
            <div class="form-group mb-3 row pb-3">
                <label for="inp_key" class="col-sm-3 text-end control-label col-form-label"></label>
                <div class="col-sm-9">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="flexCheckChecked" name="active" @checked($key->active)>
                        <label class="form-check-label" for="flexCheckChecked">
                            Активный
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-group mb-3 row pb-3">
                <label for="inp_key" class="col-sm-3 text-end control-label col-form-label">Ключ
                    <span class="text-danger">*</span>
                </label>
                <div class="col-sm-9">
                    <input value="{{ $key->key }}" name="key" type="text" class="form-control w-100 text-start" id="inp_key" required>
                </div>
            </div>
            {{-- patch v49: периоды действия — продление ключа по новой спецификации вместо нового ключа.
                 Текущий срок ключа = последний по дате окончания период --}}
            <div class="form-group mb-3 row pb-3">
                <label class="col-sm-3 text-end control-label col-form-label">Периоды действия
                    <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                    <table class="table table-sm align-middle mb-2" id="key_periods">
                        <thead>
                        <tr class="fs-7 text-muted">
                            <th>Спецификация</th>
                            <th width="150">С</th>
                            <th width="150">По</th>
                            <th width="40"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($periods as $period)
                            @include('pub.license_key.boxes._period_row', ['uid' => 'p' . $period->id, 'period' => $period, 'specs' => $specs])
                        @endforeach
                        </tbody>
                    </table>

                    <template id="key_period_template">
                        @include('pub.license_key.boxes._period_row', ['uid' => '__UID__', 'period' => null, 'specs' => $specs])
                    </template>

                    <a href="javascript:void(0)" onclick="javascript:key_period_add();" class="fs-7">
                        <x-ui.icon.regular icon="fa-plus-circle" class="me-1"/>
                        Продлить / добавить спецификацию
                    </a>
                </div>
            </div>
        </div>
    </form>

    <script>
        function box_check_form() {
            var err = false;
            $("form#keys_form input[required]").each(function() {
                if(!$(this).val()) err = true;
            });

            // patch v49: в каждом периоде «с» не позже «по» (даты Y-m-d сравниваются строкой)
            $("#key_periods tbody tr").each(function() {
                var from = $(this).find(".key_period_from").val(), to = $(this).find(".key_period_to").val();
                $(this).find(".key_period_to").toggleClass("is-invalid", !!(from && to && from > to));
                if(from && to && from > to) err = true;
            });
            if(!$("#key_periods tbody tr").length) err = true;

            if(err) {
                $("#btn_submit").attr("disabled", "1");
            } else {
                $("#btn_submit").removeAttr("disabled");
            }

            return !err;
        }

        function key_delete() {
            if(!confirm("Вы действительно хотите удалить этот ключ?")) return;
            $.ajax({
                url: "{{ route('api.license-keys.delete', [$key, '_token' => _token() ]) }}",
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
                url: "{{ route('api.license-keys.update', [$key, '_token' => _token() ]) }}",
                type: "POST",
                dataType: "json",
                data: $("form#keys_form").serialize(),
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


        /**
         * Дата Y-m-d со сдвигом (patch v49)
         */
        function key_period_shift(ymd, years, days) {
            var d = new Date(ymd + "T00:00:00");
            d.setFullYear(d.getFullYear() + years);
            d.setDate(d.getDate() + days);
            var m = ("0" + (d.getMonth() + 1)).slice(-2), day = ("0" + d.getDate()).slice(-2);
            return d.getFullYear() + "-" + m + "-" + day;
        }

        /**
         * Продление: новая строка периода. «С» — день после самого позднего «по»,
         * «по» — плюс год минус день
         */
        function key_period_add() {
            var last = "";
            $("#key_periods .key_period_to").each(function() {
                if($(this).val() > last) last = $(this).val();
            });

            var uid = "n" + Date.now();
            var html = $("#key_period_template").html().replace(/__UID__/g, uid);
            var $row = $(html).appendTo("#key_periods tbody");

            if(last) {
                var from = key_period_shift(last, 0, 1);
                $row.find(".key_period_from").val(from);
                $row.find(".key_period_to").val(key_period_shift(from, 1, -1));
            }

            box_check_form();
        }

        /**
         * Удалить строку периода; последний период удалить нельзя
         */
        function key_period_remove(el) {
            if($("#key_periods tbody tr").length <= 1) {
                toastr.error("У ключа должен остаться хотя бы один период", "Это провал!", {
                    progressBar: true,
                    "timeOut": 3000,
                });
                return;
            }
            $(el).closest("tr").remove();
            box_check_form();
        }

        $(document).ready(function() {
            $("form#keys_form").on("keyup change", "input, select", function() {
                box_check_form();
            }) ;
        });
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">

        <x-ui.button.default btn_type="danger" onclick="javascript:key_delete();">
            <x-ui.icon.solid icon="fa-trash" class="me-1"></x-ui.icon.solid>
            <span>Удалить</span>
        </x-ui.button.default>

        <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:save();" >
            <x-ui.icon.solid icon="fa-file-pdf"></x-ui.icon.solid>
            <span>Сохранить</span>
        </x-ui.button.default>
    </div>
@endsection
