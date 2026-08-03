@section('telegram_title')
    {{ "\u{274C}" }} ХАЛ: <a href="{{ route('order_task.detail', $order_task) }}">ТЗ №{{ $order_task->id }}</a> передано в работу
@endsection

@section('telegram_message')

@endsection



@section('site_title')
    ТЗ №{{ $order_task->id }} передано в работу
@endsection

@section('site_message')
    <div>ID: {{ $order_task->id }}
         <x-order_task.badge-direction :task="$order_task"></x-order_task.badge-direction>
    </div>
@endsection

@section('email_title')
    ТЗ №{{ $order_task->id }} передано в работу
@endsection

@section('email_message')
    <table>
        <tr>
            <td class="text-right">ID:</td>
            <td class="ps-1">{{ $order_task->id }}</td>
        </tr>
        <tr>
            <td class="text-right">
                Направление:
            </td>
            <td class="ps-1">
                <x-order_task.badge-direction :task="$order_task"></x-order_task.badge-direction>
            </td>
        </tr>
    </table>
@endsection
