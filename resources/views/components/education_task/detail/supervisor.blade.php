<div class="col-md-12 col-lg-12 position-relative user-row">
    <div class="card text-center alert-dismissible fade show alert p-0 mb-1" role="alert">
        <div class="p-3 pt-2 pb-2 d-flex justify-content-between align-items-center">
            @if(!empty($person))
                <div class="d-flex">
                    <div><img src="{{ $person->avatar() }}" width="60" class="rounded-circle img-fluid"></div>
                    <div class="ms-3 mt-1 align-content-start text-start ">
                        @can('users_view_profile') <a href="{{ route('users.view', $person) }}" class="person_link"> @endcan
                            <h5 class="card-title mb-1">{{ $person->fullname }}</h5>
                        @can('users_view_profile') </a> @endcan

                        <x-ui.badge.light_rounded type="{{$color}}" text="{{$color}}"
                                                  class="mb-3">{{$badge}}</x-ui.badge.light_rounded>
                        <div>
                        </div>
                    </div>
                </div>
            @else
                <div class="ms-3 mt-1 align-content-start text-start ">
                        <h5 class="card-title mb-1 text-danger">
                            <x-ui.icon.solid icon="fa-triangle-exclamation" class="me-2"></x-ui.icon.solid>
                            Руководитель ТО не назначен
                        </h5>
                </div>
            @endif


            @if($task->canSetSupervisor())
                <x-ui.button.sidebar_default href="{{ route('education-task.sidebar_supervisor', $task) }}" btn_type="danger">

                    <x-ui.icon.regular icon="fa-user-crown"></x-ui.icon.regular>
                    <span class="d-none d-lg-inline">Назначить</span>
                </x-ui.button.sidebar_default>
            @endif
        </div>
    </div>
</div>
