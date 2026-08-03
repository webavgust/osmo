@extends('components.sidebar.offcanvas-right')


@section('title')
    <div class="d-flex justify-content-between align-items-center">
        <span>{{ $title }}</span>
        <x-ui.a.sidebar href="{{ route('calendar.sidebar_show', $event) }}" class="ms-4 d-flex align-items-center">
            <x-ui.icon.regular icon="fa-eye" class="me-1"></x-ui.icon.regular>
            Просмотр
        </x-ui.a.sidebar>
    </div>
@endsection

@section('body')
    <form method="post" id="calendar_edit">
        <div class="card">
            <div class="card-body p-0">
                <h4>Продолжительность события</h4>
                <div class="mt-3 date-select">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="mode" value="day" id="mode_day"
                               @if($event->mode == 'day')checked @endif>
                        <label class="form-check-label fs-4" for="mode_day">
                            Целый день
                        </label>

                        <div mode="day" class="date-select-inputs mt-2 @unless($event->mode == 'day')d-none @endif">
                            <div class="form-group">
                                <input type="date" class="form-control" value="{{ $event->mode == 'day' ? $event->start->format('Y-m-d') : date("Y-m-d") }}" name="date">
                            </div>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" value="dates" id="mode_day" value="dates"
                               name="mode" @if($event->mode == 'dates')checked @endif>
                        <label class="form-check-label fs-4" for="mode_dates">
                            Диапазон дат
                        </label>

                        <div mode="dates" class="date-select-inputs mt-2  @unless($event->mode == 'dates')d-none @endif">
                            <div class="form-group">
                                <input type="text" class="form-control drp" name="dates">
                            </div>
                        </div>

                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="mode" value="time" id="mode_time" @if($event->mode == 'time')checked @endif>
                        <label class="form-check-label fs-4" for="mode_time">
                            По времени
                        </label>

                        <div mode="time" class="date-select-inputs mt-2 @unless($event->mode == 'time')d-none @endif">
                            <div class="input-group mb-3">
                                <input type="date" class="form-control" value="{{ $event->mode == 'time' ? $event->start->format('Y-m-d') : now()->floorMinutes(5)->format('Y-m-d') }}"
                                       name="datetime[date1]">
                                <input type="time" class="form-control" value="{{ $event->mode == 'time' ? $event->start->format('H:i') : now()->floorMinutes(5)->format('H:i') }}"
                                       name="datetime[time1]">
                            </div>
                            <div class="input-group mb-3">
                                <input type="date" class="form-control" value="{{ $event->mode == 'time' ? $event->end->format('Y-m-d') : now()->addHour()->floorMinutes(5)->format('Y-m-d') }}"
                                       name="datetime[date2]">
                                <input type="time" class="form-control" value="{{ $event->mode == 'time' ? $event->end->format('H:i') : now()->addHour()->floorMinutes(5)->format('H:i') }}"
                                       name="datetime[time2]">
                            </div>

                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="mode" value="future" id="mode_future" @if(empty($event->start)) checked @endif>
                        <label class="form-check-label fs-4" for="mode_future">
                            На будущее
                        </label>
                    </div>
                </div>

                <div class="mt-5">
                    <div class="mb-3">
                        <label>Заголовок <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="caption" value="{{ $event->title }}">
                    </div>
                    <div class="mb-3">
                        <label>Описание</label>
                        <textarea class="form-control" rows="5" name="text">{{ $event->text }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label>Цвет</label>
                        <div class="mt-1">
                            @foreach(['success', 'danger', 'warning', 'primary', 'secondary'] as $mode)
                                <input @if($event->color == $mode) checked @endif name="color" value="{{ $mode }}"
                                       class="me-2 form-check-input {{ $mode }} check-outline outline-{{ $mode }}" type="radio">
                            @endforeach

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <div>
        <x-ui.button.default btn_type="primary" id="add_event_submit" class="disabled">Сохранить событие
        </x-ui.button.default>
    </div>


    <script>
        function add_event_check() {
            var err = false;
            if (
                !$("form#calendar_edit [name='caption']").val()
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
                    url: "{{ route('api.calendar.edit', [$event, '_token' => _token() ]) }}",
                    type: "POST",
                    dataType: "json",
                    data: $("form#calendar_edit").serialize(),
                    success: function (result) {
                        location.reload();
                    },
                    error: function () {

                        toastr.error("Не получилось сохранить событие", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        $(block_elem).unblock();
                    }
                });

            });
            $("form#calendar_edit input").on("change keyup", function () {
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
                @if($event->type == 'dates')
                    "startDate": "{{ $event->start->format('d/m/Y') }}",
                    "endDate": "{{ $event->end->format('d/m/Y') }}",
                @endif
            });
        });
    </script>
@endsection
