@extends('layouts.layout')

@section('styles')
    <link rel="stylesheet" type="text/css"
          href="/assets/libs/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/extra-libs/summernote/summernote-lite.min.css">
    <link rel="stylesheet" type="text/css" href="/dist/modules/jquery-ui/jquery-ui.min.css">
    <link rel="stylesheet" type="text/css" href="/dist/modules/dropzone/dropzone.css"/>
    <style>
        .row.users .user-row:not(:last-of-type) .card {
            margin-bottom: 0;
            border-bottom: 1px solid #DDD;
        }

        .address .title {
            background: #F7F7F7;
        }

        .address .measures {
            background: #FFF;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 col-md-6 col-lg-4 col-xl-1">
                @foreach($evaluations as $iteration => $old)
                    @if($old->id === $evaluation->id)
                        <x-ui.button.default btn_type="{{$old->status_decorate['class']}}"
                                             class="w-100 mb-2 p-1">
                            <div class="fw-bolder font-12">{{ _date($old->created_at) }}
                                <sup>{{ $old->iteration }}</sup></div>
                            <div class="font-12">{{ $old->status_decorate['name'] }}</div>
                        </x-ui.button.default>
                    @else
                        <x-ui.a.outline btn_type="{{$old->status_decorate['class']}}" class="w-100 mb-2 p-1"
                                        href="{{ route('evaluation.detail', [$old->block_id, $iteration]) }}">
                            <div class="font-12">{{ _date($old->created_at) }} <sup>{{ $old->iteration }}</sup></div>
                            <div class="font-12">{{ $old->status_decorate['name'] }}</div>
                        </x-ui.a.outline>
                    @endif
                @endforeach
            </div>
            <div class="col-12 col-md-6 col-lg-4 col-xl-4">

                <div class="card mb-3">
                    <div class="card-body py-3">
                        <h2 class="mb-1">
                            @if(!empty($evaluation->portal->client_id))
                                <a href="{{ env('PORTAL_URL') }}/projects/clients/{{ $evaluation->portal->client_id }}/">
                                    {{ $evaluation->portal?->client_name ?? '?' }}
                                </a>
                            @else
                                {{ $evaluation->portal?->client_name ?? '?' }}
                            @endif
                        </h2>
                        <div class="m-0 d-flex align-items-center fs-3">
                            <a href="{{ env('PORTAL_URL') }}/projects/contracts/{{ $evaluation->sub_contract->contract_id }}/">{{ $evaluation->portal?->contract_name }}</a>
                            <x-ui.icon.solid icon="fa-slash-forward" class="mx-2 fs-2"/>
                            <span>{{ $evaluation->portal?->annex_name ?? $evaluation->block_id }}</span>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body d-flex justify-content-between">
                        <h4 class="card-title mb-0">Информация о приложении</h4>
                        <div>
                            <x-evaluation.status :evaluation="$evaluation" class="mt-2 mt-sm-0"></x-evaluation.status>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-table">
                            <x-ui.card.card_table_tr field="Номер"
                                                     value="{{ $evaluation->id }}"></x-ui.card.card_table_tr>

                            <x-ui.card.card_table_tr field="Ключ приложения"
                                                     :value="$evaluation->block_id">

                            </x-ui.card.card_table_tr>


                            <x-ui.card.card_table_tr field="Дата создания"
                                                     value="{{ _date($evaluation->created_at) }}"></x-ui.card.card_table_tr>

                            @can('users_view_profile')
                                <x-ui.card.card_table_tr field="Автор приложения"
                                                         value="{{ $evaluation->creator()->first()->fullName }}"
                                                         link="{{ route('users.view', $evaluation->creator) }}"></x-ui.card.card_table_tr>
                            @else
                                <x-ui.card.card_table_tr field="Автор приложения"
                                                         value="{{ $evaluation->creator()->first()->fullName }}"></x-ui.card.card_table_tr>
                            @endif

                            <x-ui.card.card_table_tr field="Срок выполнения"
                                                     value="{{ $evaluation->period ?? '-' }}"></x-ui.card.card_table_tr>


                        @can('evaluation_view_finance')
                                <x-ui.card.card_table_tr field="Бонус руководителя (план)">
                                    <x-ui.badge.light type="warning font-14">
                                        {{ tools()->cost_normalize($evaluation->plan_supervisor_salary) }} ₽
                                    </x-ui.badge.light>
                                </x-ui.card.card_table_tr>

                                <x-ui.card.card_table_tr field="Плановая стоимость">
                                    <x-ui.badge.light type="secondary font-14">
                                        {{ tools()->cost_normalize($evaluation->plan_cost_total) }} ₽
                                    </x-ui.badge.light>
                                </x-ui.card.card_table_tr>
                            @endcan

                            @if(!empty($evaluation->approved_comment))
                                <x-ui.card.card_table_tr field="Согласовано с заказчиком"
                                                         value="{{ _date($evaluation->approved_at) }}"></x-ui.card.card_table_tr>

                                <x-ui.notification.light type="info"
                                                         class="p-1 mt-3 fs-2">{!! $evaluation->approved_comment  !!}</x-ui.notification.light>
                            @endif

                            @if(!empty($evaluation->discount_agreement))
                                @php
                                    $comment = $evaluation->discount_agreement->users()->where('agreed', '!=', 0)->first();
                                @endphp
                                <x-ui.card.card_table_tr field="Скидка">
                                    <x-ui.a.box
                                        href="{{ route('evaluation_discount_agreement.box_history', $evaluation) }}">
                                        @if($evaluation->discount_agreement->isSubmitted())
                                            <x-ui.badge.default type="success">
                                                {{ tools()->cost_normalize($evaluation->discount_agreement->discount) }}
                                                ₽
                                                <x-ui.icon.solid icon="fa-check" class="ms-2"></x-ui.icon.solid>
                                            </x-ui.badge.default>
                                        @else
                                            <x-ui.badge.default type="danger">
                                                {{ tools()->cost_normalize($evaluation->discount_agreement->discount) }}
                                                ₽
                                                <x-ui.icon.solid icon="fa-xmark" class="ms-2"></x-ui.icon.solid>
                                            </x-ui.badge.default>
                                        @endif
                                    </x-ui.a.box>
                                </x-ui.card.card_table_tr>

                                @if(!empty($comment?->pivot?->comment))
                                    @if($evaluation->discount_agreement->isSubmitted())
                                        <x-ui.notification.light type="success" class="p-1 mt-3 font-12">
                                            {!! $comment->pivot->comment !!}
                                        </x-ui.notification.light>
                                    @else
                                        <x-ui.notification.light type="danger" class="p-1 mt-3 font-12">
                                            {!! $comment->pivot->comment !!}
                                        </x-ui.notification.light>
                                    @endif
                                @endif

                            @endif


                            @if(!empty($evaluation->order_task))
                                <x-ui.card.card_table_tr field="Из этого приложения создано">
                                    <a href="{{ route('order_task.detail', $evaluation->order_task) }}">ТЗ
                                        №{{ $evaluation->order_task->id }}</a>
                                </x-ui.card.card_table_tr>
                            @endif
                        </div>

                        @can('evaluation_view_finance')
                            <div class="d-flex justify-content-end mt-3">
                                <x-ui.a.box href="{{ route('evaluation.box_finance', $evaluation) }}" btn_type="primary"
                                            class="p-2 py-1">
                                    <x-ui.icon.regular icon="fa-table"></x-ui.icon.regular>
                                    Финансовая таблица
                                </x-ui.a.box>
                            </div>
                        @endcan
                    </div>

                    @if(!empty($evaluation->comment))
                        <x-ui.notification.light class="font-14 p-3 m-0">
                            <div class="mb-2 font-16">Комментарий:</div>
                            <div class="font-12">{!! nl2br($evaluation->comment) !!}</div>
                        </x-ui.notification.light>
                    @endif
                </div>

                <div class="row users mb-3">
                    <x-user.cards.blank :person="$evaluation->creator" badge="Автор"
                                        color="success"></x-user.cards.blank>

                    <x-user.cards.curator :person="$evaluation->responsible" :instance="$evaluation"
                                          badge="Оценщик"
                                          color="primary"></x-user.cards.curator>
                </div>
                @if(!empty($files))
                    <div class="card">
                        <div class="card-body d-flex justify-content-between align-items-center py-2 pe-2">
                            <h4 class="card-title mb-0">Файлы</h4>
                            <x-ui.a.box href="{{ route('evaluation.box_files', $evaluation) }}" class="p-1 fs-2 px-2" btn_type="info">
                                <x-ui.icon.regular icon="fa-download" class="me-1"/>
                                Загрузить
                            </x-ui.a.box>
                        </div>
                        <div class="card-body p-0">
                            @foreach($files as $chr => $row)
                                <div class="card-body-title py-2 px-2">
                                    <h4 class="card-title mb-0">{{ $row['presets']['name'] }}</h4>
                                </div>

                                @foreach($row['files'] as $file)
                                    @if(!is_array($file))
                                        <div class="font-13 p-2 px-3">
                                            <a href="{{ $file->url }}" target="_blank" class="fs-3">
                                                <x-ui.icon.files ext="{{$file->extension}}" class="me-1"></x-ui.icon.files>
                                                {{ $file->name ?? $file->filename }}
                                            </a>
                                        </div>
                                    @else
                                        @foreach($file as $file_once)
                                                <div class="font-13 p-2 px-3">
                                                    <a href="{{ $file_once->url }}" target="_blank" class="fs-3">
                                                        <x-ui.icon.files ext="{{$file_once->extension}}" class="me-1"></x-ui.icon.files>
                                                        {{ $file_once->name ?? $file_once->filename }}
                                                    </a>
                                                </div>
                                        @endforeach
                                    @endif
                                @endforeach

                                @if($chr == 'listeners')
                                    <div class="font-13 p-2 px-3">
                                        <a href="{{ route('education_application.files_clients', $education_application) }}"
                                           target="_blank" class="fs-3">
                                            <x-ui.icon.files ext="pdf" class="me-1"></x-ui.icon.files>
                                            Заявка клиента
                                        </a>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif



                @if($evaluation->isNeedDiscountApprove())
                    <x-evaluation.detail.agreement_discount_block
                        :evaluation="$evaluation"></x-evaluation.detail.agreement_discount_block>
                @elseif($evaluation->hasApprove())
                    <x-evaluation.detail.agreement_block
                        :evaluation="$evaluation"></x-evaluation.detail.agreement_block>
                @endif

                @if($evaluation->hasFinalApprove())
                    <x-evaluation.detail.agreement_final_block
                        :evaluation="$evaluation"></x-evaluation.detail.agreement_final_block>
                @endif

                <x-evaluation.detail.status_block :evaluation="$evaluation"></x-evaluation.detail.status_block>


            </div>
            <div class="col-12 col-md-6 col-lg-5 col-xl-7">
                @foreach($evaluation->objects as $object)
                    <div class="card object mb-2">
                        <div
                            class="card-body d-flex justify-content-between align-items-center flex-column flex-md-row">
                            <h4 class="card-title mb-0">
                                <x-ui.icon.regular icon="fa-industry" class="me-2"></x-ui.icon.regular>
                                {{ $object->name }}
                            </h4>

                            <div class="d-flex justify-content-between align-items-center mt-1 mt-md-0">
                                <div class="alert text-primary alert-light-primary p-1 ps-2 pe-2 m-0" role="alert">
                                    {!! $object->lab_object?->chain_name !!}
                                </div>
                            </div>
                        </div>
                        @foreach($object->addresses as $address)
                            <div class="address">
                                <div
                                    class="card-body title pt-3 pb-3  d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <x-ui.icon.solid icon="fa-location-dot" class="ms-2 me-2"></x-ui.icon.solid>
                                        <span>{{ $address->address }}</span>
                                    </h5>

                                </div>
                                <div class="card-body pb-1">
                                    @foreach($address->points as $point)
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <span class="text-danger point_name_pad d-flex align-items-center">
                                                      <x-ui.icon.solid icon="fa-map-pin"
                                                                       class="ms-4 me-2"></x-ui.icon.solid>
                                                    <span class="point_name">@if(!empty($point->number))
                                                            <span
                                                                class="mb-1 badge bg-danger mr-1">{{ $point->number }}</span>
                                                        @endif
                                                        {{ $point->name }}</span>
                                                </span>
                                            </div>
                                            <div class="col-12 ps-5">
                                                <div class="card-table ms-2 mt-2 font-14">
                                                    @foreach($point->measures as $measure)
                                                        <div class="tr">
                                                            <span class="th">
                                                                {{$measure->measure->name }}
                                                                @if(!empty($measure->comment))
                                                                    <span class="ms-2">({{ $measure->comment }})</span>
                                                                @endif
                                                            </span>
                                                            <span class="td flex-grow-1">
                                                                <nobr>
                                                                    @if($measure->cost !== $measure->cost_real)
                                                                        <x-ui.icon.solid icon="fa-triangle-exclamation"
                                                                                         class="text-warning cursor-help"
                                                                                         title="Цена отличается от справочника ({{ $measure->cost_real }} р.)"></x-ui.icon.solid>
                                                                    @endif
                                                                    {{ _cost($measure->cost) }}
                                                                    <x-ui.icon.thin icon="fa-xmark"
                                                                                    class="ms-1 me-1 font-10"></x-ui.icon.thin>
                                                                    {{ $measure->count }}
                                                                    <x-ui.icon.thin icon="fa-equals"
                                                                                    class="ms-1 me-1 font-10"></x-ui.icon.thin>
                                                                    <span style="min-width: 50px"
                                                                          class="d-inline-flex align-items-center justify-content-end">
                                                                        <strong>{{ _cost($measure->cost_total) }}</strong>
                                                                        <x-ui.icon.solid icon="fa-ruble-sign"
                                                                                         class="font-12 ms-1"></x-ui.icon.solid>
                                                                    </span>
                                                               </nobr>
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    <div class="row mb-3">
                                        <div class="col-12 ps-4">
                                            <div class="card-table ms-3 mt-2 font-14">
                                                @if(!empty($address->expanses))
                                                    <div class="tr">
                                                        <span class="th">
                                                            <x-ui.icon.light icon="fa-suitcase-rolling"
                                                                             class="me-1"></x-ui.icon.light>
                                                            <span>Командировочные расходы</span>
                                                        </span>
                                                        <span class="td">
                                                            <span style="min-width: 50px"
                                                                  class="d-inline-flex align-items-center justify-content-end">
                                                                <strong>{{ _cost($address->expanses) }}</strong>
                                                                <x-ui.icon.solid icon="fa-ruble-sign"
                                                                                 class="font-12 ms-1"></x-ui.icon.solid>
                                                            </span>
                                                        </span>
                                                    </div>
                                                @endif

                                                @if(!empty($address->transport))
                                                    <div class="tr">
                                                        <span class="th">
                                                            <x-ui.icon.light icon="fa-plane-up"
                                                                             style="margin-right: 2px"></x-ui.icon.light>
                                                            <span>Транспортные расходы</span>
                                                        </span>
                                                        <span class="td">
                                                            <span style="min-width: 50px"
                                                                  class="d-inline-flex align-items-center justify-content-end">
                                                                <strong>{{ _cost($address->transport) }}</strong>
                                                                <x-ui.icon.solid icon="fa-ruble-sign"
                                                                                 class="font-12 ms-1"></x-ui.icon.solid>
                                                            </span>
                                                        </span>
                                                    </div>
                                                @endif

                                                @if(!empty($address->specialist))
                                                    <div class="tr">
                                                        <span class="th">
                                                            <x-ui.icon.light icon="fa-car"
                                                                             style="margin-right: 5px"></x-ui.icon.light>
                                                            <span>Выезд специалиста</span>
                                                        </span>

                                                        <span class="td flex-grow-1">
                                                                <nobr>
                                                                    {{ _cost($address->specialist['cost']) }}
                                                                    <x-ui.icon.thin icon="fa-xmark"
                                                                                    class="ms-1 me-1 font-10"></x-ui.icon.thin>
                                                                    {{ $address->specialist['count']}}
                                                                    <x-ui.icon.thin icon="fa-equals"
                                                                                    class="ms-1 me-1 font-10"></x-ui.icon.thin>
                                                                    <span style="min-width: 50px"
                                                                          class="d-inline-flex align-items-center justify-content-end">
                                                                        <strong>{{ _cost($address->specialist['total']) }}</strong>
                                                                        <x-ui.icon.solid icon="fa-ruble-sign"
                                                                                         class="font-12 ms-1"></x-ui.icon.solid>
                                                                    </span>
                                                               </nobr>
                                                            </span>


                                                    </div>
                                                @endif

                                                <div class="col-12 text-end mt-3 font-16 text-danger">
                                                    <strong>= {{ _cost($address->cost_total) }}</strong>
                                                    <x-ui.icon.solid icon="fa-ruble-sign"
                                                                     class="font-12"></x-ui.icon.solid>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-end">
                        <div class="mb-1 badge bg-light-secondary text-secondary mt-1  font-18 mt-2 mb-4"
                             type="secondary">
                            = {{ _cost($object->cost_total) }}
                            <x-ui.icon.light icon="fa-ruble-sign" class="font-14 ms-1"></x-ui.icon.light>
                        </div>
                    </div>
                @endforeach

                @if($evaluation->discount > 0)
                    <div
                        class="d-flex text-danger justify-content-end align-items-center font-24 me-1 mb-1">
                        <strong>&ndash; {{ _cost($evaluation->discount) }}</strong>
                        <x-ui.icon.solid icon="fa-ruble-sign" class="font-20 ms-1"></x-ui.icon.solid>
                    </div>
                @endif
                <div
                    class="d-flex justify-content-end align-items-center font-24 me-1 border-top border-secondary pt-3">
                    <strong>= {{ _cost($evaluation->cost_total) }}</strong>
                    <x-ui.icon.solid icon="fa-ruble-sign" class="font-20 ms-1"></x-ui.icon.solid>
                </div>

            </div>
        </div>
    </div>
    @if($evaluation->canRemake())
        <div id="modal-recreate" class="modal fade" tabindex="-1" aria-labelledby="danger-header-modalLab el"
             aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class=" modal-header modal-colored-header bg-success text-white">
                        <h4 class="modal-title" id="danger-header-modalLabel">Создание копии</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Отменить"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">Вы уверены, что хотите создать копию текущего приложения?</div>
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
    <script src="/dist/modules/jquery-ui/jquery-ui.min.js"></script>
    <script src="/assets/libs/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="/assets/libs/bootstrap-datepicker/dist/locales/bootstrap-datepicker.ru.min.js"></script>
    <script src="/assets/extra-libs/summernote/summernote-lite.min.js"></script>
    <script src="/assets/extra-libs/treeview/dist/bootstrap-treeview.min.js"></script>
    <script src="/dist/modules/dropzone/dropzone-min.js"></script>


    <script>
        @if($evaluation->canRemake())
            $(document).ready(function () {
                $("#order_recreate").on("click", function () {
                    $("#modal-recreate").modal('show');
                });

                $("#btn_recreate_confirm").on("click", function() {
                    recreate();
                });
            });

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
                url: "{{ route('api.evaluation.recreate', [$evaluation, '_token' => auth()->user()->ajax_token ]) }}",
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
                    toastr.error("Не получилось пересоздать приложение", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                    window.comment_lock = false;
                }
            });
        }

        @endif

        @if($evaluation->canDoAgreementDecision())
        function methodist_cancel() {
            $("#modal-methodist-cancel").modal('show');
        }

        function methodist_cancel_submit() {
            var comment = $("#methodist_cancel textarea").val();
            if (!comment) {
                alert('Напишите сопроводительный комментарий для автора приложения!');
                return false;
            }
            block_elem = $("body");
            $(block_elem).block(block_default);

            $.ajax({
                url: "{{ route('api.evaluation.agreement_cancel', [$evaluation, '_token' => auth()->user()->ajax_token ]) }}",
                type: "POST",
                data: $("#methodist_cancel").serialize(),
                dataType: "json",
                success: function (response) {
                    if (response.status == 'success')
                        location.reload();
                    $(block_elem).unblock();
                },
                error: function () {
                    toastr.error("Не получилось вернуть приложение на доработку", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });
        }

        $(document).ready(function () {
            $("#btn_methodst_cancel_confirm").on("click", function () {
                methodist_cancel_submit();
            });

        });


        function methodist_accept() {
            $("#modal-methodist-accept").modal('show');
        }

        function methodist_accept_submit() {
            block_elem = $("body");
            $(block_elem).block(block_default);

            $.ajax({
                url: "{{ route('api.evaluation.agreement_accept', [$evaluation, '_token' => auth()->user()->ajax_token ]) }}",
                type: "POST",
                dataType: "json",
                success: function (response) {
                    if (response.status == 'success') {
                        location.reload();
                    } else {
                        toastr.error(response.message, "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                    $(block_elem).unblock();
                },
                error: function () {
                    toastr.error("Не получилось взять приложение в работу", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });
        }

        $("#btn_methodst_accept_confirm").on("click", function () {
            methodist_accept_submit();
        });
        @endif
    </script>
@endsection


