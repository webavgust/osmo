@extends('components.box.box-large')

{{-- patch v44: копия старого попапа под Metronic — у «Проиграно» несколько причин --}}

@section('body')
    @php
        // сохранённые причины в порядке выбора; первая — основная
        $selected = array_map(fn($case) => $case->value, $proposal->reasons_enum);
    @endphp

    <form id="proposal_status_form">
        <input type="hidden" name="status" id="status_value" value="{{ $proposal->status ?? 'in_work' }}" />

        {{-- Выбор статуса --}}
        <div class="d-flex flex-wrap gap-2 mb-5">
            @foreach($statuses as $code => $status)
                @php
                    // secondary в Metronic почти сливается со светлым фоном —
                    // для иконки и активного состояния берём тёмный тон
                    $tone = $status['color'] === 'secondary' ? 'dark' : $status['color'];
                @endphp
                <label class="btn btn-outline btn-outline-dashed btn-active-light-{{ $tone }} d-flex align-items-center px-4 py-3 status-option @if(($proposal->status ?? 'in_work') === $code) active @endif"
                       data-status="{{ $code }}"
                       data-need-reason="{{ !empty($status['need_reason']) ? 1 : 0 }}">
                    <i class="fa-light {{ $status['icon'] }} fs-5 me-2 text-{{ $tone }}"></i>
                    <span class="fs-7 fw-semibold text-gray-800">{{ $status['label'] }}</span>
                </label>
            @endforeach
        </div>

        {{-- Причины: только для проигрыша («Заморожено» и «Отменено» — тоже причины), можно несколько --}}
        <div id="reason_block" class="d-none">
            <div class="separator separator-dashed my-4"></div>

            <div class="d-flex justify-content-between align-items-baseline mb-3">
                <label class="form-label fs-6 fw-semibold required mb-0">Причины</label>
                <span class="fs-8 text-muted">Можно выбрать несколько — первая отмеченная станет основной</span>
            </div>

            <div class="row g-2 mb-4">
                @foreach($reasons as $code => $reason)
                    @php
                        $tone = in_array($reason['color'], ['secondary', 'light', 'white', ''], true) ? 'dark' : $reason['color'];
                        $order = array_search($code, $selected, true);
                    @endphp
                    <div class="col-6 col-md-4">
                        <label class="btn btn-outline btn-outline-dashed btn-active-light-primary w-100 h-100 text-start px-3 py-3 d-flex align-items-start gap-3 reason-option @if($order !== false) active @endif"
                               data-reason="{{ $code }}"
                               title="{{ $reason['hint'] }}">
                            <span class="form-check form-check-custom form-check-sm form-check-solid flex-shrink-0 mt-1">
                                <input class="form-check-input reason-check" type="checkbox" value="{{ $code }}"
                                       tabindex="-1" @checked($order !== false) />
                            </span>
                            <span class="flex-grow-1 min-w-0">
                                <span class="d-flex align-items-center gap-2">
                                    <span class="bullet bullet-dot bg-{{ $tone }} flex-shrink-0"></span>
                                    <span class="fs-7 fw-semibold text-gray-800">{{ $reason['label'] }}</span>
                                    <span class="badge badge-light-primary fs-9 ms-auto reason-order @if($order === false) d-none @endif">{{ $order === false ? '' : $order + 1 }}</span>
                                </span>
                                <span class="d-block fs-8 text-muted mt-1">{{ $reason['hint'] }}</span>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Комментарий --}}
        <div class="mb-2">
            <label class="form-label fs-6 fw-semibold">Комментарий</label>
            <textarea name="comment" class="form-control form-control-solid fs-7" rows="2"
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
    // причины в порядке выбора: первая — основная (proposals.status_reason)
    var proposal_status_reasons = @json($selected);

    (function () {
        function syncReasonBlock() {
            var need = $(".status-option.active").data("need-reason") == 1;

            $("#reason_block").toggleClass("d-none", !need);
        }

        // номера порядка на плитках
        function syncReasonOrder() {
            $(".reason-option").each(function () {
                var pos = proposal_status_reasons.indexOf($(this).data("reason"));

                $(this).toggleClass("active", pos !== -1);
                $(this).find(".reason-check").prop("checked", pos !== -1);
                $(this).find(".reason-order").toggleClass("d-none", pos === -1).text(pos === -1 ? "" : pos + 1);
            });
        }

        $(".status-option").on("click", function () {
            $(".status-option").removeClass("active");
            $(this).addClass("active");
            $("#status_value").val($(this).data("status"));
            syncReasonBlock();
        });

        // плитка целиком переключает причину; клик по самому чекбоксу не должен срабатывать дважды
        $(".reason-option").on("click", function (e) {
            e.preventDefault();

            var code = $(this).data("reason");
            var pos = proposal_status_reasons.indexOf(code);

            if (pos === -1) {
                proposal_status_reasons.push(code);
            } else {
                proposal_status_reasons.splice(pos, 1);
            }

            syncReasonOrder();
        });

        syncReasonBlock();
        syncReasonOrder();
    })();

    function proposal_status_save() {
        var need = $(".status-option.active").data("need-reason") == 1;
        var data = {
            status: $("#status_value").val(),
            reasons: need ? proposal_status_reasons : [],
            comment: $("[name='comment']").val(),
            _token: csrf_token()
        };

        if (need && !data.reasons.length) {
            toastr.error("Отметьте хотя бы одну причину", "Не хватает данных", {
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
