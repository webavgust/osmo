@extends('layouts.layout')

@section('styles')
    @parent
    <style>
        #asset_visit .select2-container {
            width: 100% !important;
        }

        #asset_visit .select2-container--default .select2-selection--multiple {
            border-color: #e9ecef !important;
        }

        #asset_visit .table td {
            padding: 5px 10px !important;
            font-size: 13px;
        }


        #asset_visit ol li {
            font-size: 14px;
        }

        #asset_visit .col-sm-8:has(input#date_fact) {
            padding-top: 0 !important;
        }

        #asset_visit input#date_fact {
            font-size: 14px !important;
            font-weight: 300 !important;
            margin-left: -4px;
            width: 160px;
            padding-top: 3px;
            padding-bottom: 3px;
        }

        table#measures tr:not(.header):hover td {
            background: #f4fff5;
        }

        .container_actions i {
            opacity: .5;
        }

        .container_actions i.fa-copy:hover {
            color: #ffb22b ;
        }
        .container_actions i.fa-xmark:hover {
            color: #fc4b6c ;
        }
        .container_actions i.fa-filter:hover {
            color: #1e88e5 ;
        }
        .container_actions i:hover {
            cursor: pointer;
            opacity: 1;
        }

        .is_filtered i.fa-filter {
            color: #1e88e5 ;
            opacity: 1;
        }
        .is_copy i.fa-copy {
            color: #ffb22b ;
            opacity: 1;
        }

        .container_once a.copy {
            display: none;
        }
        .container_once a.copy {
            display: none!important;
        }
        .row[point][copy] a.copy {
            display: inline-block!important;
        }

        .containers .container_once:not(:first-of-type) {
            margin-top: 2px;
        }
        .containers .container_once .container_add,
        .containers .container_once .container_delete {
            width: 18px;
            display: flex;
            justify-content: center;
        }
        .containers .container_once:first-of-type .container_delete {
            display: none;
        }
        .containers .container_once:not(:first-of-type) .container_add {
            display: none;
        }

        i.count_warning {
            display: none;
        }

        .count_warning i.count_warning {
            display: inline;
        }
    </style>
@endsection


