
<tr plan_id="{{ $view->id }}">
    <td>{{ _date($view->date) }}</td>
    <td>TODO</td>
    <td>
        <a href="{{ route("order_task.detail", $view->order['id']) }}">{{ $view->order['name'] }}</a>
    </td>
    <td>
        @forelse($view->samplers as $sampler)
            <div>{{ $sampler->full_name }}</div>
        @empty
            <x-ui.icon.light icon="fa-dash"></x-ui.icon.light>
        @endforelse
    </td>
    <td>
        @forelse($view->visits as $visit)
            <a
                href='{{ route('order_task_object.detail', $visit->object_id) }}?visit={{ $visit->id }}'>
                <div style="width: 300px">
                    <div class="badge font-14 {{ $visit->status['color']['badge'] }}">
                        <span>{{ $visit->date }} | </span>
                        <span>{{ $visit->status['name'] }}</span>
                    </div>
                </div>
            </a>
        @empty
            <x-ui.icon.light icon="fa-dash"></x-ui.icon.light>
        @endforelse
    </td>
    <td>
        <x-ui.a.box :href="route('plan-visits.box_edit', $view->id)" class="btn btn-outline-warning p-0 px-1 me-1" style="width: 30px"  >
            <i class="fa-solid fa-edit"></i>
        </x-ui.a.box>


        <button class="btn btn-outline-danger p-0 px-1" style="width: 30px" onclick="javascript:delete_plan({{ $view->id }})">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </td>
</tr>
