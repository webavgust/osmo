@extends('layouts.layout')

@section('styles')
    <link rel="stylesheet" type="text/css" href="/dist/modules/jquery-ui/jquery-ui.min.css">
    <link href="/dist/modules/fullcalendar/main.css" rel="stylesheet"/>
    <link href="/dist/modules/daterangepicker/daterangepicker.css" rel="stylesheet"/>
    <link rel="stylesheet" type="text/css"
          href="/assets/libs/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">

    <style>
        .calendar-events {

        }

        @media screen and (max-width: 1350px) {
            .fc-header-toolbar {
                display: flex;
                flex-direction: column;
            }
            .fc-toolbar-chunk:nth-child(2) {
                order: 1;
                padding-bottom: 15px;
            }
            .fc-toolbar-chunk:nth-child(1) {
                order: 2;
                padding-bottom: 5px;
            }
            .fc-toolbar-chunk:nth-child(3) {
                order: 3;
            }
        }


        .fc-daygrid-event .event {
            display: flex;
            justify-content: space-between;
            padding: 0 5px;
        }

        .fc-daygrid-event .event .time {
            font-size: 10px;
            margin-right: 5px;
        }
        .fc-daygrid-event .event .title {
            font-weight: 500;
        }


        .fc-timegrid-event {
            padding: 0;
        }

        .fc-timegrid-event .event {
            padding: 2px;
            padding-left: 5px;
            height: 100%;
            width: 100%;
            display: inline-block;
        }
        .fc-timegrid-event .fc-event-main {
            padding: 0;
        }

        .fc-timegrid-event .event:has(.reminders) {
            border-left: 5px solid #000000;
            border-radius: 3px;
        }

        .fc-timegrid-event .event .time {
            font-size: 10px;
            display: block;
        }
        .fc-timegrid-event .event .title {
            display: block;
            font-size: 11px;
        }
        .fc-timegrid-event .event .reminders {
            display: none;
        }



    </style>
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="">
                        <div class="row">
                            <div class="col-lg-3 mb-2 mb-lg-0 border-end pe-0 border-bottom">
                                <div class="card-header min-h-auto py-5 border-bottom d-block">
                                    <h4 class="card-title mt-2">Нераспределенные события</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div id="calendar-events" class="">
                                                @forelse($undefined as $event)
                                                    <x-ui.calendar.event :event="$event"></x-ui.calendar.event>
                                                @empty
                                                    Событий нет
                                                @endforelse
                                            </div>
                                            <a
                                                href="javascript:void(0)" onclick="javascript:sidebar({ href: '{{ route('calendar.sidebar_add', ['_token' => _token()]) }}'});"
                                                class="btn mt-3 btn-info d-block w-100">
                                                <i class="fa-light fa-plus"></i> Добавить событие
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-9">
                                <div class="card-body calender-sidebar">
                                    <div id="calendar"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- BEGIN MODAL -->
        <div class="modal" id="my-event">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header d-flex align-items-center">
                        <h4 class="modal-title"><strong>Add Event</strong></h4>
                        <button
                            type="button"
                            class="btn-close close-dialog"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary close-dialog"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        >
                            Close
                        </button>
                        <button
                            type="button"
                            class="btn btn-success save-event"
                        >
                            Create event
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger delete-event"
                            data-bs-dismiss="modal"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop bckdrop hide"></div>
        <!-- Modal Add Category -->
        <div class="modal none-border" id="add-new-event">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header d-flex align-items-center">
                        <h4 class="modal-title"><strong>Add</strong> a category</h4>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="control-label">Category Name</label>
                                    <input
                                        class="form-control form-white"
                                        placeholder="Enter name"
                                        type="text"
                                        name="category-name"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <label class="control-label"
                                    >Choose Category Color</label
                                    >
                                    <select
                                        class="form-select form-white"
                                        data-placeholder="Choose a color..."
                                        name="category-color"
                                    >
                                        <option value="success">Success</option>
                                        <option value="danger">Danger</option>
                                        <option value="info">Info</option>
                                        <option value="primary">Primary</option>
                                        <option value="warning">Warning</option>
                                        <option value="inverse">Inverse</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="
                      btn btn-danger
                     
                      save-category
                    "
                            data-bs-dismiss="modal"
                        >
                            Save
                        </button>
                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- END MODAL -->
    </div>
@endsection

