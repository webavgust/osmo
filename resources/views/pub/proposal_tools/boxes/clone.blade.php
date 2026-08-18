@extends('components.box.box-static-large')

@section('title')
    Клонирование КП
@endsection

@section('body')
    <div class="alert alert-light-primary d-flex align-items-center mb-5">
        <i class="fa-light fa-circle-info fs-2 text-primary me-4"></i>
        <div class="fs-7">
            Копия станет <b>отдельным КП</b> с первой редакцией: перенесём платформу, сценарии,
            ПО, работы, скидки и дополнительные платежи. Статус, сделки Битрикса, договоры,
            спецификации и платежи не переносятся.
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <label class="form-label fw-semibold">Название <span class="text-danger">*</span></label>
            <input type="text" id="clone_name" class="form-control form-control-solid"
                   value="{{ $proposal->name }} (копия)" />
        </div>

        <div class="col-6 col-lg-4">
            <label class="form-label fw-semibold">Номер <span class="text-danger">*</span></label>
            <input type="text" id="clone_number" class="form-control form-control-solid"
                   value="{{ $proposal->number }}-К" />
        </div>

        <div class="col-6 col-lg-4">
            <label class="form-label fw-semibold">Дата</label>
            <input type="date" id="clone_date" class="form-control form-control-solid"
                   value="{{ now()->format('Y-m-d') }}" />
        </div>

        <div class="col-12 col-lg-8">
            <label class="form-label fw-semibold">Компания</label>
            <select id="clone_company" class="form-select form-select-solid clone-select2">
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected($proposal->company_id == $company->id)>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12 col-lg-6">
            <label class="form-label fw-semibold">Партнёр</label>
            <select id="clone_partner" class="form-select form-select-solid clone-select2">
                @foreach($partners as $partner)
                    <option value="{{ $partner->id }}" @selected($proposal->partner_id == $partner->id)>
                        {{ $partner->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12 col-lg-6">
            <label class="form-label fw-semibold">Менеджер</label>
            <select id="clone_manager" class="form-select form-select-solid clone-select2">
                @foreach($managers as $manager)
                    <option value="{{ $manager->id }}" @selected($proposal->manager_id == $manager->id)>
                        {{ $manager->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
@endsection

@section('footer')
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
    <button type="button" class="btn btn-primary" onclick="javascript:clone_save();">
        <i class="fa-light fa-clone fs-5 me-2"></i>Склонировать
    </button>
@endsection

@section('modal')
<script>
    $(".clone-select2").select2({
        dropdownParent: $(".modal .modal-content"),
        width: '100%'
    });

    function clone_save() {
        var name = $("#clone_name").val();
        var number = $("#clone_number").val();

        if (!name || !number) {
            toastr.error("Название и номер обязательны", "Не хватает данных", {
                progressBar: true, timeOut: 3000
            });
            return;
        }

        body_block();

        $.ajax({
            url: "{{ route('api.proposal_tools.clone', [$proposal, $proposal->iteration]) }}",
            type: "POST",
            data: {
                name: name,
                number: number,
                sended_at: $("#clone_date").val(),
                company_id: $("#clone_company").val(),
                partner_id: $("#clone_partner").val(),
                manager_id: $("#clone_manager").val(),
                _token: csrf_token()
            },
            dataType: "json",
            success: function (response) {
                body_unblock();

                if (response.result !== 'success') {
                    toastr.error(response.message ?? "Не получилось склонировать", "Это провал!", {
                        progressBar: true, timeOut: 5000
                    });
                    return;
                }

                toastr.success("КП склонировано", "Это успех!", { progressBar: true, timeOut: 2000 });
                setTimeout(function () { window.location = response.url; }, 500);
            },
            error: function () {
                body_unblock();
                toastr.error("Не получилось склонировать", "Это провал!", {
                    progressBar: true, timeOut: 3000
                });
            }
        });
    }
</script>
@endsection
