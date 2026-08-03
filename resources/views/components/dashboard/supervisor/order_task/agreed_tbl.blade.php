<div class="agree-table px-2">
    <table
        class="border table tablesaw table-bordered table-hover table no-wrap"
        data-tablesaw-sortable
    >
            <thead>
            <tr>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                    data-tablesaw-sortable-numeric
                >ID</th>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                    width="1"
                >Тип</th>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                >Статус</th>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                >Создан, когда</th>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                >Создан, кем</th>
                <th
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                ></th>
                <th
                    width="1"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                ></th>
            </tr>
            </thead>
            <tbody>
            @forelse($data as $task)
                <x-dashboard.supervisor.order_task.agreed_row
                    :users="$users" :task="$task"></x-dashboard.supervisor.order_task.agreed_row>
            @empty
                <td colspan="6">
                    Таких ТЗ пока нет
                </td>
            @endforelse
            </tbody>
        </table>
</div>
