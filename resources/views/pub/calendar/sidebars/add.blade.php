@extends('components.sidebar.offcanvas-right')


@section('body')
    <form method="post" id="calendar_add">
        <div class="card">
            <div class="card-body p-0">
                <h4>Продолжительность события</h4>
                <div class="mt-3 date-select">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="mode" value="day" id="mode_day"
                               checked>
                        <label class="form-check-label fs-4" for="mode_day">
                            Целый день
                        </label>

                        <div mode="day" class="date-select-inputs mt-2">
                            <div class="form-group">
                                <input type="date" class="form-control" value="{{ date("Y-m-d") }}" name="date">
                            </div>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" value="dates" id="mode_day" value="dates"
                               name="mode">
                        <label class="form-check-label fs-4" for="mode_dates">
                            Диапазон дат
                        </label>

                        <div mode="dates" class="date-select-inputs mt-2 d-none">
                            <div class="form-group">
                                <input type="text" class="form-control drp" name="dates">
                            </div>
                        </div>

                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="mode" value="time" id="mode_time">
                        <label class="form-check-label fs-4" for="mode_time">
                            По времени
                        </label>

                        <div mode="time" class="date-select-inputs mt-2 d-none">
                            <div class="input-group mb-3">
                                <input type="date" class="form-control" value="{{  now()->floorMinutes(5)->format('Y-m-d') }}"
                                       name="datetime[date1]">
                                <input type="time" class="form-control" value="{{  now()->floorMinutes(5)->format('H:i') }}"
                                       name="datetime[time1]">
                            </div>
                            <div class="input-group mb-3">
                                <input type="date" class="form-control" value="{{ now()->addHour()->floorMinutes(5)->format('Y-m-d') }}"
                                       name="datetime[date2]">
                                <input type="time" class="form-control" value="{{  now()->addHour()->floorMinutes(5)->format('H:i') }}"
                                       name="datetime[time2]">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <div class="mb-3">
                        <label>Заголовок <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="caption">
                    </div>
                    <div class="mb-3">
                        <label>Описание</label>
                        <textarea class="form-control" rows="5" name="text"></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Цвет</label>
                        <div class="mt-1">
                            <input checked name="color" value="success"
                                   class="me-2 form-check-input success check-outline outline-success" type="radio">
                            <input name="color" value="danger"
                                   class="me-2 form-check-input danger check-outline outline-danger" type="radio">
                            <input name="color" value="warning"
                                   class="me-2 form-check-input warning check-outline outline-warning" type="radio">
                            <input name="color" value="primary"
                                   class="me-2 form-check-input primary check-outline outline-primary" type="radio">
                            <input name="color" value="secondary"
                                   class="me-2 form-check-input secondary check-outline outline-secondary" type="radio">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <div>
        <x-ui.button.default btn_type="primary" id="add_event_submit" class="disabled">Добавить событие</x-ui.button.default>
    </div>


    <script>
        function add_event_check() {
            var err = false;
            if (
                !$("form#calendar_add [name='caption']").val()
            ) err = true;

            if (err) {
                $("#add_event_submit").addClass("disabled");
                return false;
            } else {
                $("#add_event_submit").removeClass("disabled");
                return true;
            }
        }

        $(document).ready(function () {
            $('input[type="time"]').on('blur', function() {
                const value = $(this).val();
                const [hours, minutes] = value.split(':').map(Number);

                // Округляем минуты до ближайших кратных 5
                const roundedMinutes = Math.round(minutes / 10) * 10;

                // Если округленные минуты больше 59, увеличиваем часы
                const newHours = hours + Math.floor(roundedMinutes / 60);
                const newMinutes = roundedMinutes % 60;

                // Устанавливаем новое значение
                $(this).val(`${String(newHours).padStart(2, '0')}:${String(newMinutes).padStart(2, '0')}`);
            });


            $("#add_event_submit").on("click", function () {
                if (!add_event_check) return false;

                var block_elem = $("body");
                $(block_elem).block({
                    message: '<i class="fas fa-spin fa-sync text-white"></i>',
                    baseZ: 100000,
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
                    url: "{{ route('api.calendar.add', ['_token' => _token() ]) }}",
                    type: "POST",
                    dataType: "json",
                    data: $("form#calendar_add").serialize(),
                    success: function (result) {
                        location.reload();
                    },
                    error: function () {

                        toastr.error("Не получилось создать событие", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        $(block_elem).unblock();
                    }
                });

            });
            $("form#calendar_add input").on("change keyup", function () {
                add_event_check();
            });
            $("[name='mode']").on("change", function () {
                $(".date-select .date-select-inputs").addClass("d-none");
                $(".date-select [mode='" + $(this).val() + "']").removeClass("d-none");
            });

            $(".drp").daterangepicker({
                "minYear": '2022',
                "autoApply": true,
                ranges: {
                    '2020 - НВ': [moment().year(2020).startOf('year'), moment()],
                    '7 дней': [moment().subtract(6, 'days'), moment()],
                    '30 дней': [moment().subtract(29, 'days'), moment()],
                    'Прошлый месяц': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Этот месяц': [moment().startOf('month'), moment()],
                    'Этот год': [moment().startOf('year'), moment()]
                },
                "locale": {
                    "format": "DD.MM.YYYY",
                    "separator": " - ",
                    "applyLabel": "Применить",
                    "cancelLabel": "Отменить",
                    "fromLabel": "От",
                    "toLabel": "До",
                    "customRangeLabel": "Свой",
                    "weekLabel": "Н",
                    "daysOfWeek": ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],
                    "monthNames": ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"],
                    "firstDay": 1,
                },
                "alwaysShowCalendars": true,
                "minDate": "01/01/2022",
                "startDate": moment().format('DD/MM/YYYY'),
                "endDate": moment().add(1, 'days').format('DD/MM/YYYY'),
            });
        });
    </script>
@endsection
