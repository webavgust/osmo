@extends('layouts.layout')


@section('styles')
    <link rel="stylesheet" type="text/css" href="/assets/libs/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
    <style>
        ::placeholder {
            color: #DDD!important;
        }
        ::-webkit-input-placeholder { /* WebKit, Blink, Edge */
            color: #DDD!important;
        }
        .datepicker {
            margin-top: 3px;
        }
        .datepicker-months tbody tr { display: none; }
        .month {
            width: 100%;
        }
        .month td.weekend {
            background: #ff0000;
        }
        .month td:not(.week_num) {
            width: 13%;
            padding: 0;
            text-align: center;
            vertical-align: center;
            font-size: 11px;
        }
        .month thead th,
        .week_num {
            background: #F7F7F7;
            color: #BBB;
            font-size: 9px;
            text-align: center;
        }

        .month td .control {
            position: relative;
            height: 35px;
        }
        .month td .control input {
            visibility: hidden;
        }
        .month td .control.week_start {
        }
        .month td .control label {
            position: absolute;
            left: 0px;
            top: 0px;
            width: 100%;
            height: calc(100% + 1px);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .month td .control input:checked ~ label {
            background: #ffcfcf;
            color: #df3300;
            font-weight: bold;
        }

        #datepicker {
            cursor: pointer;
        }
        label[for='cb_lock'] {
            width: 16px;
        }
    </style>
@endsection



@section('breadcrumb_add')
    <span class="badge bg-warning text-dark fs-3 ms-2 fw-bold" id="datepicker" data-orient="bottom">{{ $year }}</span>
@endsection



@section('breadcrumb_right')
    <div class="d-flex align-items-center">
        <x-ui.button.outline btn_type="warning" id="button_copy" class="d-none me-1" title="Взять из производственного календаря">Копировать дни</x-ui.button.outline>
        <x-ui.button.outline btn_type="info" id="button_submit" class="d-none">Сохранить</x-ui.button.outline>
        <div class="form-check form-switch ms-3">
            <input class="form-check-input" type="checkbox" id="cb_lock" checked>
            <label class="form-check-label" for="flexSwitchCheckDefault"><i class="fas fa-lock"></i></label>
        </div>
    </div>
@endsection



@section('content')
<div class="container-fluid">
    <form id="calendar">
        <div class="row">
            <div class="col-3">
                <div class="card">
                    <div class="card-body p-3 d-flex justify-content-between">
                        <h4 class="m-0">Время работы</h4>
                    </div>
                    <div class="card-body">
                        @for($i = 1; $i <= 7; $i++)
                            <div class="row mb-1">
                                <div class="col-12">
                                    <div class="input-group">
                                        <span class="input-group-text" style="width: 110px">{{ config('settings.day_names')[$i] }}</span>
                                        <div class="input-group-text">
                                            <div class="form-check">
                                                <input type="checkbox" name="time[{{$i}}][active]" class="form-check-input" value="1" @if($user_working_time[$i]['active']) checked @endif }}>
                                            </div>
                                        </div>
                                        <input class="form-control" name="time[{{$i}}][from]" type="time" value="{{ tools()->time_convert($user_working_time[$i]['from'] ?? null) }}">
                                        <input class="form-control" name="time[{{$i}}][to]" type="time" value="{{ tools()->time_convert($user_working_time[$i]['to'] ?? null) }}">
                                    </div>

                                </div>
                            </div>
                        @endfor
                    </div>
                </div>


            </div>
            <div class="col-9">
                <div class="row">
                    @for($i = 1; $i <= 12; $i++)
                        <div class="col-4">
                            <x-work-calendar :month="$i" :year="$year" :dates="$dates" :custom="$user_custom_time"></x-work-calendar>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </form>
</div>

@endsection

@section('js')
    @parent
    <script src="/assets/libs/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#datepicker").datepicker({
                format: "mm-yyyy",
                startView: "months",
                minViewMode: "months",
                orientation: 'auto bottom',
                setDate: '01.01.{{$year}}'
            }).on('changeYear', function(e){
                location.replace('{{ route('users.work_calendar', [$user]) }}/' + e.date.getUTCFullYear());
            }).datepicker("setDate",'01-{{$year}}');

            $("#button_submit").on("click", function(e) {
                var block_elem = $(".page-wrapper");
                $(block_elem).block({
                    message: '<i class="fas fa-spin fa-sync text-white"></i>',
                    timeout: 2000, //unblock after 2 seconds
                    overlayCSS: {
                        backgroundColor: "#000",
                        opacity: 0.5,
                        cursor: "wait",
                    },
                    css: {
                        border: 0,
                        padding: 0,
                        backgroundColor: "transparent",
                    },
                });

                $.ajax({
                    url: '{{ route('api.users.work_calendar.set', [$user, $year, '_token' => auth()->user()->ajax_token]) }}',
                    type: 'POST',
                    dataType: 'json',
                    data: $("form#calendar").serialize(),
                    success: function() {
                        $(".month input[type='checkbox']").removeAttr("readonly");
                        toastr.success("Даты сохранены!", "Это успех!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        $(block_elem).unblock();
                    },
                    error: function() {
                        $(".month input[type='checkbox']").removeAttr("readonly");
                        toastr.error("Произошла ошибка!", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                });
            });

            $("#button_copy").on("click", function(e) {
                if(!confirm('Вы хотите скопировать график на этот год из производственного календаря. Это действие сотрёт все дни за текущий год. Вы уверены?')) return false;

                var block_elem = $(".page-wrapper");
                $(block_elem).block({
                    message: '<i class="fas fa-spin fa-sync text-white"></i>',
                    timeout: 2000, //unblock after 2 seconds
                    overlayCSS: {
                        backgroundColor: "#000",
                        opacity: 0.5,
                        cursor: "wait",
                    },
                    css: {
                        border: 0,
                        padding: 0,
                        backgroundColor: "transparent",
                    },
                });

                $.ajax({
                    url: '{{ route('api.users.work_calendar.copy', [$user, $year, '_token' => auth()->user()->ajax_token]) }}',
                    type: 'POST',
                    dataType: 'json',
                    success: function() {
                        $(".month input[type='checkbox']").removeAttr("readonly");
                        toastr.success("Даты сохранены!", "Это успех!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        $(block_elem).unblock();
                        location.reload();
                    },
                    error: function() {
                        $(".month input[type='checkbox']").removeAttr("readonly");
                        toastr.error("Произошла ошибка!", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                });
            });
            $(".month label").on("click", function() {
                if($("#cb_lock").prop('checked')) {
                    var input = $(this).parents('.control').find('input');
                    if(!input.prop('checked')) sidebar({href:'{{ route('users.sidebar_work_calendar_set_time', $user) }}/' + input.val()})
                }
            });
            $("#cb_lock").on("change", function() {
                if(!$(this).prop("checked")) {
                    $("label[for='cb_lock'] i").removeClass("fa-lock").addClass("fa-lock-open");
                    $(".month input[type='checkbox']").removeAttr("disabled");
                    $("#button_submit").removeClass("d-none");
                    $("#button_copy").removeClass("d-none");
                }  else {
                    $("label[for='cb_lock'] i").removeClass("fa-lock-open").addClass("fa-lock");
                    $(".month input[type='checkbox']").attr("disabled", "Y");
                    $("#button_submit").addClass("d-none");
                    $("#button_copy").addClass("d-none");
                }
            });
        });
    </script>
@endsection
