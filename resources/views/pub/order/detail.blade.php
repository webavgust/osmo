@extends('layouts.layout')

@section('styles')
    <link rel="stylesheet" type="text/css" href="/assets/libs/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/extra-libs/summernote/summernote-lite.min.css">
    <link rel="stylesheet" type="text/css" href="/dist/modules/jquery-ui/jquery-ui.min.css">
    <style>
        .order_comments_pad {
            margin-left: 11px;
        }

        .new_comment_pad:not(.visible) + .order_comments_pad {
            margin-top: -16px;
        }

        .new_comment_pad {
            margin-top: -16px;
            margin-left: 11px;
            display: none;
        }

        .note-editable {
            font-size: 13px;
        }

        .profiletimeline .sl-item:last-of-type {
            padding-bottom: 20px;
        }

        ul.task_create {

        }

        .row.users .user-row:not(:last-of-type) .card
        {
            margin-bottom: 0;
            border-bottom: 1px solid #DDD;
        }

        .order_task .tree {
            border-top: 1px solid #EEE;
        }

    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 col-md-6 col-lg-5 col-xl-3">
                <div class="row users">
                    <x-order.detail.person_card :person="$order->manager" badge="Менеджер"
                                                color="success"></x-order.detail.person_card>

                    <x-order.detail.person_card :person="$order->curator" badge="Куратор" color="primary" curator="1"
                                                type="curator"></x-order.detail.person_card>
                </div>
                <div class="card">
                    @can('order_tech_leader')
                        <button type="button" class="
                                    position-absolute
                                    btn btn-light-secondary btn-circle btn-sm
                                    d-inline-flex
                                    align-items-center
                                    justify-content-center
                                  " style="z-index: 10; right: -15px; top: -15px" data-bs-toggle="modal" data-bs-target="#status-modal">

                            <i class="fa-light fa-pen"></i>
                        </button>
                    @endcan
                    <div class="card-body d-flex justify-content-between">
                        <h4 class="card-title mb-0">Информация о заявке</h4>
                        <div id="order_status">
                            <x-order.detail.status_badge :order="$order"></x-order.detail.status_badge>
                        </div>

                    </div>
                    <div class="card-body">
                        <div class="card-table">
                            <x-ui.card.card_table_tr field="Номер" value="{{ $order->id }}"
                                                     link="{{ env('PORTAL_URL') }}/projects/orders/{{ $order->id }}/"></x-ui.card.card_table_tr>
                            <x-ui.card.card_table_tr field="Название"
                                                     value="{!! $order->order_name !!}"></x-ui.card.card_table_tr>
                            <x-ui.card.card_table_tr field="Дата получения (ТЗ отправлено) "
                                                     value="{{ _date($order->order_sent_to_techdep) }}"></x-ui.card.card_table_tr>
                        </div>
                    </div>
                    @can('order_info_company')
                        <div class="card-body-title">
                            <h4 class="card-title mb-0">Заказчик</h4>
                        </div>
                        <div class="card-body">
                            <div class="card-table">
                                <x-ui.card.card_table_tr field="Компания" value="{!! $order->customer_name !!}"
                                                         link="{{ env('PORTAL_URL') }}/projects/clients/{{ $order->customer_id }}/"></x-ui.card.card_table_tr>
                                <x-ui.card.card_table_tr field="Дата заключения контракта"
                                                         value="{{ _date($order->contract_conclusion) }}"></x-ui.card.card_table_tr>
                            </div>
                        </div>
                    @endcan

                    {{--                    <div class="card-body-title">--}}
                    {{--                        <h4 class="card-title mb-0">Панель куратора</h4>--}}
                    {{--                    </div>--}}


                </div>



            </div>
            <div class="col-12 col-sm-12 col-md-6 col-lg-7 col-xl-9">
                <div class="row">
                    <div class="col-md-12 col-xl-7">
                        <!-- сроки, локация и периодичность -->
                        <div class="card w-100" class="specify_block position-relative">
                            @can('order_curator', $order)
                                <button type="button" class="
                                    position-absolute
                                    btn btn-light-secondary btn-circle btn-sm
                                    d-inline-flex
                                    align-items-center
                                    justify-content-center
                                  " style="z-index: 10; right: -15px; top: -15px" data-bs-toggle="modal"
                                        data-bs-target="#curator-modal">

                                    <i class="fa-light fa-pen"></i>
                                </button>
                            @endcan
                            <img class="rounded-top" src="/assets/images/background/weatherbg.jpg"
                                 style="max-height: 105px">
                            <div class="card-img-overlay" style="height: 110px">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <h4 class="card-title text-white mb-0" id="period_location">
                                            {{ $order->md_specify_locationplace ?? 'Место не указано' }}
                                        </h4>
                                    </div>
                                    <div class="ms-auto">
                                        <x-ui.badge.light type="secondary" text="dark"
                                          class="mt-1" id="period_period">{{ $order->md_specify_periodicity }}</x-ui.badge.light>

                                        <x-ui.badge.light type="secondary" text="dark"
                                          class="mt-1" id="period_end_date">{{ _date($order->md_specify_end_period_date) }}</x-ui.badge.light>


                                    </div>

                                </div>
                            </div>
                            <div class="p-3 weather-small">
                                <div class="row">
                                    <div class="col-6 order-0 col-xl-3 order-xl-0 text-center">
                                        <h1 class="fw-light mb-0" title="Дата получения (ТЗ отправлено)">ДП</h1>
                                        <span class="font-12">
                                            @if(empty($order->order_sent_to_techdep))
                                                Не указано
                                            @else
                                                {{ _date($order->order_sent_to_techdep) }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="col-12 order-2 col-xl-6 order-xl-1 align-self-end" style="margin-bottom: 6px;">
                                        <div id="period_percent" class="@if(empty($period_data)) d-none @endif">
                                            <div class="fs-1 text-center mt-1"><span class="left">{{ $period_data['left'] ?? 0 }}</span>
                                                / <span class="total">{{ $period_data['total'] ?? 0 }}</span></div>
                                            <div class="progress" style="height: 15px">
                                                <div class="progress-bar bg-primary pd-1 fs-1"
                                                     style=" width: {{ $period_data['percent'] ?? 0 }}%"
                                                     role="progressbar">{{ $period_data['percent'] ?? 0 }}%
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 order-1 col-xl-3 order-xl-2 text-center">
                                        <h1 class="fw-light mb-0"
                                            title="Дней на выполнение" id="period_days">{{ $order->md_specify_days }}</h1>

                                        <span class="font-12" title="Дата окончания" id="period_date">{{ _date($order->md_specify_finaldate) }} </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @can('order_task_view')
                            <div class="card order_task">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <h4 class="card-title mb-0">Техническое задание</h4>

                                    <div>
                                        @if(!empty($order->order_task->number))
                                            <x-document_number.badge  :row="$order->order_task->number"></x-document_number.badge>
                                        @endif
                                        @if(!empty($order->order_task))
                                            @can('order_task_copy')
                                                <x-ui.a.sidebar href="{{route('order_task.copy.form', $order)}}" class="ms-1">
                                                    <i class="fa-solid fa-copy"></i>
                                                </x-ui.a.sidebar>
                                            @endcan
                                        @endif
                                    </div>
                                </div>

                                <x-order.detail.order_task_control :order="$order"></x-order.detail.order_task_control>
                                @if(!empty($order->order_task))
                                    <div class="card-body p-1">
                                        <div class="p-3" id="tree_link">
                                            <a href="javascript:void(0);" onclick="javascript:$('#tree_link').remove();$('#tree').removeClass('d-none');" class="link ">
                                                <i class="fa-light fa-folder-tree me-1"></i>
                                                Показать дерево технического задания
                                            </a>
                                        </div>
                                        <div id="tree" class="d-none"></div>
                                    </div>
                                @endif
                            </div>

                        @endcan
                    </div>
                    <div class="col-md-12 col-xl-5">
                        <!-- Даты + комментарии -->
                        <div class="card w-100 control_pad">
                            <div class="card-body h-100">
                                <div class="row">
                                    <div class="col-6 text-center border-end ">
                                        <div class="mb-2">Дата контроля 1</div>
                                        <h2 class="mb-0 fw-light" id="order_first_date">
                                            @if(!empty($order->last_control_date))
                                                {{ _date($order->last_control_date) }}
                                            @else
                                                <i class="fa-light fa-dash text-light"></i>
                                            @endif
                                        </h2>
                                    </div>
                                    <div class="col-6 text-center">
                                        <div class="mb-2">Дата контроля 2</div>
                                        <h2 class="mb-0 fw-light" id="order_second_date">
                                            @if(!empty($order->second_control_date))
                                                {{ _date($order->second_control_date) }}
                                            @else
                                                <i class="fa-light fa-dash text-light"></i>
                                            @endif
                                        </h2>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-12 border-top mt-3">
                                        <div class="card-body ps-0 pe-0 pb-0">
                                            <x-order.comments :comments="$order->comments()->orderBy('created_at', 'desc')->get()"></x-order.comments>

                                            @can('order_comments_control')
                                                <div class="mb-2 new_comment_pad">
                                                    <div class="summernote"></div>
                                                    <div class="row mt-1">
                                                        <div class="col-6">
                                                            <div class="input-group">
                                                                    <span class="input-group-text">
                                                                        <i class="fa-thin fa-circle-1 text-danger"></i>
                                                                    </span>
                                                                <input type="text"
                                                                       class="form-control comment_datepicker"
                                                                       id="comment_first_date">
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="input-group">
                                                                    <span class="input-group-text">
                                                                        <i class="fa-thin fa-circle-2"></i>
                                                                    </span>
                                                                <input type="text"
                                                                       class="form-control comment_datepicker"
                                                                       id="comment_second_date">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endcan
                                            <div class="order_comments_pad d-flex justify-content-between row">
                                                <div id="btn_comments_more"
                                                     class="@if($order->comments()->count() < 2) d-none @endif col-12 col-lg-6 order-1 mt-1 order-lg-0 mt-lg-0 flex-grow-1">
                                                    <x-ui.button.outline btn_type="primary" class="btn-show">
                                                        <i class="fa-light fa-circle-chevron-down me-2"></i> + <span
                                                            class="count">{{ $order->comments()->count()-1  }} {{ \App\Facades\Tools::num_rus($order->comments()->count()-1, ['комментария', 'комментарий', 'комментариев']) }}</span>
                                                    </x-ui.button.outline>

                                                    <x-ui.button.outline btn_type="primary" class="btn-hide d-none">
                                                        <i class="fa-light fa-circle-chevron-up me-2"></i> Скрыть
                                                        комментарии
                                                    </x-ui.button.outline>
                                                </div>
                                                @can('order_comments_control')
                                                    <div class="col-12 col-lg-6 order-0 order-lg-1">
                                                        <x-ui.button.outline btn_type="success" id="btn_comments_save"
                                                                             class="d-none font-14" >
                                                            <i class="fa-thin fa-floppy-disk me-2"></i> Сохранить комментарий
                                                        </x-ui.button.outline>
                                                        <x-ui.button.outline btn_type="success" id="btn_comments_add" class="font-14">
                                                            <i class="fa-light fa-circle-plus me-2"></i> Добавить комментарий
                                                        </x-ui.button.outline>
                                                    </div>
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                    </div>
                </div>


            </div>
        </div>
    </div>
    @can('order_tech_leader')
        <div
            id="status-modal"
            class="modal fade"
            tabindex="-1"
            aria-labelledby="danger-header-modalLab el"
            aria-hidden="true"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class=" modal-header modal-colored-header bg-primary text-white">
                        <h4 class="modal-title" id="danger-header-modalLabel">Изменение статуса заявки</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Не сохранять"></button>
                    </div>
                    <div class="modal-body">
                        <form id="status">
                            <div class="mt-4 row">
                                <label class="col-sm-3 text-end control-label col-form-label">Статус</label>
                                <div class="col-sm-9 pt-1">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input success check-light-success" type="radio" name="is_finished" id="success-light-radio" value="0" @if(!$order->is_finished) checked @endif>
                                        <label class="form-check-label" for="success-light-radio">Незавершённая</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input secondary check-light-secondary" type="radio" name="is_finished" id="secondary-light-radio" value="1" @if($order->is_finished) checked @endif>
                                        <label class="form-check-label" for="success-light-radio">Завершённая</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 row">
                                <label class="col-sm-3 text-end control-label col-form-label"></label>
                                <div class="col-sm-9 pt-1">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input danger check-light-danger" type="checkbox" id="danger2-light-check" value="1" name="is_archived" @if($order->is_archived) checked @endif>
                                        <label class="form-check-label" for="danger2-light-check">Архивная</label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal"
                        >
                            Не сохранять
                        </button>
                        <button
                            type="button"
                            id="btn_status_confirm"
                            data-bs-dismiss="modal"
                            class="
                                btn btn-primary
                                font-weight-medium
                              "
                            >
                            СОХРАНИТЬ
                        </button>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>

        <div
            id="techleader-modal"
            class="modal fade"
            tabindex="-1"
            aria-labelledby="danger-header-modalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class=" modal-header modal-colored-header bg-primary text-white">
                        <h4 class="modal-title" id="danger-header-modalLabel">Изменение куратора</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Не сохранять"></button>
                    </div>
                    <div class="modal-body">
                        <form id="techleader">
                            <div class="mt-4 row">
                                <label class="col-sm-3 text-end control-label col-form-label">Куратор</label>
                                <div class="col-sm-9 pt-1">
                                    <select class="select2 form-control"
                                            style="height: 36px; width: 100%" name="curator">
                                            <option value="0"></option>
                                            @foreach($curators as $user)
                                                <option value="{{$user->id}}"
                                                    @if(!empty($order->curator) && $order->curator->id == $user->id) selected @endif >{{$user->fullname}}</option>
                                            @endforeach
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal"
                        >
                            Не сохранять
                        </button>
                        <button
                            type="button"
                            id="btn_techleader_confirm"
                            data-bs-dismiss="modal"
                            disabled
                            class="
                                btn btn-primary
                                font-weight-medium
                              "
                            >
                            СОХРАНИТЬ
                        </button>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
    @endcan
    @can('order_curator', $order)
        <div
            id="curator-modal"
            class="modal fade"
            tabindex="-1"
            aria-labelledby="danger-header-modalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class=" modal-header modal-colored-header bg-warning text-white">
                        <h4 class="modal-title" id="danger-header-modalLabel">Изменение данных</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Не сохранять"></button>
                    </div>
                    <div class="modal-body">
                        <form id="control">
                            <div class="mb-3">
                                <label for="md_specify_locationplace" class="control-label">Место:</label>
                                <input name="location" type="text" class="form-control" id="md_specify_locationplace" value="{{ $order->md_specify_locationplace }}">
                            </div>
                            <div class="mb-3 row">
                                <div class="col-7">
                                    <label for="md_specify_periodicity" class="control-label">Периодичность:</label>
                                    <input name="period" type="text" class="form-control" id="md_specify_periodicity" value="{{ $order->md_specify_periodicity }}">
                                </div>
                                <div class="col-5">
                                    <label for="md_specify_periodicity" class="control-label">Окончание периода:</label>
                                    <input name="end_period_date" type="text" class="form-control text-center flex-grow-0 end_period_date_datepicker" value="{{ _date($order->md_specify_end_period_date) }}" style="width: 120px">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <div class="col-8" id="comment_date_pad">
                                    <label for="recipient-name" class="control-label">Дата окончания и дни:</label>
                                    <div class="input-group justify-content-start">
                                        <input name="date" type="text" class="form-control text-center flex-grow-0 curator_datepicker" value="{{ _date($order->md_specify_finaldate) }}" style="width: 120px">
                                        <span class="input-group-text"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text feather-sm fill-white"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></span>
                                        <input name="days" type="text" class="form-control text-center flex-grow-0 curator_days" value="{{ $order->md_specify_days }}" style="width: 60px">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal"
                        >
                            Не сохранять
                        </button>
                        <button
                            type="button"
                            id="btn_curator_confirm"
                            data-bs-dismiss="modal"
                            class="
                                    btn btn-light-warning
                                    text-warning
                                    font-weight-medium
                                  "
                        >
                            СОХРАНИТЬ
                        </button>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
    @endcan
    @can("order_task_submit")
        <div
            id="order-task-submit-modal"
            class="modal fade"
            tabindex="-1"
            aria-labelledby="primary-header-modalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class=" modal-header modal-colored-header bg-primary text-white">
                        <h4 class="modal-title" id="danger-header-modalLabel">Техническое задание</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Не сохранять"></button>
                    </div>
                    <div class="modal-body">
                        <p>Передать техническое задание в работу?</p>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal"
                        >
                            Не передавать
                        </button>
                        <button
                            type="button"
                            id="btn_order-task-confirm"
                            data-bs-dismiss="modal"
                            class="
                                    btn btn-light-primary
                                    text-primary
                                    font-weight-medium
                                  "
                        >
                            ПЕРЕДАТЬ В РАБОТУ
                        </button>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
    @endcan
@endsection

@section('js')
    @parent
    <script src="/dist/modules/jquery-ui/jquery-ui.min.js"></script>
    <script src="/assets/libs/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="/assets/libs/bootstrap-datepicker/dist/locales/bootstrap-datepicker.ru.min.js"></script>
    <script src="/assets/extra-libs/summernote/summernote-lite.min.js"></script>
    <script src="/assets/extra-libs/treeview/dist/bootstrap-treeview.min.js"></script>
    <script>

    </script>
    @can("order_task_submit")
        <script>
            $(document).ready(function() {
               $("#btn_order-task-confirm").on("click", function() {
                   var block_elem = $(".card.order_task");
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
                       url: "{{ route('api.order_task.status_work', [$order, '_token' => auth()->user()->ajax_token ]) }}",
                       type: "POST",
                       data: $("form#status").serialize(),
                       dataType: "html",
                       success: function (result) {
                           toastr.success("ТЗ отправлено в работу", "Это успех!", {
                               progressBar: true,
                               "timeOut": 3000,
                           });

                           $("#order_task_body")[0].outerHTML = result;

                           $(block_elem).unblock();
                       },
                       error: function () {
                           toastr.error("Не получилось отправить ТЗ в работу", "Это провал!", {
                               progressBar: true,
                               "timeOut": 3000,
                           });
                           $(block_elem).unblock();
                       }
                   });
               });
            });
        </script>
    @endcan
    @can("order_task_view")
    <script>
        var defaultData = @json($order->task_tree);
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
    @endcan

    <script>
        $(document).ready(function () {
            $("#btn_comments_more .btn-show").on("click", function () {
                $("#btn_comments_more .btn-show").addClass('d-none');
                $("#btn_comments_more .btn-hide").removeClass('d-none');
                $(".order_comments .sl-item").removeClass('d-none');
            });
            $("#btn_comments_more .btn-hide").on("click", function () {
                $("#btn_comments_more .btn-hide").addClass('d-none');
                $("#btn_comments_more .btn-show").removeClass('d-none');
                $(".order_comments .sl-item:not(:first-of-type)").addClass('d-none');
            });

            @can('order_tech_leader')
                $("#btn_status_confirm").on("click", function() {
                    var block_elem = $("body");
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
                        url: "{{ route('api.order.set.status', [$order, '_token' => auth()->user()->ajax_token ]) }}",
                        type: "POST",
                        data: $("form#status").serialize(),
                        dataType: "html",
                        success: function (result) {
                            toastr.success("Статус изменён", "Это успех!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });

                            $("#order_status").html(result);

                            $(block_elem).unblock();
                        },
                        error: function () {
                            toastr.error("Не получилось сохранить статус", "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                            $(block_elem).unblock();
                        }
                    });
                });
                window.curator_id = '{{ $order->curator->id ?? 0 }}';
                $("#techleader-modal .select2").select2({
                    dropdownParent: $("#techleader-modal")
                }).on("change", function (e) {
                    if(!$(this).val() || $(this).val() == window.curator_id) {
                        $("#btn_techleader_confirm").attr("disabled", "Y");
                    } else {
                        $("#btn_techleader_confirm").removeAttr("disabled");
                    }
                });

                $("#btn_techleader_confirm").on("click", function() {
                    var block_elem = $("body");
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
                        url: "{{ route('api.order.set.curator', [$order, '_token' => auth()->user()->ajax_token ]) }}",
                        type: "POST",
                        data: {
                            curator: $("#techleader-modal .select2").val()
                        },
                        dataType: "json",
                        success: function (result) {
                            toastr.success("Комментарий добавлен", "Это успех!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });

                            var block = $(".row.users .card[curator]");

                            block.find('img').attr("src", result.user.avatar);
                            block.find('h5').html(result.user.name);

                            parent_a = block.parents('.person_link');

                            if(!parent_a.length) {
                                block.wrap('<a href="{{ route('users.view') }}/' + result.user.id + '"></a>').addClass('card-hover');
                            } else {
                                parent_a.attr('href', '{{ route('users.view') }}/' + result.user.id);
                            }

                            $("#btn_techleader_confirm").attr("disabled", "Y");
                            window.curator_id = result.user.id;



                            $(block_elem).unblock();
                        },
                        error: function () {
                            toastr.error("Не получилось сохранить куратора", "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                            $(block_elem).unblock();
                        }
                    });


                });
            @endcan
            @can('order_curator', $order)
                $(".end_period_date_datepicker").datepicker({
                    format: "dd.mm.yyyy",
                    startView: "days",
                    minViewMode: "days",
                    orientation: 'auto bottom',
                    language: 'ru',
                    autoclose: true
                });
                $(".curator_datepicker").datepicker({
                    format: "dd.mm.yyyy",
                    startView: "days",
                    minViewMode: "days",
                    orientation: 'auto bottom',
                    language: 'ru',
                    autoclose: true
                }).on('change', function (e) {
                    curator_days_sync('date');
                });


                $(".curator_days").on("change", function() {
                    curator_days_sync('days');
                });

                $("#btn_curator_confirm").on("click", function() {
                    var block_elem = $("body");
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
                        url: "{{ route('api.order_period.add', [$order, '_token' => auth()->user()->ajax_token ]) }}",
                        type: "POST",
                        data: $("#control").serialize(),
                        dataType: "json",
                        success: function (response) {
                            $(block_elem).unblock();

                            if(response.result == 'pass') {
                                return false;
                            }
                            toastr.success("Комментарий добавлен", "Это успех!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });

                            $("#period_location").html(response.data.location);
                            if(response.data.period) {
                                $("#period_period").html(response.data.period).removeClass("d-none");
                            } else {
                                $("#period_period").addClass("d-none");
                            }

                            if(response.data.end_period_date) {
                                $("#period_end_date").html(response.data.end_period_date).removeClass("d-none");
                            } else {
                                $("#period_end_date").addClass("d-none");
                            }


                            $("#period_percent").removeClass("d-none").find('.left').html(response.progress.left);
                            $("#period_percent .total").html(response.progress.total);
                            $("#period_percent .progress-bar").html(response.progress.percent + '%').css('width', response.progress.percent + '%');
                            $("#period_days").html(response.data.days);
                            $("#period_date").html(response.data.date);
                        },
                        error: function () {
                            toastr.error("Не получилось сохранить комментарий", "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                            $(block_elem).unblock();
                        }
                    });

                });
            @endcan
            @can('order_comments_control')
                $("#btn_comments_add").on("click", function () {
                    $(this).addClass('d-none');
                    $("#btn_comments_save").removeClass('d-none');
                    $(".new_comment_pad").addClass("visible").show();
                });

                $("#btn_comments_save").on("click", function () {
                    var block_elem = $(".control_pad");
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

                    cleanText = $(".summernote").summernote('code').replace(/<\/?[^>]+(>|$)/g, "");
                    if (!cleanText) return false;

                    $.ajax({
                        url: "{{ route('api.order_comment.add', [$order, '_token' => auth()->user()->ajax_token ]) }}",
                        type: "POST",
                        data: {
                            text: cleanText,
                            first_date: $("#comment_first_date").val(),
                            second_date: $("#comment_second_date").val(),
                        },
                        dataType: "html",
                        success: function (resultFull) {
                            var data = resultFull.split('|');
                            toastr.success("Комментарий добавлен", "Это успех!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });


                            $(".order_comments .sl-item").addClass('d-none');
                            $(".order_comments .sl-item .dates").removeClass('d-none');
                            $(".order_comments").prepend(data[0]);
                            $(".order_comments .sl-item:first-of-type").hide().delay(100).show('bounce', 1000);
                            if (data[1]) {
                                $("#btn_comments_more").removeClass("d-none");
                                $("#btn_comments_more .btn-hide").click();
                                $("#btn_comments_more").find(".count").html(data[1]);
                            }

                            $("#order_first_date").html($("#comment_first_date").val());
                            $("#order_second_date").html($("#comment_second_date").val() ? $("#comment_second_date").val() : '<i class="fa-light fa-dash text-light"></i>');

                            comment_clear();


                            $(block_elem).unblock();
                        },
                        error: function () {
                            toastr.error("Не получилось сохранить комментарий", "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                            $(block_elem).unblock();
                        }
                    });

                });

                $(".summernote").summernote({
                    toolbar: [],
                    placeholder: 'Введите текст комментария',
                    height: 100,
                    fontsize: 10
                }).on("summernote.enter", function (we, e) {
                    $(this).summernote("pasteHTML", "<br><br>");
                    e.preventDefault();
                }).on("summernote.change", function (e) {   // callback as jquery custom event
                    comment_form_check();
                });


                $(".comment_datepicker").datepicker({
                    format: "dd.mm.yyyy",
                    startView: "days",
                    minViewMode: "days",
                    orientation: 'auto bottom',
                    language: 'ru',
                    autoclose: true
                }).on('change', function (e) {
                    comment_form_check();
                });
            @endcan

        });


        @can('order_curator', $order)
            function curator_days_sync(type) {
                if(window.comment_lock) return;
                window.comment_lock = true;
                var block_elem = $("#curator-modal .modal-content");
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
                    url: "{{ route('api.order.filter.days_calc', [$order, '_token' => auth()->user()->ajax_token ]) }}",
                    type: "POST",
                    data: {
                        type: type,
                        days: $(".curator_days").val(),
                        date: $(".curator_datepicker").val()
                    },
                    dataType: "json",
                    success: function (response) {
                        if(response.result == 'success') {
                            $(".curator_datepicker").val(response.data.date);
                            $(".curator_days").val(response.data.days);
                            $(".curator_datepicker").datepicker("setDate", response.data.date) ;
                        } else {
                            toastr.error("Не получилось получить данные", "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                        }
                        $(block_elem).unblock();
                        window.comment_lock = false;
                    },
                    error: function () {
                        toastr.error("Не получилось получить данные", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        $(block_elem).unblock();
                        window.comment_lock = false;
                    }
                });
            }
        @endcan


        @can('order_comments_control')
            function comment_form_check() {
                cleanText = $(".summernote").summernote('code').replace(/<\/?[^>]+(>|$)/g, "");
                if (cleanText.length > 0 && $("#comment_first_date").val()) {
                    $("#btn_comments_save").removeClass("btn-outline-success").addClass("btn-success").removeAttr("disabled");
                } else {
                    $("#btn_comments_save").addClass("btn-outline-success").removeClass("btn-success").attr("disabled", "Y");
                }
            }

            function comment_clear() {
                $(".summernote").summernote('code', '');
                $("#btn_comments_add").removeClass('d-none');
                $("#btn_comments_save").addClass('d-none');
                $(".new_comment_pad").removeClass("visible").hide();
                $("#comment_first_date").val('');
                $("#comment_second_date").val('');
            }
        @endcan
    </script>
@endsection


