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
                >Номер акта</th>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                >ТЗ</th>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                >Объект</th>
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
                    scope="col"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                >Статус</th>
                <th
                    width="1"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                ></th>
            </tr>
            </thead>
            <tbody>
            @forelse($data as $visit)
                <x-dashboard.sampler.visit.visit_process_row
                    :users="$users" :visit="$visit"></x-dashboard.sampler.visit.visit_process_row>
            @empty
                <td colspan="6">
                    Таких Актов пока нет
                </td>
            @endforelse
            </tbody>
        </table>
</div>
