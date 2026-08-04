@extends('components.box.box-large')

@section('body')
    <form id="proposal_status_form">
        <input type="hidden" name="status" id="status_value" value="{{ $proposal->status ?? 'in_work' }}" />

        {{-- Выбор статуса --}}
        <div class="d-flex flex-wrap gap-2 mb-5">
            @foreach($statuses as $code => $status)
                <label class="btn btn-outline btn-outline-dashed btn-active-light-{{ $status['color'] }} d-flex align-items-center p-3 status-option @if(($proposal->status ?? 'in_work') === $code) active @endif"
                       data-status="{{ $code }}"
                       data-need-reason="{{ !empty($status['need_reason']) ? 1 : 0 }}">
                    <i class="fa-light {{ $status['icon'] }} fs-3 me-3 text-{{ $status['color'] }}"></i>
                    <span class="fw-semibold">{{ $status['label'] }}</span>
                </label>
            @endforeach
        </div>

        {{-- Причина: показывается только для проигрыша / заморозки / отмены --}}
        <div id="reason_block" class="d-none">
            <div class="separator separator-dashed my-4"></div>

            <label class="form-label fw-semibold required">Причина</label>
            <div class="row g-2 mb-4">
                @foreach($reasons as $code => $reason)
                    <div class="col-6 col-md-4">
                        <label class="btn btn-outline btn-outline-dashed btn-active-light-primary w-100 text-start p-3 reason-option @if($proposal->status_reason === $code) active @endif"
                               data-reason="{{ $code }}"
                               title="{{ $reason['hint'] }}">
                            <span class="fw-semibold d-block">{{ $reason['label'] }}</span>
                            <span class="fs-8 text-muted">{{ $reason['hint'] }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
            <input type="hidden" name="reason" id="reason_value" value="{{ $proposal->status_reason }}" />
        </div>

        {{-- Комментарий --}}
        <div class="mb-2">
            <label class="form-label fw-semibold">Комментарий</label>
            <textarea name="comment" class="form-control form-control-solid" rows="2"
                      maxlength="500"
                      placeholder="Что произошло — своими словами">{{ $proposal->status_comment }}</textarea>
        </div>

        @if($proposal->status_changed_at)
            <div class="text-muted fs-8">
                Последнее изменение: {{ $proposal->status_changed_at->format('d.m.Y H:i') }}
                @if($proposal->status_author)
                    — {{ $proposal->status_author->name }}
                @endif
            </div>
        @endif
    </form>
@endsection

@section('footer')
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
    <button type="button" class="btn btn-primary" onclick="javascript:proposal_status_save();">
        <i class="fa-light fa-floppy-disk fs-5 me-2"></i>
        Сохранить
    </button>
@endsection

@section('modal')
<script>
    (function () {
        function syncReasonBlock() {
            var active = $(".status-option.active");
            var need = active.data("need-reason") == 1;

            $("#reason_block").toggleClass("d-none", !need);
        }

        $(".status-option").on("click", function () {
            $(".status-option").removeClass("active");
            $(this).addClass("active");
            $("#status_value").val($(this).data("status"));
            syncReasonBlock();
        });

        $(".reason-option").on("click", function () {
            $(".reason-option").removeClass("active");
            $(this).addClass("active");
            $("#reason_value").val($(this).data("reason"));
        });

        syncReasonBlock();
    })();

    function proposal_status_save() {
        var data = {
            status: $("#status_value").val(),
            reason: $("#reason_value").val(),
            comment: $("[name='comment']").val(),
            _token: csrf_token()
        };

        if ($(".status-option.active").data("need-reason") == 1 && !data.reason) {
            toastr.error("Укажите причину", "Не хватает данных", {
                progressBar: true, timeOut: 3000
            });
            return;
        }

        body_block();

        $.ajax({
            url: "{{ route('api.proposal.status', [$proposal, $proposal->iteration]) }}",
            type: "POST",
            data: data,
            dataType: "json",
            success: function (response) {
                body_unblock();

                if (response.result !== 'success') {
                    toastr.error(response.message ?? "Не получилось сохранить", "Это провал!", {
                        progressBar: true, timeOut: 4000
                    });
                    return;
                }

                box_close();
                toastr.success("Статус обновлён", "Это успех!", {
                    progressBar: true, timeOut: 2000
                });
                setTimeout(function () { location.reload(); }, 600);
            },
            error: function () {
                body_unblock();
                toastr.error("Не получилось сохранить", "Это провал!", {
                    progressBar: true, timeOut: 3000
                });
            }
        });
    }
</script>
@endsection
