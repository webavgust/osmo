<div class="card card-body p-0" id="variant_task">
    <div class="
                                                  invoice-header
                                                  d-flex
                                                  align-items-center
                                                  border-bottom
                                                  px-4 py-3
                                                " style="padding-bottom: 12px!important">
        <h3 class="font-weight-medium text-uppercase mb-0">
            Задачи
        </h3>

        <x-ui.a.box href="{{ route('proposal-variant.box_edit', $variant) }}">
            <i class="fas fa-edit text-warning"></i>
        </x-ui.a.box>


    </div>

    @if(!empty($variant->task))
        <x-ui.notification.light type="light-secondary" class="m-3">
            <span class="text-secondary fw-normal">{!! $variant->task !!}</span>
        </x-ui.notification.light>
    @else
        <div class="px-4 py-3">Нет записи</div>
    @endif
</div>
