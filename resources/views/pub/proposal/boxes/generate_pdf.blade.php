@extends('components.box.box-static-large')

@section('body')
    <form method="POST" id="generate" action="{{ route('proposal.report', [$proposal, $proposal->iteration]) }}" target="_blank">
        @csrf

        <div class="row">
            <div class="col-12">
                <div class="fs-4 fw-bold">Выберите варианты для добавления в КП:</div>

            </div>

            <div class="px-3 mt-2">
                @php
                    $cameras_from_software = $proposal->software()
                    ->where(function($builder) {
                        $builder->where('description', 'like', '%платформа%')
                        ->orWhere('description', 'like', '%platform%');
                    })
                    ->first()->id ?? 0;
                @endphp
                @foreach($proposal->variants as $variant)
                    <div class="input-group mt-1">
                        <div class="input-group-text">
                            <div class="form-check">
                                <input class="form-check-input" name="active[]" type="checkbox" value="{{ $variant->id }}" id="variant_{{ $loop->iteration }}" checked="">
                            </div>
                        </div>

                        <div class="form-floating">
                            <input name="variant[{{ $variant->id }}][name]"  type="text" class="form-control" aria-label="Text input with checkbox">

                            <label for="tb-fname">Название</label>
                        </div>

                        <div class="form-floating" style="width: 70px">
                            @php
                            $count = $variant->proposal_software->where('proposal_software_id', $cameras_from_software)->first()?->count ?? 0;
//                                $count = $variant->proposal_softwares->where('software_id', $cameras_from_software)->count;
                            @endphp
                            <input name="variant[{{ $variant->id }}][cameras]"  type="text" class="form-control" aria-label="Text input with checkbox" value="{{ $count }}" >

                            <label for="tb-fname">Камер</label>
                        </div>

                        <div class="form-floating" style="width: 200px">
                            <input name="variant[{{ $variant->id }}][period_po]"  type="text" class="form-control" >

                            <label for="tb-fname">Срок поставки ПО</label>
                        </div>

                        <div class="form-floating" style="width: 200px">
                            <input name="variant[{{ $variant->id }}][period_pk]"  type="text" class="form-control" >

                            <label for="tb-fname">Срок поставки ПК</label>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3">
                <div>
                    <div class="form-floating mb-3">
                        <input name="form[contact]" type="text" class="form-control" id="tb-fname" placeholder="" value="{{ $proposal->partner->contact ?? '' }}">
                        <label for="tb-fname">Контактное лицо</label>
                    </div>
                </div>
            </div>


            <div class="col-6 mt-3">
                    <div>
                        <div class="fs-4 fw-bold">Выберите язык КП:</div>

                        <div class="mt-2">
                            <select name="language" class="form-select">
                                <option value="ru" @selected($proposal->currency_slug == 'RUB' && $proposal->lang == 'ru')>Русский</option>
                                <option value="en" @selected(!($proposal->currency_slug == 'RUB' && $proposal->lang == 'ru'))>Английский</option>
                            </select>
                        </div>
                    </div>
            </div>
            <div class="col-6 mt-3">
                    <div>
                        <div class="fs-4 fw-bold">Шаблон КП:</div>

                        <div class="mt-2">
                            <select name="template" class="form-select">
                                <option value="default">По умолчанию</option>
{{--                                TODO: сделать автовыбор если есть хоть одна скидка клиента--}}
                                <option value="client_discount">Со скидкой клиента</option>
                            </select>
                        </div>
                    </div>
            </div>

            <div class="col-12 mt-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="flexCheckChecked" checked="" name="show_unprocessed" value="1">
                    <label class="form-check-label" for="flexCheckChecked">
                        Выводить неактивные записи
                    </label>
                </div>
            </div>
        </div>
    </form>

    <script>
        function save() {
            if (!box_check_form()) return;

            $("#generate").submit();
        }


        function box_check_form() {
            cb_count = $('input[name="active[]"]:checked').length;
            if(!cb_count) {
                 $("#btn_submit").attr("disabled", "1");
                 return false;
            } else {
                $("#btn_submit").removeAttr("disabled");
                return true;
            }
        }

        $(document).ready(function() {
            $("form#generate input").on("change", function() {
                box_check_form();
            }) ;
        });
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>

        <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:save();">
            <x-ui.icon.solid icon="fa-file-pdf"></x-ui.icon.solid>
            <span>Создать</span>
        </x-ui.button.default>
    </div>
@endsection
