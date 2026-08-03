<div class="col-md-12 col-lg-12 position-relative user-row">

    <div class="card text-center alert-dismissible fade show alert p-0" role="alert">
        <div class="p-3 pt-2 pb-2 d-flex">
            <div><img src="{{ $person->avatar() }}" width="60" class="rounded-circle img-fluid"></div>
            <div class="ms-3 mt-1 align-content-start text-start ">
                @can('users_view_profile') <a href="{{ route('users.view', $person) }}" class="person_link"> @endcan
                    <h5 class="card-title mb-1">{{ $person->fullname }}</h5>
                    @can('users_view_profile') </a> @endcan

                <x-ui.badge.light_rounded type="{{$color}}" text="{{$color}}"
                                          class="mb-3">{{$badge}}</x-ui.badge.light_rounded>

                <div>
                    @if($task->canAcceptWork())
                        <div class="d-flex">
                            <x-ui.button.default btn_type="danger flex-grow-1 me-1" id="order_back"
                                                 onclick="javascript:methodist_cancel()">
                                <x-ui.icon.regular icon="fa-ban" class="me-1"></x-ui.icon.regular>
                                Вернуть
                            </x-ui.button.default>

                            <x-ui.a.default btn_type="success flex-grow-1 ms-1" id="order_accept"
                                            onclick="javascript:methodist_accept()">
                                <x-ui.icon.regular icon="fa-circle-check" class="me-1"></x-ui.icon.regular>
                                Принять
                            </x-ui.a.default>
                        </div>
                    @elseif($task->isRefused())
                        <x-ui.notification.light class="p-1 fs-1" type="danger">{!! nl2br($task->methodist_decline_message) !!}</x-ui.notification.light>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
