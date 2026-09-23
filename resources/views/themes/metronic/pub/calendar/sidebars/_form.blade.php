{{--
    Форма события календаря в сайдбаре (тема Metronic): создание и правка.

    Переменные: $form_id (calendar_add | calendar_edit), $action (адрес API с токеном),
    $event (Calendar при правке, null при создании), $date (Y-m-d — день, по которому
    кликнули в виджете «Календарь»; только при создании), $submit (подпись кнопки),
    $error (текст тостера при ошибке). Режим «На будущее» (событие без даты) — только
    при правке, как в старой теме. Стили — public/metronic/css/osmo-compat-pages.css (.cal-form).
--}}
@php
    $now = now()->floorMinutes(5);
    $day = $date ?? $now->format('Y-m-d');

    $mode = $event ? ($event->start ? ($event->mode ?: 'day') : 'future') : 'day';
    $modes = ['day' => 'Весь день', 'dates' => 'Период', 'time' => 'По времени'];
    if ($event) $modes['future'] = 'На будущее';

    // значения полей: у правки — из события, если режим совпадает; иначе от выбранного дня и текущего времени
    $day_value = $event && $mode === 'day' ? $event->start->format('Y-m-d') : $day;
    $from = $event && $mode === 'time' ? $event->start : \Carbon\Carbon::parse($day . ' ' . $now->format('H:i'));
    $to = $event && $mode === 'time' ? ($event->end ?? $event->start) : $from->copy()->addHour();
    $range_from = $event && $mode === 'dates' ? $event->start : \Carbon\Carbon::parse($day);
    $range_to = $event && $mode === 'dates' ? ($event->end ?? $event->start) : $range_from->copy()->addDay();

    // цвета, которые принимает API; «серый» рисуем gray-500 — bg-secondary в Metronic почти белый
    $colors = [
        'success' => ['Зелёный', 'bg-success'],
        'danger' => ['Красный', 'bg-danger'],
        'warning' => ['Жёлтый', 'bg-warning'],
        'primary' => ['Синий', 'bg-primary'],
        'secondary' => ['Серый', 'bg-gray-500'],
    ];
    $color = $event && isset($colors[$event->color]) ? $event->color : 'success';
@endphp
<form method="post" id="{{ $form_id }}" class="cal-form" novalidate>
    <div class="mb-6">
        <label class="form-label fw-semibold">Когда</label>
        <div class="d-flex flex-wrap gap-2">
            @foreach($modes as $value => $label)
                <input type="radio" class="btn-check" name="mode" value="{{ $value }}" id="{{ $form_id }}_mode_{{ $value }}" @checked($mode === $value)>
                <label class="btn btn-sm btn-light btn-active-light-primary fw-semibold flex-fill px-2" for="{{ $form_id }}_mode_{{ $value }}">{{ $label }}</label>
            @endforeach
        </div>

        <div class="date-select mt-3">
            <div mode="day" @class(['date-select-inputs', 'd-none' => $mode !== 'day'])>
                <input type="date" class="form-control" name="date" value="{{ $day_value }}">
            </div>
            <div mode="dates" @class(['date-select-inputs', 'd-none' => $mode !== 'dates'])>
                <input type="text" class="form-control drp" name="dates" autocomplete="off">
            </div>
            <div mode="time" @class(['date-select-inputs', 'd-none' => $mode !== 'time'])>
                <div class="text-gray-600 fs-7 mb-1">Начало</div>
                <div class="input-group mb-3">
                    <input type="date" class="form-control" name="datetime[date1]" value="{{ $from->format('Y-m-d') }}">
                    <input type="time" class="form-control cal-form-time" name="datetime[time1]" value="{{ $from->format('H:i') }}">
                </div>
                <div class="text-gray-600 fs-7 mb-1">Окончание</div>
                <div class="input-group">
                    <input type="date" class="form-control" name="datetime[date2]" value="{{ $to->format('Y-m-d') }}">
                    <input type="time" class="form-control cal-form-time" name="datetime[time2]" value="{{ $to->format('H:i') }}">
                </div>
            </div>
            @if($event)
                <div mode="future" @class(['date-select-inputs text-gray-600 fs-7', 'd-none' => $mode !== 'future'])>
                    Событие без даты — попадёт в список «На будущее» на странице календаря
                </div>
            @endif
        </div>
    </div>

    <div class="mb-6">
        <label class="form-label fw-semibold" for="{{ $form_id }}_caption">Заголовок <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="{{ $form_id }}_caption" name="caption" value="{{ $event->title ?? '' }}" autocomplete="off">
    </div>

    <div class="mb-6">
        <label class="form-label fw-semibold" for="{{ $form_id }}_text">Описание</label>
        <textarea class="form-control" rows="4" id="{{ $form_id }}_text" name="text">{{ $event->text ?? '' }}</textarea>
    </div>

    <div class="mb-8">
        <label class="form-label fw-semibold">Цвет</label>
        <div class="d-flex gap-3">
            @foreach($colors as $value => [$name, $bg])
                <input type="radio" class="btn-check" name="color" value="{{ $value }}" id="{{ $form_id }}_color_{{ $value }}" @checked($color === $value)>
                <label class="cal-color {{ $bg }}" for="{{ $form_id }}_color_{{ $value }}" title="{{ $name }}"><i class="fa-solid fa-check"></i></label>
            @endforeach
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100" id="{{ $form_id }}_submit">{{ $submit }}</button>
</form>

