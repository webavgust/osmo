@extends('components.box.box-static-extralarge')

@section('body')
    <style>
        table#payments[count='1'] td.delete, table#payments[count='1'] th.delete {
            display: none;
        }
    </style>


    <form method="POST" id="form">
        <div class="card-body">
            <table id="payments" count="{{ $spec->payments->count() }}" class="table table-bordered rules w-100 mb-0 border-1 border-start border-top border-bottom">
                <thead>
                <tr class=" bg-light-secondary">
                    <th rowspan="2" style="width: 30px">?</th>
                    <th colspan="2" class="text-center">План</th>
                    <th colspan="2" class="text-center">Факт</th>
                    <th rowspan="2" style="width: 300px">Менеджер</th>
                    <th rowspan="2" class="delete" style="width: 1px"/>
                </tr>
                <tr class="border-top-0 bg-light-secondary">
                    <th class="w-10 p-1 fs-9 text-center">Дата</th>
                    <th class="w-10 p-1 fs-9 text-center">Сумма ({{ $spec->currency->symbol }})</th>
                    <th class="w-10 p-1 fs-9 text-center">Дата</th>
                    <th class="w-10 p-1 fs-9 text-center">Сумма ({{ $spec->currency->symbol }})</th>
                </tr>
                </thead>
                <tbody class="payments">
                    @for($i = 1; $i < 100; $i++)
                        @php
                            $payment = $spec->payments[$i - 1] ?? null;
                        @endphp
                        <tr @class(["once border-bottom-0", "d-none" => $i > 1 && empty($payment)])>
                            <td class="p-1 pe-0">
                                <div class="form-check">
                                    <input class="form-check-input warning" type="checkbox" name="payment[{{ $i }}][is_unknown]" value="1" id="flexCheckChecked" @checked($payment?->is_unknown)>
                                </div>
                            </td>
                            <td class="p-0">
                                <input name="payment[{{ $i }}][date_plan]" type="date" class="date_plan border-0 p-1 text-center w-100" value="{{ $payment?->date_plan?->format("Y-m-d") ?? null }}">
                            </td>
                            <td class="p-0">
                                <input name="payment[{{ $i }}][amount_plan]" type="text" class="amount_plan border-0 p-1 text-center pe-2 w-100" value="{{ $payment->amount_plan ?? null }}">
                            </td>
                            <td class="p-0">
                                <input name="payment[{{ $i }}][date_fact]" type="date" class="date_fact border-0 p-1 text-center w-100" value="{{ $payment?->date_fact?->format("Y-m-d") ?? null }}">
                            </td>
                            <td class="p-0">
                                <input name="payment[{{ $i }}][amount_fact]" type="text" class="amount_fact border-0 p-1 text-center pe-2 w-100" value="{{ $payment->amount_fact ?? null }}">
                            </td>
                            <td class="p-0">
                                <x-ui.select.single value="{{ $payment->user?->id ?? null }}" name="payment[{{ $i }}][user_id]" class="manager" :items="$users" id="id" value-name="full_name" blank-name="Выберите менеджера" blank-id="0"/>
                            </td>
                            <td class="p-0 delete text-center pt-1">
                                <a href="javascript:void(0);" onclick="javascript:delete_payment($(this))">
                                    <x-ui.icon.regular icon="fa-xmark" class="text-danger"/>
                                </a>
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>

            <div class="mt-2">
                <a href="javascript:void(0);" onclick="javascript:add_payment();">
                    <x-ui.icon.regular icon="fa-plus"/>
                    Добавить платёж
                </a>
            </div>
        </div>
    </form>

    <script>

        function delete_payment(obj) {
            if(!confirm('Вы действительно хотите удалить этот платёж?')) return false;

            obj.parents("tr").remove();

            count = $("tbody.payments tr.once:not(.d-none)").length;
            $("table#payments").attr("count", count);

            box_check_form();
        }

        function add_payment() {
            var $hiddenRow = $("tbody.payments tr.once.d-none").first();

            if ($hiddenRow.length) {
                $hiddenRow.removeClass("d-none");
            } else {
                console.warn("Нет скрытых строк для отображения.");
            }

            count = $("tbody.payments tr.once:not(.d-none)").length;
            $("table#payments").attr("count", count);

            box_check_form();
        }

        function box_check_form() {
            var err = 0;
            $("tbody.payments tr.once:not(.d-none)").each(function() {
                date_plan = $(this).find(".date_plan").val();
                amount_plan = $(this).find(".amount_plan").val();
                date_fact = $(this).find(".date_fact").val();
                amount_fact = $(this).find(".amount_fact").val();
                manager = Number($(this).find(".manager").val());
                console.log(manager);

                if(!amount_plan && !amount_fact) err++;
                if(amount_fact && !date_fact) err++;
                if(manager === 0) err++;
            });

            console.log(err);

            if(err) {
                $("#btn_submit").attr("disabled", "1");
            } else {
                $("#btn_submit").removeAttr("disabled");
            }

            return !err;
        }


        function save() {
            if (!box_check_form()) return;
            if(!confirm("Вы действительно хотите сохранить платежи?")) return false;

            $("body").block(block_default);
            $.ajax({
                url: "{{ route('api.payment.create', [$spec, '_token' => _token() ]) }}",
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
            $("table#payments input, table#payments select").on("keyup change", function() {
                box_check_form();
            }) ;

            var types = @json(\App\Modules\Pub\Contract\Models\ContractType::getDecorated());
            var formattedData = $.map(types, function(value, key) {
                return {
                    id: key,
                    text: '<span class="text-center text-' + value.color + '"><i class="fa-regular ' + value.icon + ' me-2"></i>' + value.label + '</span>',
                };
            });

            $("select[name='proposal']").select2({
                dropdownParent: $(".modal  .modal-content"),
                width: '100%',
            });

            $("select[name='organization']").select2({
                dropdownParent: $(".modal  .modal-content"),
                width: '100%',
            });




            box_check_form();
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
            <span>Сохранить</span>
        </x-ui.button.default>
    </div>
@endsection
