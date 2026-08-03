@extends('components.sidebar.offcanvas-right')


@section('body')
    <style>
        .select2-results__message {
            display: none;
        }
    </style>
    <div class="card">
        <div class="card-body p-0">
            <h4>Общие данные</h4>
            <div class="card-table">
                <div class="tr">
                    <span class="th">Объектов</span>
                    <span class="td">
                        {{ $order_task->objects_all->count() }}
                     </span>
                </div>
                <div class="tr">
                    <span class="th">Адресов</span>
                    <span class="td">
                      {{ $order_task->addresses_all->count() }}
                     </span>
                </div>
                <div class="tr">
                    <span class="th">Точек</span>
                    <span class="td">
                          {{ $order_task->points_all->count() }}
                     </span>
                </div>
                <div class="tr">
                    <span class="th">Измерений</span>
                    <span class="td">
                          {{ $order_task->measures_all->count() }}
                     </span>
                </div>
            </div>

            @if(!empty($order_task->contacts))
                <h4 class="mt-4">Контактные данные</h4>
                <div>{!! nl2br($order_task->contacts) !!}</div>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-body p-0">
            <h4>Структура ТЗ</h4>
            <div id="tree" ></div>
        </div>
    </div>

    <script>
        var defaultData = @json($task_tree);
        $(document).ready(function() {
            $("#tree").treeview({
                onhoverColor: "rgba(0, 0, 0, 0.05)",
                expandIcon: "fa-light fa-square-plus me-2 text-secondary",
                collapseIcon: "fa-light fa-square-minus me-2 text-secondary",
                nodeIcon: "fa fa-bookmark",
                data: defaultData,
            });
        });
    </script>


@endsection
