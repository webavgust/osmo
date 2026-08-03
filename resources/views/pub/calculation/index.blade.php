@extends('layouts.layout')


@section('styles')
    <link rel="stylesheet" type="text/css"
          href="/assets/libs/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">

@endsection



@section('breadcrumb_add')
    <span class="badge bg-warning text-dark fs-3 ms-2 fw-bold" id="datepicker"
          data-orient="bottom">{{ $pointer->format('m.Y') }}</span>
@endsection



@section('breadcrumb_right')
    @can('calculation_recalc')
        <div class="d-flex align-items-center">
            @if(!empty($calculation))
                <span class="font-10 text-center me-3">Последний расчёт:<br/><?=$calculation->created_at->format('d.m.y H:i')?></span>
            @endif
            <x-ui.button.outline btn_type="info" id="button_submit" class="d-none">Пересчитать</x-ui.button.outline>
            <div class="form-check form-switch ms-3">
                <input class="form-check-input" type="checkbox" id="cb_lock" checked>
                <label class="form-check-label" for="flexSwitchCheckDefault"><i class="fas fa-lock"></i></label>
            </div>
        </div>
    @endcan
@endsection

@section('content')
    <div class="container-fluid">
        <x-calculation.block name="Руководители" type="supervisor" :data="$salaries['supervisor'] ?? []"></x-calculation.block>

        <x-calculation.block name="Пробоотборщики" type="methodist" :data="$salaries['sampler'] ?? []"></x-calculation.block>

        <x-calculation.block name="Аналитики" type="tender" :data="$salaries['analytic'] ?? []"></x-calculation.block>
    </div>

@endsection

@section('js')
    @parent
    <script src="/assets/libs/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script>
        $(document).ready(function () {
            var pointer =  moment('{{ $pointer->format('Y-m-d') }}');

            $("#datepicker").datepicker({
                language: "ru-RU",
                format: "MM-YYYY",
                startView: "months",
                minViewMode: "months",
                orientation: 'auto bottom',
                maxDate: 'now'
            }).on('changeMonth', function (e) {
                location.replace('{{ route('calculation.index') }}/' + moment(e.date).format('M.YYYY'));
            }).datepicker("setDate", pointer.toDate());;

            $("#button_submit").on("click", function (e) {
                if(!confirm('Вы действительно хотите запустить пересчёт запрплаты?'))
                    return false;

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
                    url: '{{ route('api.calculation.recalc', ['_token' => auth()->user()->ajax_token]) }}',
                    type: 'POST',
                    dataType: 'json',
                    success: function () {
                        alert("Расчёт запущен в работу и займёт продолжительное время. По окончании расчёта придёт письмо на почту {{ \App\Modules\Pub\User\Models\User::find(\App\Modules\Pub\Constant\Models\Constant::get('salary_calc_notification_user_id'))['email'] ?? 'неизвестно' }}")
                    },
                    error: function () {
                        $(".month input[type='checkbox']").removeAttr("readonly");
                        toastr.error("Произошла ошибка!", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                });
            });

            $("#cb_lock").on("change", function () {
                if (!$(this).prop("checked")) {
                    $("label[for='cb_lock'] i").removeClass("fa-lock").addClass("fa-lock-open");
                    $(".month input[type='checkbox']").removeAttr("disabled");
                    $("#button_submit").removeClass("d-none");
                } else {
                    $("label[for='cb_lock'] i").removeClass("fa-lock-open").addClass("fa-lock");
                    $(".month input[type='checkbox']").attr("disabled", "Y");
                    $("#button_submit").addClass("d-none");
                }
            });
        });
    </script>
@endsection
