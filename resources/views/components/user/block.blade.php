@can('users_view_profile') <a href="{{ route('users.view', $user) }}"> @endcan

    <div class="d-flex align-items-center p-2">
        <img src="{{ $user->avatar() }}" class="rounded-circle" alt="user" width="32">
        <div class="ms-2">
            <div class="user-meta-info">
                <h6 class="user-name mb-0 font-weight-medium">
                    {{$user->name}} {{$user->last_name}}
                </h6>

                @if(!empty($slot))
                    <div>
                        {{ $slot }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@can('users_view_profile')</a> @endcan
