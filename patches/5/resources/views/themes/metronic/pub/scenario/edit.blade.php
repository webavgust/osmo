@extends('layouts.layout')

@section('content')
    <link
        rel="stylesheet"
        type="text/css"
        href="/assets/libs/ckeditor/samples/toolbarconfigurator/lib/codemirror/neo.css"
    />

    <style>
        #neuro_table[count='1'] i.fa-xmark {
            display: none!important;
        }
    </style>

    <div class="container-fluid">
        <div class="card p-2">
        <div class="card-body border-bottom">
            <h4 class="card-title">Редактирование сценария</h4>
        </div>
        <form id="scenario_create" action="{{ route('scenario.update', $scenario) }}" method="POST"
              class="needs-validation novalidate">
            @csrf
            @method('PUT')
            <div class="container-fluid">
                <div class="row mt-4">
                    <div class="col-12 col-md-7">

                        <div class="mb-3 row">
                            <label for="tb-class"
                                   class="col-4 col-md-2 col-form-label fw-semibold text-lg-end text-nowrap">Сортировка <span
                                    class="text-danger">*</span>
                            </label>
                            <div class="col-4">
                                <input name="sort" type="text" class="form-control" id="tb-class"
                                       placeholder="" required="" value="{{ $scenario->sort }}"
                                       value="{{ old('sort') }}">

                                @error('sort')
                                <div
                                    class=" alert alert-danger d-flex align-items-center p-2 mt-1"
                                    role="alert">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="tb-fname"
                                   class="col-4 col-md-2 col-form-label fw-semibold text-lg-end text-nowrap">Название<span
                                    class="text-danger">*</span></label>
                            <div class="col-8">
                                <input name="name" type="text" class="form-control " id="tb-fname"
                                       placeholder="" required="" value="{{ $scenario->name }}">

                                @error('name')
                                <div
                                    class=" alert alert-danger d-flex align-items-center p-2 mt-1"
                                    role="alert">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="tb-fname"
                                   class="col-4 col-md-2 col-form-label fw-semibold text-lg-end text-nowrap">Раздел<span
                                    class="text-danger">*</span></label>
                            <div class="col-8">
                                <x-ui.select.single :items="$groups" name="group" id="id" blank-ignore="1" required="" value="{{ $scenario->scenario_group->id }}"></x-ui.select.single>

                                @error('group')
                                <div
                                    class=" alert alert-danger d-flex align-items-center p-2 mt-1"
                                    role="alert">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="tb-fname"
                                   class="col-4 col-md-2 col-form-label fw-semibold text-lg-end"></label>
                            <div class="col-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="flexCheckDefault" name="active" @checked($scenario->active)>
                                    <label class="form-check-label" for="flexCheckDefault">
                                        Активный
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 mb-3 row">
                            <div class="offset-md-2 col-12 col-md-10">
                                <div class="d-flex justify-content-between">
                                    <h3>Стоимость</h3>
                                </div>


                                <table count="1" id="cost_table" class="table table-bordered w-auto">
                                    <tr>
                                        <th/>
                                        <th colspan="100" class="text-center">Стоимость</th>
                                    </tr>
                                    <tr class="items">
                                        <td>Штук, от</td>
                                        @for($i = 0; $i < 99; $i++)
                                            <td num="{{ $i }}" @class(["p-0 cost align-content-center", "d-none" => $i >= count($cost_rules)]) >
                                                @if($i == 0)
                                                    <input name="cost_rules[{{ $i }}][c]" type="text" class="cost-input cost_c w-100 border-0 p-1 text-center" min="0" readonly value="{{ $cost_rules->keys()[$i] ?? 1 }}">
                                                @else
                                                    <input name="cost_rules[{{ $i }}][c]" type="text" class="cost-input cost_c w-100 border-0 p-1 text-center" min="0" value="{{ $cost_rules->keys()[$i] ?? '' }}">
                                                @endif
                                            </td>
                                        @endfor
                                    </tr>
                                    <tr class="year">
                                        <td>Год</td>
                                        @for($i = 0; $i < 99; $i++)
                                            <td num="{{ $i }}" @class(["p-0 cost align-content-center", "d-none" => $i >= count($cost_rules)]) >
                                                <input name="cost_rules[{{ $i }}][y]" type="text" class="cost-input cost_y w-100 border-0 p-1 text-center" min="0" value="{{ $cost_rules->pluck('y')[$i] ?? '' }}">
                                            </td>
                                        @endfor
                                    </tr>
                                    <tr class="unlimited">
                                        <td>Бессрочная</td>
                                        @for($i = 0; $i < 99; $i++)
                                            <td num="{{ $i }}" @class(["p-0 cost align-content-center", "d-none" => $i >= count($cost_rules)]) >
                                                <input name="cost_rules[{{ $i }}][u]" type="text" class="cost-input cost_u w-100 border-0 p-1 text-center" min="0" value="{{ $cost_rules->pluck('u')[$i] ?? '' }}">
                                            </td>
                                        @endfor
                                    </tr>
                                    <tr>
                                        <td class="p-0"/>
                                        @for($i = 0; $i < 99; $i++)
                                            <td num="{{ $i }}" @class(["p-0 cost text-center", "d-none" => $i >= count($cost_rules)]) >
                                                @if($i > 0)
                                                    <x-ui.icon.regular icon="fa-xmark" class="text-danger ms-2 cursor-pointer" onclick="javascript:cost_delete({{ $i }})"/>
                                                @endif
                                            </td>
                                        @endfor
                                    </tr>
                                </table>



                                {{--                                    <table count="1" id="cost_table" class="table table-bordered">--}}
                                {{--                                        <tr>--}}
                                {{--                                            <th rowspan="2" width="20">№</th>--}}
                                {{--                                            <th rowspan="2" width="120">Штук, от</th>--}}
                                {{--                                            <th colspan="2">Стоимость</th>--}}
                                {{--                                            <th rowspan="2" width="20"></th>--}}
                                {{--                                        </tr>--}}
                                {{--                                        <tr>--}}
                                {{--                                            <th>Год</th>--}}
                                {{--                                            <th>Бессрочная</th>--}}
                                {{--                                        </tr>--}}
                                {{--                                        @for($i = 0; $i < 99; $i++)--}}
                                {{--                                            <tr @class(["cost", "d-none" => $i >= count($cost_rules)]) >--}}

                                {{--                                                <td class="fs-3 fw-bold p-1 text-center" ><span class="num">{{ $i + 1 }}</span>.</td>--}}
                                {{--                                                <td class="p-0">--}}
                                {{--                                                    @if($i == 0)--}}
                                {{--                                                        <input name="cost_rules[{{ $i }}][c]" type="number" class="cost_c w-100 border-0 p-1 text-center" min="0" readonly value="{{ $cost_rules->keys()[$i] ?? 1 }}">--}}
                                {{--                                                    @else--}}
                                {{--                                                        <input name="cost_rules[{{ $i }}][c]" type="number" class="cost_c w-100 border-0 p-1 text-center" min="0" value="{{ $cost_rules->keys()[$i] ?? '' }}">--}}
                                {{--                                                    @endif--}}
                                {{--                                                </td>--}}
                                {{--                                                <td class="p-0" width="120">--}}
                                {{--                                                    <input name="cost_rules[{{ $i }}][y]" type="number" class="cost_y w-100 border-0 p-1 text-center" min="0" value="{{ $cost_rules->pluck('y')[$i] ?? '' }}">--}}
                                {{--                                                </td>--}}
                                {{--                                                <td class="p-0" width="120">--}}
                                {{--                                                    <input name="cost_rules[{{ $i }}][u]" type="number" class="cost_u w-100 border-0 p-1 text-center" min="0" value="{{ $cost_rules->pluck('u')[$i] ?? '' }}">--}}
                                {{--                                                </td>--}}
                                {{--                                                <td class="p-1">--}}
                                {{--                                                    @if($i > 0)--}}
                                {{--                                                        <x-ui.icon.regular icon="fa-xmark" class="text-danger ms-2 cursor-pointer" onclick="javascript:cost_delete($(this))"/>--}}
                                {{--                                                    @endif--}}
                                {{--                                                </td>--}}
                                {{--                                            </tr>--}}
                                {{--                                        @endfor--}}
                                {{--                                    </table>--}}

                                <a href="javascript:void(0);" onclick="javascript:cost_add();">Добавить правило</a>


                            </div>
                        </div>
                        <div class="mt-5 mb-3 row">
                            <div class="offset-md-2 col-12 col-md-10">
                                <div class="d-flex justify-content-between">
                                    <h3>Нейросервисы</h3>

                                    <div class="d-none"> <!-- TODO: REMOVE -->
                                        <div id="cost_total" @class(["d-none" => !empty($scenario->cost_force)])>
                                            <x-ui.badge.default type="info" class="p-0 d-flex-inline align-items-center py-1">
                                                <span class="px-2 fs-3 fw-bold me-2 border-end border-1 border-white">1</span>
                                                <span class="fs-2 me-2 d-inline-block" style="width: 60px"><span id="cost_year">0</span> ₽</span>
                                            </x-ui.badge.default>

                                            <x-ui.badge.default type="primary" class="p-0 d-flex-inline align-items-center py-1">
                                                <span class="ps-2 pe-1 fs-3 fw-bold me-2 border-end border-1 border-white">
                                                    <i class="fa-solid fa-infinity"></i>
                                                </span>
                                                <span class="fs-2 me-2 d-inline-block" style="width: 60px"><span id="cost_unlimited">0</span> ₽</span>
                                            </x-ui.badge.default>
                                        </div>

                                        <div class="text-end" id="cost_force">
                                            <a href="javascript:void(0);"  @class(["fs-2 add", "d-none" => !empty($scenario->cost_force)]) onclick="javascript:$('#cost_force a.add').addClass('d-none');$('#cost_force .pad').removeClass('d-none');$('#cost_total').addClass('d-none');">Указать стоимость сценария вручную</a>
                                            <div @class(["pad mt-1", "d-none" => empty($scenario->cost_force)])>
                                                <div class="input-group">
                                                    <span class="input-group-text p-1" id="basic-addon1">1</span>
                                                    <input name="cost_force[year]" type="number" class="form-control text-end px-2 py-1 fs-2" style="width: 70px" value="{{ $scenario->cost_force['year'] ?? '' }}">
                                                    <span class="input-group-text p-1" id="basic-addon1"><i class="fa-solid fa-infinity"></i></span>
                                                    <input name="cost_force[unlimited]" type="number" class="form-control text-end px-2 y-1 fs-2" style="width: 70px" value="{{ $scenario->cost_force['unlimited'] ?? '' }}">
                                                </div>

                                                <a href="javascript:void(0);" class="fs-2 mt-2 cancel text-danger" onclick="javascript:$('#cost_force .add').removeClass('d-none');$('#cost_force > .pad').addClass('d-none').find('input').val('');$('#cost_total').removeClass('d-none');">Отменить</a>
                                            </div>
                                        </div>
                                    </div>


                                </div>

                                <table count="1" id="neuro_table">
                                    @for($i = 1; $i < 10; $i++)
                                        <tr @class(["neuro", "d-none" => $i > 1 && empty($scenario->neuroservices[$i-1])])>
                                            <td class="fs-3 fw-bold pe-3" ><span class="num">{{ $i }}</span>.</td>
                                            <td>
                                                <x-ui.select.single name="neuro[]" value-name="name_cost" :items="$services" id="id" class="select2 fs-3" value="{{ $scenario->neuroservices[$i-1]->id ?? 0 }}"/>
                                            </td>
                                            <td>
                                                <x-ui.icon.regular icon="fa-xmark" class="text-danger ms-2 cursor-pointer" onclick="javascript:neuro_delete($(this))"/>
                                            </td>
                                        </tr>
                                    @endfor
                                </table>

                                <a href="javascript:void(0);" onclick="javascript:neuro_add();">Добавить нейросервис</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-5">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <h4>Сценарий работы</h4>
                                <textarea name="work_scenario" type="text" class="form-control d-none" id="work_scenario"
                                          placeholder="" required="" value="{{ old('work_scenario') }}">{{ $scenario->work_scenario }}</textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <h4>Результат работы</h4>
                                <textarea name="work_result" type="text" class="form-control d-none" id="work_result"
                                          placeholder="" required="" value="{{ old('work_result') }}">{{ $scenario->work_result }}</textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <h4>Событие / уведомление</h4>
                                <textarea name="event_reminder" type="text" class="form-control d-none" id="event_reminder"
                                          placeholder="" required="" value="{{ old('event_reminder') }}">{{ $scenario->event_reminder }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center mt-4">
                    <div class="col-sm-4 col-ml text-center">
                        <button type="submit" class=" btn btn-primary fw-semibold rounded-pill px-4" disabled id="btn_submit">
                            <div class="d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round"
                                     class="feather feather-send feather-sm fill-white me-2">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                                {{ __('button.save') }}
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    </div>
@endsection

@section('js')
    @parent
    <script src="/assets/libs/ckeditor/ckeditor.js"></script>

    <script>
        let count = 1;
        let cost_count = 1;

        function cost_delete(num) {
            cost_count--;
            $("#cost_table").attr("count", cost_count);

            $(`td[num='${num}']`).remove();

            // пересчитаем
            $("#cost_table tr.cost").each(function(index, tr) {
                $(this).find(".num").html(index + 1);
            });
            recalc();
        }

        function cost_add() {
            cost_count++;
            $("#cost_table").attr("count", cost_count);
            const num = $("#cost_table td[num].d-none").first().attr("num");


            $(`#cost_table td[num=${num}]`).removeClass('d-none');
            recalc();
        }

        function neuro_delete(obj) {
            count--;
            $("#neuro_table").attr("count", count);
            obj.parents("tr").remove();

            // пересчитаем
            $("#neuro_table tr").each(function(index, tr) {
                $(this).find(".num").html(index + 1);
            });
            recalc();
        }
        function neuro_add() {
            count++;
            $("tr.neuro.d-none:first").removeClass("d-none").find("select").select2();
            $("#neuro_table").attr("count", count);
            recalc();
        }


        $(document).ready(function() {
            $("form#scenario_create *").on("change keyup", function() {
               recalc();
            });

            $('.cost-input').mask('# ##0', {
                reverse: true,
                translation: {
                    '#': { pattern: /\d/, recursive: true }
                },
                onKeyPress: function(value, e, field, options) {
                    // Автоматически ставим пробелы при вводе
                    var val = value.replace(/\D/g, '');
                    if (val.length > 3) {
                        var formatted = '';
                        while (val.length > 3) {
                            formatted = ' ' + val.substr(-3) + formatted;
                            val = val.substr(0, val.length - 3);
                        }
                        formatted = val + formatted;
                        $(field).val(formatted);
                    }
                }
            });

            recalc();


        });

        function recalc() {
            let err = 0;
            $("#cost_table tr.items td[num]:not(.d-none)").each(function() {
                const num = $(this).attr("num");

                let c = Number($(`#cost_table tr.items td[num=${num}] input`).val());
                let y = Number($(`#cost_table tr.year td[num=${num}] input`).val());
                let u = Number($(`#cost_table tr.unlimited td[num=${num}] input`).val());

                if(c < 1 || y < 1 || u < 1) err++;
            });

            if(!err) {
                $("#btn_submit").removeAttr("disabled");
            } else {
                $("#btn_submit").attr("disabled", 1);
            }
        }


        CKEDITOR.replace("work_scenario", {
            height: 100,
            toolbar: [
                {
                    name: 'basicstyles',
                    items: ['Bold', 'Italic', 'Underline', 'TextColor', 'BGColor']
                },
                {
                    name: 'paragraph',
                    items: ['NumberedList', 'BulletedList']
                },
                {
                    name: 'styles',
                    items: ['Styles']
                }
            ]
        });
        CKEDITOR.replace("event_reminder", {
            height: 100,
            toolbar: [
                {
                    name: 'basicstyles',
                    items: ['Bold', 'Italic', 'Underline', 'TextColor', 'BGColor']
                },
                {
                    name: 'paragraph',
                    items: ['NumberedList', 'BulletedList']
                },
                {
                    name: 'styles',
                    items: ['Styles']
                }
            ]
        });
        CKEDITOR.replace("work_result", {
            height: 100,
            toolbar: [
                {
                    name: 'basicstyles',
                    items: ['Bold', 'Italic', 'Underline', 'TextColor', 'BGColor']
                },
                {
                    name: 'paragraph',
                    items: ['NumberedList', 'BulletedList']
                },
                {
                    name: 'styles',
                    items: ['Styles']
                }
            ]
        });
    </script>

@endsection

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
@endsection
