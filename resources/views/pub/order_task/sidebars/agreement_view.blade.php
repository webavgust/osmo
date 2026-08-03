@extends('components.sidebar.offcanvas-right')


@section('body')
    <style>
        .select2-results__message {
            display: none;
        }
    </style>
    <div class="card">
        <div class="card-body">
            <div class="card-table">
                <div class="tr">
                    <span class="th"><strong>ID</strong></span>
                    <span class="td">
                        {{ $agreement->id }}
                     </span>
                </div>
                <div class="tr">
                    <span class="th"><strong>Статус</strong></span>
                    <span class="td">
                        {{ \App\Modules\Pub\OrderTaskAgreement\Models\OrderTaskAgreement::STATUS_LANG[$agreement->status] }}
                     </span>
                </div>
                <div class="tr">
                    <span class="th"><strong>Дата отправки</strong></span>
                    <span class="td">
                        {{ _date($agreement->created_at, ['format' => 'd.m.Y H:i:s']) }}
                     </span>
                </div>
            </div>

            @if(count($files) > 0)
                <h4 class="mt-4">Файлы</h4>
                @foreach($files as $ext => $rows)
                    @foreach($rows as $file)
                        <div class="font-15 mt-1">
                            <a href="{{ download_path($file->path) }}" target="_blank">
                                <x-ui.icon.files ext="{{$ext}}" class="me-1" asd="1"></x-ui.icon.files>
                                {{ $file->filename }}
                            </a>
                        </div>
                    @endforeach
                @endforeach
            @endif


            <h3 class="mt-4">Согласованты</h3>
            <div class="card-table">
                @foreach($agreement->users as $user)
                    <div class="tr">
                        <span class="th">{{ $loop->iteration }}) {{ $user->full_name }}</span>
                        <span class="td">

                            @switch($user->pivot->agreed)
                                @case(0)
                                    <i class="fa-regular fa-clock text-warning"></i>
                                    @break
                                @case(1)
                                    <i class="fa-duotone fa-check text-success cursor-help" title="{{ $user->pivot->updated_at->format('d.m.Y H:i:s') }}"></i>
                                    @break
                                @case(-1)
                                    <i class="fa-solid fa-xmark text-danger cursor-help" title="{{ $user->pivot->updated_at->format('d.m.Y H:i:s') }}"></i>
                                    @break

                            @endswitch
                         </span>
                    </div>


                    @if(!empty($user->pivot->comment))
                        @if($user->pivot->agreed == 1)
                            <div class="alert alert-success mt-2 p-1 ps-2 pe-2 ms-3" role="alert">
                                {{ $user->pivot->comment }}
                            </div>
                        @else
                            <div class="alert alert-danger mt-2 p-1 ps-2 pe-2 ms-3" role="alert">
                                {{ $user->pivot->comment }}
                            </div>
                        @endif
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <script>

        $(document).ready(function() {
            $("form#agreement input").on("change", function() {
               if($("form#agreement input:checked").length > 0) {
                   $("#agree_confirm").removeAttr("disabled");
               } else {
                   $("#agree_confirm").attr("disabled", 1);
               }
            });
            $("#agree_confirm").on("click", function() {
                Swal.fire({
                    title: "Вы уверены?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Да",
                    cancelButtonText: "Нет",
                }).then((result) => {
                    if (result.value) {
                        agree_submit();
                    }
                });
            });
        });


        function agree_submit() {
            var block_elem = $("body");
            $(block_elem).block({
                message: '<i class="fas fa-spin fa-sync text-white"></i>',
                baseZ: 100000,
                overlayCSS: {
                    backgroundColor: "#000",
                    opacity: 0.5,
                    cursor: "wait",
                },
                css: {
                    border: 0,
                    padding: 0,
                    backgroundColor: "transparent",
                },
            });


            $.ajax({
                url: "{{ route('api.order_task.agree', [$order_task, '_token' => auth()->user()->ajax_token ]) }}",
                type: "POST",
                dataType: "html",
                data: $("form#agreement").serialize(),
                success: function (result) {
                    $(block_elem).unblock();

                    $(".agree-table tr[task_id='{{ $order_task->id }}']").replaceWith(result);
                    toastr.success("ТЗ отпралвено на согласование", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    sidebar_close();
                },
                error: function () {
                    $("#attach_confirm span").addClass("d-none");
                    toastr.error("Не получилось отправить ТЗ на согласование", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $(block_elem).unblock();
                }
            });

        }
    </script>
@endsection
