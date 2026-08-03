<div class="agree-table">
    <div class="table-responsive">
        <table class="table stylish-table v-middle mb-0 no-wrap">
            <thead>
            <tr>
                <th class="border-0 text-muted fw-normal" width="1">ID</th>
                <th class="border-0 text-muted fw-normal" width="1">Итер.</th>
                <th class="border-0 text-muted fw-normal" width="1">Статус</th>
                <th class="border-0 text-muted fw-normal">Договор</th>
                <th class="border-0 text-muted fw-normal">Версия</th>
                <th class="border-0 text-muted fw-normal">Приложение</th>
                <th class="border-0 text-muted fw-normal">Ваше решение</th>
                <th class="border-0 text-muted fw-normal">Создан</th>
            </tr>
            </thead>
            <tbody>
            @forelse($data as $task)
                <x-dashboard.order_task_agree.tr_row_agreementer
                    :task="$task"></x-dashboard.order_task_agree.tr_row_agreementer>
            @empty
                <td colspan="4">
                    Таких заявок пока нет
                </td>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
