@extends('layouts.layout')

@section('content')
    @php
        $columns = 5;
        $rows = 30;
    @endphp
    <link
        rel="stylesheet"
        type="text/css"
        href="/assets/libs/ckeditor/samples/toolbarconfigurator/lib/codemirror/neo.css"
    />

    <style>
        .cke_editable {
            padding: 5px!important;
        }
        /*#table_data[count='1'] .scenario_delete {*/
        /*    display: none!important;*/
        /*}*/
        #table_data[column='1'] .column_delete {
            display: none!important;
        }
        #table_data[column='1'] .main_selector {
            display: none!important;
        }

        #table_data:not([column='1']) .column_hl {
            background: #735fec1a!important;
        }
        .scenario_selector span.select2,
        .work_selector span.select2,
        .soft_selector span.select2
        {
            width: 100%!important;
        }


        td[column] :not(input.active:checked) ~ .cell-active {
            visibility: hidden!important;
        }


        tr.scenario.once:not(.scenario_selected) .cell {
            visibility: hidden;
        }

        .ui-sortable-helper {
            /*display: none!important;*/
            background: yellow!important;
        }
        .ui-sortable-placeholder {
            display: none;
        }


        table[count='0'] tr.discount,
        table[count='0'] tr.total,
        table[count='0'] tr.neuro_partner,
        table[count='0'] .subtotal .init_clear
        {
            display: none!important;
        }

        table[work_count='0'] .work_discount,
        table[work_count='0'] .work_partner,
        table[work_count='0'] .work_total,
        table[work_count='0'] .work_subtotal .init_clear {
            display: none;
        }

        table[soft_count='0'] .soft_discount,
        table[soft_count='0'] .soft_partner,
        table[soft_count='0'] .soft_total,
        table[soft_count='0'] .soft_subtotal .init_clear {
            display: none;
        }

        table[platform_count='0'] .platform_discount,
        table[platform_count='0'] .platform_partner,
        table[platform_count='0'] .platform_total,
        table[platform_count='0'] .platform_subtotal .init_clear {
            display: none;
        }

        table[count='0'][work_count='0'][soft_count='0'][platform_count='0'] .partner,
        table[count='0'][work_count='0'][soft_count='0'][platform_count='0'] .proposal_total,
        table[count='0'][work_count='0'][soft_count='0'][platform_count='0'] .proposal_nds
        {
            display: none!important;
        }

        div:has(table[count='0'][work_count='0'][soft_count='0'][platform_count='0']) #variant_add {
            display: none;
        }


        table[count='0'][work_count='0'][soft_count='0'][platform_count='0'] th[column],
        table[count='0'][work_count='0'][soft_count='0'][platform_count='0'] td[column],
        table[count='0'][work_count='0'][soft_count='0'][platform_count='0'] tr:not(.header) th:last-of-type,
        table[count='0'][work_count='0'][soft_count='0'][platform_count='0'] tr:not(.header) td:last-of-type
        {
            display: none;
        }


        .select2_works_container,
        .select2_softs_container {
            height: 0!important;
            overflow: hidden;
        }

        /* PLATFORM */
        tr.platform_header {
            /*border-right: 3px solid #7460ee69;*/
            border-left: 10px solid rgb(8 8 8 / 41%);
        }
        tbody#platforms tr td:first-of-type {
            border-left: 10px solid rgb(8 8 8 / 41%);
            /*border-right: 3px solid #7460ee69;*/
            /*border-bottom: 3px solid #7460ee69;*/
        }

        /* SCENARIOS */
        tr.scenario_header {
            /*border-right: 3px solid #fc4b6c57;*/
            border-left: 10px solid #fc4b6c57;
            /*border-top: 3px solid #fc4b6c57;*/
        }
        tbody#scenarios tr td:first-of-type{
            border-left: 10px solid #fc4b6c57;
            /*border-right: 3px solid #fc4b6c57;*/
            /*border-bottom: 3px solid #fc4b6c57;*/
        }

        /* SOFT */
        tr.soft_header {
            /*border-right: 3px solid #7460ee69;*/
            border-left: 10px solid #7460ee69;
        }
        tbody#softs tr td:first-of-type {
            border-left: 10px solid #7460ee69;
            /*border-right: 3px solid #7460ee69;*/
            /*border-bottom: 3px solid #7460ee69;*/
        }

        /* WORK */
        tr.work_header {
            /*border-right: 3px solid #ffb22b99;*/
            border-left: 10px solid #ffb22b99;
        }
        tbody#works tr td:first-of-type {
            border-left: 10px solid #ffb22b99;
            /*border-right: 3px solid #ffb22b99;*/
            /*border-bottom: 3px solid #ffb22b99;*/
        }

        .fast_scenario_add, .fast_work_add, .fast_soft_add, .fast_platform_add { display: none!important }
        #table_data[platform_count='0'] .fast_platform_add,
        #table_data[count='0'] .fast_scenario_add,
        #table_data[work_count='0'] .fast_work_add,
        #table_data[soft_count='0'] .fast_soft_add {
            display: flex!important;
        }


        #table_data[platform_count='0'] .common_platform_add,
        #table_data[count='0'] .common_scenario_add,
        #table_data[work_count='0'] .common_work_add,
        #table_data[soft_count='0'] .common_soft_add {
            display: none;
        }


        table[platform_count='0'] label[for='platform_nds_cb'] { display: none!important; }
        table[count='0'] label[for='neuro_nds_cb'] { display: none!important; }

        input#platform_nds_cb:checked + label { background: #000!important; color: white!important }

        tbody#platforms .form-check-input:checked { background-color: var(--bs-gray-600); border-color: var(--bs-gray-600) }
        tbody#scenarios .form-check-input:checked { background-color: var(--bs-text-danger); border-color: var(--bs-text-danger) }
        tbody#works .form-check-input:checked { background-color: var(--bs-text-warning); border-color: var(--bs-text-warning) }

       #neuro_nds input:not(:checked) + label {
            background-color: transparent!important;
            border: 1px solid var(--bs-text-danger);
            border-radius: 50rem !important;
            border-color: var(--bs-text-danger)!important;
            color: var(--bs-text-danger)!important;
        }
        #neuro_nds input:checked + label {
            background-color: var(--bs-text-danger) !important;
            color: white!important;
            border-radius: 50rem !important;
        }
    </style>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <form id="form_create">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-3">
                                    <label class="text-end control-label col-form-label">Название <span class="text-danger">*</span></label>
                                    <input required name="name" type="text" class="form-control " id="tb-fname"
                                           placeholder="" value="{{ old('name') }}">
                                </div>
                                <div class="col-3">
                                    <label class="text-end control-label col-form-label">Название (альт.)</label>
                                    <input name="name_alt" type="text" class="form-control " id="tb-fname"
                                           placeholder="" value="">
                                </div>
                                <div class="col-2">
                                    <label class="text-end control-label col-form-label">Дата КП <span class="text-danger">*</span></label>
                                    <input required name="date" type="date" class="form-control w-100" id="tb-fname"
                                           placeholder="" value="{{ old('date') }}" style="width: 140px">
                                </div>
                                <div class="col-2">
                                    <label class="text-end control-label col-form-label">Менеджер <span class="text-danger">*</span></label>
                                    <x-ui.select.single :items="$users" id="id" value-name="full-name" required name="manager"></x-ui.select.single>
                                </div>
                                <div class="col-1 pe-0">
                                    <label class="text-end control-label col-form-label">
                                        Номер КП <span class="text-danger">*</span>
                                    </label>
                                    <div class="d-flex align-items-center">
                                        <input required name="number" type="text" class="form-control w-100" id="tb-fname" value=""
                                               placeholder="" style="width: 100px">
                                    </div>
                                </div>
                                <div class="col-1 pe-0">
                                    <label class="text-end control-label col-form-label">
                                        НДС <span class="text-danger">*</span>
                                    </label>
                                    <div class="d-flex align-items-center">
                                        <input required name="nds" type="text" class="form-control" id="tb-fname" value="{{ \App\Modules\Pub\Constant\Models\Constant::get('nds_rate') }}"
                                               placeholder="" style="width: 70px">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-5 row">
                                <div class="col-3">
                                    <label class="col-sm-4 control-label col-form-label">Компания <span class="text-danger">*</span></label>
                                    <x-ui.select.single  name="company" required :items="$companies" id="key" value-name="label" :value="$company_default"></x-ui.select.single>
                                </div>
                                <div class="col-3">
                                    <label class="col-sm-4 control-label col-form-label">Партнёр <span class="text-danger">*</span></label>
                                    <x-ui.select.single name="partner" required :items="$partners" id="key" value-name="label"></x-ui.select.single>
                                </div>
                                <div class="col-3 ">
                                    <label class="col-sm-4 control-label col-form-label">Язык</label>
                                    <div class="d-flex mt-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="lang" id="lang_ru" value="ru" checked="">
                                            <label class="form-check-label text-dark" for="lang_ru">
                                                Русский
                                            </label>
                                        </div>
                                        <div class="form-check ms-3">
                                            <input class="form-check-input" type="radio" name="lang" id="lang_en" value="en">
                                            <label class="form-check-label text-dark" for="lang_en">
                                                English
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="row">
                                <div class="col-md-12 position-relative">
                                    <div id="variant_add" class=" position-absolute" style="right: 5px; top: -8px;">
                                        <a href="javascript:column_add();">
                                            <x-ui.icon.solid icon="fa-circle-plus text-success" class="fs-4"/>
                                        </a>
                                    </div>

                                    <div class=" m-t-40 table-responsive" style="clear: both">

                                        <table border="1" class="table table-hover border-light-secondary" id="table_data" count="0" work_count="0" soft_count="0" platform_count="0" column="1" style="width: auto; min-width: 100%">

                                        {{-- СЕЛЕКТОР ВАРИАНТОВ --}}
                                        <tr class="variants">
                                            <td colspan="3" style=" border-left: 1px solid white; border-top: 1px solid white; border-bottom: 1px solid white;"/>
                                            @for($i = 1; $i <= $columns; $i++)
                                                <th column="{{ $i }}" @class(["p-1 px-2 border-start border-bottom flex-grow-0", "d-none" => $i > 1, "column_hl" => $i == 1]) style="width: 250px!important">
                                                    <div class="d-flex">
                                                        <div class="flex-grow-1 d-flex align-items-center period_type_selector">
                                                            {{--                                                            <x-ui.icon.regular icon="fa-ellipsis-stroke-vertical" class="me-2 fs-6 handler"/>--}}

                                                            <div class="input-group">
                                                                <input type="checkbox" name="period_active[{{ $i }}]" @class(["d-none period_active"]) @checked($i == 1) value="1">
                                                                <select name="period[{{ $i }}]" class="form-select period p-1 fs-6 flex-grow-1" prev-value="year">
                                                                    <option value="pilot">Пилот</option>
                                                                    <option value="year" selected>Годовая</option>
                                                                    <option value="unlimited">Безлимит</option>
                                                                </select>
                                                                <input name="period_value[{{ $i }}]" maxlength="2" type="number"  class="form-control p-1 fs-5 flex-grow-0 text-center count" style="width: 50px" value="1" prev-value="1">

                                                            </div>
                                                        </div>
                                                        <div class="pt-1 column_delete">
                                                            <x-ui.icon.regular icon="fa-xmark" class="text-danger ms-2 cursor-pointer" onclick="javascript:column_delete($(this))"/>
                                                        </div>
                                                    </div>
                                                </th>
                                            @endfor
                                        </tr>


                                        {{-- ОСНОВНОЙ --}}
                                        <tr class="main_selector">
                                            <td colspan="3" class="text-end py-2" style=" border-left: 1px solid white; border-top: 1px solid white;"/>
                                            @for($i = 1; $i <= $columns; $i++)
                                                <td column="{{ $i }}" @class(["border-start border-bottom flex-grow-0 border-bottom-0 p-0 py-2", "d-none" => $i > 1, "column_hl" => $i == 1])  style="width: 140px!important">
                                                    <div class="d-flex justify-content-center">
                                                        <input type="radio" class="btn-check" id="btn-main-{{ $i }}"  @checked($i == 1) name="period_main" value="{{ $i }}">
                                                        <label @class(["btn rounded-pill font-weight-medium py-1 px-3 fs-5 m-0", "btn-light-info" => $i > 1, "btn-info" => $i== 1]) for="btn-main-{{ $i }}">Основной</label>
                                                    </div>
                                                </td>
                                            @endfor
                                        </tr>

                                        {{--  ПЛАТФОРМА --}}
                                            <tr class="platform_header bg-gray-200 ">
                                                <th style="min-width: 35vw" class="p-1 px-2 border-bottom" colspan="3">
                                                    <div class="d-flex justify-content-between">
                                                        <h4 class="m-0 ms-1 text-secondary d-flex align-items-center" style="height: 32px">
                                                            <x-ui.icon.regular icon="fa-desktop" class="me-2"/>
                                                            Платформа
                                                        </h4>

                                                        <div class="d-flex justify-content-center align-items-center">
                                                            <div id="platform_nds">
                                                                <input type="checkbox" class="btn-check" id="platform_nds_cb" >
                                                                <label class="
                                                                      btn btn-outline
                                                                      font-weight-medium
                                                                      rounded-pill py-1 fs-7 px-2 m-0 me-1
                                                                    " for="platform_nds_cb">НДС</label>
                                                            </div>

                                                            <a class="fast_platform_add d-flex align-items-center fw-bold fs-6" href="javascript:void(0);" onclick="javascript:platform_add();">
                                                                <x-ui.icon.regular icon="fa-circle-plus fs-7 text-secondary"/>
                                                                <span class="ms-2 text-secondary">Добавить</span>
                                                            </a>
                                                     </div>
                                                    </div>
                                                </th>
                                                <th colspan="100"/>
                                            </tr>
                                            <tbody id="platforms">
                                            @for($i = 1; $i <= $rows; $i++)
                                                <tr @class(["platform once", "d-none" => $i >= 1]) num="{{ $i }}">
                                                    <td style="width: 20px; height: 240px" class="p-1 px-2 pt-2">
                                                        <div class="d-flex flex-column align-items-center" style="width: 20px; height: 100%; align-items: stretch;">
                                                            <div class="flex-grow-1 d-flex flex-column align-items-center">
                                                                <div class="form-check form-check-inline m-0" style="margin-left: 7px!important">
                                                                    <input name="platform[{{ $i }}][cb_process]" class="cb_process form-check-input secondary check-light-primary " type="checkbox" value="1" @checked($soft->cb_process ?? true)>
                                                                </div>
                                                            </div>

                                                            <x-ui.icon.solid icon="fa-xmark" @class(["soft_delete text-danger cursor-pointer fs-6 mb-1"]) onclick="javascript:platform_delete($(this))"/>
                                                        </div>
                                                    </td>
                                                    <td style="width: 20px" class="p-1 px-2 text-center pt-2"><span class="num">{{ $i }}</span>.</td>
                                                    <td class="p-0 pt-2 soft_selector">
                                                        <div class="d-flex">
                                                            <div class="w-50">
                                                                <div class="mb-1">Описание</div>
                                                                <textarea name="platform[{{ $i }}][extended]" class="platform_extended" id="{{ \Illuminate\Support\Str::uuid() }}">{{ __('proposal.textarea__platform_extended') }}</textarea>
                                                            </div>
                                                            <div class="w-50">
                                                                <div class="mb-1">Примечание</div>
                                                                <textarea name="platform[{{ $i }}][notice]" class="platform_notice" id="{{ \Illuminate\Support\Str::uuid() }}">{{ __('proposal.textarea__platform_notice') }}</textarea>
                                                            </div>
                                                        </div>


                                                    </td>
                                                    @for($j = 1; $j <= $columns; $j++)
                                                        @for($j = 1; $j <= $columns; $j++)
                                                            <td column="{{ $j }}" @class(["border-start p-1", "column_hl" => $j == 1, "d-none" => $j > 1])>
                                                                <div class="cell">
                                                                    <div class="form-check d-flex justify-content-between">
                                                                        <input name="platform_cell[{{ $i }}][{{ $j }}][active]" class="active form-check-input mt-7 me-1" type="checkbox" id="flexCheckDefault" checked value="1">

                                                                        <div class="cell-active flex-grow-1">
                                                                            <div class="d-flex justify-content-between fs-1">
                                                                                <span class="text-nowrap fs-6">Кол-во</span>
                                                                                <span class="text-nowrap fs-6">Скидка (%)</span>
                                                                            </div>
                                                                            <div class="input-group">
                                                                                <input name="platform_cell[{{ $i }}][{{ $j }}][count]" type="number" min="1" max="999999" value="1" class="count form-control p-1 fs-6 text-end" aria-label="Text input with checkbox">
                                                                                <input name="platform_cell[{{ $i }}][{{ $j }}][discount]" type="number" min="0" max="99" value="0" class="discount form-control p-1 fs-6 text-end" aria-label="Text input with checkbox">
                                                                            </div>

                                                                            <div class="text-start mt-1 fs-6 text-secondary">
                                                                                <input type="hidden" name="platform_cost[{{ $i }}][{{ $j }}]" value="{{ $cost_rules[0][1]['y'] ?? 0 }}" class="fs-1 p-0 inp_cost_cell">
                                                                                <a class="cost_cell" href="javascript:void(0);" onclick="javascript:setForcedNeuroCost($(this));">0</a> ₽

                                                                                <span class="fw-bold text-nowrap">
                                                                                   <x-ui.icon.regular icon="fa-arrow-right" class="mx-1"/>
                                                                                   <span class="cost_cell_total"><?=$costs['year']['platform']?></span> ₽
                                                                               </span>
                                                                            </div>

                                                                            <div class="fs-2 d-flex align-items-center mt-2 justify-content-start d-none">
                                                                                <input name="platform_cell[{{ $i }}][{{ $j }}][nds]" class="active form-check-input secondary me-1 cb_nds" type="checkbox" id="platform_nds_{{ $i }}_{{ $j }}" value="1">
                                                                                <label class="form-check-label fs-1 fw-normal m-0 ms-1" for="platform_nds_{{ $i }}_{{ $j }}">НДС</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        @endfor
                                                    @endfor
                                                </tr>
                                            @endfor
                                            <tr class="common_platform_add">
                                                <td colspan="3">
                                                    <a href="javascript:void(0);" onclick="javascript:platform_add();" class="fw-bold fs-6 ms-3">
                                                        <x-ui.icon.regular icon="fa-plus"/>
                                                        Добавить платформу
                                                    </a>
                                                </td>
                                                @for($j = 1; $j <= $columns; $j++)
                                                    <td column="{{ $j }}" @class(["border-start p-1", "column_hl" => $j == 1, "d-none" => $j > 1])></td>
                                                @endfor
                                            </tr>
                                            </tbody>

                                            <tr class="soft_header bg-light-primary d-none">
                                                <th style="min-width: 35vw" class="p-1 px-2 border-bottom" colspan="3">
                                                    <div class="d-flex justify-content-between">
                                                        <h4 class="m-0 ms-1 text-primary d-flex align-items-center" style="height: 32px">
                                                            <x-ui.icon.regular icon="fa-circle-1" class="me-2"/>
                                                            ПО
                                                        </h4>

                                                        <a class="fast_soft_add d-flex align-items-center fw-bold fs-6" href="javascript:void(0);" onclick="javascript:soft_add();">
                                                            <x-ui.icon.regular icon="fa-circle-plus fs-7 text-primary"/>
                                                            <span class="ms-2 text-primary">Добавить</span>
                                                        </a>
                                                    </div>
                                                </th>
                                                <th colspan="100"/>
                                            </tr>
                                            <tbody id="softs">
                                            @for($i = 1; $i <= $rows; $i++)
                                                <tr @class(["soft once", "d-none" => $i >= 1])>
                                                    <td style="width: 20px; height: 240px" class="p-1 px-2 pt-2">
                                                        <div class="d-flex flex-column align-items-center" style="width: 20px; height: 100%; align-items: stretch;">
                                                            <div class="flex-grow-1 d-flex flex-column align-items-center">
                                                                <div class="form-check form-check-inline m-0" style="margin-left: 7px!important">
                                                                    <input name="soft[{{ $i }}][cb_process]" class="cb_process form-check-input primary check-light-primary " type="checkbox" value="1" @checked($soft->cb_process ?? true)>
                                                                </div>

                                                                <a class="mt-2" href="javascript:void(0);" onclick="javascript:$(this).parents('tr').find('.select2_softs').select2('open');">
                                                                    <x-ui.icon.solid icon="fa-bolt-lightning" class="fs-6"/>
                                                                </a>
                                                            </div>

                                                            <x-ui.icon.solid icon="fa-xmark" @class(["soft_delete text-danger cursor-pointer fs-6 mb-1"]) onclick="javascript:soft_delete($(this))"/>
                                                        </div>
                                                    </td>
                                                    <td style="width: 20px" class="p-1 px-2 text-center pt-2"><span class="num">{{ $i }}</span>.</td>
                                                    <td class="p-0 pt-2 soft_selector">
                                                        <div class="d-flex">
                                                            <div class="w-50">
                                                                <div class="select2_softs_container">
                                                                    <x-ui.select.single class="select2_softs" :items="$softs" id="id" valueName="name"></x-ui.select.single>
                                                                </div>
                                                                <div class="mb-1">Описание</div>
                                                                <textarea name="soft[{{ $i }}][extended]" class="soft_extended" id="{{ \Illuminate\Support\Str::uuid() }}"></textarea>
                                                            </div>
                                                            <div class="w-50">
                                                                <div class="mb-1">Примечание</div>
                                                                <textarea name="soft[{{ $i }}][notice]" class="soft_notice" id="{{ \Illuminate\Support\Str::uuid() }}"></textarea>
                                                            </div>
                                                        </div>

                                                    </td>
                                                    @for($j = 1; $j <= $columns; $j++)
                                                        <td style="height: 240px" column="{{ $j }}" @class(["border-start p-1", "column_hl" => $j == 1, "d-none" => $j > 1])>
                                                            <div class="h-100 d-flex flex-column">
                                                                <div class="cell soft_cell flex-grow-1">
                                                                    <div class="form-check d-flex justify-content-between align-items-start flex-grow-0 m-1 h-100">
                                                                        <input name="soft_cell[{{ $i }}][{{ $j }}][active]" class="active form-check-input mt-3 me-1" type="checkbox" value="1" id="flexCheckDefault" checked value="1">

                                                                        <div class="cell-active h-100 d-flex flex-column">
                                                                            <div class="flex-grow-1">
                                                                                <div class="d-flex justify-content-between fs-1">
                                                                                    <span class="text-nowrap">Стоимость</span>
                                                                                    <span class="text-nowrap">Кол-во</span>
                                                                                    <span class="text-nowrap">Скидка</span>
                                                                                </div>
                                                                                <div class="input-group">
                                                                                    <input name="soft_cell[{{ $i }}][{{ $j }}][cost]" type="number" min="1" max="999999" value="0" class="cost form-control p-0 py-1 fs-1 text-end" style="width: 30px;">
                                                                                    <input name="soft_cell[{{ $i }}][{{ $j }}][count]" type="number" min="0" max="9999" value="0" class="count form-control p-0 py-1 fs-1 text-end"  style="width: 25px;">
                                                                                    <input name="soft_cell[{{ $i }}][{{ $j }}][discount]" type="number" min="0" max="99" value="0" class="discount form-control p-0 py-1 fs-1 text-end" >

                                                                                </div>

                                                                                <div class="fs-2 d-flex align-items-center mt-2 justify-content-start">
                                                                                    <input name="soft_cell[{{ $i }}][{{ $j }}][nds]" class="active form-check-input secondary me-1 cb_nds" type="checkbox" id="soft_nds_{{ $i }}_{{ $j }}" value="1" @checked($cell->cb_nds ?? true)>
                                                                                    <label class="form-check-label fs-1 fw-normal m-0 ms-1" for="soft_nds_{{ $i }}_{{ $j }}">НДС</label>
                                                                                </div>

