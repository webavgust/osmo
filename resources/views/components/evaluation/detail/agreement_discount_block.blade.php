<div class="card agreement_block mb-1">
    <div class="card-body d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Согласование скидки</h4>
        <x-ui.badge.default type="warning">{{ tools()->cost_normalize($evaluation->discount_agreement->discount) }} ₽</x-ui.badge.default>
    </div>
    <div class="card-body">
        <div class="card-table">
            @foreach($evaluation->discount_agreement->users as $user)
                <x-evaluation.detail.agreement_discount_row :evaluation="$evaluation" :loop="$loop" :user="$user"></x-evaluation.detail.agreement_discount_row>
            @endforeach
        </div>
    </div>
</div>

<div id="modal-discount_confirm" class="modal fade" tabindex="-1" aria-labelledby="danger-header-modalLab el"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class=" modal-header modal-colored-header bg-success text-white">
                <h4 class="modal-title" id="danger-header-modalLabel">Согласование скидки</h4>
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
                    id="btn_discount_agreement_confirm"
                    onclick="javascript:discount_agreement_decision('confirm')"
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

<div id="modal-discount_decline" class="modal fade" tabindex="-1" aria-labelledby="danger-header-modalLab el"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class=" modal-header modal-colored-header bg-danger text-white">
                <h4 class="modal-title" id="danger-header-modalLabel">Согласование скидки</h4>
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
                    id="btn_discount_agreement_decline"
                    onclick="javascript:discount_agreement_decision('decline')"
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

<script>
    function discount_agreement_confirm(user_id) {
        window.user_id = user_id;
        $("#modal-discount_confirm").modal('show');
    }

    function discount_agreement_decline(user_id) {
        window.user_id = user_id;
        $("#modal-discount_decline").modal('show');
    }

    function discount_agreement_decision(decision) {
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

        if (decision == 'confirm') {
            comment = $("#modal-confirm textarea").val();
        } else {
            comment = $("#modal-decline textarea").val();
        }
        $.ajax({
            url: "{{ route('api.evaluation.discount_agree_decision', [$evaluation, '_token' => auth()->user()->ajax_token ]) }}",
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
</script>
