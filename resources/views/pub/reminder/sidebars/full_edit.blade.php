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
                        <input type="hidden" name="module[name]" value="{{$module['module']}}">
                        <input type="hidden" name="module[id]" value="{{$module['id']}}">
                        <h4>Объект напоминания</h4>
                        <div class="mb-4">
                            <div class="card-table">
                                <x-ui.card.card_table_tr field="Название">{{ $module['name'] }}</x-ui.card.card_table_tr>
                                <x-ui.card.card_table_tr field="ID">{{ $module['id'] }}</x-ui.card.card_table_tr>
                            </div>
                        </div>
                    @endif

                    @if($subUsers->count() > 1)
                            <div>
                                <label>Для кого <span class="text-danger">*</span></label>
                                <select class="form-control select2" multiple name="user[]">
                                    @foreach($subUsers as $user)
                                        <option value="{{ $user->id }}" @if(in_array($user->id, $usersSelected)) selected @endif>{{ $user->fullName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-check mt-1 mb-3 fs-3">
                                <input name="hide" class="form-check-input" type="checkbox" value="1" id="cb_hide" @if($reminder->hide) checked @endif>
                                <label class="form-check-label" for="cb_hide">
                                    Не показывать людям других получателей
                                </label>
                            </div>
                    @endif
                    <div class="mb-3">
                        <label>Заголовок <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" required value="{{ $reminder->title }}">
                    </div>
                    <div class="mb-3">
                        <label>Описание</label>
                        <textarea class="form-control" rows="5" name="message">{!! ($reminder->message) !!} </textarea>
                    </div>
                </div>

                <h4 class="mt-4 mb-3">Время и способы</h4>
                <div class="mb-1" id="times">
                    @foreach($reminder->reminder_times as $time)
                        <x-reminder.edit_time :notificators="$notificators" :time="$time"></x-reminder.edit_time>
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
                url: "{{ route('api.reminder.full_edit', [$reminder, '_token' => _token()]) }}",
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
            $("#remind [required]").each(function() {
               if(!$(this).val()) err = true
            });
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
