@extends('components.box.box-static-large')

@section('body')
    <style>
        .select2-selection__rendered {
            line-height: 38px !important;
            font-weight: 400;
            color: #54667a !important;
        }

        .select2-container .select2-selection--single {
            height: 38px !important;
        }

        .select2-selection__arrow {
            height: 37px !important;
        }
        .select2-selection__rendered {
            line-height: 38px !important;
            font-weight: 400;
            color: #54667a!important;
        }
        .select2-container .select2-selection--single {
            height: 38px !important;
        }
        .select2-selection__arrow {
            height: 37px !important;
        }
        .select2-container--default .select2-selection--multiple {
            border-color: #e9ecef;
        }

        #samplers_pad .select2 {
            width: 100%!important;
        }
    </style>
    <form class="form-horizontal" method="POST" id="create">
        @csrf

        <input type="hidden" name="action" value="create">
        <div class="card-body">
            <div class="mb-3 row">
                <label for="fdate" class="col-sm-3 text-end control-label col-form-label">Плановая дата
                    <span class="text-danger">*</span>
                </label>
                <div class="col-sm-9">
                    <input type="date" name="date" class="form-control" id="fdate" value="{{ now()->format('Y-m-d') }}" style="width: 140px">
                </div>
            </div>
            <div class="mb-3 row" id="order_task_pad">
                <label for="lname" class="col-sm-3 text-end control-label col-form-label">ТЗ, заказ, заказчик
                    <span class="text-danger">*</span>
                </label>
                <div class="col-sm-9">
                    <x-ui.select.single class="w-100" name="order_task" :items="[]"></x-ui.select.single>
                </div>
            </div>
            <div class="mb-3 row" id="samplers_pad">
                <label for="samplers" class="col-sm-3 text-end control-label col-form-label">Пробоотборщики</label>
                <div class="col-sm-9">
                    <x-ui.select.multiple multiple="1" class="w-100" name="samplers[]" :items="$samplers" id="id" value-name="full_name"></x-ui.select.multiple>
                </div>
            </div>
        </div>
    </form>

    <script>
        $("select[name='samplers[]']").select2({
            dropdownParent: $(".modal-body #samplers_pad"),
            placeholder: 'Выберите пользователя',
        });

        $("select[name='order_task']").select2({
            dropdownParent: $(".modal-body #order_task_pad"),
            placeholder: 'Начните ввод, минимум 3 символа для поиска',
            minimumInputLength: 3,
            ajax: {
                url: '{{ route('api.plan-visits.order_search') }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term, // передача поискового запроса на сервер,
                        '_token': '{{ _token() }}'
                    };
                },
                processResults: function (response) {
                    return {
                        results: response
                    };
                },
            },
        });

        function create() {
            console.log($("form#create").serialize());
            if(!$("#fdate").val()) {
                toastr.error("Заполните поле ДАТА", "Ошибка", {
                    progressBar: true,
                    "timeOut": 3000,
                });
                return false;
            }
            if(!$("select[name='order_task']").val()) {
                toastr.error("Заполните поле ТЗ", "Ошибка", {
                    progressBar: true,
                    "timeOut": 3000,
                });
                return false;
            }

            if(confirm('Вы действительно хотите создать запланированные выезд?')) {
                $("form#create").submit();
            }
        }
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-center">
        <x-ui.button.default btn_type="primary" onclick="javascript:create();">Создать</x-ui.button.default>
    </div>
@endsection

