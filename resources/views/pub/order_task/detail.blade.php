@extends('layouts.layout')

@section('styles')
    <style>
        .objects .address .title,
        .objects .services .title {
            background: #F7F7F7;
        }

        a .object {
            transition: all .5s;
        }

        a .object:hover {
            transform: translateY(-4px) scale(1.03);
            box-shadow: 0 14px 24px rgb(62 57 107 / 10%);
        }

    </style>
@endsection

@section('breadcrumb_right')
    @if($view_modes->count() > 1)
        <div class="btn-group" role="group" aria-label="Basic example">
            @foreach($view_modes as $mode => $ar)
                @if($mode == $view_mode)
                    <span class=" btn btn-light-info text-info font-weight-medium">
                        <x-ui.icon.solid :icon="$ar['icon']" class="me-1"></x-ui.icon.solid>
                        {{ $ar['name'] }}
                    </span>
                @else
                    <a type="button" class=" btn btn-light-secondary text-secondary font-weight-medium"
                       href="?mode={{ $mode }}">
                        <x-ui.icon.solid :icon="$ar['icon']" class="me-1"></x-ui.icon.solid>
                        {{ $ar['name'] }}
                    </a>
                @endif
            @endforeach
        </div>

    @endif
@endsection

@section('content')
    <div class="container-fluid" page="order_task.detail">
        <div class="row">
                    <div class="col-12 col-md-4 col-xl-4 flex-md-column">
                        <div class="row">
                            <div class="col-12">

                                <div class="card mb-3">
                                    <div class="card-body py-3">
                                        <h2 class="mb-1"><a href="{{ env('PORTAL_URL') }}/projects/clients/{{ $order_task->evaluation->portal->client_id }}/">{{ $order_task->evaluation->portal?->client_name }}</a></h2>
                                        <div class="m-0 d-flex align-items-center fs-3">
                                            <a href="{{ env('PORTAL_URL') }}/projects/contracts/{{ $order_task->evaluation->sub_contract->contract_id }}/">{{ $order_task->evaluation->portal?->contract_name }}</a>
                                            <x-ui.icon.solid icon="fa-slash-forward" class="mx-2 fs-2"/>
                                            <span>{{ $order_task->evaluation->portal?->annex_name ?? $order_task->evaluation->block_id }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="card m  b-0">
                                    <div class="card-body d-flex justify-content-between align-items-center flex-column flex-sm-row">
                                        <h4 class="card-title mb-0">Общая информация</h4>
                                        <x-order_task.status :order-task="$order_task" class="mt-2 mt-sm-0"></x-order_task.status>
                                    </div>
                                    <div class="card-body pb-3">
                                        <div class="card-table">
                                            <x-ui.card.card_table_tr field="Номер"
                                                                     value="{{ $order_task->id }}"></x-ui.card.card_table_tr>








                                            <x-ui.card.card_table_tr field="Дата создания"
                                                                     value="{{ _date($order_task->created_at) }}"></x-ui.card.card_table_tr>

                                            @can('users_view_profile')
                                                <x-ui.card.card_table_tr field="Автор Приложения"
                                                                         value="{{ $order_task->evaluation?->creator?->fullName }}"
                                                                         link="{{ route('users.view', $order_task->evaluation?->creator) }}"></x-ui.card.card_table_tr>
                                            @else
                                                <x-ui.card.card_table_tr field="Автор Приложения"
                                                                         value="{{ $order_task->evaluation->creator->fullName }}"></x-ui.card.card_table_tr>
                                            @endif

                                            @can('users_view_profile')
                                                <x-ui.card.card_table_tr field="Автор ТЗ"
                                                                         value="{{ $order_task->creator()->first()->fullName }}"
                                                                         link="{{ route('users.view', $order_task->creator) }}"></x-ui.card.card_table_tr>
                                            @else
                                                <x-ui.card.card_table_tr field="Автор ТЗ"
                                                                         value="{{ $order_task->creator()->first()->fullName }}"></x-ui.card.card_table_tr>
                                            @endif

                                            @if(!empty($order_task->evaluation))
                                                <x-ui.card.card_table_tr field="Создано на основании">
                                                    <a href="{{ route('evaluation.detail', $order_task->evaluation) }}">Приложение №{{ $order_task->evaluation->id }}</a>
                                                </x-ui.card.card_table_tr>
                                            @endif
                                        </div>

                                        <div class="mt-4 d-flex flex-column flex-grow-1">
                                            <x-ui.a.box btn_type="light-secondary" class="flex-grow-1 mb-1 text-secondary" :href="route('order_task.box_summary', $order_task)">
                                                <x-ui.icon.light icon="fa-table" class="me-1"></x-ui.icon.light>
                                                Сводная таблица
                                            </x-ui.a.box>


                                            <x-ui.a.box btn_type="light-info" class="flex-grow-1 mb-1 text-info" :href="route('order_task.box_visits', $order_task)">
                                                <x-ui.icon.light icon="fa-file" class="me-1"></x-ui.icon.light>
                                                Акты отбора
                                            </x-ui.a.box>

                                            @can('evaluation_view_finance')
                                                <x-ui.a.box href="{{ route('evaluation.box_finance', $order_task->evaluation) }}" btn_type="primary" class="p-2 py-1">
                                                    <x-ui.icon.regular icon="fa-table"></x-ui.icon.regular>
                                                    Финансовая таблица
                                                </x-ui.a.box>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                                @if(0)
                                    <div class="card my-0">
                                    <div
                                        class="card-body d-flex justify-content-between align-items-center flex-column flex-sm-row">
                                        <h4 class="card-title mb-0">Портал</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="card-table">
                                            <x-ui.card.card_table_tr field="Договор"
                                                                     link="{{ env('PORTAL_URL') }}/projects/contracts/{{ $order_task->evaluation?->sub_contract?->contract->id }}/">
                                                <b>{{ $order_task->portal_data['contract_namenumber'] ?? '?' }}</b>
                                                от
                                                <b>{{ !empty($order_task->portal_data['contract_date']) ?  \Carbon\Carbon::createFromTimestamp(strtotime($order_task->portal_data['contract_date']))->format('d.m.Y') : '?'  }}</b>
                                            </x-ui.card.card_table_tr>

                                            @if(!empty($order_task->portal_data['order_id']))
                                                <x-ui.card.card_table_tr field="Заказ"
                                                                         link="{{ env('PORTAL_URL') }}/projects/orders/{{ $order_task->portal_data['order_id'] }}/">
                                                    <b>Перейти</b>
                                                </x-ui.card.card_table_tr>
                                            @endif

{{--                                            <x-ui.card.card_table_tr field="Приложение">--}}
{{--                                                <b>{{ $order_task->portal_data['annex_fake_number'] ?? '?'}}</b>--}}
{{--                                                от <b>--}}
{{--                                                    @if(!empty($order_task->portal_data['annex_up_date']))--}}
{{--                                                        {{ \Carbon\Carbon::createFromTimestamp(strtotime($order_task->portal_data['annex_up_date']))->format('d.m.Y')  }}--}}
{{--                                                    @else--}}
{{--                                                        ?--}}
{{--                                                    @endif--}}
{{--                                                </b>--}}
{{--                                            </x-ui.card.card_table_tr>--}}

                                            @if(!empty($order_task->portal_data['manager_id']))
                                                @can('users_view_profile')
                                                    <x-ui.card.card_table_tr field="Менеджер"
                                                                             value="{{ \App\Modules\Pub\User\Models\User::find($order_task->portal_data['manager_id'])->fullName }}"
                                                                             link="{{ route('users.view', $order_task->portal_data['manager_id']) }}"></x-ui.card.card_table_tr>
                                                @else
                                                    <x-ui.card.card_table_tr field="Менеджер"
                                                                             value="{{ \App\Modules\Pub\User\Models\User::find($order_task->portal_data['manager_id'])->fullName }}"></x-ui.card.card_table_tr>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif
                                <x-order_task.detail.status_block :task="$order_task"></x-order_task.detail.status_block>
                            </div>
                            <div class="col-12">
                                @if(!empty($order_task->agreement) && !$order_task->isWorking() && !$order_task->isFinished())
                                    <x-order_task.detail.agreement_block :task="$order_task"></x-order_task.detail.agreement_block>

                                    @if(!empty($order_task->agreement->documents))
                                        <div class="card">
                                            <div class="card-body d-flex justify-content-between align-items-center">
                                                <h4 class="card-title mb-0">Файлы</h4>
                                            </div>
                                            <div class="card-body pt-2 pb-2">
                                                @foreach($order_task->agreement->getDocuments() as $ext => $rows)
                                                    @foreach($rows as $file)
                                                        <div class="font-15 mt-1">
                                                            <a href="{{ $file->url }}" target="_blank">
                                                                <x-ui.icon.files ext="{{$ext}}" class="me-1" asd="1"></x-ui.icon.files>
                                                                {{ $file->filename }}
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-8 col-xl-8 objects">
                        @if($order_task->hasWorkMode() && $view_mode == 'working')
                            @include('pub.order_task.details.working')
                        @else
                            @include('pub.order_task.details.default')
                        @endif
                    </div>

        </div>
    </div>



    <div id="modal-confirm" class="modal fade" tabindex="-1" aria-labelledby="danger-header-modalLab el" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class=" modal-header modal-colored-header bg-success text-white">
                    <h4 class="modal-title" id="danger-header-modalLabel">Согласование ТЗ</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Отменить"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <label class="col-12 control-label col-form-label">Комментарий</label>
                        <div class="col-12 pt-1">
                            <div class="form-group">
                                <textarea class="form-control" rows="7" placeholder=""></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Отменить
                    </button>
                    <button
                        type="button"
                        id="btn_agreement_confirm"
                        data-bs-dismiss="modal"
                        class="
                                btn btn-success
                                font-weight-medium
                              "
                    >
                        СОГЛАСОВАТЬ
                    </button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>

    <div id="modal-decline" class="modal fade" tabindex="-1" aria-labelledby="danger-header-modalLab el" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class=" modal-header modal-colored-header bg-danger text-white">
                    <h4 class="modal-title" id="danger-header-modalLabel">Согласование ТЗ</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Отменить"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <label class="col-12 control-label col-form-label">Комментарий</label>
                        <div class="col-12 pt-1">
                            <div class="form-group">
                                <textarea class="form-control" rows="7" placeholder=""></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Отменить
                    </button>
                    <button
                        type="button"
                        id="btn_agreement_decline"
                        data-bs-dismiss="modal"
                        class="
                                btn btn-danger
                                font-weight-medium
                              "
                    >
                        ОТКАЗАТЬ
                    </button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>


    @if($order_task->status == \App\Modules\Pub\OrderTask\Models\OrderTask::STATUS_DECLINED && $order_task->hasAccess())
        <div id="modal-archive" class="modal fade" tabindex="-1" aria-labelledby="danger-header-modalLab el" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class=" modal-header modal-colored-header bg-danger text-white">
                        <h4 class="modal-title" id="danger-header-modalLabel">В архив</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Отменить"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">Вы уверены, что хотите отправить техническое задание в архив?</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal"
                        >
                            Отменить
                        </button>
                        <button
                            type="button"
                            id="btn_archive_confirm"
                            data-bs-dismiss="modal"
                            class="
                                btn btn-danger
                                font-weight-medium
                              "
                        >
                            В АРХИВ
                        </button>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>

        <div id="modal-recreate" class="modal fade" tabindex="-1" aria-labelledby="danger-header-modalLab el" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class=" modal-header modal-colored-header bg-success text-white">
                        <h4 class="modal-title" id="danger-header-modalLabel">Создание копии</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Отменить"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">Вы уверены, что хотите создать копию текущего технического задания?</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal"
                        >
                            Отменить
                        </button>
                        <button
                            type="button"
                            id="btn_recreate_confirm"
                            data-bs-dismiss="modal"
                            class="
                                btn btn-success
                                font-weight-medium
                              "
                        >
                            СОЗДАТЬ КОПИЮ
                        </button>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
    @endif

@endsection

@section('js')
    @parent


    <script>
        function agreement_confirm(user_id) {
            window.user_id = user_id;
            $("#modal-confirm").modal('show');
        }

        function agreement_decline(user_id) {
            window.user_id = user_id;
            $("#modal-decline").modal('show');
        }

        function agreement_decision(decision)
        {
            var block_elem = $(".agreement_block");
            $(block_elem).block({
                message: '<i class="fas fa-spin fa-sync text-white"></i>',
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

            if(decision == 'confirm') {
                comment = $("#modal-confirm textarea").val();
            } else {
                comment = $("#modal-decline textarea").val();
            }
            $.ajax({
                url: "{{ route('api.order_task.agree_decision', [$order_task, '_token' => auth()->user()->ajax_token ]) }}",
                type: "POST",
                data: {
                    user_id: window.user_id,
                    decision: decision,
                    comment: comment
                },
                dataType: "json",
                success: function (response) {
                    location.reload();
                },
                error: function () {
                    toastr.error("Не получилось сохранить данные", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                    window.comment_lock = false;
                }
            });
        }


        @switch($order_task->status)
            @case(\App\Modules\Pub\OrderTask\Models\OrderTask::STATUS_DECLINED)
                @if($order_task->hasAccess())
                            function archive(decision)
                            {
                                var block_elem = $(".status_block");
                                $(block_elem).block({
                                    message: '<i class="fas fa-spin fa-sync text-white"></i>',
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
                                    url: "{{ route('api.order_task.cancel', [$order_task, '_token' => auth()->user()->ajax_token ]) }}",
                                    type: "POST",
                                    dataType: "json",
                                    success: function (response) {
                                        if(response.status == 'success') {
                                            location.reload();
                                        } else {
                                            $(block_elem).unblock();
                                        }
                                    },
                                    error: function () {
                                        toastr.error("Не получилось отправить ТЗ в архив", "Это провал!", {
                                            progressBar: true,
                                            "timeOut": 3000,
                                        });
                                        $(block_elem).unblock();
                                        window.comment_lock = false;
                                    }
                                });
                            }

                            @if($order_task->canRemake())
                                function recreate(decision)
                                {
                                    var block_elem = $(".status_block");
                                    $(block_elem).block({
                                        message: '<i class="fas fa-spin fa-sync text-white"></i>',
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
                                        url: "{{ route('api.order_task.recreate', [$order_task, '_token' => auth()->user()->ajax_token ]) }}",
                                        type: "POST",
                                        dataType: "json",
                                        success: function (response) {
                                            if(response.status == 'success') {
                                                location.replace(response.url);
                                            } else {
                                                $(block_elem).unblock();
                                            }
                                        },
                                        error: function () {
                                            toastr.error("Не получилось пересоздать ТЗ", "Это провал!", {
                                                progressBar: true,
                                                "timeOut": 3000,
                                            });
                                            $(block_elem).unblock();
                                            window.comment_lock = false;
                                        }
                                    });
                                }
                            @endif


                            $(document).ready(function () {
                                    $("#order_archive").on("click", function () {
                                        $("#modal-archive").modal('show');
                                    });
                                    $("#order_recreate").on("click", function () {
                                        $("#modal-recreate").modal('show');
                                    });

                                    $("#btn_archive_confirm").on("click", function() {
                                        archive();
                                    });
                                    $("#btn_recreate_confirm").on("click", function() {
                                        recreate();
                                    });
                            });
                @endif
            @break
        @endswitch


        $(document).ready(function () {
            $("#btn_agreement_confirm").on("click", function () {
                agreement_decision('confirm');
            });
            $("#btn_agreement_decline").on("click", function () {
                agreement_decision('decline');
            });
        });
    </script>
@endsection
