<div class="d-flex flex-column flex-grow-1 flex-lg-row flex-lg-grow-1 justify-content-end">
    @can('users_have_sub')
        <x-ui.button.sidebar_light btn_type="secondary" href="{{ route('dashboard.sidebar_user_select') }}"  class="mt-2 mt-md-0 me-lg-3 me-0 mb-1 mb-lg-0"><strong>{{ $user_mode['data']['button'] }} @if($user_mode['mode'] !== 'self') <sup>{{ $user_mode['users']->count() }}</sup> @endif</strong></x-ui.button.sidebar_light>
    @endcan
    <x-ui.button.light btn_type="info"  id="dashboard_dates" class="justify-content-center">{{ $dates['start']->format('d.m.Y') }} &ndash; {{ $dates['end']->format('d.m.Y') }}</x-ui.button.light>
</div>

@section('js')
    @parent
    <script>
        $(document).ready(function() {
            $("#dashboard_dates").daterangepicker({
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
                    "firstDay": 1
                },
                "alwaysShowCalendars": true,
                "startDate": "{{ $dates['start']->format('d/m/Y') }}",
                "endDate": "{{ $dates['end']->format('d/m/Y') }}",
                "maxDate": "{{ $dates['end']->now()->format('d/m/Y') }}",
                "minDate": "01/01/2022"
            }).on('apply.daterangepicker', function(obj, instance) {
                $.ajax({
                    url: '{{ route('dashboard.set_dates') }}',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        start: instance.startDate.format('DD.MM.YYYY'),
                        end: instance.endDate.format('DD.MM.YYYY'),
                    },
                    method: 'POST',
                    dataType: 'json',
                    success: function (response) {
                        location.reload();
                    }
                });
            });
        });
    </script>
@endsection