@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <form class="form-horizontal" id="asset_visit">
                <div id="asset_visit" class="card">
                    <table class="m-2">
                        <tr>
                            <td class="text-right pe-3">Адрес:</td>
                            <td>{{ $visit->order_task_address->address }}</td>
                        </tr>
                        <tr>
                            <td class="text-right pe-3">Фактическая дата отбора:</td>
                            <td>{{ _date($visit->fact_visit_at) }}</td>
                        </tr>
                        <tr>
                            <td class="text-right pe-3">{{ $visit->users->count() == 1 ? "Пробоотборщик" : "Пробоотборщики" }}
                                :
                            </td>
                            <td>
                                <ol class="m-0 ps-2">
                                    @foreach($visit->users as $user)
                                        <li @class(['fw-bold text-danger' => $show_id == $user->id])>
                                                @if(is_admin() && $show_id !== $user->id && $visit->visit_containers->where('created_by', $user->id)->count() > 0)
                                                    <a href="{{ route('visit.edit', [$visit, $user->id]) }}" @class(['fw-bold text-danger' => $show_id == $user->id])>
                                                        {{ $user->full_name }}
                                                        @if($user->pivot['as_admin'])
                                                            <span class="ms-3 text-warning">
                                                            <x-ui.icon.light icon="fa-unlock-keyhole"></x-ui.icon.light> админ
                                                        </span>
                                                        @endif
                                                    </a>
                                                @else
                                                    {{ $user->full_name }}
                                                    @if($user->pivot['as_admin'])
                                                        <span class="ms-3 text-warning">
                                                            <x-ui.icon.light icon="fa-unlock-keyhole"></x-ui.icon.light> админ
                                                        </span>
                                                    @endif
                                                @endif
                                        </li>
                                    @endforeach
                                </ol>
                            </td>
                        </tr>
                        <tr>
                            <td width="300" class="text-right pe-3">Факт. передача проб для анализа:</td>
                            <td>
                                <input type="datetime-local" class="form-control" name="date" id="date_fact"
                                       value="{{ ($date ?? now())->format('Y-m-d H:i') }}"
                                       class="w-auto">
                            </td>
                        </tr>
                    </table>

                        <div class="points">
                            @foreach($points as $point)
                                <div class="bg-light-secondary mt-3 px-2 py-1">
                                    <h6 class="m-0 font-12 fw-bold d-flex align-items-center">
                                        <x-ui.badge.default type="danger">{{ $point->number }}</x-ui.badge.default>
                                        <x-ui.icon.light icon="fa-map-pin" class="mx-2"></x-ui.icon.light>
                                        <span>{{ $point->name }}</span>
                                    </h6>
                                </div>


                                <div class="row" point="{{ $point->id }}">
                                    <div class="col-9 table-responsive">
                                        <table id="measures" class="table customize-table mb-0 v-middle border-1"
                                               border="1">
                                            <tbody>
                                            <tr class="header">
                                                <td/>
                                                {{--                            <td class="text-center font-10 fw-bold">Всего по ТЗ</td>--}}
                                                <td class="text-center font-10 fw-bold">План</td>
                                                <td class="text-center font-10 fw-bold">Остаток</td>

                                                <td class="text-start font-10 fw-bold" container="{{ 0 }}">
                                                    <nobr>Кол-во и контейнер</nobr>
                                                </td>


                                            </tr>
                                            @foreach($measures_data[$point->id] as $visit_order_task_measure)
                                                @php
                                                    $measure = $visit_order_task_measure->order_task_measure;
                                                @endphp
                                                <tr class="measure">

                                                    <td>
                                                        {{ $measure->measure->name }}
                                                    </td>

                                                    <td class="text-center" width="60">
                                                        <span  @class(['counter', 'cursor-pointer', 'fw-bold text-success' => ($measure->count <= ($counts[$measure->id] ?? 0))])>
                                                            {{ $counts[$measure->id] ?? 0 }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center" width="60">
                                                        <span  @class(['counter', 'cursor-pointer', 'fw-bold text-success' => ($measure->count <= ($counts[$measure->id] ?? 0))]) count="{{ $counts[$measure->id] ?? 0 }}">
                                                            {{ $counts_possible[$visit_order_task_measure->id] ?? 0 }}
                                                        </span>

                                                        <x-ui.icon.solid icon="fa-triangle-exclamation" class="count_warning text-warning cursor-help" title="Указанное кол-во превышает запланированное"></x-ui.icon.solid>
                                                    </td>
                                                    <td class="text-center" align="center" container="1">
                                                        {{-- Выводим все измерения --}}
                                                        @php
                                                            $max = $counts_possible[$visit_order_task_measure->id] ?? 0;
                                                        @endphp
{{--                                                        @if($visit->onlyViewAssets())--}}
{{--                                                        --}}
{{--                                                        @else--}}
                                                            <div class="containers d-flex vertical-align-top justify-content-center flex-grow-1 flex-column">
                                                                    @foreach($containers[$point->id][$visit_order_task_measure->id] as $sub_container)

                                                                        <div class="container_once input-group" unbind="1">
                                                                            @if($max == 0)
                                                                                <code>Все данные внесены</code>
                                                                            @else
                                                                                <input
                                                                                    name="count[{{ $visit_order_task_measure->id }}][]"
                                                                                    type="number"
                                                                                    class="form-control text-center p-0 font-12 count_inp d-inline-block flex-grow-0"
                                                                                    min="0"
                                                                                    max="{{ $max }}"
                                                                                    value="{{ !empty($sub_container['count']) ? min($counts_possible[$visit_order_task_measure->id], $sub_container['count']) : ($max ?? 0) }}"
                                                                                    style="width: 40px"
                                                                                >


                                                                                <x-ui.select.single
                                                                                    name="mark[{{ $visit_order_task_measure->id }}][]"
                                                                                    blank-name="<без маркировки>"
                                                                                    blank-id="self"
                                                                                    :items="collect(['new' => '[+] новый'])->union($containers_flat[$point->id] ?? collect())->toArray()"
                                                                                    :value="$sub_container['container'] ?? 'self'"
                                                                                    class="p-1 font-12"
                                                                                ></x-ui.select.single>

                                                                                <a href="javascript:void(0)" onclick="javascript:container_paste($(this))" class="copy">
                                                                                    <x-ui.icon.light icon="fa-copy"
                                                                                                     class="mt-1 ms-2 text-info"></x-ui.icon.light>
                                                                                </a>

                                                                                <a href="javascript:void(0)" class="container_add" onclick="javascript:container_add($(this))">
                                                                                    <x-ui.icon.solid icon="fa-add"
                                                                                                     class="mt-1 ms-2 text-secondary"></x-ui.icon.solid>
                                                                                </a>

                                                                                <a href="javascript:void(0)" onclick="javascript:container_once_delete($(this))" class="container_delete">
                                                                                    <x-ui.icon.light icon="fa-xmark"
                                                                                                     class="mt-1 ms-2 text-danger"></x-ui.icon.light>
                                                                                </a>
                                                                            @endif
                                                                        </div>
                                                                    @endforeach
                                                            </div>
{{--                                                        @endif--}}
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-3 pe-4">
                                        <h6 class="mt-2">Контейнеры</h6>
                                        <div class="card-table containers-control mb-4"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                </div>
                </form>
                <div class="d-flex justify-content-between align-items-center w-100">
                    <x-ui.a.default btn_type="danger" href="{{ route('order_task_object.detail', $visit->getObject()->id) }}">
                        <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
                        <span>Вернуться</span>
                    </x-ui.a.default>


                    <div>
                        @unless($visit->onlyViewAssets())
                            <x-ui.button.default id="btn_submit" btn_type="info" onclick="javascript:finish();" disabled>
                                <x-ui.icon.duotone icon="fa-save" class="me-1"></x-ui.icon.duotone>
                                <span>Сохранить пробы</span>
                            </x-ui.button.default>
                        @endunless
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('js')
    @parent
    @if($visit->canEdit())
        <script>
            {{-- @lang('js') --}}

            function save() {
                if (!count_check() || !confirm('Вы действительно хотите создать выезд?'))
                    return false;

                $("body").block(block_default);
                $.ajax({
                    url: '{{ route('api.visit.create', $visit->order_task_address) }}?_token={{ _token() }}',
                    data: $("form#create_visit").serialize(),
                    method: "POST",
                    dataType: 'json',
                    success: function (answer) {
                        if (answer.result == 'success') {
                            location.reload();
                        } else {
                            $("body").unblock();
                            toastr.error("Не получилось сохранить выезд", "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                        }
                    },
                    error: function () {
                        $("body").unblock();
                        toastr.error("Не получилось сохранить выезд", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                })
            }

            function save_check() {

                let count = 0;
                $(".count_inp").each(function () {
                   count += $(this).val()-0;
                });
                if (count < 1)
                    return false;

                let err = false;
                $("table#measures .measure").each(function () {
                    if(!check_count($(this))) {
                        err = true;
                        return false;
                    }
                });

                return !err;
            }

            function check_button() {
                $("#btn_draft").attr("disabled", "disabled");
                $("#btn_submit").attr("disabled", "disabled");

                if (save_check()) {
                    $("#btn_draft").removeAttr("disabled");
                    $("#btn_submit").removeAttr("disabled");
                }
            }

            function draft() {
                if (!save_check() || !confirm('Вы действительно хотите сохранить черновик?'))
                    return false;

                $("body").block(block_default);
                $.ajax({
                    url: '{{ route('api.visit.draft', $visit) }}?_token={{ _token() }}',
                    data: $("form#asset_visit").serialize(),
                    method: "POST",
                    dataType: 'json',
                    success: function (answer) {
                        if (answer.result == 'success') {
                            location.replace('{{ route('order_task_object.detail', $visit->getObject()) }}');
                        } else {
                            $("body").unblock();
                            toastr.error("Не получилось сохранить данные", "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                        }
                    },
                    error: function () {
                        $("body").unblock();
                        toastr.error("Не получилось сохранить данные", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                })
            }

            function finish() {
                if (!save_check() || !confirm('Вы действительно хотите сохранить пробы?'))
                    return false;

                $("body").block(block_default);
                $.ajax({
                    url: '{{ route('api.visit.update', $visit) }}?_token={{ _token() }}',
                    data: $("form#asset_visit").serialize(),
                    method: "POST",
                    dataType: 'json',
                    success: function (answer) {
                        if (answer.result == 'success') {
                            location.replace('{{ route('order_task_object.detail', $visit->getObject()) }}');
                        } else {
                            $("body").unblock();
                            toastr.error(answer.message, "Это провал!", {
                                progressBar: true,
                                "timeOut": 3000,
                            });
                        }
                    },
                    error: function () {
                        $("body").unblock();
                        toastr.error("Не получилось сохранить данные", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                })
            }

            let containers = [];
            function containers_recalc() {
                containers = [];
                $('[point]').each(function() {
                    const point = $(this).attr('point');
                    const selectElements = $(this).find('select');

                    selectElements.each(function() {
                        const selectValue = $(this).val();

                        if (!containers[point]) {
                            containers[point] = {};
                        }

                        $(this).find('option').each(function() {
                            const optionValue = $(this).val();
                            if (optionValue == 'new')
                                return;

                            if (!containers[point][optionValue]) {
                                containers[point][optionValue] = 0;
                            }

                            if (optionValue === selectValue) {
                                containers[point][optionValue]++;
                            }
                        });
                    });
                });

                let containers_sorted = [];
                for (let point in containers) {
                    if (containers.hasOwnProperty(point)) {
                        let subarray = containers[point];
                        let sortedSubarray = Object.entries(subarray).sort(function(a, b) {
                            return b[1] - a[1];
                        });
                        containers_sorted[point] = [];
                        for (let i = 0; i < sortedSubarray.length; i++) {
                            containers_sorted[point].push({
                                key: sortedSubarray[i][0],
                                value: sortedSubarray[i][1]
                            });
                        }
                    }
                }

                containers = containers_sorted;
                check_button();
                containers_draw();
            }

            function containers_draw() {

                $("[point]").each(function () {
                    html = ``;
                    let point = $(this);
                    points = containers[$(this).attr("point")];
                    $.each(points, function (index, point) {
                        console.log(point);
                        key = point.key;
                        count = point.value;
                        // if(count < 1)
                        //     return;

                        is_default = false;
                        if(key == 'self') {
                            key = '<без маркировки>';
                            is_default = true;
                        }

                        is_filtered = $(".row[point]").attr("filter") == key;
                        is_copy = $(".row[point]").attr("copy") == key;

                        html += `
                            <div class="tr font-12 `
                            + (is_filtered ? "is_filtered" : "")
                            + (is_copy ? "is_copy" : "")
                            + `" container="` + key + `">
                                <span class="th">` + key + `</span>
                                <span class="td">
                                    <span>` + count + `</span>
                                    <span class="container_actions">
                                        <i class="fa-solid fa-filter ms-2" icon="fa-filter" onclick="javascript:container_filter($(this))"></i>
                                        <i class="fa-solid fa-copy ms-2" icon="fa-copy" onclick="javascript:container_copy($(this))"></i>
                                        ` + (is_default ? `&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;` : `<i class="fa-solid fa-xmark ms-2" icon="fa-xmark" onclick="javascript:container_delete($(this))"></i>`) + `
                                    </span>
                                </span>
                            </div>`;
                    });

                    point.find(".containers-control", 0).html(html);
                });
            }

            function check_count(parent) {
                max_count = parent.find('span.counter[count]').attr("count") - 0;
                current_count = 0;
                parent.find("input[type='number']").each(function () {
                        current_count += ($(this).val() - 0);
                });

                if (current_count > max_count) {
                    parent.addClass('count_warning');
                    return false;
                } else {
                    parent.removeClass('count_warning');
                    return true;
                }
            }

            function set_container(select) {
                if(select.val() == 'new') {
                    let parent = select.parents("table#measures");
                    let point = parent.attr("point");

                    item = prompt('Введите название нового контейнера');
                    if (item && item.length > 0) {
                        // проверим на уникальность
                        if (point in containers  && item in containers[point]) {
                            select.val(item);
                        } else {
                            parent.find(".container_once select").each(function () {
                                $(this).append($('<option>', {
                                    value: item,
                                    text : item
                                }));
                            });
                            select.val(item);
                        }
                    } else {
                        select.val('self');
                    }


                }

                containers_recalc();
                if (select.parents('.row[point]').attr("filter")) {
                    icon = select.parents('.row[point]').find(".is_filtered i.fa-filter");
                    container_unfilter(icon);
                    container_filter(icon);
                }
            }

            function container_delete(obj) {
                if(!confirm('Вы действительно хотите удалить этот контейнер?'))
                    return false;

                    container_unfilter(obj);
                    container_uncopy(obj);

                    let container = obj.parents('[container]').attr("container");

                    obj.parents(".row[point]").find("select").each(function () {
                        $(this).find('option[value="' + container + '"]').remove().val("");
                    });

                    containers_recalc();
            }

            function container_filter(obj) {
                let parent = obj.parents('.row[point]');
                let container = obj.parents('[container]').attr("container");

                if (parent.attr("filter") == container) {
                    // уберём фильтр
                    container_unfilter(obj);
                } else {
                    container_unfilter(obj);
                    parent.attr("filter", container);
                    if(container == '<без маркировки>')
                        container = 'self';

                    // скроем ненужное
                    parent.find("tr.measure").addClass("d-none");
                    parent.find(".container_once select").each(function () {
                        if($(this).val() !== container) {
                            $(this).parents(".container_once").addClass("d-none");
                        } else {
                            $(this).parents('tr.measure').removeClass("d-none");
                        }
                    });
                }

                containers_draw();
            }

            function container_unfilter(obj) {
                let parent = obj.parents('.row[point]');
                parent.removeAttr("filter");
                parent.find(".container_once").removeClass("d-none");
                parent.find("tr.measure").removeClass("d-none");
                parent.find(".container_once").removeClass("d-none");
            }

            function container_copy(obj) {
                let parent = obj.parents('.row[point]');
                let container = obj.parents('[container]').attr("container");
                if (parent.attr("copy") == container) {
                    // уберём фильтр
                    container_uncopy(obj);
                } else {
                    parent.attr("copy", container);
                    if(container == '<без маркировки>')
                        container = 'self';
                }

                containers_draw();
            }

            function container_uncopy(obj) {
                let parent = obj.parents('.row[point]');
                parent.removeAttr("copy");
            }



            function container_paste(obj) {
                val = obj.parents('[copy]').attr('copy');


                if(val == '<без маркировки>')
                    val = 'self';

                console.log(val);

                obj.prev('select').val(val);

                containers_recalc();
            }

            function rebind() {
                $(".containers .container_once[unbind]").each(function () {
                    $(this).removeAttr("unbind");
                    $(this).find("select").on("change", function () {
                        set_container($(this));
                    });
                    $(this).find("input").on("change keyup", function () {
                        check_count($(this).parents('.measure'));
                    });
                });
            }

            function container_once_delete(obj) {
                if (!confirm('Удалить контейнер?'))
                    return false;

                var index = obj.parents('.containers').find('a.container_delete').index(obj);
                if (index > 0) {
                    let measure = obj.parents(".measure");
                    obj.parents('.container_once').remove();

                    check_count(measure);
                    containers_recalc();
                }
            }

            function container_add(obj) {
                var html = $(obj).parents('.container_once').prop('outerHTML');
                var newElement = $(html).attr('unbind', '1');
                $(obj).parents('.containers').append(newElement);

                rebind();
                check_count($(obj).parents('.measure'));
                containers_recalc();
            }


            $(document).ready(function () {
                $("form#asset_visit input").on("change keyup", function () {
                    check_button();
                });

                $("i.clone").on("click", function () {
                    if (!confirm('Вы действительно хотите откопировать маркировку на все пробы в точке?'))
                        return false;

                    $(this).parents("tbody[point]").find(".mark").val($(this).prev('input').val());
                });
                check_button();


                rebind();
                containers_recalc();
            });
        </script>
    @endunless
@endsection