{{--                                                                                <div class="fs-2 d-flex align-items-center mt-2 justify-content-start">--}}
{{--                                                                                    <input name="soft_cell[{{ $i }}][{{ $j }}][partner]" class="active form-check-input secondary me-1 cb_partner" type="checkbox" id="soft_partner_{{ $i }}_{{ $j }}" value="1">--}}
{{--                                                                                    <label class="form-check-label fs-1 fw-normal m-0 ms-1" for="soft_partner_{{ $i }}_{{ $j }}">Учитывать скидку партнёра</label>--}}
{{--                                                                                </div>--}}

                                                                                <div>
                                                                                    <span class="text-start fs-2 text-secondary">
                                                                                       <span class="soft_cost_total">0</span> ₽
                                                                                       <span class="fw-bold text-nowrap discount_pad d-none">
                                                                                           <x-ui.icon.regular icon="fa-arrow-right" class="mx-1"/>
                                                                                           <span class="soft_cost_total_with_discount">0</span> ₽
                                                                                       </span>
                                                                                    </span>
                                                                                </div>
                                                                            </div>

                                                                            <div class="text-end">
                                                                                <a href="javascript:void(0)" onclick="javascript:copy_soft($(this))" class="m-1 link cell-active" style="opacity: .4">
                                                                                    <x-ui.icon.regular icon="fa-copy"/>
                                                                                </a>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    @endfor
                                                </tr>
                                            @endfor

                                            {{-- ИТОГО --}}
                                            <tr class="common_soft_add">
                                                <td colspan="3">
                                                    <a href="javascript:void(0);" onclick="javascript:soft_add();" class="fw-bold fs-6 ms-3">
                                                        <x-ui.icon.regular icon="fa-plus"/>
                                                        Добавить ПО
                                                    </a>
                                                </td>
                                                @for($j = 1; $j <= $columns; $j++)
                                                    <td column="{{ $j }}" @class(["border-start p-1", "column_hl" => $j == 1, "d-none" => $j > 1])></td>
                                                @endfor
                                            </tr>
                                            </tbody>
                                            {{-- ПОДИТОГ --}}

                                            <tr class="scenario_header bg-light-danger">
                                                <th style="min-width: 35vw" class="p-1 px-2 border-bottom sort_star" colspan="3">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h4 class="m-0 ms-1 text-danger d-flex align-items-center" style="height: 32px">
                                                            <x-ui.icon.regular icon="fa-scroll" class="me-2"/>
                                                            Сценарии
                                                        </h4>

                                                        <div class="d-flex justify-content-center align-items-center">
                                                            <div id="neuro_nds">
                                                                <input type="checkbox" class="btn-check" id="neuro_nds_cb" checked>
                                                                <label class="
                                                                      btn btn-outline-danger
                                                                      font-weight-medium
                                                                      rounded-pill py-1 fs-7 px-2 m-0 me-1
                                                                    " for="neuro_nds_cb">НДС</label>
                                                            </div>


                                                            <a class="fast_scenario_add d-flex align-items-center fw-bold fs-6" href="javascript:void(0);" onclick="javascript:scenario_add();">
                                                                <x-ui.icon.regular icon="fa-circle-plus fs-7 text-danger"/>
                                                                <span class="ms-2 text-danger">Добавить</span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th colspan="100"/>
                                            </tr>

                                            <tbody id="scenarios">
                                            @for($i = 1; $i <= $rows; $i++)
                                                <tr @class(["scenario once", "d-none" => $i >= 1]) num="{{ $i }}">
                                                    <td style="width: 20px; height: 60px" class="p-1 px-2 pt-2">
                                                        <div class="d-flex flex-column align-items-center" style="width: 20px; height: 100%; align-items: stretch;">
                                                            <div class="flex-grow-1 d-flex flex-column align-items-center">
                                                                <div class="form-check form-check-inline m-0" style="margin-left: 7px!important">
                                                                    <input name="scenario[{{ $i }}][cb_process]" class="cb_process form-check-input danger check-light-danger " type="checkbox" value="1" @checked($scenario->cb_process ?? true)>
                                                                </div>
                                                            </div>

                                                            <x-ui.icon.solid icon="fa-xmark" @class(["soft_delete text-danger cursor-pointer fs-6 mb-1"]) onclick="javascript:scenario_delete($(this))"/>
                                                        </div>
                                                    </td>

                                                    <td style="width: 20px" class="p-1 px-2 text-center pt-2"><span class="num">{{ $i }}</span>.</td>
                                                    <td class="scenario_selector p-0">
                                                        <div class="d-flex">
                                                            <div class="flex-grow-1 pt-1 w-50 overflow-hidden">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="flex-grow-1">
                                                                        <x-ui.select.single name="scenario[{{ $i }}][scenario]" class="w-100" :items="$scenarios" id="id" valueName="name"></x-ui.select.single>
                                                                    </div>
                                                                    <x-ui.icon.regular icon="fa-text" @class(["ms-1 cursor-pointer"]) onclick="javascript:$(this).parents('.scenario_selector').find('.mnemonic_pad').removeClass('d-none'); $(this).addClass('invisible')"/>
                                                                </div>

                                                                <div @class(["mnemonic_pad d-none"])>
                                                                    <div class="fs-7 mt-1">Альтернативное название (отображается в КП)</div>
                                                                    <input name="scenario[{{ $i }}][mnemonic_name]" type="text" class="form-control fs-2 py-1 px-2">
                                                                </div>
                                                            </div>
                                                            <div class="ms-3 w-50">
                                                                <textarea class="neuro_comment" id="{{ \Illuminate\Support\Str::uuid() }}" height="70" name="scenario[{{ $i }}][comment]"></textarea>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    @for($j = 1; $j <= $columns; $j++)
                                                        <td column="{{ $j }}" @class(["border-start p-1", "column_hl" => $j == 1, "d-none" => $j > 1])>
                                                            <div class="cell">
                                                                <div class="form-check d-flex justify-content-between">
                                                                    <input name="cell[{{ $i }}][{{ $j }}][active]" class="active form-check-input mt-7 me-1" type="checkbox" id="flexCheckDefault" checked value="1">

                                                                    <div class="cell-active flex-grow-1">
                                                                        <div class="d-flex justify-content-between fs-1">
                                                                            <span class="text-nowrap fs-6">Кол-во</span>
                                                                            <span class="text-nowrap fs-6">Скидка (%)</span>
                                                                        </div>
                                                                        <div class="input-group">
                                                                            <input name="cell[{{ $i }}][{{ $j }}][count]" type="number" min="1" max="999999" value="1" class="count form-control p-1 fs-6 text-end" aria-label="Text input with checkbox">
                                                                            <input name="cell[{{ $i }}][{{ $j }}][discount]" type="number" min="0" max="99" value="0" class="discount form-control p-1 fs-6 text-end" aria-label="Text input with checkbox">
                                                                        </div>

                                                                        <div class="text-start fs-6 text-secondary mt-1">
                                                                           <input type="hidden" name="cost[{{ $i }}][{{ $j }}]" value="0" class="fs-1 p-0 inp_cost_cell">
                                                                           <a class="cost_cell" href="javascript:void(0);" onclick="javascript:setForcedNeuroCost($(this));">0</a> ₽
                                                                           <span class="fw-bold text-nowrap">
                                                                               <x-ui.icon.regular icon="fa-arrow-right" class="mx-1"/>
                                                                               <span class="cost_cell_total">0</span> ₽
                                                                           </span>
                                                                        </div>

                                                                        <div class="fs-2 d-flex align-items-center mt-2 justify-content-start d-none">
                                                                            <input name="cell[{{ $i }}][{{ $j }}][nds]" class="active form-check-input secondary me-1 cb_nds" type="checkbox" id="nds_{{ $i }}_{{ $j }}" value="1" @checked($cell->cb_nds ?? true)>
                                                                            <label class="form-check-label fs-1 fw-normal m-0 ms-1" for="nds_{{ $i }}_{{ $j }}">НДС</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    @endfor
                                                </tr>
                                            @endfor

                                            <tr class="common_scenario_add">
                                                <td colspan="3">
                                                    <a href="javascript:void(0);" onclick="javascript:scenario_add();" class="fw-bold fs-6 ms-3">
                                                        <x-ui.icon.regular icon="fa-plus"/>
                                                        Добавить сценарий
                                                    </a>
                                                </td>
                                                @for($j = 1; $j <= $columns; $j++)
                                                    <td column="{{ $j }}" @class(["border-start p-1", "column_hl" => $j == 1, "d-none" => $j > 1])></td>
                                                @endfor
                                            </tr>
                                            </tbody>


                                            <tr class="work_header bg-light-warning">
                                                <th style="min-width: 35vw" class="p-1 px-2 border-bottom" colspan="3">
                                                    <div class="d-flex justify-content-between">
                                                        <h4 class="m-0 ms-1 text-warning d-flex align-items-center" style="height: 32px">
                                                            <x-ui.icon.regular icon="fa-person-digging" class="me-2"/>
                                                            Работы
                                                        </h4>

                                                        <a class="fast_work_add d-flex align-items-center fw-bold fs-6" href="javascript:void(0);" onclick="javascript:work_add();">
                                                            <x-ui.icon.regular icon="fa-circle-plus fs-7 text-warning"/>
                                                            <span class="ms-2 text-warning">Добавить</span>
                                                        </a>
                                                    </div>
                                                </th>
                                                <th colspan="100"/>
                                            </tr>

                                            <tbody id="works">
                                            @for($i = 1; $i <= $rows; $i++)
                                                <tr @class(["work once", "d-none" => $i >= 1])>
                                                    <td style="width: 20px; height: 240px" class="p-1 px-2 pt-2">
                                                        <div class="d-flex flex-column align-items-center" style="width: 20px; height: 100%; align-items: stretch;">
                                                            <div class="flex-grow-1 d-flex flex-column align-items-center">
                                                                <div class="form-check form-check-inline m-0" style="margin-left: 7px!important">
                                                                    <input name="work[{{ $i }}][cb_process]" class="cb_process form-check-input warning check-light-warning " type="checkbox" value="1" @checked($work->cb_process ?? true)>
                                                                </div>

                                                                <a class="mt-2" href="javascript:void(0);" onclick="javascript:$(this).parents('tr').find('.select2_works').select2('open');">
                                                                    <x-ui.icon.solid icon="fa-bolt-lightning" class="fs-6"/>
                                                                </a>
                                                            </div>

                                                            <x-ui.icon.solid icon="fa-xmark" @class(["soft_delete text-danger cursor-pointer fs-6 mb-1"]) onclick="javascript:work_delete($(this))"/>
                                                        </div>
                                                    </td>


                                                    <td style="width: 20px" class="p-1 px-2 text-center pt-2"><span class="num">{{ $i }}</span>.</td>
                                                    <td class="p-0 pt-2 work_selector">
                                                        <div class="d-flex">
                                                            <div class="w-50">
                                                                <div class="select2_works_container">
                                                                    <select class="select2_works" >
                                                                        @foreach($works as $work)
                                                                            <option value="{{ $work->id }}" data-lang="{{ $work->lang }}">{{ $work->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="mb-1">Описание</div>
                                                                <textarea name="work[{{ $i }}][extended]" class="work_extended" id="{{ \Illuminate\Support\Str::uuid() }}"></textarea>
                                                            </div>
                                                            <div class="w-50">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div class="mb-1">Примечание</div>

                                                                    <div class="d-flex align-items-center">
                                                                        <span class="me-2">Группа</span>

                                                                        <select name="work[{{ $i }}][group]" class="form-select p-1 flex-grow-0 work_group" style="max-width: 300px; font-size: 0.7rem" >
                                                                            <option value="0" static>Без группы</option>
                                                                            <option value="new" static>[+] Добавить группу</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <textarea name="work[{{ $i }}][notice]" class="work_notice" id="{{ \Illuminate\Support\Str::uuid() }}"></textarea>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    @for($j = 1; $j <= $columns; $j++)
                                                        <td style="height: 240px" column="{{ $j }}" @class(["border-start p-1", "column_hl" => $j == 1, "d-none" => $j > 1])>
                                                            <div class="h-100 d-flex flex-column">
                                                                <div class="cell work_cell flex-grow-1">
                                                                        <div class="form-check d-flex justify-content-start align-items-start flex-grow-0 m-1">
                                                                            <input name="work_cell[{{ $i }}][{{ $j }}][active]" class="active form-check-input mt-7 me-1" type="checkbox" value="1" id="flexCheckDefault" checked value="1">

                                                                            <div class="cell-active flex-grow-1">
                                                                                <div class="d-flex justify-content-between fs-1">
                                                                                    <span class="text-nowrap fs-6">Стоимость</span>
                                                                                    <span class="text-nowrap fs-6">Часов</span>
                                                                                </div>
                                                                                <div class="input-group">
                                                                                    <input name="work_cell[{{ $i }}][{{ $j }}][cost]" type="number" min="1" max="999999" value="7000" class="cost form-control p-1 fs-6 text-end" style="min-width: 60px">
                                                                                    <input name="work_cell[{{ $i }}][{{ $j }}][count]" type="number" min="0" max="9999" value="0" class="count form-control p-1 fs-6 text-end">
                                                                                </div>

                                                                                <div class="fs-2 d-flex align-items-center mt-1 justify-content-start" style="margin-left: 23px">
                                                                                    <input name="work_cell[{{ $i }}][{{ $j }}][nds]" class="active form-check-input secondary me-1 cb_nds" type="checkbox" id="work_nds_{{ $i }}_{{ $j }}" value="1" @checked($cell->cb_nds ?? true)>
                                                                                    <label class="form-check-label fs-6 fw-normal m-0 ms-1" for="work_nds_{{ $i }}_{{ $j }}">включить НДС</label>
                                                                                </div>

{{--                                                                                <div class="fs-2 d-flex align-items-center mt-2 justify-content-end">--}}
{{--                                                                                    <input name="work_cell[{{ $i }}][{{ $j }}][partner]" class="active form-check-input secondary me-1 cb_partner" type="checkbox" id="work_partner_{{ $i }}_{{ $j }}" value="1">--}}
{{--                                                                                    <label class="form-check-label fs-6 fw-normal m-0 ms-1" for="work_partner_{{ $i }}_{{ $j }}">Учитывать скидку партнёра</label>--}}
{{--                                                                                </div>--}}


                                                                                <div class="d-flex justify-content-between fs-1 mt-3">
                                                                                    <span class="text-nowrap fs-6">Скидка клиента</span>
                                                                                </div>
                                                                                <div class="d-flex align-items-center justify-content-start">
                                                                                    <input style="width: 60px" name="work_cell[{{ $i }}][{{ $j }}][discount]" type="number" min="0" max="99" value="0" class="discount form-control p-1 fs-6 text-end">
                                                                                    <span class="text-nowrap">
                                                                                        = <span class="amount_discount">0</span> ₽
                                                                                    </span>
                                                                                </div>


                                                                                <div class="d-flex justify-content-between fs-1 mt-3">
                                                                                    <span class="text-nowrap fs-6">Скидка партнёра</span>
                                                                                </div>
                                                                                <div class="d-flex align-items-center justify-content-start">
                                                                                    <input style="width: 60px" name="work_cell[{{ $i }}][{{ $j }}][discount_partner]" type="number" min="0" max="99" value="0" class="discount_partner form-control p-1 fs-6 text-end">
                                                                                    <span class="text-nowrap ms-1">
                                                                                        = <span class="amount_partner">0</span> ₽
                                                                                    </span>
                                                                                </div>




                                                                                <div class="mt-3 text-start fs-6 text-secondary">
                                                                                    =
                                                                                   <span class="work_cost_total">0</span> ₽
                                                                                   <span class="fw-bold text-nowrap discount_pad d-none">
                                                                                       <x-ui.icon.regular icon="fa-arrow-right" class="mx-1"/>
                                                                                       <span class="work_cost_total_with_discount">0</span> ₽
                                                                                   </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                </div>

                                                                <div>
                                                                    <a href="javascript:void(0)" onclick="javascript:copy_work($(this))" class="m-1 link cell-active" style="opacity: .4">
                                                                        <x-ui.icon.regular icon="fa-copy"/>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    @endfor
                                                </tr>
                                            @endfor


                                            <tr class="common_work_add">
                                                <td colspan="3">
                                                    <a href="javascript:void(0);" onclick="javascript:work_add();" class="fw-bold fs-6 ms-3">
                                                        <x-ui.icon.regular icon="fa-plus"/>
                                                        Добавить работу
                                                    </a>
                                                </td>
                                                @for($j = 1; $j <= $columns; $j++)
                                                    <td column="{{ $j }}" @class(["border-start p-1", "column_hl" => $j == 1, "d-none" => $j > 1])></td>
                                                @endfor
                                            </tr>
                                            </tbody>
                                            {{-- ПОДИТОГ --}}


                                            {{-- СКИДКА ПАРТНЁРУ (ПЛАТФОРМА) --}}
                                            <tr class="platform_partner">
                                                <td colspan="3" class="text-end py-2">
                                                    <div><sup class="me-1"><i class="fa-solid fa-percent"></i></sup> Скидка партнёру (Платформа)</div>
                                                </td>
                                                @for($i = 1; $i <= $columns; $i++)
                                                    <td column="{{ $i }}" @class(["border-start border-bottom flex-grow-0  py-2", "d-none" => $i > 1, "column_hl" => $i == 1])  style="width: 140px!important">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <input name="partner_platform_discount[{{ $i }}]" type="number" min="0" max="99" value="0" class="partner form-control p-1 fs-6 text-end" style="width: 42px">

                                                            <div class="text-end flex-grow-1 text-nowrap ms-2">
                                                                <span class="column_subtotal fs-6">0</span> ₽
                                                            </div>
                                                        </div>
                                                    </td>
                                                @endfor
                                            </tr>

                                            {{-- СКИДКА ПАРТНЁРУ (СЦЕНАРИИ) --}}
                                            <tr class="neuro_partner">
                                                <td colspan="3" class="text-end py-2">
                                                    <div><sup class="me-1"><i class="fa-solid fa-percent"></i></sup> Скидка партнёру (Сценарии)</div>
                                                </td>
                                                @for($i = 1; $i <= $columns; $i++)
                                                    <td column="{{ $i }}" @class(["border-start border-bottom flex-grow-0  py-2", "d-none" => $i > 1, "column_hl" => $i == 1])  style="width: 140px!important">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <input name="partner_neuro_discount[{{ $i }}]" type="number" min="0" max="99" value="0" class="partner form-control p-1 fs-6 text-end" style="width: 42px">

                                                            <div class="text-end flex-grow-1 text-nowrap ms-2">
                                                                <span class="column_subtotal fs-6">0</span> ₽
                                                            </div>
                                                        </div>
                                                    </td>
                                                @endfor
                                            </tr>

                                            {{-- СКИДКА ПАРТНЁРУ (РАБОТЫ) --}}
                                            <tr class="work_partner">
                                                <td colspan="3" class="text-end py-2">
                                                    <div><sup class="me-1"><i class="fa-solid fa-percent"></i></sup> Скидка партнёру (Работы)</div>
                                                </td>
                                                @for($i = 1; $i <= $columns; $i++)
                                                    <td column="{{ $i }}" @class(["border-start border-bottom flex-grow-0  py-2", "d-none" => $i > 1, "column_hl" => $i == 1])  style="width: 140px!important">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div class="text-end flex-grow-1 text-nowrap ms-2">
                                                                <span class="column_subtotal fs-6">0</span> ₽
                                                            </div>
                                                        </div>
                                                    </td>
                                                @endfor
                                            </tr>


                                            {{-- ИТОГО --}}
                                            <tr class="platform_total">
                                                <td colspan="3" class="text-end py-2">
                                                    Платформа
                                                </td>
                                                @for($i = 1; $i <= $columns; $i++)
                                                    <td column="{{ $i }}" @class(["border-start border-bottom flex-grow-0 py-2", "d-none" => $i > 1, "column_hl" => $i == 1])  style="width: 140px!important">
                                                        <div class="text-end fs-6 text-nowrap">
                                                            <span class="column_platform_subtotal cursor-help" title="+ ПО = 0 ₽">0</span> ₽
                                                        </div>
                                                        <div class="fs-7 text-end nds text-info d-none">НДС: <span class="amount">0</span> ₽</div>
                                                    </td>
                                                @endfor
                                            </tr>

                                            <tr class="soft_total">
                                                <td colspan="3" class="text-end py-2">
                                                    ПО
                                                </td>
                                                @for($i = 1; $i <= $columns; $i++)
                                                    <td column="{{ $i }}" @class(["border-start border-bottom flex-grow-0 py-2", "d-none" => $i > 1, "column_hl" => $i == 1])  style="width: 140px!important">
                                                        <div class="text-end fs-6 text-nowrap">
                                                            <span class="column_soft_subtotal">0</span> ₽
                                                        </div>
                                                        <div class="fs-7 text-end nds text-info d-none">НДС: <span class="amount">0</span> ₽</div>
                                                    </td>
                                                @endfor
                                            </tr>

                                            {{-- ИТОГО --}}
                                            <tr class="total">
                                                <td colspan="3" class="text-end py-2">
                                                    Нейросервисы
                                                </td>
                                                @for($i = 1; $i <= $columns; $i++)
                                                    <td column="{{ $i }}" @class(["border-start border-bottom flex-grow-0 py-2", "d-none" => $i > 1, "column_hl" => $i == 1])  style="width: 140px!important">
                                                        <div class="text-end fs-6 text-nowrap">
                                                            <span class="column_subtotal cursor-help" title="+ ПО = 0 ₽">0</span> ₽
                                                        </div>
                                                        <div class="fs-7 text-end nds text-info d-none">НДС: <span class="amount">0</span> ₽</div>
                                                    </td>
                                                @endfor
                                            </tr>


                                            {{-- ИТОГО --}}
                                            <tr class="work_total">
                                                <td colspan="3" class="text-end py-2">
                                                    Работы
                                                </td>
                                                @for($i = 1; $i <= $columns; $i++)
                                                    <td column="{{ $i }}" @class(["border-start border-bottom flex-grow-0 py-2", "d-none" => $i > 1, "column_hl" => $i == 1])  style="width: 140px!important">
                                                        <div class="text-end fs-6 text-nowrap">
                                                            <span class="column_work_subtotal">0</span> ₽
                                                        </div>
                                                        <div class="fs-7 text-end nds text-info d-none">НДС: <span class="amount">0</span> ₽</div>
                                                    </td>
                                                @endfor
                                            </tr>



                                            {{-- НДС --}}
                                            <tr class="proposal_nds d-none">
                                                <td colspan="3" class="text-end py-2">
                                                    НДС (<span id="nds_rate">{{ $nds }}</span>%)
                                                </td>
                                                @for($i = 1; $i <= $columns; $i++)
                                                    <td column="{{ $i }}" @class(["border-start border-top border-bottom flex-grow-0 py-2", "d-none" => $i > 1, "column_hl" => $i == 1])  style="width: 140px!important">
                                                        <div class="text-end fs-6 text-nowrap">
                                                            <span class="total">0</span> ₽
                                                        </div>
                                                    </td>
                                                @endfor
                                            </tr>


                                            {{-- ИТОГО --}}
                                            <tr class="proposal_total">
                                                <td colspan="3" class="text-end py-2 border-top">
                                                    <span class="fw-bold fs-5">ИТОГО</span>
                                                </td>
                                                @for($i = 1; $i <= $columns; $i++)
                                                    <td column="{{ $i }}" @class(["border-start border-top border-bottom flex-grow-0 py-2", "d-none" => $i > 1, "column_hl" => $i == 1])  style="width: 140px!important">
                                                        <div class="text-end fs-6 fw-bold text-nowrap">
                                                            <span class="total">0</span> ₽
                                                        </div>
                                                    </td>
                                                @endfor
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-4">
                                    <div class="text-end">
                                        <button type="button" id="submit" class=" btn btn-info font-weight-medium rounded-pill px-4 disabled" onclick="javascript:sbm();">
                                            <div class="d-flex align-items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                     stroke-linecap="round" stroke-linejoin="round"
                                                     class="feather feather-send feather-sm fill-white me-2">
                                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                                </svg>
                                                @lang('button.create')
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-12">
                <div class="d-md-flex align-items-center mt-3">
                    <div class="ms-auto mt-3 mt-md-0">
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('js')
    @parent

    <script src="/assets/libs/ckeditor/ckeditor.js"></script>
    <script>
        var users = @json($users);
        var company_partner = @json($companies->pluck('partner_id', 'id'));
        var cost_rules = @json($cost_rules);
        var costs = @json($costs);
        var softs = @json($softs);
        var works = @json($works);
        var mode = 'year';
        var cost_total = 0;
        var scenario_count = 0;
        var platform_count = 0;
        var soft_count = 0;
        var work_count = 0;
        var columns = 1;
        var work_groups = [];
        var neuroForceCost = {};

        // логика групп для работ
        function work_group_redraw() {
            // Обновляем все select
            $(".work_group").each(function() {
                currentVal = $(this).val();

                // Сохраняем статические option
                var staticOptions = $(this).find("option[static]").detach();

                // Очищаем select полностью
                $(this).empty();

                // Добавляем первый статический option "Без группы"
                $(this).append(staticOptions.filter('[value="0"]'));

                // Добавляем option из массива work_groups
                $.each(work_groups, function(index, group) {
                    $(this).append($('<option>', {
                        value: group,
                        text: group
                    }));
                }.bind(this));

                // Добавляем последний статический option "[+] Добавить группу"
                $(this).append(staticOptions.filter('[value="new"]'));

                $(this).val(currentVal);
            });
        }

        function work_group_set(obj, new_value) {
            // если выбран пункт "добавить новый"
            if(obj.val() == 'new') {
                new_value = prompt('Укажите название новой группы для работ:');
                if (!new_value) {
                    obj.val(0);
                }
            }

            // Добавляем новую группу, если её ещё нет в массиве
            if(work_groups.indexOf(new_value) === -1) {
                work_groups.push(new_value);
            }

            // Сортируем массив по возрастанию
            work_groups.sort(function(a, b) {
                return a.localeCompare(b);
            });

            work_group_redraw();

            // В текущем select делаем выбранным новый добавленный пункт
            obj.val(new_value);
        }

        // копирование ПО
        function copy_soft(obj) {
            if(!confirm("Вы действительно хотите скопировать значения из этой ячейки в другие ячейки этой строки?")) return;
            cell = obj.parents('td[column]');
            cost = cell.find(".cost").val();
            count = cell.find(".count").val();
            cb_nds = cell.find(".cb_nds").prop("checked");
            cb_partner = cell.find(".cb_partner").prop("checked");

            parent = cell.parents('.once');
            parent.find(".cost").val(cost);
            parent.find(".count").val(count);
            parent.find(".cb_nds").prop("checked", cb_nds);
            parent.find(".cb_partner").prop("checked", cb_partner);

            table_data_recalc();
        }

        // копирование работы
        function copy_work(obj) {
            if(!confirm("Вы действительно хотите скопировать значения из этой ячейки в другие ячейки этой строки?")) return;
            cell = obj.parents('td[column]');
            cost = cell.find(".cost").val();
            count = cell.find(".count").val();
            cb_nds = cell.find(".cb_nds").prop("checked");
            cb_partner = cell.find(".cb_partner").prop("checked");

            parent = cell.parents('.once');
            parent.find(".cost").val(cost);
            parent.find(".count").val(count);
            parent.find(".cb_nds").prop("checked", cb_nds);
            parent.find(".cb_partner").prop("checked", cb_partner);

            table_data_recalc();
        }

        // удаление нейро
        function scenario_delete(obj) {
            if(!confirm("Удалить строку? Все данные в этой строке потеряются...")) return;
            scenario_count--;
            $("#table_data").attr("count", scenario_count);
            obj.parents("tr").remove();

            // пересчитаем
            $("#table_data tr.scenario.once").each(function(index, tr) {
                $(this).find(".num").html(index + 1);
            });

            table_data_recalc();
        }

        // добавление нейро
        function scenario_add() {
            scenario_count++;
            row = $("#table_data tr.scenario.once.d-none:first");
            let num = row.attr("num");
            row.removeClass("d-none");
            row.find("select").select2().on('change', function() {
                // Получаем значение выбранного пункта
                var selectedValue = $(this).val();  // id сценария


                // обойдём все колонки
                $(this).parents('.scenario.once').find('td[column]').each(function(column_index, column) {
                    let cell = row.find(`td[column='${column_index + 1}']`);
                    updateNeuroCostFromRule(cell);
                });

                $(this).parents('.scenario.once').find('td[column].d-none').each(function() {
                    // определим тип колонки
                    $(this).find('.inp_cost_cell').val(costs['year'][selectedValue]);
                });
                table_data_recalc();
            });

            ta = row.find("textarea.neuro_comment");
            CKEDITOR.replace(ta.attr("id"), {height: ta.attr("height"), toolbar: [{name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'TextColor', 'BGColor']}, {name: 'paragraph', items: ['NumberedList', 'BulletedList']}, {name: 'styles', items: ['Styles']}]});

            $("#table_data").attr("count", scenario_count);

            table_data_recalc();
        }

        // удаление платформы
        function platform_delete(obj) {
            if(!confirm("Удалить строку? Все данные в этой строке потеряются...")) return;
            platform_count--;
            $("#table_data").attr("platform_count", platform_count);
            obj.parents("tr").remove();

            // пересчитаем
            $("#table_data tr.platform.once").each(function(index, tr) {
                $(this).find(".num").html(index + 1);
            });

            table_data_recalc();
        }

        // добавление платформы
        function platform_add () {
            lang = $('input[name="lang"]:checked').val();

            platform_count++;
            $("#table_data").attr("platform_count", platform_count);

            row = $("#table_data tr.platform.once.d-none:first");
            row.removeClass("d-none");

            if(lang !== 'ru') row.find("textarea.platform_extended").val('{{ __('proposal.textarea__platform_extended', [], 'en') }}');

            id = row.find("textarea.platform_extended").attr("id");

            CKEDITOR.replace(id, {
                height: $(this).attr("height") ?? 150,
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
            var editor = CKEDITOR.instances[id];
            editor.on('change', function() {
                delayedCheck();
            });

            if(lang !== 'ru') row.find("textarea.platform_notice").val('{{ __('proposal.textarea__platform_notice', [], 'en') }}');

            id = row.find("textarea.platform_notice").attr("id");
            CKEDITOR.replace(id, {
                height: 150,
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
            var editor = CKEDITOR.instances[id];
            editor.on('change', function() {
                delayedCheck();
            });

            table_data_recalc();
        }

        // удаление ПО
        function soft_delete(obj) {
            if(!confirm("Удалить строку? Все данные в этой строке потеряются...")) return;
            soft_count--;
            $("#table_data").attr("soft_count", soft_count);
            obj.parents("tr").remove();

            // пересчитаем
            $("#table_data tr.soft.once").each(function(index, tr) {
                $(this).find(".num").html(index + 1);
            });

            table_data_recalc();
        }

        // добавление ПО
        function soft_add () {
            soft_count++;
            $("#table_data").attr("soft_count", soft_count);

            row = $("#table_data tr.soft.once.d-none:first");
            row.removeClass("d-none");
            row.find("select").select2();

            id = row.find("textarea.soft_extended").attr("id");
            CKEDITOR.replace(id, {
                height: $(this).attr("height") ?? 150,
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
            var editor = CKEDITOR.instances[id];
            editor.on('change', function() {
                delayedCheck();
            });

            id = row.find("textarea.soft_notice").attr("id");
            CKEDITOR.replace(id, {
                height: 150,
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
            var editor = CKEDITOR.instances[id];
            editor.on('change', function() {
                delayedCheck();
            });

            table_data_recalc();
        }

        // удаление работы
        function work_delete(obj) {
            if(!confirm("Удалить строку? Все данные в этой строке потеряются...")) return;
            work_count--;
            $("#table_data").attr("work_count", work_count);
            obj.parents("tr").remove();

            // пересчитаем
            $("#table_data tr.work.once").each(function(index, tr) {
                $(this).find(".num").html(index + 1);
            });

            table_data_recalc();
        }

        // добавление работы
        function work_add () {
            work_count++;
            $("#table_data").attr("work_count", work_count);

            row = $("#table_data tr.work.once.d-none:first");
            row.removeClass("d-none");
            row.find(".select2_works").select2({
                templateResult: function(option) {
                    // Получаем выбранный язык
                    var currentLang = $('input[name="lang"]:checked').val();
                    // Сравниваем с data-lang опции
                    if ($(option.element).data('lang') !== currentLang) {
                        return null; // Скрываем опцию
                    }
                    return option.text; // Отображаем текст опции
                }
            });

            id = row.find("textarea.work_extended").attr("id");
            CKEDITOR.replace(id, {
                height: 150,
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
            var editor = CKEDITOR.instances[id];
            editor.on('change', function() {
                delayedCheck();
            });

            id = row.find("textarea.work_notice").attr("id");
            CKEDITOR.replace(id, {
                height: 150,
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
            var editor = CKEDITOR.instances[id];
            editor.on('change', function() {
                delayedCheck();
            });


            table_data_recalc();
        }

        // удаление варианта
        function column_delete(obj) {
            if(!confirm("Удалить столбец? Все данные в этом столбце потеряются...")) return;


            column_p = obj.parents("th[column]").attr("column");
            $("th[column='" + column_p + "'],td[column='" + column_p + "']").remove();

            if(!$("input[name='period_main']:checked").length) {
                $("input[name='period_main']").first().click();
            }

            columns = $("th[column]:not(.d-none)").length;
            $("#table_data").attr("column", columns);

            table_data_recalc();
        }

        // добавление варианта
        function column_add() {
            column_id = $("th[column].d-none").first().attr("column");

            $("#table_data th[column='" + column_id + "'] .period_active").prop("checked", true);
            $("#table_data th[column='" + column_id + "'], #table_data td[column='" + column_id + "']").removeClass("d-none");

            var variants_count = $("th[column]:not(.d-none)").length;
            $("#table_data").attr("column", variants_count);
            if(!$("th[column].d-none").length) {
                $("#column_add").addClass("d-none");
            }

            // обновим сценарии
            $("#table_data tr.scenario:not(.d-none) td[column]:not(.d-none)").each(function() {
                updateNeuroCostFromRule($(this));
            });
            table_data_recalc();
        }

        // изменение стоимости нейро
        function change_cost(obj) {
            price = prompt('Введите новую стоимость для этого сценария (для пилота указывается цена за 1 месяц, для годовой цена указывается за 1 год)');
            if(!(price - 0 >= 0)) return;
            cell = obj.parents('.cell');
            cell.find(".inp_cost_cell").val(price);

            table_data_recalc();
        }

        // изменение стоимости платформы
        function platform_change_cost(obj) {

            price = prompt('Введите новую стоимость для платформы (для пилота указывается цена за месяц, для годовой цена указывается за 1 год)');
            if(!(price - 0 > 0)) return;
            cell = obj.parents('.cell');
            cell.find(".inp_cost_cell").val(price);

            table_data_recalc();
        }

        // пересчёт всех данных
        function table_data_recalc() {
            nds_global = false;
            var nds = $("input[name='nds']").val() - 0;
            $("#nds_rate").html(nds);

            var data = [];
            $("th[column]:not(.d-none)").each(function() {  // обходим варианты
                column_th = $(this);
                column_i = column_th.attr("column") - 0;

                platform_partner_percent = $("tr.platform_partner td[column='" + column_i + "'] input.partner").val() - 0 ?? 0;
                platform_partner = 0;

                neuro_partner_percent = $("tr.neuro_partner td[column='" + column_i + "'] input.partner").val() - 0 ?? 0;
                neuro_partner = 0;

                soft_partner_percent = $("tr.soft_partner td[column='" + column_i + "'] input.partner").val() - 0 ?? 0;
                soft_partner_percent = 0;

                soft_partner = 0;

                work_partner = 0;

                period_mode = column_th.find(".period").val() ?? 'year'
                period_count = period_mode !== 'unlimited' ? column_th.find(".count").val() ?? 1 : 1;


                platform_total = platform_cost_total = platform_discount_total = 0;
                cost_total = 0;
                platform_nds = 0;
                neuro_nds = 0;
                discount_total = 0
                column_total = 0;
                partner_total = 0;


                // SOFT
                if(1) {
                        soft_subtotal = soft_discount = soft_nds = soft_partner = 0;
                        $("#table_data tr.soft.once:not(.d-none)").each(function () {
                            process = $(this).find(".cb_process").prop("checked");

                            cell = $(this).find("td[column='" + column_i + "']");
                            if (!cell.find('input.active').prop("checked")) return;

                            count = cell.find('.count').val() - 0;
                            cost = cell.find('.cost').val() - 0;
                            discount_customer = cell.find('.discount').val() - 0;
                            total = cost * count;

                            cell.find(".soft_cost_total").html(cost_normalize(total, true));

                            show_extended_total = false;
                            soft_customer_discount = 0;

                            // проверим скидку заказчика
                            if (discount_customer > 0) {
                                show_extended_total = true;
                                soft_customer_discount = (total / 100 * discount_customer);
                                if (process) soft_discount += soft_customer_discount;
                            } else {
                                work_customer_discount = 0;
                            }

                            // проверим скидку партнёра
                            if (cell.find('input.cb_partner').prop("checked")) {
                                show_extended_total = true;
                                soft_partner_discount = ((total - soft_customer_discount) / 100 * soft_partner_percent);
                                if (process) soft_discount += soft_partner_discount;
                            } else {
                                soft_partner_discount = 0;
                            }

                            if (show_extended_total) {
                                cell.find('.discount_pad').removeClass("d-none");
                                cell.find('.soft_cost_total_with_discount').html(cost_normalize(total - soft_partner_discount - soft_customer_discount, true));
                            } else {
                                cell.find('.discount_pad').addClass("d-none");
                            }

                            // проверим НДС
                            if (process) {
                                if (cell.find('input.cb_nds').prop("checked")) {
                                    soft_nds += Math.round((total - soft_partner_discount - soft_customer_discount) / 100 * nds);
                                }
                                soft_subtotal += total;

                                // скидка партнёра
                                soft_partner += soft_partner_discount;
                                soft_partner += soft_customer_discount;
                            }

                        });

                        // $("tr.soft_subtotal td[column='" + column_i + "'] .column_subtotal").html(cost_normalize(soft_subtotal, true));

                        // скидка партнёра (глобально)
                        soft_partner = Math.round((cost_total - discount_total) * (soft_partner_percent / 100));

                        $("tr.soft_partner td[column='" + column_i + "'] .column_subtotal").html(cost_normalize(soft_partner));
                        partner_total += soft_partner;

                        //$("tr.partner td[column='" + column_i + "'] .column_subtotal").html(cost_normalize(partner_total));
                        soft_total = cost_total - discount_total - soft_partner;





                        // НДС
                        if (soft_nds) {
                            $(".soft_total td[column='" + column_i + "'] .nds").removeClass("d-none").find(".amount").html(cost_normalize(soft_nds, true));
                        } else {
                            $(".soft_total td[column='" + column_i + "'] .nds").addClass("d-none");
                        }
                    }

                // ПЛАТФОРМА
                if(1) {
                        $("#table_data tr.platform.once:not(.d-none)").each(function () {
                            process = $(this).find(".cb_process").prop("checked");
                            cell = $(this).find("td[column='" + column_i + "']");
                            if (!cell.find('input.active').prop("checked")) return;

                            count = cell.find(".count").val() ?? 1;
                            discount = cell.find(".discount").val() ?? 0;
                            cost = cell.find(".inp_cost_cell").val() ?? 0;
                            cost *= period_count;

                            // стоимость
                            cost_cell = Math.round(cost * count);

                            // скидка
                            cost_discount = Math.round(cost_cell * (discount / 100));

                            if (process) {
                                platform_cost_total += cost_cell;
                                platform_discount_total += cost_discount;
                            }

                            // итого
                            cell.find(".cost_cell").html(cost_normalize(cost, true));
                            cell.find(".cost_cell_total").html(cost_normalize(cost_cell - cost_discount, true));
                        });

                        $("tr.subtotal td[column='" + column_i + "'] .column_subtotal").html(cost_normalize(platform_cost_total, true));
                        $("tr.discount td[column='" + column_i + "'] .column_subtotal").html(cost_normalize(platform_cost_total - platform_discount_total, true));

                        // скидка партнёра
                        platform_partner = Math.round((platform_cost_total - platform_discount_total) * (platform_partner_percent / 100))
                        $("tr.platform_partner td[column='" + column_i + "'] .column_subtotal").html(cost_normalize(platform_partner));
                        partner_total += platform_partner;

                        platform_total = platform_cost_total - platform_discount_total - platform_partner;

                        // проверим НДС
                        if ($("input#platform_nds_cb").prop("checked")) {
                            platform_nds = ((platform_total) / 100 * nds);
                        }

                        // НДС
                        if (platform_nds) {
                            $(".platform_total .nds").removeClass("d-none").find(".amount").html(cost_normalize(platform_nds, true));
                        } else {
                            $(".platform_total .nds").addClass("d-none");
                        }

                        $("tr.platform_total td[column='" + column_i + "'] .column_platform_subtotal")
                            .html(cost_normalize(platform_total, true))
                        ;
                }

                // NEURO
                if(1) {
                        $("#table_data tr.scenario.once:not(.d-none)").each(function () {
                            process = $(this).find(".cb_process").prop("checked");

                            scenario_id = $(this).find("select").val();

                            cell = $(this).find("td[column='" + column_i + "']");
                            if (!cell.find('input.active').prop("checked")) return;

                            count = cell.find(".count").val() ?? 1;
                            discount = cell.find(".discount").val() ?? 0;
                            cost = cell.find(".inp_cost_cell").val() ?? 0;
                            cost *= period_count;

                            // стоимость
                            cost_cell = Math.round(cost * count);

                            // скидка
                            cost_discount = Math.round(cost_cell * (discount / 100));

                            if (process) {
                                cost_total += cost_cell;
                                discount_total += cost_discount;
                            }


                            // итого
                            cell.find(".cost_cell").html(cost_normalize(cost, true));
                            cell.find(".cost_cell_total").html(cost_normalize(cost_cell - cost_discount, true));
                        });

                        $("tr.subtotal td[column='" + column_i + "'] .column_subtotal").html(cost_normalize(cost_total, true));
                        $("tr.discount td[column='" + column_i + "'] .column_subtotal").html(cost_normalize(cost_total - discount_total, true));

                        // скидка партнёра (глобально)
                        neuro_partner = Math.round((cost_total - discount_total) * (neuro_partner_percent / 100))
                        $("tr.neuro_partner td[column='" + column_i + "'] .column_subtotal").html(cost_normalize(neuro_partner));
                        partner_total += neuro_partner;

                        //$("tr.partner td[column='" + column_i + "'] .column_subtotal").html(cost_normalize(partner_total));
                        scenario_total = cost_total - discount_total - neuro_partner;


                        // проверим НДС
                        if ($("input#neuro_nds_cb").prop("checked")) {
                            neuro_nds = ((scenario_total) / 100 * nds);
                        }

                        // НДС
                        if (neuro_nds) {
                            $(".total .nds").removeClass("d-none").find(".amount").html(cost_normalize(neuro_nds, true));
                        } else {
                            $(".total .nds").addClass("d-none");
                        }

                        $("tr.total td[column='" + column_i + "'] .column_subtotal")
                            .html(cost_normalize(scenario_total, true))
                        ;
                }

                // WORK
                if(1) {
                        work_subtotal = work_discount = work_nds = work_partner = 0;
                        $("#table_data tr.work.once:not(.d-none)").each(function () {
                            process = $(this).find(".cb_process").prop("checked");

                            cell = $(this).find("td[column='" + column_i + "']");
                            if (!cell.find('input.active').prop("checked")) return;

                            count = cell.find('.count').val() - 0;
                            cost = cell.find('.cost').val() - 0;
                            discount_customer = cell.find('.discount').val() - 0;
                            total = cost * count;

                            cell.find(".work_cost_total").html(cost_normalize(total, true));


                            show_extended_total = false;
                            work_customer_discount = 0;
                            // проверим скидку заказчика
                            if (discount_customer > 0) {
                                show_extended_total = true;
                                work_customer_discount = (total / 100 * discount_customer);
                                if (process) work_discount += work_customer_discount;
                            } else {
                                work_customer_discount = 0;
                            }
                            cell.find(".amount_discount").html(cost_normalize(work_customer_discount));

                            work_partner_percent = cell.find(".discount_partner").val() - 0;

                            // проверим скидку партнёра
                            if (work_partner_percent > 0) {
                                show_extended_total = true;
                                work_partner_discount = ((total - work_customer_discount) / 100 * work_partner_percent);
                                if (process) work_discount += work_partner_discount;
                            } else {
                                work_partner_discount = 0;
                            }
                            cell.find(".amount_partner").html(cost_normalize(work_partner_discount));

                            if (show_extended_total) {
                                cell.find('.discount_pad').removeClass("d-none");
                                cell.find('.work_cost_total_with_discount').html(cost_normalize(total - work_partner_discount - work_customer_discount));
                            } else {
                                cell.find('.discount_pad').addClass("d-none");
                            }


                            // проверим НДС
                            if (process) {
                                if (cell.find('input.cb_nds').prop("checked")) {
                                    work_nds += ((total - work_partner_discount - work_customer_discount) / 100 * nds);
                                }

                                // скидка партнёра
                                work_partner += work_partner_discount;
                                // work_partner += work_customer_discount;

                                work_subtotal += total;
                            }
                        });


                        $("tr.work_subtotal td[column='" + column_i + "'] .column_subtotal").html(cost_normalize(work_subtotal, true));

                        // partner
                        $("tr.work_partner td[column='" + column_i + "'] .column_subtotal").html(cost_normalize(work_partner, true));

                        work_total = work_subtotal - work_discount;
                        $("tr.work_total td[column='" + column_i + "'] .column_work_subtotal").html(cost_normalize(work_total, true));


                        $("tr.work_total td[column='" + column_i + "'] .column_work_subtotal").html(cost_normalize(work_total, true));


                        // $("tr.partner td[column='" + column_i + "'] .column_subtotal").html(cost_normalize(work_partner + soft_partner + partner_total, true));


                        // НДС
                        if (work_nds) {
                            $(".work_total .nds").removeClass("d-none").find(".amount").html(cost_normalize(work_nds, true));
                        } else {
                            $(".work_total .nds").addClass("d-none");
                        }
                }


                // НДС global
                nds_total = work_nds + soft_nds + neuro_nds + platform_nds;
                if(nds_total) nds_global = true;

                $("tr.proposal_nds td[column='" + column_i + "'] .total").html(cost_normalize(nds_total, true));

                // TOTAL
                $("tr.proposal_total td[column='" + column_i + "'] .total").html(cost_normalize(platform_total + scenario_total + work_total + soft_total + nds_total, true));
            });




            if(nds_global) {
                $(".proposal_nds").removeClass("d-none");
            } else {
                $(".proposal_nds").addClass("d-none");
            }

            discount = $("#discount").val()-0;
            cost_total -= discount;
            cost_total += nds_total;
            $(".cost_total").html(cost_normalize(cost_total, true));

            form_check();
        }

        // отложенная проверка
        function delayedCheck() {
            // Clear any existing timeout
            clearTimeout(window.to_check);

            // Set a new timeout
            window.to_check = setTimeout(function() {
                form_check(); // Call the form_check function after 1000 ms
            }, 1000);
        }

        // проверка формы
        function form_check() {

            var err = 0;
            $("input[required],select[required]").each(function() {
               if($(this).attr("type") == "checkbox") {
                   if(!$(this).prop("checked")) err++;
               } else if(!$(this).val()) err++;
            });



            block_good = 0;

            // PLATFORM
            $("th[column]:not(.d-none)").each(function() {
                column_th = $(this);
                column_i = column_th.attr("column") - 0;
                if($("#table_data tbody#platforms tr.once:not(.d-none)").length > 0) {
                    block_good++;
                    has_active_cells = false;
                    $("#table_data tbody#platforms tr.once:not(.d-none)").each(function () {

                        // если не отмечена главная галочка, пропускаем проверку всех столбцов
                        force_active = !$(this).find(".cb_process").prop("checked");

                        cell = $(this).find("td[column='" + column_i + "']");
                        if (cell.find('input.active').prop("checked")) {
                            has_active_cells = true;
                            return;
                        }

                        // ПРОВЕРИМ, ЕСТЬ ЛИ ВЫБРАННЫЕ ЯЧЕЙКИ
                        active_cell = 0;
                        $(this).find('td:not(.d-none)').each(function() {
                            if($(this).find("input.active").prop("checked")) active_cell++;
                        });
                        if(!force_active && !active_cell) err++;
                    });
                    if (!force_active && !has_active_cells) err++;
                }
            });

            // SCENARIOS
            $("th[column]:not(.d-none)").each(function() {
                column_th = $(this);
                column_i = column_th.attr("column") - 0;
                if($("#table_data tbody#scenarios tr.once:not(.d-none)").length > 0) {
                    block_good++;
                    has_active_cells = false;
                    $("#table_data tbody#scenarios tr.once:not(.d-none)").each(function () {

                        // если не отмечена главная галочка, пропускаем проверку всех столбцов
                        force_active = !$(this).find(".cb_process").prop("checked");

                        if ($(this).hasClass("scenario_selected")) {
                            scenario_id = $(this).find("select").val();
                            if (scenario_id) {
                                cell = $(this).find("td[column='" + column_i + "']");
                                if (cell.find('input.active').prop("checked")) {
                                    has_active_cells = true;
                                    return;
                                }
                            }
                        } else {
                            err++;
                        }


                        // ПРОВЕРИМ, ЕСТЬ ЛИ ВЫБРАННЫЕ ЯЧЕЙКИ
                        active_cell = 0;
                        $(this).find('td:not(.d-none)').each(function() {
                            if($(this).find("input.active").prop("checked")) active_cell++;
                        });
                        if(!force_active && !active_cell) err++;

                    });
                    if (!force_active && !has_active_cells) err++;
                }
            });

            // WORKS
            if($("#table_data tbody#works tr.once:not(.d-none)").length > 0) {
                block_good++;
                $("#table_data tbody#works tr.once:not(.d-none)").each(function() {

                    // если не отмечена главная галочка, пропускаем проверку всех столбцов
                    force_active = !$(this).find(".cb_process").prop("checked");

                    // ПРОВЕРИМ TEXTAREA
                    check_ta = false;
                    $(this).find("textarea.work_extended").each(function() {
                        var editorInstance = CKEDITOR.instances[$(this).attr("id")];
                        if (editorInstance)
                            if(stripTags(editorInstance.getData()).length > 0) check_ta = true;
                    });
                    if(!check_ta) err++;


                    // ПРОВЕРИМ, ЕСТЬ ЛИ ВЫБРАННЫЕ ЯЧЕЙКИ
                    active_cell = 0;
                    $(this).find('td:not(.d-none)').each(function() {
                       if($(this).find("input.active").prop("checked")) active_cell++;
                    });
                    if(!force_active && !active_cell) err++;
                });
            }

            // SOFT
            if($("#table_data tbody#softs tr.once:not(.d-none)").length > 0) {
                block_good++;
                $("#table_data tbody#softs tr.once:not(.d-none)").each(function() {

                    // если не отмечена главная галочка, пропускаем проверку всех столбцов
                    force_active = !$(this).find(".cb_process").prop("checked");

                    // ПРОВЕРИМ TEXTAREA
                    check_ta = false;
                    $(this).find("textarea.soft_extended").each(function() {
                        var editorInstance = CKEDITOR.instances[$(this).attr("id")];
                        if (editorInstance)
                            if(stripTags(editorInstance.getData()).length > 0) check_ta = true;
                    });
                    if(!check_ta) err++;

                    // ПРОВЕРИМ, ЕСТЬ ЛИ ВЫБРАННЫЕ ЯЧЕЙКИ
                    active_cell = 0;
                    $(this).find('td:not(.d-none)').each(function() {
                       if($(this).find("input.active").prop("checked")) active_cell++;
                    });
                    if(!force_active && !active_cell) err++;
                });
            }

            if(!block_good) err++;

            if(err) {
                $("#submit").addClass("disabled");
            } else {
                $("#submit").removeClass("disabled");
            }

            return err == 0;
        }

        function sbm() {
            if(!form_check) return;
            if(!confirm("Вы действительно хотите это сделать?")) return;


            $("body").block(block_default);

            // удалим лишнее
            $(".once.d-none").remove();

            // прокинем textarea

            for (var id in CKEDITOR.instances) {
                if (CKEDITOR.instances.hasOwnProperty(id)) {
                    var instance = CKEDITOR.instances[id];
                    $("textarea#" + id).val(instance.getData());
                }
            }

            var formData = $("form#form_create").serializeArray();
            // formData.push({
            //     name: 'neuroForceCost',
            //     value: JSON.stringify(neuroForceCost)
            // });

            $.ajax({
                url: "{{ route('api.proposal.store', ['_token' => _token() ]) }}",
                type: "PUT",
                dataType: "json",
                data: $.param(formData), // сюда надо добавить данные из переменной neuroForceCost
                success: function (response) {
                    if (response.result == 'success') {
                        location.replace(response.url);
                    } else {
                        toastr.error("Не получилось сохранить данные", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        $("body").unblock();
                    }
                },
                error: function () {
                    toastr.error("Не получилось сохранить данные", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $("body").unblock();
                }
            });
        }

        $(document).ready(function() {
            // work_groups
            $(".work_group").on("change", function() {
                work_group_set($(this), $(this).val());
            });
            work_group_redraw();

            $(".currency_selector").on("change", function() {
                if($(this).val() == '{{ \App\Modules\Pub\Currency\Models\Currency::CURRENCY_DEFAULT }}') {
                    $("#currency-pad").addClass("d-none");
                } else {
                    $("#currency-pad").removeClass("d-none");
                }
            });


            $("input#neuro_nds_cb").on("change", function() {
                prop = $(this).prop("checked");
                $(".scenario.once input.cb_nds").prop("checked", prop);
            });

            $("input#platform_nds_cb").on("change", function() {
                prop = $(this).prop("checked");
                $(".platform.once input.cb_nds").prop("checked", prop);
            });

            // $("input.partner").on("keyup change", function() {
            //     discount = $(this).val();
            //     column = $(this).parents("td[column]").attr("column") - 0;
            //
            //     $("td[column='" + column + "'] input.partner").val(discount);
            // });

            $("select[name='manager']").on("change", function() {
                ret = users[$(this).val()]['initials'] + '{{ $max_number }}';
                $("input[name='number']").val(ret);
            });

            $("input[name='period_main']").on("change", function() {
                var main_column = $(this).val();

                $("tr.main_selector label").removeClass("btn-outline-primary").addClass("btn-light-info");
                $("tr.main_selector td[column='" + main_column + "'] label").removeClass("btn-light-info").addClass("btn-info");

                $("#table_data th.column_hl, #table_data td.column_hl").removeClass("column_hl");
                $("#table_data th[column='" + main_column + "'], #table_data td[column='" + main_column + "']").addClass("column_hl");
            });
            var sortable_target = 0;

            $("input[required],select[required]").on("keyup change", function() {
                form_check();
            }) ;

            $(".period").on("change", function() {
                var selector = $(this);
                var previousValue = $(this).attr('prev-value');
                var newValue = $(this).val();


                if($(this).val() == 'unlimited') {
                    $(this).next('input').addClass("d-none");
                } else {
                    $(this).next('input').removeClass("d-none");
                }

                if(previousValue == 'year') {
                    k = 1;
                } else if(previousValue == 'pilot') {
                    monthes = $(this).parents('.input-group').find('.count').val();
                    k = 1/monthes * 12;
                } else {
                    k = 1/3;
                }

                if(newValue == 'year') {
                    k *= 1;
                } else if(newValue == 'pilot') {
                    monthes = $(this).parents('.input-group').find('.count').val();
                    k *= monthes / 12;
                } else {
                    k *= 3;
                }

                // пересчитаем стоимости
                var column = $(this).parents('th[column]').attr("column");
                $("tr.once td[column='" + column + "'] .inp_cost_cell").each(function() {
                    $(this).val(Math.round($(this).val() * k));
                    updateNeuroCostFromRule($(this).parents('td'));
                });


                $(this).attr('prev-value', newValue);
                table_data_recalc();
            });

            $('input[ name="period"]').on("change", function() {
                mode = $(this).val();
                if(mode !== 'pilot') {
                    $("#period_month").addClass("d-none");
                } else {
                    $("#period_month").removeClass("d-none");
                }
                if(mode !== 'year') {
                    $("#year_count").addClass("d-none");
                } else {
                    $("#year_count").removeClass("d-none");
                }
                table_data_recalc();
            });
            $("#period_month").on("keyup change", function() {
                if($(this).val() - 0 > 11) $(this).val(11);
                if($(this).val() - 0 < 1) $(this).val(1);
                table_data_recalc();
            });
            $("#year_count").on("keyup change", function() {
                if($(this).val() - 0 > 4) $(this).val(4);
                if($(this).val() - 0 < 1) $(this).val(1);
                table_data_recalc();
            });
            $("#discount").on("keyup change", function() {
                if($(this).val() - 0 > 9999999) $(this).val(9999999);
                if($(this).val() - 0 < 0) $(this).val(0);
                table_data_recalc();
            });

            $("#scenarios select[select2]").select2().on('change', function() {
                // Получаем значение выбранного пункта
                var selectedValue = $(this).val();

                // Находим родительский tr
                var parentTr = $(this).closest('tr');

                // Проверяем, есть ли родительский tr и выбран ли пункт с value > 0
                if (parentTr.length) {
                    if (selectedValue > 0) {
                        parentTr.addClass('scenario_selected'); // Добавляем класс
                    } else {
                        parentTr.removeClass('scenario_selected'); // Убираем класс
                    }
                    table_data_recalc();
                }
            });

            $(".select2_softs").select2().on('change', function() {
                if($(this).val()) {
                    // exteded
                    text = `<p>` + softs[$(this).val()]['name'] + `</p>` + (softs[$(this).val()]['extended'] ?? '');
                    $(this).parents(".soft_selector").find("textarea.soft_extended").val(text);     // надо обновить прикрепленный ckeditor
                    var editorInstance = CKEDITOR.instances[$(this).parents(".soft_selector").find("textarea.soft_extended").attr("id")];
                    if (editorInstance) {
                        editorInstance.setData(text); // Устанавливаем новое значение
                    }

                    // notice
                    text = softs[$(this).val()]['notice'];
                    $(this).parents(".soft_selector").find("textarea.soft_notice").val(text);     // надо обновить прикрепленный ckeditor
                    var editorInstance = CKEDITOR.instances[$(this).parents(".soft_selector").find("textarea.soft_notice").attr("id")];
                    if (editorInstance) {
                        editorInstance.setData(text); // Устанавливаем новое значение
                    }

                    // nds
                    cb_nds = softs[$(this).val()]['cb_nds'];
                    $(this).parents(".soft.once").find(".cb_nds").prop("checked", cb_nds);


                    $(this).parents("tr").find(".count").val(softs[$(this).val()]['count'] ?? 0);
                    $(this).parents("tr").find(".cost").val(softs[$(this).val()]['cost'] ?? 0);
                    $(this).val(0).trigger('change'); // очистить значение

                    table_data_recalc();
                }
            });

            $(".select2_works").select2().on('change', function() {
                if($(this).val()) {
                    // exteded
                    // text = `<p>` + works[$(this).val()]['name'] + `</p>` + (works[$(this).val()]['extended'] ?? '');
                    text = (works[$(this).val()]['extended'] ?? works[$(this).val()]['name']);
                    $(this).parents(".work_selector").find("textarea.work_extended").val(text);     // надо обновить прикрепленный ckeditor
                    var editorInstance = CKEDITOR.instances[$(this).parents(".work_selector").find("textarea.work_extended").attr("id")];
                    if (editorInstance) {
                        editorInstance.setData(text); // Устанавливаем новое значение
                    }

                    // notice
                    text = works[$(this).val()]['notice'];
                    $(this).parents(".work_selector").find("textarea.work_notice").val(text);     // надо обновить прикрепленный ckeditor
                    var editorInstance = CKEDITOR.instances[$(this).parents(".work_selector").find("textarea.work_notice").attr("id")];
                    if (editorInstance) {
                        editorInstance.setData(text); // Устанавливаем новое значение
                    }

                    // group
                    work_group_set($(this).parents('.work.once').find(".work_group"), works[$(this).val()]['group']);





                    $(this).parents("tr").find(".count").val(works[$(this).val()]['count'] ?? 0);
                    $(this).parents("tr").find(".cost").val(works[$(this).val()]['cost'] ?? 0);
                    $(this).val(0).trigger('change'); // очистить значение

                    table_data_recalc();
                    $(this).trigger('close'); // очистить значение
                }
            });


            $("select[name='company']").on("change", function() {
                partner_id = company_partner[$(this).val()];
                $("select[name='partner']").val(partner_id).trigger('change');
            });

            $("#table_data input, #table_data select").on("keyup change", function() {
                table_data_recalc();
            });


            $("tr.platform input.count").on("keyup change", function() {
                updateNeuroCostFromRule($(this).parents('td'));
                table_data_recalc();
            });
            $("tr.scenario input.count").on("keyup change", function() {
                updateNeuroCostFromRule($(this).parents('td'));
                table_data_recalc();
            });





            // логика при смене языка
            $('input[name="lang"]').on('change', function() {
                // Определяем направление перевода
                var lang = $(this).val();
                var translate = {
                    ru: {
                        extended: {
                            from: '{{ __('proposal.textarea__platform_extended', [], 'en') }}',
                            to: '{{ __('proposal.textarea__platform_extended', [], 'ru') }}',
                        },
                        notice: {
                            from: '{{ __('proposal.textarea__platform_notice', [], 'en') }}',
                            to: '{{ __('proposal.textarea__platform_notice', [], 'ru') }}',
                        },
                    },
                    en: {
                        extended: {
                            from: '{{ __('proposal.textarea__platform_extended', [], 'ru') }}',
                            to: '{{ __('proposal.textarea__platform_extended', [], 'en') }}',
                        },
                        notice: {
                            from: '{{ __('proposal.textarea__platform_notice', [], 'ru') }}',
                            to: '{{ __('proposal.textarea__platform_notice', [], 'en') }}',
                        },
                    },
                }

                $(['extended', 'notice']).each(function(id, mode) {
                    from = translate[lang][mode].from;
                    to = translate[lang][mode].to;

                    $("textarea.platform_" + mode).each(function() {
                        var textareaId = $(this).attr('id');
                        if (textareaId && CKEDITOR.instances[textareaId]) {
                            var editor = CKEDITOR.instances[textareaId];
                            var editorContent = editor.getData();

                            // Получаем текст без тегов и лишних пробелов
                            var plainText = editorContent.replace(/<[^>]*>/g, '')
                                .trim()
                                .replace(/\s+/g, ' ');

                            // Сравниваем очищенный текст и обновляем если нужно
                            if(plainText === from) {
                                editor.setData(to);
                            }
                        }
                    });
                });
            });

            $("input[name='nds']").on("change keyup", function() {
                table_data_recalc();
            });
        });


        function updateNeuroCostFromRule(cell) {
                // 1. Получаем необходимые данные с проверками
                const row_num = parseInt(cell.closest('tr').attr("num")) || 0;
                const column_num = parseInt(cell.attr('column')) || 0;

                const scenarioSelect = cell.closest('tr').find('.select2');
                const scenario_id = parseInt(scenarioSelect.val()) || 0;
                console.log(scenario_id);

                const columnHeader = $(`th[column='${column_num}']`);
                const columnMode = columnHeader.find('.period').val() || 'year';

                const countInput = cell.find('.count');
                const count = parseFloat(countInput.val()) || 0;

                // 2. Определяем соответствие режимов
                const arMode = {
                    'pilot': 'p',
                    'year': 'y',
                    'unlimited': 'u',
                };

                const modeKey = arMode[columnMode] || 'y';

                // 3. Получим нужное правило
                let bestRuleCount = -1;
                if (cost_rules && cost_rules[scenario_id]) {
                    $.each(cost_rules[scenario_id], (rule_count_str, rule) => {
                        const rule_count = parseFloat(rule_count_str);
                        if (rule_count <= count && rule_count > bestRuleCount) {
                            bestRuleCount = rule_count;
                            bestRule = rule;
                        }
                    });
                }

                // 4. Получаем forced cost если есть
                let finalCost = 0;
                if (neuroForceCost &&
                    neuroForceCost[row_num] &&
                    neuroForceCost[row_num][column_num] &&
                    neuroForceCost[row_num][column_num][scenario_id] &&
                    neuroForceCost[row_num][column_num][scenario_id][bestRuleCount]) {
                    finalCost = neuroForceCost[row_num][column_num][scenario_id][bestRuleCount] ?? null;
                } else {
                    finalCost = bestRule[modeKey];
                }

                // 5. Обновляем поле ввода
                const costInput = cell.find('.inp_cost_cell');
                costInput.val(finalCost);


                // 7. Логируем для отладки (можно убрать)
                console.log('Cost updated:', {
                    row: row_num,
                    column: column_num,
                    scenario: scenario_id,
                    mode: columnMode,
                    count: count,
                    cost: finalCost,
                    ruleUsed: bestRule
                });
        }

        function setForcedNeuroCost(input) {
            const cell = input.parents('td[column]');

            try {
                // 1. Получаем данные из ячейки
                const row_num = parseInt(cell.parents('tr').attr("num")) || 0;
                const column_num = parseInt(cell.attr('column')) || 0;

                const scenarioSelect = cell.closest('tr').find('.select2');
                const scenario_id = parseInt(scenarioSelect.val()) || 0;

                const columnHeader = $(`th[column='${column_num}']`);
                const columnMode = columnHeader.find('.period').val() || 'year';

                const countInput = cell.find('.count');
                const count = parseFloat(countInput.val()) || 0;
                let bestRuleCount = -1;


                // 2. Определяем ключ режима
                const arMode = {
                    'pilot': 'p',
                    'year': 'y',
                    'unlimited': 'u',
                };

                // 3. Получим нужное правило
                if (cost_rules && cost_rules[scenario_id]) {
                    $.each(cost_rules[scenario_id], (rule_count_str, rule) => {
                        const rule_count = parseFloat(rule_count_str);
                        if (rule_count <= count && rule_count > bestRuleCount) {
                            bestRuleCount = rule_count;
                        }
                    });
                }


                const modeKey = arMode[columnMode] || 'y';

                // 3. Запрашиваем сумму у пользователя
                const currentValue = neuroForceCost &&
                neuroForceCost[row_num] &&
                neuroForceCost[row_num][column_num] &&
                neuroForceCost[row_num][column_num][scenario_id] &&
                neuroForceCost[row_num][column_num][scenario_id][bestRuleCount] &&
                neuroForceCost[row_num][column_num][scenario_id][bestRuleCount][modeKey] ?
                    neuroForceCost[row_num][column_num][scenario_id][bestRuleCount][modeKey] : 0;

                const promptMessage = `Введите новую стоимость.` +
                    `Текущее значение: ${currentValue}\n\n` +
                    `(Введите число или оставьте пустым для сброса)`;

                const userInput = prompt(promptMessage, currentValue || '');

                // 4. Обрабатываем результат
                if (userInput === null) {
                    // Пользователь нажал "Отмена"
                    console.log('Пользователь отменил ввод');
                    return;
                }

                let newValue = null;

                if (userInput.trim() === '') {
                    // Пустая строка - сброс значения
                    newValue = null;
                    console.log('Сброс принудительной стоимости');
                } else {
                    // Преобразуем в число
                    const parsedValue = parseFloat(userInput.replace(',', '.'));

                    if (isNaN(parsedValue)) {
                        alert('Ошибка: Введите корректное число!');
                        return;
                    }

                    newValue = parsedValue;
                }

                // 5. Инициализируем структуру данных если нужно
                if (!neuroForceCost) {
                    neuroForceCost = {};
                }
                if (!neuroForceCost[row_num]) {
                    neuroForceCost[row_num] = {};
                }
                if (!neuroForceCost[row_num][column_num]) {
                    neuroForceCost[row_num][column_num] = {};
                }
                if (!neuroForceCost[row_num][column_num][scenario_id]) {
                    neuroForceCost[row_num][column_num][scenario_id] = {};
                }

                if (!neuroForceCost[row_num][column_num][scenario_id][bestRuleCount]) {
                    neuroForceCost[row_num][column_num][scenario_id][bestRuleCount] = {
                        'p': null,
                        'y': null,
                        'u': null
                    };
                }

                // 6. Устанавливаем новое значение
                neuroForceCost[row_num][column_num][scenario_id][bestRuleCount] = newValue;

                // 7. Обновляем отображение в таблице
                updateNeuroCostFromRule(cell);
                table_data_recalc();
            } catch (error) {
                console.error('Error in setForcedNeuroCost:', error);
                alert('Произошла ошибка при установке стоимости');
            }
        }
    </script>
@endsection
