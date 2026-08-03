<div class="col-md-12 col-lg-12 position-relative user-row">
    @can('users_view_profile') <a href="{{ route('users.view', $person) }}" class="person_link"> @endcan
        <div class="card text-center alert-dismissible fade show alert p-0 @can('users_view_profile')card-hover @endcan" role="alert" >
            <div class="p-3 pt-2 pb-2 d-flex">
                <div><img src="{{ $person->avatar() }}" width="60" class="rounded-circle img-fluid"></div>
                <div class="ms-3 mt-1 align-content-start text-start ">
                    <h5 class="card-title mb-1">{{ $person->fullname }}</h5>
                    <x-ui.badge.light_rounded type="{{$color}}" text="{{$color}}" class="mb-3">{{$badge}}</x-ui.badge.light_rounded>
                </div>
            </div>
        </div>
    @can('users_view_profile') </a> @endcan
</div>
