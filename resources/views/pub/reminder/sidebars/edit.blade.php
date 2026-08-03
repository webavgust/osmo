@extends('components.sidebar.offcanvas-right')

@section('body')
    <style>
        .time_delete {
            display: none;
            top: -10px;
            right: -10px;
            cursor: pointer;
        }

        [mode='time']:hover .time_delete {
            display: inline;
        }
    </style>
    <form method="post" id="remind" class="needs-validation" novalidate>
        <div class="card">
            <div class="card-body p-0">
                <div class="mt-2">
                    @if(!empty($module))
                        <h4>Объект напоминания</h4>
                        <div class="mb-4">
                            <div class="card-table">
                                <x-ui.card.card_table_tr field="Название">{{ $module['name'] }}</x-ui.card.card_table_tr>
                                <x-ui.card.card_table_tr field="ID">{{ $module['id'] }}</x-ui.card.card_table_tr>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label>Для кого <span class="text-danger">*</span></label>
                        <div>
                            @foreach($subUsers as $user)
                                <x-ui.badge.default type="primary">{{ $user->fullName }}</x-ui.badge.default>
                            @endforeach
                        </div>
                    </div>
                    @if($reminder->hide)
                        <div class="mt-1">
                            <x-ui.icon.regular icon="fa-square-check"></x-ui.icon.regular> Не показывать людям других получателей
                        </div>
                    @endif
                    <div class="mb-3 mt-3">
                        <label>Заголовок</label>
                        <h4 class="ms-1 border-start ps-2 border-4 border-primary">{{ $reminder->title }}</h4>
                    </div>
                    <div class="mb-3">
                        <label>Описание</label>
                        <div class="ms-1 border-start ps-2 border-4 border-primary">
                            {!! nl2br($reminder->message) !!}
                        </div>
                    </div>
                </div>

                <h4 class="mt-4 mb-3">Время и способы</h4>
                <div class="mb-1" id="times">
                    @foreach($reminder->reminder_times as $time)
                        @if($time->notified)
                            <x-reminder.show_time :notificators="$notificators" :time="$time"></x-reminder.show_time>
                        @else
                            <x-reminder.edit_time :notificators="$notificators" :time="$time"></x-reminder.edit_time>
                        @endif
                    @endforeach
                </div>

                <div class="d-flex justify-content-center m-2">
                    <x-ui.icon.light icon="fa-circle-plus" class="font-24 text-secondary cursor-pointer" id="add_time"></x-ui.icon.light>
                </div>

                <x-ui.button.outline type="button" btn_type="primary" class="mt-3 w-100 " id="remind_submit">
                    Сохранить напоминание
                </x-ui.button.outline>
            </div>
        </div>
    </form>

    <script>
        $(document).ready(function() {
            $(".select2").select2({
                dropdownParent: $("form#remind")
            });
            rebind();
            $("#remind_submit").on("click", function() {
                remind_submit();
            });
            $("#add_time").on("click", function() {
                add_time();
            });
        });

        function remind_submit(event) {
            var block_elem = $("body");
            $(block_elem).block({
                message: '<i class="fas fa-spin fa-sync text-white"></i>',
                baseZ: 100000,
                overlayCSS: {
                    backgroundColor: "#000",
                    opacity: 0.5,
                    cursor: "wait",
                },
                css: {
                    border: 0,
                    padding: 0,
                    backgroundColor: "transparent",
                },
            });


            $.ajax({
                url: "{{ route('api.reminder.edit', [$reminder, '_token' => _token()]) }}",
                type: "POST",
                data: $("form#remind").serialize(),
                dataType: "json",
                success: function (html) {
                    $(block_elem).unblock();
                    location.reload();
                },
                error: function () {
                    toastr.error("Не получилось добавить напоминание", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });
        }


        function form_check() {
            var err = false;

            $("#remind [mode='time']").each(function() {
               if($(this).find("input:checked").length == 0) err = true;
            });


            if(err) {
                $("#remind_submit").addClass('disabled');
            } else {
                $("#remind_submit").removeClass('disabled');
            }
            return err;
        }
        function rebind()
        {
            $("#remind input").on("change keyup", function() {
               form_check();
            });
        }
        function add_time() {
            var block_elem = $("body");
            $(block_elem).block({
                message: '<i class="fas fa-spin fa-sync text-white"></i>',
                baseZ: 100000,
                overlayCSS: {
                    backgroundColor: "#000",
                    opacity: 0.5,
                    cursor: "wait",
                },
                css: {
                    border: 0,
                    padding: 0,
                    backgroundColor: "transparent",
                },
            });


            $.ajax({
                url: "{{ route('reminder.component_time') }}",
                type: "GET",
                dataType: "html",
                success: function (html) {
                    $("#times").after(html);
                    rebind();
                    form_check();
                    $(block_elem).unblock();
                },
                error: function () {
                    toastr.error("Не получилось добавить блок", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });
        }
    </script>
@endsection
