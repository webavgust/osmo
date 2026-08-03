@extends('layouts.layout')

@section('styles')
    <style>
        .btn-outline-secondary { border-left: 0; border-right: 0 }
        .accordion-collapse {
            -webkit-box-shadow: 0px 2px 10px -2px rgba(34, 60, 80, 0.24) inset;
            -moz-box-shadow: 0px 2px 10px -2px rgba(34, 60, 80, 0.24) inset;
            box-shadow: 0px 2px 10px -2px rgba(34, 60, 80, 0.24) inset;
            padding: 30px 0;
            background: #FDFDFD !important;
        }
        .todo-item:last-of-type {
            border-bottom: 0!important;
        }
        .todo-item .btn-group .btn.hl:first-of-type
        {
            box-shadow: 0px 0 7px 0px rgb(242 0 0 / 80%)
        }
        .todo-item .btn-group .btn.hl:last-of-type
        {
            box-shadow: 0px 0 7px 0px rgb(65 186 26 / 91%)
        }

    </style>
@endsection

@section('content')
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Пользователь "{{ $user->fullname }}"</h4>
                                <h6 class="card-subtitle lh-base">
                                    Назначение доступов для пользователя "{{ $user->fullname }}"
                                </h6>

                                <a href="javascript:void(0)" onclick="javascript:$(this).remove(); $('#detail').removeClass('d-none');">Показать подразделения и группы</a>
                                <div class="row d-none" id="detail">
                                    <div class="col-12">
                                        <h6>Подразделения:</h6>
                                        <div>
                                            @foreach($user->departments as $dep)
                                                <a href="{{ route('access_set.department', $dep) }}"><span class="mb-1 badge bg-danger">{{ $dep->name }}</span></a>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <h6>Группы:</h6>
                                        <div>
                                            @foreach($user->groups as $group)
                                                <a href="{{ route('access_set.group', $group) }}"><span class="mb-1 badge bg-secondary">{{ $group->name }}</span></a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <form method="post" id="access">
                                <div class="accordion accordion-flush bg-warning">
                                    @foreach($access_groups as $access_group)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="flush-heading{{$access_group->id}}">
                                            <button class="fs-5 accordion-button bg-white collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse{{$access_group->id}}" aria-expanded="true" aria-controls="flush-collapse{{$access_group->id}}">
                                                @if($access_group->icon)
                                                    <i class="{{ $access_group->icon  }} me-3"></i>
                                                @endif
                                                {{ $access_group->name }}
                                            </button>
                                        </h2>
                                        <div id="flush-collapse{{$access_group->id}}" class="accordion-collapse collapse bg-white" aria-labelledby="flush-heading{{$access_group->id}}" style="">
                                            <div class="accordion-body p-0 ps-5">
                                                @foreach($access_group->accesses as $access)
                                                    <div class=" todo-item all-todo-list p-3 pt-4 ps-0 border-bottom position-relative">
                                                        <div class="inner-item d-flex align-items-start">
                                                            <div class="w-100">
                                                                <div class=" checkbox checkbox-info d-flex align-items-start form-check ps-0">
                                                                    <x-access.set_row  :group="$user" :access="$access" checked="{{ $access_save[$access->id] ?? null }}"></x-access.set_row>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach

                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>


@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function() {
            $("form#access input[type='radio']").on("change", function() {

                var block_ele = $(this).closest(".card");
                $(block_ele).block({
                    message: null,
                    overlayCSS: {
                        backgroundColor: "#FFF",
                        opacity: 0.7,
                        cursor: "wait",
                    },
                    css: {
                        border: 0,
                        padding: 0,
                        backgroundColor: "transparent",
                    },
                });

                $.ajax({
                    url: '{{ route('api.access_set.user', [$user->id, '_token' => auth()->user()->ajax_token]) }}',
                    method: 'post',
                    dataType: 'json',
                    data: $("form#access").serialize(),
                    success: function(response) {
                        block_ele.unblock();
                    },
                    error: function(response) {
                        toastr.error("Произошла ошибка!", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        block_ele.unblock();
                    }
                });



                $(block_ele).unblock();

            });
        });
    </script>
@endsection
