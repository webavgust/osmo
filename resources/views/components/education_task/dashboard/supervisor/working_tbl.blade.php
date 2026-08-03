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
                    class="border">ID</th>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                    class="border">Статус</th>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    class="border">Менеджер портал</th>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    class="border">Создан, кем</th>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    class="border">Создан, когда</th>
                <th
                    scope="col"
                    class="border"></th>
            </tr>
            </thead>
            <tbody>
            @forelse($data as $task)
                <x-education_task.dashboard.supervisor.working_row
                    :task="$task"></x-education_task.dashboard.supervisor.working_row>
            @empty
                <td colspan="6">
                    Таких заявок пока нет
                </td>
            @endforelse
            </tbody>
        </table>
</div>
