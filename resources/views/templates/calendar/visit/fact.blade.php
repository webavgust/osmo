<div class="table-responsive mt-2">
    <table class="table table-bordered border-light-secondary">
        <tbody>
        <tr>
            <td class="p-1">ТЗ</td>
            <td class="p-1">
                <a href="{{ route('order_task.detail', $visit->getOrderTask()->id) }}">
                    №{{ $visit->getOrderTask()->id }}
                </a>
            </td>
        </tr>
        <tr>
            <td class="p-1">Объект</td>
            <td class="p-1">
                <a href="{{ route('order_task_object.detail', $visit->order_task_address->object) }}">
                    {{ $visit->order_task_address->object->name }}
                </a>
            </td>
        </tr>
        <tr>
            <td class="p-1">Адрес</td>
            <td class="p-1">
                {{ $visit->order_task_address->address }}
            </td>
        </tr>
        <tr>
            <td class="p-1">Измерений</td>
            <td class="p-1">
                {{ $visit->visit_order_task_measures->count() }}
            </td>
        </tr>
        <tr>
            <td class="p-1">Проб</td>
            <td class="p-1">
                {{ $visit->visit_order_task_measures->sum('count') }}
            </td>
        </tr>
        </tbody>
    </table>

    <h4>Пробоотборщики</h4>
    <ol>
        @foreach($visit->users as $user)
            <li>
                @if($user->id == auth()->id())
                    <x-ui.icon.solid class="text-warning me-1" icon="fa-star"></x-ui.icon.solid>
                @endif
                {{ $user->full_name }}
            </li>
        @endforeach
    </ol>

</div>
