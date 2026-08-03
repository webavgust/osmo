
@section('site_title')
    Напоминание о выезде
@endsection

@section('site_message')
    @php
        $color = match($day) {
            10 => 'info',
            5 => 'warning',
            1 => 'danger',
            default => 'dark'
        };
    @endphp
    <div>
        Через
        <x-ui.badge.default :type="$color">{{ tools()->num_rus($day, ['дня', 'день', 'дней'], true) }}</x-ui.badge.default>
        у вас должен состоятся выезд.
    </div>

    <table class="mt-2">
        <tr>
            <td class="text-end pe-2 fw-bold">Номер акта:</td>
            <td>{!! _docnumber($visit->number->number) !!} </td>
        </tr>
        <tr>
            <td class="text-end pe-2 fw-bold">Дата:</td>
            <td>{{ _date($visit->fact_visit_at) }}</td>
        </tr>
        <tr>
            <td class="text-end pe-2 fw-bold">Объект:</td>
            <td><a href="{{ route('order_task_object.detail', $visit->getObject()) }}">{{ $visit->getObject()->name }}</a></td>
        </tr>
        <tr>
            <td class="text-end pe-2 fw-bold">Адрес:</td>
            <td>{{ $visit->order_task_address->address }}</td>
        </tr>
    </table>
@endsection
