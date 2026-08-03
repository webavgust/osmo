@extends('layouts.layout')

@section('styles')
    <link rel="stylesheet" type="text/css"
          href="/assets/libs/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">

    <link
        rel="stylesheet"
        type="text/css"
        href="/assets/extra-libs/summernote/summernote-lite.min.css"
    />
    <style>
        .log_once  {
            cursor: pointer;
            transition: all .5s;
        }
        .log_once + .log_once {
            border-top: 1px solid #EEE;
        }
        .log_once:hover {
            transform: scale(1.05);
        }
        .day.highlight {
            font-weight: bold;
            color: #ff0087;
        }
        .day.active {
            background-image: linear-gradient(to bottom, #0042ff, #000a8f)!important;
            font-weight: bold;
        }
        .day.highlight.active {
            background-image: linear-gradient(to bottom, #de80af, #b40060)!important;
        }
    </style>
@endsection


@section('breadcrumb_right')
    <span class="badge bg-primary fs-5 py-2 px-4 fw-bold cursor-pointer" id="datepicker"
          data-orient="bottom">{{ $pointer->format('d.m.Y') }}</span>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-9">
                <form id="log">
                    <div class="card mb-2">
                        <div class="card-header d-flex p-2">
                            <x-ui.select.single id="id" class="company_select2 w-50" :items="$companies" name="company" blank-id="0" blank-name="Выбрать компанию"/>
                            <x-ui.select.single id="id" class="proposal_select2 w-50" :items="$proposals" value-name="name_number" name="proposal_group" />
                        </div>
                        <div class="card-body p-0">
                            <div class="summernote"></div>
                        </div>
                    </div>
                </form>

                <div class="d-flex justify-content-between d-none" id="action">
                    <x-ui.button.outline btn_type="danger">
                        <x-ui.icon.regular icon="fa-xmark" class="me-1"/>
                        Сбросить
                    </x-ui.button.outline>

                    <x-ui.button.default btn_type="primary" onclick="javascript:form_sbm();">
                        <x-ui.icon.light icon="fa-save" class="me-1"/>
                        Сохранить
                    </x-ui.button.default>
                </div>
            </div>
            <div class="col-3" id="log_story">
                <x-log.story :date="$pointer"/>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script src="/assets/libs/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="/assets/libs/bootstrap-datepicker/dist/locales/bootstrap-datepicker.ru.min.js"></script>
    <script src="/assets/extra-libs/summernote/summernote-lite.min.js"></script>

    <script>
        var proposals = @json($proposals);

        var inited = 0;
        var days = @json($days);

        function form_clear() {
            $(".summernote").summernote("code", "");
            form_redraw();
        }
        function list_update() {
            $.ajax({
                url: '{{ route('api.log.story', ['_token' => _token()]) }}',
                data: {
                    date: '{{ $pointer->format('Y-m-d') }}'
                },
                method: 'POST',
                dataType: 'json',
                success: function (response) {
                    if(response.result == 'success') {
                        $("#log_story").html(response.html);
                    } else {
                        toastr.error("Ошибка!", "Не получилось обновить список заметок!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                    $("body").unblock();
                },
                error: function() {
                    toastr.error("Ошибка!", "Не получилось обновить список заметок!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $("body").unblock();
                }
            });
        }


        function form_sbm() {
            if(!form_redraw()) return;

            $("body").block(block_default);

            company_id = $("select[name='company']").val();
            text = $('form#log .summernote').summernote('code');

            $.ajax({
                url: '{{ route('api.log.create', ['_token' => _token()]) }}',
                data: {
                    company: $("select[name='company']").val(),
                    proposal_group: $("select[name='proposal_group']").val(),
                    text: $('form#log .summernote').summernote('code'),
                    date: '{{ $pointer->format('Y-m-d') }}'
                },
                method: 'POST',
                dataType: 'json',
                success: function (response) {
                    if(response.result == 'success') {
                        toastr.success("Успех", "Заметка сохранена", {
                            progressBar: true,
                            "timeOut": 3000,
                        });

                        form_clear();
                        list_update();
                    } else {
                        toastr.error("Ошибка!", "Не получилось сохранить заметку!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        $("body").unblock();
                    }

                },
                error: function() {
                    toastr.error("Ошибка!", "Не получилось сохранить заметку!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $("body").unblock();
                }
            });

        }

        function form_redraw() {
            company_id = $("select[name='company']").val();
            text = stripTags($('form#log .summernote').summernote('code'));

            if(company_id > 0 && text.length) {
                $("#action").removeClass("d-none");

                return true;
            } else {
                $("#action").addClass("d-none");

                return false;
            }
        }


        $(document).ready(function () {

            var pointer = moment('{{ $pointer->format('Y-m-d') }}');

            var currentDate = moment().toDate();
            $("#datepicker").datepicker({
                language: 'ru',
                format: "DD-MM-YYYY",
                startView: "days",
                minViewMode: "days",
                orientation: 'auto bottom',
                endDate: currentDate, // Установка максимальной даты на текущую
                beforeShowDay: function(date) {
                    // Преобразуем дату в формат "d.m.Y"
                    var formattedDate = moment(date).format("YYYY-MM-DD");
                    // Проверяем, есть ли эта дата в массиве specialDates
                    if (days.hasOwnProperty(formattedDate)) {
                        return {
                            enabled: true,
                            classes: 'highlight', // Присваиваем CSS-класс
                            content: moment(date).format("D") + `<sup class="ms-1">${days[formattedDate]}</sup>`,
                        };
                    }
                    return [true, '', '']; // Для остальных дат возвращаем true без класса
                }
            }).on('changeDate', function (e) {
                if (moment(e.date).isAfter(moment())) {
                    // Отменяем действие, если дата больше текущей
                    return false; // Можно добавить уведомление, если нужно
                }
                if(inited)
                    location.replace('{{ route('log.index') }}/' + moment(e.date).format('DD.MM.YYYY'));
            }).datepicker("setDate", pointer.toDate());


            $(".company_select2").select2({
                placeholder: 'Выберите компанию'
            }).on("change", function() {
                var companyID = $(".company_select2").val();

                $.ajax({
                    url: "{{ route("api.proposals.company") }}?_token={{ _token() }}",
                    data: {
                        company: companyID
                    },
                    method: "POST",
                    success: function(answer) {
                        $(".proposal_select2").select2("destroy"); // destroy the select2 instance
                        $(".proposal_select2").empty(); // empty the select element

                        $(".proposal_select2").select2({
                            data: answer.data,
                        }).on("change", function(a,b,c) {
                            var selectedOption = $(this).select2('data')[0]; // получаем выбранный элемент
                            if (selectedOption) {
                                $(".company_select2").val(selectedOption.company).trigger('change');
                            }
                        })
                    }
                })
            });

            $(".proposal_select2").select2({
                placeholder: 'Выберите сделку',
                data: proposals
            }).on("change", function(a,b,c) {
                var selectedOption = $(this).select2('data')[0]; // получаем выбранный элемент
                if (selectedOption) {
                    $(".company_select2").val(selectedOption.company_id).trigger('change');
                }
            });

            $(".summernote").summernote({
                height: 350, // set editor height
                minHeight: null, // set minimum height of editor
                maxHeight: null, // set maximum height of editor
                focus: false, // set focus to editable area after initializing summernote
            }).on("summernote.enter", function (we, e) {
                $(this).summernote("pasteHTML", "<br><br>");
                e.preventDefault();
            }).on("summernote.change", function (e) {   // callback as jquery custom event
                form_redraw();
            })

            $("select[name='company']").on("change keyup", function() {
                form_redraw();
            });

            inited = true;
        });
    </script>
@endsection
