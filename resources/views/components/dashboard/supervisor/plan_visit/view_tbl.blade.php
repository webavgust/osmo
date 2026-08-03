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
                    width="1"
                >Дата</th>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                >Заказчик</th>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                >ТЗ</th>
                <th
                    scope="col"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                >Пробоотборщик</th>
                <th
                    width="1"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                >Выезды</th>
                <th
                    width="1"
                    data-tablesaw-sortable-col
                    data-tablesaw-priority="persist"
                >

                </th>
            </tr>
            </thead>
            <tbody>
            @forelse($data as $view)
                <x-dashboard.supervisor.plan_visit.view_row
                    :users="$users" :view="$view"></x-dashboard.supervisor.plan_visit.view_row>
            @empty
                <td colspan="6">
                    Таких записей пока нет
                </td>
            @endforelse
            </tbody>
        </table>
</div>

<script>
    function delete_plan(id) {
        if(confirm('Вы действительно хотите удалить этот выезд?')) {
            $.ajax({
                method: 'DELETE',
                url: '{{ route('api.plan-visits.delete') }}/' + id + '?_token={{ _token() }}',
                dataType: 'json',
                success: function(answer) {
                    $("tr[plan_id='" + id + "']").css("background", "#fff1f1").html('<td colspan=6>Удалено!</td>')
                }
            })
        }
    }
</script>