@section('js')
    @parent
    <script src="/dist/modules/jquery-ui/jquery-ui.min.js"></script>
    <script src="/dist/modules/fullcalendar/main.js"></script>
    <script src="/dist/modules/fullcalendar/locales/ru.js"></script>
    <script src="/dist/modules/daterangepicker/daterangepicker.js"></script>
    <script src="/assets/libs/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="/assets/libs/bootstrap-datepicker/dist/locales/bootstrap-datepicker.ru.min.js"></script>
    <script src="/dist/modules/daterangepicker/moment.min.js"></script>
    <script>
        $(document).ready(function() {
            var Draggable = FullCalendar.Draggable;

            var calendarEl = document.getElementById('calendar');

            var dragEventsContainer = document.getElementById('calendar-events');
            new Draggable(dragEventsContainer, {
                itemSelector: '.calendar-events',
                locale: 'ru',
                eventData: function(eventEl) {
                    var el = $(eventEl);
                    return {
                        id: el.data('id'),
                        title: el.data('title'),
                        classNames: ['border-0', el.data('color')],
                        reminders_count: el.data('reminders_count'),
                        duration: el.data('duration') ?? 1
                    };
                }
            });


            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth', //'dayGridWeek', 'timeGridDay', 'listWeek' .
                locale: 'ru',
                // editable: true,
                eventResizableFromStart: true,
                events: @json($events),
                eventClick: function(info) {
                    sidebar({ href: '{{ route('calendar.sidebar_show') }}/' + info.event.id});
                },
                customButtons: {
                    add: {
                        icon: 'fc-fa-light fa-plus-square',
                        click: function() {
                            sidebar({ href: '{{ route('calendar.sidebar_add', ['_token' => _token()]) }}'});
                        }
                    },
                    list: {
                        text: 'Список',
                        click: function() {
                            events_list();
                        }
                    }
                },
                eventContent:  function(arg) {
                    if(arg.event._def.extendedProps.reminders_count > 0) {
                        digits = arg.event._def.extendedProps.reminders_count.toString().split('').map(function (item) {
                            return '<i class="fa-solid fa-' + item + '"></i>';
                        }).join('');

                        return {
                            html: `<div class="event">
                                            <span>
                                                <span class="time">` + arg.timeText + `</span>
                                                <span class="title">` + arg.event._def.title + `</span>
                                            </span>
                                            <span class='reminders'>
                                                ` + digits + `
                                                <i class="fa-solid fa-bell ms-1"></i>
                                            </span>
                                    </div>`
                        }
                    }

                    return {
                        html: `<div class="event">
                                    <span>
                                        <span class="time">` + arg.timeText + `</span>
                                        <span class="title">` + arg.event._def.title + `</span>
                                    </span>
                                </div>`
                    }
                },
                headerToolbar: {
                    start: 'dayGridMonth,timeGridWeek,timeGridDay add list',
                    center: 'title',
                    end: 'prevYear prev today next nextYear',
                },
                drop: function(info) {
                    info.draggedEl.parentNode.removeChild(info.draggedEl);
                    if($("#calendar-events .calendar-events").length == 0)
                        $("#calendar-events").html('Событий нет');
                        event_set($(info.draggedEl).data('id'), {
                            allDay: info.allDay ? 1 : 0,
                            set_date: moment(info.date).format('DD.MM.YYYY')
                        });
                },
                eventDrop: function(info) {
                    console.log(info.event._instance.range.start);
                    console.log(info.event._instance.range.end);
                    event_set(info.event._def.publicId, {
                       allDay: info.event._def.allDay ? 1 : 0,
                       start: moment(info.event._instance.range.start).format('DD.MM.YYYY HH:mm'),
                       end: moment(info.event._instance.range.end).format('DD.MM.YYYY HH:mm'),
                    });
                },
                eventResize: function(info) {
                    console.log(info.event._instance.range.start);
                    console.log(info.event._instance.range.end);
                    event_set(info.event._def.publicId, {
                        allDay: info.event._def.allDay ? 1 : 0,
                        start: moment(info.event._instance.range.start).format('DD.MM.YYYY HH:II'),
                        end: moment(info.event._instance.range.end).format('DD.MM.YYYY HH:II'),
                    });
                }

            });
            calendar.render();
            $("document").on("fc_event_resize", function() { alert("!"); });
        });

        function events_list() {
            location.replace('{{ route('calendar.schedule.pdf') }}');
        }

        function event_set(id, data)
        {
            $.ajax({
                url: "{{ route('api.calendar.set') }}/" + id,
                global: false,
                type: "POST",
                data: ({
                    data: data,
                    _token: '{{ auth()->user()->ajax_token }}'
                }),
                dataType: "json",
                success: function (result) {
                    if (result.status == 'success') {
                        toastr.success("Событие сохранено", "Это успех!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    } else {
                        toastr.error("Не получилось сохранить событие", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                    }
                }
            });
        }
    </script>
@endsection
