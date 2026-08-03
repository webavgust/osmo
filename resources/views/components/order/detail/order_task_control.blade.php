@php
    use App\Modules\Pub\OrderTask\Models\OrderTask;
    if(empty($order->order_task)) {
        $task_level = 0;
    } else {
        switch($order->order_task->status) {
            case OrderTask::STATUS_CREATED:
                $task_level = 1;
                break;
            case OrderTask::STATUS_CREATED:
                $task_level = 2;
                break;
            default:
                $task_level = 3;
                break;
        }
    }
@endphp
@if($task_level > 2)
    <div class="card-body d-flex justify-content-between p-0" id="order_task_body">
        <div class="col-12 text-center">
            <div class="alert alert-secondary m-0" role="alert">
                <strong>Техническое задание </strong> было передано в работу!
            </div>
        </div>
    </div>
@else
    <div class="card-body d-flex justify-content-between pt-4 pb-4 flex-column flex-sm-row " id="order_task_body">
        <div class="col-12 col-sm-4 text-center">
            <div class="mb-2 font-12">
                <i class="fa-thin fa-circle-1"></i>

                <span class="d-md-none d-lg-inline">Объекты, адреса, точки</span>
                <span class="d-none d-md-inline d-lg-none">Шаг 1</span>
            </div>
            @can('order_task_edit')
                @if($task_level == 0)
                    @can('order_task_attach')
                        <x-ui.button.sidebar href="{{route('order.attach_task.form', $order)}}" class="ms-1" btn_type="primary">
                            <span class="d-md-none d-lg-inline">Прикрепить ТЗ</span>
                            <span class="d-none d-md-inline d-lg-none">+ ТЗ</span>
                        </x-ui.button.sidebar>
                    @else
                        <x-ui.button.outline btn_type="secondary"  class="disabled">
                            <span class="d-md-none d-lg-inline">Прикрепить ТЗ</span>
                            <span class="d-none d-md-inline d-lg-none">+ ТЗ</span>
                        </x-ui.button.outline>
                    @endcan
                @elseif($task_level > 2)
                    <x-ui.button.outline btn_type="secondary"  class="disabled">
                        <span class="d-md-none d-lg-inline">Недоступно</span>
                        <span class="d-none d-md-inline d-lg-none">X</span>
                    </x-ui.button.outline>
                @else
                    @php $link = route("order_task.edit_step1", $order->order_task->id); @endphp
                    <x-ui.a.light btn_type="primary"  class="text-primary" href="{{$link}}"><i class="fa-regular fa-pen me-1"></i>
                        <span class="d-md-none d-lg-inline">Редактировать</span>
                        <span class="d-none d-md-inline d-lg-none">edit</span>
                    </x-ui.a.light>
                @endif
            @else
                <x-ui.button.outline btn_type="secondary"  class="disabled">
                    <span class="d-md-none d-lg-inline">Недоступно</span>
                    <span class="d-none d-md-inline d-lg-none">X</span>
                </x-ui.button.outline>
            @endcan
        </div>
        <div class="col-12 col-sm-4 mt-4 mt-sm-0 text-center">
            <div class="mb-2 font-12">
                <i class="fa-thin fa-circle-2"></i>

                <span class="d-md-none d-lg-inline">Измерения и услуги</span>
                <span class="d-none d-md-inline d-lg-none">Шаг 2</span>
            </div>

            @can('order_task_edit')
                @if($task_level == 0)
                    <x-ui.button.outline btn_type="secondary"  class="disabled">Перейти</x-ui.button.outline>
                @elseif($task_level > 2)
                    <x-ui.button.outline btn_type="secondary"  class="disabled">Недоступно</x-ui.button.outline>
                @else
                    @if($task_level == 1)
                        @php $link = route("order_task.create_step2", $order->order_task->id); @endphp
                        <x-ui.a.default btn_type="primary"   href="{{$link}}">Перейти</x-ui.a.default>
                    @else
                        @php $link = route("order_task.edit_step2", $order->order_task->id); @endphp
                        <x-ui.a.light btn_type="primary"  class="text-primary" href="{{$link}}"><i class="fa-regular fa-pen me-1"></i> Редактировать</x-ui.a.light>
                    @endif
                @endif
            @else
                <x-ui.button.outline btn_type="secondary"  class="disabled">Недоступно</x-ui.button.outline>
            @endcan
        </div>
        <div class="col-12 col-sm-4 mt-4 mt-sm-0 text-center">
            <div class="mb-2 font-12">
                <i class="fa-thin fa-circle-3"></i>

                <span class="d-md-none d-lg-inline">Завершение</span>
                <span class="d-none d-md-inline d-lg-none">Шаг 3</span>
            </div>

            @if(!_can('order_task_submit') || $task_level < 2)
                <x-ui.button.outline btn_type="secondary" class="disabled">
                    <span class="d-md-none d-lg-inline">Передать в работу</span>
                    <span class="d-none d-md-inline d-lg-none">Передать</span>
                </x-ui.button.outline>
            @elseif($task_level == 2)
{{--                @php $link = route("order_task.create_step1", $order); @endphp--}}
                <x-ui.button.light btn_type="danger" class="text-danger" href="{{$link}}"  data-bs-toggle="modal"
                                   data-bs-target="#order-task-submit-modal">
                    <span class="d-md-none d-lg-inline">Передать в работу</span>
                    <span class="d-none d-md-inline d-lg-none">Передать</span></x-ui.button.light>
            @else
                <x-ui.button.outline btn_type="danger" class="disabled">
                    <span class="d-md-none d-lg-inline">Передать в работу</span>
                    <span class="d-none d-md-inline d-lg-none">Передать</span></x-ui.button.outline>
            @endif
        </div>
    </div>
@endif
