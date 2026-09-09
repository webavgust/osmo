@extends('components.box.box-static-large')

@section('body')
    {{-- без target="_blank": при отправке POST-формы в новое окно браузер
         повторяет запрос методом GET и маршрут отвечает 405. Ответ приходит
         вложением, поэтому страница под попапом остаётся на месте --}}
    <form method="POST" id="deal_export" action="{{ route('crm-deal.export') }}">
        @csrf

        {{-- текущий отбор страницы уходит в выгрузку как есть --}}
        <input type="hidden" name="has_proposal" value="{{ $params['has_proposal'] }}"/>
        <input type="hidden" name="q" value="{{ $params['q'] }}"/>
        @foreach(['stage', 'manager', 'country'] as $field)
            @foreach($params[$field] as $value)
                <input type="hidden" name="{{ $field }}[]" value="{{ $value }}"/>
            @endforeach
        @endforeach

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="fs-4 fw-bold">Колонки выгрузки</div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-light-primary" onclick="javascript:columns_all(1);">
                    Все
                </button>
                <button type="button" class="btn btn-sm btn-light" onclick="javascript:columns_all(0);">
                    Ничего
                </button>
            </div>
        </div>

        <div class="row">
            @foreach($columns as $code => $label)
                <div class="col-12 col-sm-6">
                    <div class="form-check form-check-custom form-check-sm mb-2">
                        <input class="form-check-input" type="checkbox" name="columns[]" value="{{ $code }}"
                               id="deal_export_{{ $code }}" checked>
                        <label class="form-check-label fs-7 text-gray-800" for="deal_export_{{ $code }}">
                            {{ $label }}
                        </label>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="fs-8 text-muted mt-4">
            В файл попадут {{ $count }} сделк(и) — ровно те, что показывает страница с текущим фильтром.
            Если не отметить ни одной колонки, выгрузятся все.
        </div>
    </form>

    <script>
        function columns_all(state) {
            $('#deal_export input[name="columns[]"]').prop('checked', !!state);
        }

        function save() {
            document.getElementById('deal_export').submit();

            toastr.success("Файл готовится, скоро начнётся загрузка", "Это успех!", {
                progressBar: true,
                "timeOut": 3000,
            });

            setTimeout(function () { box_close(); }, 300);
        }
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <button type="button" class="btn btn-light" onclick="javascript:box_close();">
            <i class="fa-light fa-xmark fs-5 me-2"></i>
            <span>Закрыть</span>
        </button>

        <button type="button" id="btn_save" class="btn btn-success" onclick="javascript:save();">
            <i class="fa-light fa-file-excel fs-5 me-2"></i>
            <span>Выгрузить</span>
        </button>
    </div>
@endsection
