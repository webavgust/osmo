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
        #create_visit .card-body .row label+div {
            padding-top: 6px!important;
        }

        #create_visit .col-sm-8:has(input#date_fact) {
            padding-top: 0!important;
        }

        #create_visit input#date_fact {
            font-size: 16px!important;
            font-weight: 300!important;
            margin-left: -4px;
            width: 150px;
        }
    </style>
    <div id="create_visit">
        <form class="form-horizontal" id="aprove_visit">
            <div class="card-body">
                <div class="mb-1 row">
                    <label for="fname" class="col-sm-4 text-end control-label col-form-label">Адрес</label>
                    <div class="col-sm-8 pt-1 font-16 ps-4">
                        {{ $visit->order_task_address->address }}
                    </div>
                </div>
                @if(!empty($visit->plan_visit))
                    <div class="mb-1 row">
                        <label for="fname" class="col-sm-4 text-end control-label col-form-label">По плану в календаре выездов</label>
                        <div class="col-sm-8 pt-1 font-16 ps-4">
                            {{ _date($visit->plan_visit->date) }}
                        </div>
                    </div>
                @endif
                <div class="mb-1 row">
                    <label for="lname" class="col-sm-4 text-end control-label col-form-label">
                        {{ $visit->users->count() == 1 ? "Пробоотборщик" : "Пробоотборщики" }}
                    </label>
                    <div class="col-sm-8 ps-1">
                        <ol>
                            @foreach($visit->users as $user)
                                <li class=" pt-1 font-16">
                                    {{ $user->full_name }}
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
                <div class="mb-1 row">
                    <label for="lname" class="col-sm-4 text-end control-label col-form-label">Предполагаемая дата
                        отбора</label>
                    <div class="col-sm-8 pt-1 font-16 ps-4">
                        {{ _date($visit->plan_visit_at) }}
                    </div>
                </div>
                <div class="mb-1 row">
                    <label for="lname" class="col-sm-4 text-end control-label col-form-label">Фактическая дата отбора</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="date" id="date_fact"
                               value="{{ $visit->plan_visit_at->format('d.m.Y') }}" class="w-auto" readonly>

                    </div>
                </div>
                <div class="mb-1 row">
                    <label for="lname" class="col-sm-4 text-end control-label col-form-label">Номер акта</label>
                    <div class="col-sm-8 pt-1 font-16 ps-4" style="margin-left: -13px">
                        <div class="input-group justify-content-start">
                            <input id="document_dp" name="document_dp" readonly style="width: 85px;" type="text" class="form-control flex-grow-0 text-right" value="{{ \Illuminate\Support\Str::substr($number->number, 0, 6) }}">
                            <input id="document_num" name="document_num" style="width: 65px" type="number" class="form-control flex-grow-0 fw-bold padding-left: 8px" value="{{ \Illuminate\Support\Str::substr($number->number, 6) }}" maxlength="3" min="{{ \App\Modules\Pub\DocumentNumber\Models\DocumentNumber::START_FROM[\App\Modules\Pub\Visit\Models\Visit::class] }}">
                            <div class="document_loader align-items-center ms-2 d-flex align-items-center">
                                <span id="error" class="d-none"><i class="fa-solid fa-xmark text-danger"></i></span>
                                <span id="success" class="d-none"><i class="fa-solid fa-check text-success"></i></span>
                                <span id="spinner" class="d-1none spinner-border spinner-border-sm" role="status">
                                    <span class="sr-only"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        $("#date_fact").datepicker({
            startView: "days",
            minViewMode: "days",
            orientation: 'auto bottom',
            language: 'ru',
            autoclose: true,
        }).on('changeDate', function (e) {
            var selectedDate = moment(e.date);
            selectedDate.subtract(3, 'days');
            $("#document_dp").datepicker('setStartDate', selectedDate.toDate());
            selectedDate.add(4, 'days');
            $("#document_dp").datepicker('setEndDate', selectedDate.toDate());
            $("#document_dp").datepicker('update', e.date);
            $("#document_dp").val(moment(e.date).format("YYMMDD"));

            number_get_available();
        });;

        $("#document_dp").datepicker({
            format: "yymmdd",
            startView: "days",
            minViewMode: "days",
            orientation: 'auto bottom',
            startDate: '-3d',
            endDate: '+1d',
            language: 'ru',
            autoclose: true,
        }).on('changeDate', function (e) {
           number_get_available();
        });


        function number_check() {
            clearTimeout(window.nc_timer);
            $("#btn_submit").attr("disabled", "disabled");
            window.nc_timer = setTimeout(() => {
                number_check_proceed();
            }, 500);
        }
        function number_get_available() {
            $("#btn_submit").attr("disabled", "disabled");
            $.ajax({
                url: "{{ route('api.document_number.available', ['mode' => 'visit']) }}?_token={{ _token() }}",
                type: "POST",
                dataType: "json",
                data: {
                    'date': $("#document_dp").val()
                },
                success: function (result) {
                    $(".document_loader > *").addClass("d-none");
                    $(".document_loader > #success").removeClass("d-none");
                    $("#document_confirm").removeClass("d-none");
                    $("#document_num").val(result.number);

                    window.b_number_check = true;
                    save_check();
                },
                error: function () {
                    $(".document_loader > *").addClass("d-none");
                }
            });
        }
        function number_check_proceed() {
            $.ajax({
                url: "{{ route('api.document_number.simple_check', ['mode' => 'visit']) }}?_token={{ _token() }}",
                type: "POST",
                dataType: "json",
                data: {
                    'date': $("#document_dp").val(),
                    'number': $("#document_num").val(),

                },
                success: function (result) {
                    $(".document_loader > *").addClass("d-none");
                    if(result.status == 'success') {
                        $(".document_loader > #success").removeClass("d-none");
                        $("#document_confirm").removeClass("d-none");
                        $("#document_new_number").removeClass("invisible").find("strong").html(result.number);
                        window.b_number_check = true;
                    } else {
                        if(result.status != 'same') $(".document_loader > #error").removeClass("d-none");
                        $("#document_confirm").addClass("d-none");
                        $("#document_new_number").addClass("invisible");
                        window.b_number_check = false;
                    }

                    save_check();
                },
                error: function () {
                    $(".document_loader > *").addClass("d-none");
                }
            });
        }

        function save_check() {
            var err = false;
            if(!window.b_number_check) err = true;

            if(err) {
                $("#btn_submit").attr("disabled", "disabled");
            } else {
                $("#btn_submit").removeAttr("disabled");
            }
            return !err;
        }

        function save() {
            if(!save_check() || !confirm('Вы действительно хотите подтвердить выезд?'))
                return false;

            $("body").block(block_default);
            $.ajax({
                url: '{{ route('api.visit.set_working', $visit) }}?_token={{ _token() }}',
                data: $("form#aprove_visit").serialize(),
                method: "POST",
                dataType: 'json',
                success: function (answer) {
                    if(answer.result == 'success') {
                        location.reload();
                    } else {
                        $("body").unblock();
                        toastr.error(answer.message, "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        number_get_available();
                    }
                },
                error: function () {
                    $("body").unblock();
                    toastr.error("Не получилось подтвердить выезд", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                }
            })
        }

        $(document).ready(function () {
            $("#document_num").on("keyup change", function() {
                number_check();
            });

            number_check();
        });
    </script>
@endsection

@section('footer')
    @can('visit_edit', $visit)
        <div class="d-flex justify-content-between align-items-center w-100">
            <x-ui.a.ajax url="{{ route('api.visit.delete', $visit) }}" method="post"  btn_type="danger" submit_message="Вы действительно хотите удалить выезд?" :reload="true">
                <x-ui.icon.solid icon="fa-trash" class="me-1"></x-ui.icon.solid>
                <span>Удалить</span>
            </x-ui.a.ajax>

            <div>
                <x-ui.a.box btn_type="warning" href="{{ route('visit.box_edit', $visit) }}" class="me-2">
                    <x-ui.icon.solid icon="fa-close" class="me-1"></x-ui.icon.solid>
                    <span>Редактировать</span>
                </x-ui.a.box>

                <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:save();" disabled>
                    <x-ui.icon.solid icon="fa-save" class="me-1"></x-ui.icon.solid>
                    <span>Сохранить</span>
                </x-ui.button.default>
            </div>
        </div>
    @endcan
@endsection