<script>
    (function () {
        var $form = $("#{{ $form_id }}");
        var $submit = $("#{{ $form_id }}_submit");

        // кнопка активна, только когда есть заголовок
        function check() {
            var ok = $.trim($form.find("[name='caption']").val()) !== "";
            $submit.prop("disabled", !ok);

            return ok;
        }

        $form.find("[name='caption']").on("input change", check);
        check();

        // режим события: показываем только его поля
        $form.find("[name='mode']").on("change", function () {
            $form.find(".date-select-inputs").addClass("d-none");
            $form.find(".date-select [mode='" + this.value + "']").removeClass("d-none");
        });

        // время округляем до 10 минут
        $form.find("input[type='time']").on("blur", function () {
            if (!this.value) return;
            var parts = this.value.split(":").map(Number);
            var minutes = Math.round(parts[1] / 10) * 10;
            var hours = (parts[0] + Math.floor(minutes / 60)) % 24;

            this.value = String(hours).padStart(2, "0") + ":" + String(minutes % 60).padStart(2, "0");
        });

        $form.find(".drp").daterangepicker({
            "autoApply": true,
            "locale": {
                "format": "DD.MM.YYYY",
                "separator": " - ",
                "applyLabel": "Применить",
                "cancelLabel": "Отменить",
                "fromLabel": "От",
                "toLabel": "До",
                "weekLabel": "Н",
                "daysOfWeek": ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],
                "monthNames": ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"],
                "firstDay": 1,
            },
            "minDate": moment("2022-01-01"),
            "startDate": moment("{{ $range_from->format('Y-m-d') }}"),
            "endDate": moment("{{ $range_to->format('Y-m-d') }}"),
        });

        $form.on("submit", function (event) {
            event.preventDefault();
            if (!check()) return;

            body_block();
            $.ajax({
                url: "{{ $action }}",
                type: "POST",
                dataType: "json",
                data: $form.serialize(),
                success: function () {
                    body_unblock();
                    // рабочий стол: перерисовать виджет «Календарь» вместо перезагрузки страницы
                    if (window.Desk && window.Desk.grid) {
                        $(document).trigger("desk:refresh", ["calendar"]);
                        sidebar_close();
                        return;
                    }
                    location.reload();
                },
                error: function () {
                    body_unblock();
                    toastr.error("{{ $error }}", "Это провал!", {progressBar: true, timeOut: 3000});
                }
            });
        });
    })();
</script>
