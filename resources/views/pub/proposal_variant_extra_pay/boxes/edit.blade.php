@extends('components.box.box-static-large')

@section('title')
    <div>
        <h3 class="m-0">Редактирование платежей</h3>
    </div>
@endsection


@section('body')
    <style>
        #tbl_percent[count='1'] .percent_delete {
            display: none;
        }
    </style>
    <form id="extra_pay">
        <div class="fs-2 text-secondary px-1 fw-bold">
            <div class="d-flex justify-content-between">
                <h2>Проценты</h2>
                <div>
                    <x-ui.badge.default type="info">
                        <x-ui.icon.regular icon="fa-desktop" class="me-1" title="Платформа + Нейронки"/>
                        {{ tools()->cost_normalize($cost_software) }} {{ $currency->symbol }}
                    </x-ui.badge.default>
                    <x-ui.badge.default type="primary">
                        <x-ui.icon.regular icon="fa-person-digging" class="me-1" title="Работы"/>
                        {{ tools()->cost_normalize($variant->work_cost_total) }} {{ $currency->symbol }}
                    </x-ui.badge.default>
                    <x-ui.badge.default type="secondary">
                        = {{ tools()->cost_normalize($cost_software + $cost_work) }} {{ $currency->symbol }}
                    </x-ui.badge.default>
                </div>
            </div>
            <table id="tbl_percent" class="table table-bordered mb-0" count="{{ max(1, $variant->extra_pays->count()) }}">
                <tr>
                    <th style="width: 20px"></th>
                    <th style="width: 20px" class="text-center">№</th>
                    <th>Наименование</th>
                    <th style="width: 70px">Раздел</th>
                    <th style="width: 150px">Процент</th>
                    <th style="width: 80px">Итого КП</th>
                    <th style="width: 30px"></th>
                </tr>
                <tbody class="rows">
                @for($i = 0; $i < 10; $i++)
                    @php
                        $have = $variant->extra_pays[$i] ?? null;

                    @endphp
                    <tr @class(["once", "d-none" => $i > 0 && empty($have)])>
                        <td class="py-1 text-center fs-5">
                            <x-ui.icon.regular icon="fa-ellipsis-vertical" class="handler"/>
                        </td>
                        <td class="py-0 text-center num">{{ $i + 1 }}</td>
                        <td class="p-0">
                            <input value="{{ $have->name ?? '' }}" type="text" class="w-100 border-0 fs-2 p-2" placeholder="Без названия" name="data[percent][{{ $i }}][name]">
                        </td>
                        <td class="p-0">
                            <select class="border-0 ps-1 type" name="data[percent][{{ $i }}][type]">
                                <option value="all">Общий</option>
                                <option value="software" @selected(!empty($have) && $have->block == 'software')>ПО</option>
                                <option value="work" @selected(!empty($have) && $have->block == 'work')>Работы</option>
                            </select>
                        </td>
                        <td class="p-0">
                            <div class="d-flex justify-content-between align-items-center pe-2">
                                <input value="{{ $have->percent ?? 0}}" style=width:70px;" type="number" step="0.1" min="0" max="100"
                                       class="amount border-0 fs-2 p-2" name="data[percent][{{ $i }}][amount]">
                                <span class="text-nowrap">= <span class="row_amount">0</span> {{ $currency->symbol }}</span>
                            </div>
                        </td>
                        <td class="text-nowrap text-end">
                            <span class="kp_total">0</span> {{ $currency->symbol }}
                        </td>
                        <td class="px-0 text-center">
                            <a class="percent_delete" href="javascript:void(0);"
                               onclick="javascript:percent_delete($(this))">
                                <x-ui.icon.regular icon="fa-xmark" class="text-danger"/>
                            </a>
                        </td>
                    </tr>
                @endfor
                </tbody>
            </table>

            <x-ui.button.light btn_type="info" class="fs-2 px-2 py-0 mt-1" onclick="javascript:percent_add();" >
                <x-ui.icon.regular icon="fa-plus" class="me-1"/>
                Добавить
            </x-ui.button.light>
        </div>

        @if($variant->proposal->variants->count() > 1)
            <div class="mt-3">
                <div class="form-check fs-2 mt-2">
                    <input class="form-check-input" type="checkbox" value="1" id="cb_all" name="cb_all">
                    <label class="form-check-label fs-3" for="cb_all">
                        Сохранить во все варианты
                    </label>
                </div>
            </div>
        @endif
    </form>
    <script>
        var count = 1;
        $(document).ready(function () {
            $("table#tbl_percent select, table#tbl_percent .amount").on("change keyup", function () {
                recalc();
            });

            $("table#tbl_percent tbody").sortable({
                handle: '.handler',
                stop: function (event, ui) {
                    $("table#tbl_percent tbody .once").each(function (index) {
                       $(this).find(".num").html(index + 1);
                    });

                    recalc();
                }
            });


        });


        function percent_add() {
            $("table#tbl_percent .once.d-none").first().removeClass("d-none");
            count++;
            $("table#tbl_percent").attr("count", count);
        }

        function percent_delete(obj) {
            obj.parents('tr').remove();
            $("table#tbl_percent tbody .once").each(function (index) {
                $(this).find(".num").html(index + 1);
            });
        }


        function recalc() {
            var base_software = {{ $cost_software }};
            var base_work = {{ $cost_work }};

            $("table#tbl_percent tbody .once:not(.d-none)").each(function () {
                type = $(this).find("select.type").val();
                percent = $(this).find("input.amount").val() - 0;
                additional_software = additional_work = 0;

                switch (type) {
                    case 'software':
                        additional_software = Math.round(base_software * percent) / 100;
                        base_software += additional_software;
                        break;
                    case 'work':
                        additional_work = Math.round(base_work * percent) / 100;
                        $(this).find(".row_amount").html(cost_normalize(additional_work));
                        base_work += additional_work;
                        break;
                    case 'all':
                        additional_software = Math.round(base_software * percent) / 100;
                        base_software += additional_software;

                        additional_work = Math.round(base_work * percent) / 100;
                        $(this).find(".row_amount").html(cost_normalize(additional_work));
                        base_work += additional_work;
                        break;
                }

                $(this).find(".row_amount").html(cost_normalize(additional_software + additional_work));
                $(this).find(".kp_total").html(cost_normalize(base_software + base_work));
            });
        }


        function save() {
            if (!confirm("Вы действительно хотите сохранить данные?")) return;

            $("body").block();

            $.ajax({
                url: "{{ route('api.proposal-variant-extra-pay.store', [$variant, '_token' => _token() ]) }}",
                method: "POST",
                dataType: "json",
                data: $("form#extra_pay").serialize(),
                success: function (response) {
                    location.reload();
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

        recalc();
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>

        <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:save();">
            <x-ui.icon.solid icon="fa-save"></x-ui.icon.solid>
            <span>Сохранить</span>
        </x-ui.button.default>
    </div>
@endsection


