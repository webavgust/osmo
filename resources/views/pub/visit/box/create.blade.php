@extends('components.box.box-static-large')

@section('body')
    <style>
        #create_visit .select2-container {
            width: 100% !important;
        }

        #create_visit .select2-container--default .select2-selection--multiple {
            border-color: #e9ecef !important;
        }

        #create_visit .table td {
            padding: 5px 10px !important;
            font-size: 13px;
        }
    </style>
    <div id="create_visit">
        <form class="form-horizontal" id="create_visit">
            <div class="card-body">
                <div class="mb-3 row">
                    <label for="fname" class="col-sm-4 text-end control-label col-form-label">Адрес</label>
                    <div class="col-sm-8 pt-1 font-16">
                        {{ $address->address }}
                    </div>
                </div>

                @if(!empty($plan_visits))
                    <div class="mb-3 row">
                        <label for="lname" class="col-sm-4 text-end control-label col-form-label">Плановый выезд</label>
                        <div class="col-sm-8">
                            <x-ui.select.single id="plan_visit" name="plan_visit" class="select2" :items="$plan_visits"
                                                  value-name="date_out" id="id" blank-ignore="1" :value="$plan ?? null"></x-ui.select.single>
                        </div>
                    </div>
                @endif


                <div class="mb-3 row">
                    <label for="lname" class="col-sm-4 text-end control-label col-form-label">Пробоотборщик</label>
                    <div class="col-sm-8">
                        @can('can_select_sampler')
                            <x-ui.select.multiple id="samplers" name="samplers[]" class="select2 sampler" :items="$samplers"
                                                  value-name="full_name" id="id"
                                                  :selected="[auth()->id()]" blank-ignore="1"></x-ui.select.multiple>
                        @else
                            <div class=" pt-1 font-16">
                                {{ auth()->user()->full_name }}
                            </div>
                        @endif
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="lname" class="col-sm-4 text-end control-label col-form-label">Предполагаемая дата
                        отбора</label>
                    <div class="col-sm-8">
                        <input type="date" class="form-control" name="date"
                               value="{{ (!empty($plan) && !empty($plan_visits[$plan])) ? $plan_visits[$plan]->date->format('Y-m-d') : now()->addWeek()->format('Y-m-d') }}" class="w-auto">
                    </div>
                </div>
            </div>

            <div class="points table-responsive">
                <table id="measures" class="table customize-table mb-0 v-middle border-1" border="1">
                    @foreach($address->points as $point)
                        <tbody class="point d-none"
                               samplers="|{{ $samplers->pluck('id')->implode("|")  }}|">
                        <tr>
                            <td colspan="10" class="p-2 bg-light-secondary">
                                <div class="d-flex justify-content-between">
                                    <h6 class="m-0 font-12 fw-bold d-flex align-items-center">
                                        <x-ui.icon.light icon="fa-map-pin" class="me-2"></x-ui.icon.light>
                                        <span>{{ $point->name }}</span>
                                    </h6>

                                    <div>
                                        <x-ui.badge.default type="danger">{{ $point->number }}</x-ui.badge.default>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td/>
                            <td/>
                            <td class="text-center font-10 fw-bold">Создано</td>
                            <td class="text-center font-10 fw-bold">Внесено</td>
                            <td class="text-center font-10 fw-bold">Назначить</td>
                        </tr>
                        @foreach($point->measures as $measure)
                            <tr>
                                <td style="padding-right: 0!important;">
                                    @if($measure->count <= ($counts[$measure->id] ?? 0))
                                       <x-ui.icon.solid class="text-success" icon="fa-check"></x-ui.icon.solid>
                                    @endif
                                </td>
                                <td>

                                    {{ $measure->measure->name }}
                                </td>

                                <td class="text-center ">
                                    <span  @class(['counter', 'cursor-pointer', 'fw-bold text-success' => ($measure->count <= ($counts[$measure->id] ?? 0))])>
                                        {{ $counts[$measure->id] ?? 0 }} / {{ $measure->count }}
                                    </span>
                                </td>
                                <td class="text-center ">
                                    <span  @class(['counter', 'cursor-pointer', 'fw-bold text-success' => ($measure->count <= ($asseted[$measure->id] ?? 0))])>
                                        {{ $asseted[$measure->id] ?? 0 }} / {{ $measure->count }}
                                    </span>
                                </td>
                                <td align="center">
                                    <div class="form-group d-flex align-items-center justify-content-center">
                                        <input name="count[{{ $measure->id }}]" type="number"
                                               class="form-control text-center p-0 font-12" min="0"
                                               limit="{{ max(0, $measure->count - ($counts[$measure->id] ?? 0)) }}" value="{{ max(0, $measure->count - ($counts[$measure->id] ?? 0)) }}" style="width: 50px">
                                        <x-ui.icon.solid icon="fa-triangle-exclamation"
                                                         class="text-warning ms-2 invisible cursor-help"
                                                         title="Превышено кол-во по ТЗ"></x-ui.icon.solid>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>
        </form>
    </div>

    <script>
        function selector_process() {

            $("#measures tbody").addClass('d-none');
            @can('can_select_sampler')
                $.each($("#create_visit .select2.sampler").val(), function (key, id) {
                    $("#measures tbody[samplers*=\"|" + id + "|\"]").removeClass('d-none');
                });
            @else
                $("#measures tbody[samplers*=\"|{{ auth()->id() }}|\"]").removeClass('d-none');
            @endcan

            count_check();
        }

        $(document).ready(function () {

            $("#create_visit .select2").select2({
                dropdownParent: $("#create_visit"),
                // maximumSelectionLength: 99,
            }).on("change", function () {
                selector_process();
            });
            selector_process();

            $("#create_visit input[limit]").on("change keyup", function () {
                if ($(this).attr("limit")-0 < $(this).val()) {
                    $(this).next("i").removeClass('invisible');
                } else {
                    $(this).next("i").addClass('invisible');
                }

                count_check();
            });

            $(".counter").on("click", function () {
                inp = $(this).parents("tr").find("input");
                inp.val(inp.attr("limit"));
                count_check();
            });
        });

        function count_check() {
            var err = true;
            $("input[limit]").each(function () {
                if ($(this).val() > 0) {
                    return err = false;
                }
            });

            @can('can_select_sampler')
                if ($("[name='samplers[]']").val().length == 0)
                    err = true;
            @endcan


            if (!err) {
                $("#btn_submit").removeAttr("disabled");
            } else {
                $("#btn_submit").attr("disabled", "disabled");
            }
            return !err;
        }

        function save() {
            if (!count_check() || !confirm('Вы действительно хотите создать выезд?'))
                return false;

            $("body").block(block_default);
            $.ajax({
                url: '{{ route('api.visit.create', $address) }}?_token={{ _token() }}',
                data: $("form#create_visit").serialize(),
                method: "POST",
                dataType: 'json',
                success: function (answer) {
                    if(answer.result == 'success') {
                        location.reload();
                    } else {
                        $("body").unblock();
                        toastr.error("Не получилось сохранить выезд", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                },
                error: function () {
                    $("body").unblock();
                    toastr.error("Не получилось сохранить выезд", "Это провал!", {
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

        <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:save();" disabled>
            <x-ui.icon.solid icon="fa-save"></x-ui.icon.solid>
            <span>Сохранить</span>
        </x-ui.button.default>
    </div>
@endsection


