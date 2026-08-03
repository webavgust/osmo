<div id="{{ $agreement->id }}" num="{{$loop->iteration}}" class="mt-2">

    <x-ui.a.box href="{{ route('education_task_edit_agree.box_show', $agreement) }}" class="ps-0 w-100 justify-content-start">
        <div class="tr">
            <div class="th flex-column align-items-start">
                {{ $loop->iteration }}) {{ $agreement->creator->full_name }}
            </div>
            <div class="td">
                 @switch($agreement->status)
                    @case(\App\Modules\Pub\EducationTaskEditAgreement\Models\EducationTaskEditAgreement::STATUS_CREATED)
                     <i class="fa-regular fa-clock text-warning"></i>
                    @break
                    @case(\App\Modules\Pub\EducationTaskEditAgreement\Models\EducationTaskEditAgreement::STATUS_ACCEPTED)
                    <i class="fa-duotone fa-check text-success cursor-help"
                       title="{{ $agreement->decisioned_at->format('d.m.Y H:i:s') }}"></i>
                    @break
                    @case(\App\Modules\Pub\EducationTaskEditAgreement\Models\EducationTaskEditAgreement::STATUS_DECLINED)
                    <i class="fa-solid fa-xmark text-danger cursor-help"
                       title="{{ $agreement->decisioned_at->format('d.m.Y H:i:s') }}"></i>
                    @break
                @endswitch
            </div>
        </div>
        <div class="d-flex justify-content-start">
            <span class="fs-1 text-info mt-1 ms-3">Нажмите, чтобы помотреть комментарий</span>
        </div>
    </x-ui.a.box>

    @if($agreement->education_task->canEditMakeDecision($agreement) && $agreement->canMakeDecision())
        <div class="mt-3 mb-3 d-flex">
            <x-ui.button.default btn_type="danger flex-grow-1 me-1" onclick="javascript:edit_agreement_decision({{ $agreement->id }}, -1)">
                <x-ui.icon.regular icon="fa-xmark" class="me-1"></x-ui.icon.regular>
                Отказать
            </x-ui.button.default>

            <x-ui.a.default btn_type="success flex-grow-1 ms-1" onclick="javascript:edit_agreement_decision({{ $agreement->id }}, 1)">
                <x-ui.icon.regular icon="fa-check" class="me-1"></x-ui.icon.regular>
                Согласовать
            </x-ui.a.default>
        </div>
    @endif
</div>
