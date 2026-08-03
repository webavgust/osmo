@if($evaluation->canView())
    <div class="mt-2 mb-2 status_block d-flex flex-column flex-grow-1">
        @if($evaluation->canControl())
            <div class="d-flex flex-column ">
                <x-ui.button.default btn_type="success" class="flex-grow-1 mb-2"
                                     onclick="$('#modal-aprove').modal('show');">
                    <x-ui.icon.duotone icon="fa-handshake" class="me-1"></x-ui.icon.duotone>
                    Согласовано с клиентом
                </x-ui.button.default>

                <div class="d-flex justify-content-between align-items-center">

                    <x-ui.button.default btn_type="warning" class="flex-grow-1"
                                         onclick="$('#modal-discount').modal('show');">
                        <x-ui.icon.duotone icon="fa-percent" class="me-1"></x-ui.icon.duotone>
                        Запросить скидку
                    </x-ui.button.default>

                    <x-ui.button.default btn_type="danger" class="ms-2"
                                         onclick="$('#modal-cancel').modal('show');">
                        <x-ui.icon.duotone icon="fa-xmark" class="me-1"></x-ui.icon.duotone>
                        Отказ
                    </x-ui.button.default>
                </div>
            </div>


            <div id="modal-aprove" class="modal fade" tabindex="-1" aria-labelledby="danger-header-modalLab el"
                 aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class=" modal-header modal-colored-header bg-light-secondary text-dark-warning">
                            <h4 class="modal-title text-dark" id="danger-header-modalLabel">Согласование с
                                заказчиком</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Не сохранять"></button>
                        </div>
                        <div class="modal-body">

                            <div>Вы действительно хотите поставить отметку о согласовании с заказчиком и отправить приложение на проверку?</div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <small id="textHelp" class="form-text text-muted">
                                        <label for="aprove_comment">Сопроводительный комментарий</label>
                                        <span class="text-danger">*</span>
                                    </small>

                                    <textarea class="form-control" rows="3" placeholder=""
                                              id="aprove_comment"></textarea>
                                </div>
                            </div>
                        </div>


                        <div class="modal-footer">
                            <button type="button" class="btn btn-light text-secondary" data-bs-dismiss="modal">
                                Отменить
                            </button>
                            <button type="button" id="btn_status_confirm" class="
                                btn btn-light-success text-success
                          font-weight-medium
                              " onclick="javascript:aproveСonfirm()">
                                ОТПРАВИТЬ НА ПРОВЕРКУ
                            </button>
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>

            <div id="modal-discount" class="modal fade" tabindex="-1" aria-labelledby="danger-header-modalLab el"
                 aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class=" modal-header modal-colored-header bg-light-secondary text-dark-warning">
                            <h4 class="modal-title text-dark" id="danger-header-modalLabel">Запрос скидки</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Не сохранять"></button>
                        </div>
                        <div class="modal-body">
                            <div class="align-items-center d-flex justify-content-start mb-2" style="max-width: 300px">
                                <label class=" control-label text-start font-weight-medium text-nowrap me-2">Размер скидки:</label>
                                <div class="input-group flex-grow-0">
                                    <input type="text" class="form-control" placeholder="" aria-label="" id="discount_inp">
                                    <span class="input-group-text">
                                        <x-ui.icon.regular icon="fa-ruble-sign"></x-ui.icon.regular>
                                    </span>
                                </div>
                            </div>
                            <span>Максимальный размер скидки:
                                <mark><code>{{ tools()->cost_normalize($evaluation->cost_total + $evaluation->discount) }} ₽</code></mark>
                            </span>

                            {{--                            <div>Вы действительно хотите запросить скидку?</div>--}}
                        </div>


                        <div class="modal-footer">
                            <button type="button" class="btn btn-light text-secondary" data-bs-dismiss="modal">
                                Отменить
                            </button>
                            <button type="button" id="btn_status_confirm"   class="
                                btn btn-light-warning text-warning
                          font-weight-medium
                              " onclick="javascript:discountСonfirm()">
                                ЗАПРОСИТЬ СКИДКУ
                            </button>
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>

            <div id="modal-cancel" class="modal fade" tabindex="-1" aria-labelledby="danger-header-modalLab el"
                 aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class=" modal-header modal-colored-header bg-light-danger">
                            <h4 class="modal-title text-danger" id="danger-header-modalLabel">Отмена приложения</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Не сохранять"></button>
                        </div>
                        <div class="modal-body">
                            <div>Вы действительно хотите <strong>ОТМЕНИТЬ</strong> всю работу по приложению?</div>

                        </div>


                        <div class="modal-footer">
                            <button type="button" class="btn btn-light text-secondary" data-bs-dismiss="modal">
                                Отменить
                            </button>
                            <button type="button" id="btn_status_confirm"   class="
                                btn btn-light-danger text-danger
                          font-weight-medium
                              " onclick="javascript:cancelСonfirm()">
                                ОТМЕНИТЬ ПРИЛОЖЕНИЕ
                            </button>
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>


            <script>
                function aproveСonfirm() {
                    if (!$("#aprove_comment").val()) {
                        alert('Заполните поле комментарий!');
                        return false;
                    }
                    $("body").block(block_default);
                    $.ajax({
                        url: "{{ route('api.evaluation.approve', $evaluation) }}?_token={{ _token() }}",
                        type: "POST",
                        dataType: "json",
                        data: {
                            comment: $("#aprove_comment").val()
                        },
                        success: function (json) {
                            $("body").unblock();
                            if (json.result == 'success') {
                                location.reload();
                            } else {
                                toastr.error("Не получилось отправить подтверждение согласования", "Это провал!", {
                                    progressBar: true,
                                    "timeOut": 3000,
                                });
                            }
                        },
                        error: function () {
                            $("body").unblock();
                            toastr.error("Не получилось отправить подтверждение согласования", "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                        }
                    });
                }

                function cancelСonfirm() {

                    $("body").block(block_default);
                    $.ajax({
                        url: "{{ route('api.evaluation.cancel', $evaluation) }}?_token={{ _token() }}",
                        type: "POST",
                        dataType: "json",
                        success: function (json) {
                            $("body").unblock();
                            if (json.result == 'success') {
                                location.reload();
                            } else {
                                toastr.error("Не получилось отменить приложение", "Это провал!", {
                                    progressBar: true,
                                    "timeOut": 3000,
                                });
                            }
                        },
                        error: function () {
                            $("body").unblock();
                            toastr.error("Не получилось отменить приложение", "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                        }
                    });
                }

                function discountСonfirm() {
                    if (!$("#discount_inp").val() || $("#discount_inp").val() - 0 < 1 || $("#discount_inp").val() > {{ $evaluation->cost_total }}) {

                        alert('Укажите правильную скидку');
                        return false;
                    }
                    $("body").block(block_default);
                    $.ajax({
                        url: "{{ route('api.evaluation.discount_agreement', $evaluation) }}?_token={{ _token() }}",
                        type: "POST",
                        dataType: "json",
                        data: {
                            discount: $("#discount_inp").val()
                        },
                        success: function (json) {
                            $("body").unblock();
                            if (json.result == 'success') {
                                location.reload();
                            } else {
                                toastr.error("Не получилось отправить запрос на скидку", "Это провал!", {
                                    progressBar: true,
                                    "timeOut": 3000,
                                });
                            }
                        },
                        error: function () {
                            $("body").unblock();
                            toastr.error("Не получилось отправить запрос на скидку", "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                        }
                    });
                }
            </script>
        @endif

        @if(0 && $evaluation->canTransform())
            <x-ui.button.default btn_type="warning flex-grow-1 mb-1" onclick="javascript:transform();">
                <x-ui.icon.regular icon="fa-diagram-subtask" class="me-1"></x-ui.icon.regular>
                Создать ТЗ
            </x-ui.button.default>
            <script>
                function transform() {
                    if (!confirm('Вы действительно хотите превратить приложение в ТЗ?'))
                        return false;

                    $("body").block(block_default);
                    $.ajax({
                        url: "{{ route('api.evaluation.transform', $evaluation) }}?_token={{ _token() }}",
                        type: "POST",
                        dataType: "json",
                        success: function (json) {
                            $("body").unblock();
                            if (json.result == 'success') {
                                location.replace(json.redirect);
                            } else {
                                toastr.error("Не получилось превратить приложение в ТЗ", "Это провал!", {
                                    progressBar: true,
                                    "timeOut": 3000,
                                });
                            }
                        },
                        error: function () {
                            $("body").unblock();
                            toastr.error("Не получилось превратить приложение в ТЗ", "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                        }
                    });
                }
            </script>
        @endif

        @if($evaluation->canEdit())
            <x-ui.a.default href="{{ route('evaluation.edit', $evaluation) }}" btn_type="warning flex-grow-1 mb-1">
                <x-ui.icon.regular icon="fa-edit" class="me-1"></x-ui.icon.regular>
                Редактировать
            </x-ui.a.default>
        @endif

        @if($evaluation->canRemake())
            <x-ui.a.default btn_type="success flex-grow-1 mb-1" id="order_recreate">
                <x-ui.icon.regular icon="fa-arrows-rotate" class="me-1"></x-ui.icon.regular>
                Пересоздать
            </x-ui.a.default>
        @endif

        @if($evaluation->canSendToAgreement())
            <x-ui.a.default btn_type="info flex-grow-1 mb-1" onclick="javascript:sendToAgreement()">
                <x-ui.icon.regular icon="fa-magnifying-glass" class="me-1"></x-ui.icon.regular>
                Отправить на проверку
            </x-ui.a.default>

            <div id="modal-send-agreement" class="modal fade" tabindex="-1" aria-labelledby="danger-header-modalLab el"
                 aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class=" modal-header modal-colored-header bg-light-secondary text-dark-warning">
                            <h4 class="modal-title text-dark" id="danger-header-modalLabel">Отправить на проверку</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Не сохранять"></button>
                        </div>
                        <div class="modal-body">
                            Вы действительно хотите отправить приложение на проверку?
                        </div>


                        <div class="modal-footer">
                            <button type="button" class="btn btn-light text-secondary" data-bs-dismiss="modal">
                                Не отправлять
                            </button>
                            <button type="button" id="btn_status_confirm" data-bs-dismiss="modal" class="
                                btn btn-light-success text-success
                          font-weight-medium
                              " onclick="javascript:sendToAgreementConfirm()">
                                ОТПРАВИТЬ
                            </button>
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>
        @endif
    </div>
@endif


<script>
    @if($evaluation->canSendToAgreement())
    function sendToAgreement() {
        $("#modal-send-agreement").modal('show');
    }

    function sendToAgreementConfirm() {
        var block_elem = $("body");
        block_elem.block(block_default);
        $.ajax({
            url: '{{ route('api.evaluation.agreement.send', [$evaluation, '_token' => auth()->user()->ajax_token ]) }}',
            method: "POST",
            dataType: "json",
            success: function (response) {
                block_elem.unblock();
                if (response.result == 'success') {
                    location.reload();
                } else {
                    toastr.error("Не получилось отправить на проверку", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                }
            },
            error: function () {
                toastr.error("Не получилось отправить на проверку", "Это провал!", {
                    progressBar: true,
                    "timeOut": 3000,
                });
                block_elem.unblock();
            }
        });
    }
    @endif
</script>
