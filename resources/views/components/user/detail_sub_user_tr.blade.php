<!-- contact -->
@if(_can('users_view_profile'))
    <a href="{{ route('users.view', $subUser) }}" class="p-3 d-flex align-items-start rounded-3 border-top">
@else
    <div class="p-3 d-flex align-items-start rounded-3 border-top">
@endif
    <div class="user-img position-relative d-inline-block me-2">
        <img src="{{ $subUser->avatar() }}" alt="user" class="rounded-circle w-100">
        @if($subUser->isOnline)
            <x-user.status-online></x-user.status-online>
        @endif
    </div>
    <div class="ps-2 v-middle d-md-flex align-items-center w-100">
        <div>
            <h5 class="my-1 text-dark font-weight-medium">
                {{ $subUser->fullName }}
            </h5>
            <div class="fs-3 mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark me-2">{{ $subUser->work_department }}</div>
            <div class="fs-3 mb-1 badge bg-light-primary text-primary ">{{ $subUser->work_position }}</div>
        </div>
    </div>
@if(_can('users_view_profile'))
    </a>
@else
    </div>
@endif
