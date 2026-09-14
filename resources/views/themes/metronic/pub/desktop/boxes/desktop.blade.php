{{--
    Попап стола (patch v30, этап A4c1): новый стол, переименование, копия,
    сохранение как системного пресета. Режим — DesktopBoxController::desktop.

    Отправка — Desk.desks.submit() из public/metronic/js/osmo-desktop-desks.js:
    берёт data-mode формы, поле name и переключатель copy_from.
--}}
@extends('components.box.box-static-large')

@section('body')
    <form id="desk_box_form" data-mode="{{ $mode }}" data-desktop="{{ $desktop?->id }}" autocomplete="off">
        @if($mode === 'system')
            <div class="alert alert-primary d-flex align-items-center p-4 mb-5 fs-7">
                <i class="fa-light fa-shield-halved fs-3 text-primary me-3"></i>
                @if($desktop)
                    Пресет станет копией стола «{{ $desktop->name }}» в сохранённом виде. Его увидят все пользователи и смогут сделать своим.
                @else
                    Будет создан пустой пресет. Его увидят все пользователи и смогут сделать своим.
                @endif
            </div>
        @elseif($mode === 'copy')
            <div class="alert alert-light d-flex align-items-center p-4 mb-5 fs-7 border border-dashed">
                <i class="fa-light fa-clone fs-3 text-gray-600 me-3"></i>
                В личные столы попадёт копия «{{ $desktop->name }}» в сохранённом виде.
            </div>
        @endif

        <div class="mb-4">
            <label class="form-label fw-semibold" for="desk_box_name">Название <span class="text-danger">*</span></label>
            <input type="text" id="desk_box_name" name="name" class="form-control" maxlength="100" required
                   value="{{ $name }}" placeholder="Например, «Финансы»"/>
            <div class="form-text">До 100 символов</div>
        </div>

        @if($can_copy)
            <div class="form-check form-switch form-check-custom form-check-solid">
                <input class="form-check-input" type="checkbox" id="desk_box_copy" name="copy_from" value="{{ $desktop->id }}"/>
                <label class="form-check-label text-gray-800" for="desk_box_copy">
                    Скопировать текущий стол «{{ $desktop->name }}»
                </label>
            </div>
        @endif
    </form>

    <script>
        $(function () {
            // фокус в поле, курсор — в конец названия
            setTimeout(function () {
                var input = document.getElementById('desk_box_name');
                if (!input) return;
                input.focus();
                input.setSelectionRange(input.value.length, input.value.length);
            }, 300);
        });
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="light" onclick="javascript:box_close();">
            <span>Закрыть</span>
        </x-ui.button.default>

        <x-ui.button.default id="desk_box_submit" btn_type="primary" onclick="javascript:Desk.desks.submit();">
            <i class="fa-light {{ $mode === 'rename' ? 'fa-floppy-disk' : 'fa-plus' }} me-2"></i>
            <span>{{ $button }}</span>
        </x-ui.button.default>
    </div>
@endsection
