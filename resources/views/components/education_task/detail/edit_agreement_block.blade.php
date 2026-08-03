<div class="card agreement_block mb-1 mt-2">
    <div class="card-body d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Заявки на редактирование</h4>

        @if($task->canEditAgree())
            <x-ui.a.default href="javascript:edit_agree_modal()" btn_type="warning" class="py-0">
                <x-ui.icon.regular icon="fa-plus"></x-ui.icon.regular>
            </x-ui.a.default>
        @endif
    </div>
    <div class="card-body py-2">
        <div class="card-table">
            @foreach($task->education_task_edit_agreements as $agreement)
                <x-education_task.detail.edit_agreement_row :agreement="$agreement" :loop="$loop"></x-education_task.detail.edit_agreement_row>
            @endforeach
        </div>
    </div>
</div>
