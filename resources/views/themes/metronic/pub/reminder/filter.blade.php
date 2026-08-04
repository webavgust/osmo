@extends('layouts.layout')

@section('styles')
    <style>
        #remind_add {
            transition: all .5s;
            cursor: pointer;
        }
        #remind_add:hover {
            box-shadow: 0px 10px 6px 0px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }
    </style>
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <ul class="timeline timeline-left">
                            <li class="timeline-inverted timeline-item">
                                <div class="timeline-badge warning" id="remind_add" onclick="javascript:sidebar({href: '{{ route('reminder.sidebar_add') }}', data: {module:'{{$object->getModuleSlug()}}', id:{{$object->id}}},  method: 'POST'})">
                                    <x-ui.icon.light icon="fa-plus" class="text-white"></x-ui.icon.light>
                                </div>
                                <div class="timeline-panel">
                                    Фильтр: <x-ui.notification.light type="warning" class="p-1 ps-2 pe-2 m-0 d-inline">{{ $object::getModuleName() }} #{{$object->id}}</x-ui.notification.light>

                                    <x-ui.a.outline href="{{ route('reminder.index') }}" class="text-danger">
                                        <x-ui.icon.regular icon="fa-ban"></x-ui.icon.regular>
                                    </x-ui.a.outline>
                                </div>

                            </li>
                            @foreach($reminders as $remind)
                                <x-reminder.row :remind="$remind" :filtered="true"></x-reminder.row>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        id="delete_modal"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="danger-header-modalLab el"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class=" modal-header modal-colored-header bg-danger text-white">
                    <h4 class="modal-title" id="danger-header-modalLabel">Удаление напоминания</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Не сохранять"></button>
                </div>
                <div class="modal-body">
                    <p>Вы действительно хотите удалить напоминание? Это действие необратимо</p>
                </div>


                <div class="modal-footer">
                    <button type="button" class="btn btn-light text-secondary" data-bs-dismiss="modal">
                        Не удалять
                    </button>
                    <button type="button" id="btn_status_confirm" data-bs-dismiss="modal" class="
                                btn btn-danger text-white
                          fw-semibold
                              " onclick="javascript:form_submit()">
                        УДАЛИТЬ
                    </button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
@endsection

@section('js')
    @parent
    <script>
        function remind_delete(id) {
            window.remind_id = id;
            $("#delete_modal").modal('show');
        }

        function form_submit() {
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
                url: "{{ route('api.reminder.delete') }}/" + window.remind_id + '?_token={{ _token() }}',
                type: "POST",
                dataType: "html",
                success: function (html) {
                    $(block_elem).unblock();
                    $("li[id='" + window.remind_id+ "']").remove();

                    toastr.success("Напоминание успешно удалено!", "Это успех!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                },
                error: function () {
                    toastr.error("Не получилось удалить напоминание", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });

        }
    </script>
@endsection
