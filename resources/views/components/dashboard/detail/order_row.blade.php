<tr>
    <td>
        @can('order_view_detail')<a href="{{ route('order.detail', $task->order) }}" target="_blank">@endcan
            {{ $task->order->id }}
            @can('order_view_detail')</a>@endcan
        <a href="javascript:sidebar({href:'{{ route('order_task.sidebar_view', $task) }}'});">
            <i class="fa-regular fa-clipboard-check ms-2 text-danger cursor-help" title="Есть ТЗ"></i>
        </a>
    </td>
    <td>
        {{ $task->order->curator->full_name }}
    </td>
    <td class="text-center">
        {{ _date($task->order->periods()->first()->created_at) }}
    </td>
    <td>
        <button type="button" class="btn waves-effect waves-light btn-light-danger text-danger" btn_type="danger" href="" data-bs-toggle="modal" data-bs-target="#order-task-submit-modal">
            Забрать
        </button>
    </td>
</tr>
