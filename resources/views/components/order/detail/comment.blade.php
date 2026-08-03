<div class="sl-item mt-2 mb-3 @if(empty($mode) && !empty($loop)) @unless($loop->first) d-none @endif @endif">
    <div class="sl-left float-left me-3">
        <img src="{{ $comment->user->avatar() }}" alt="user" class="rounded-circle">
    </div>
    <div class="sl-right">
        <div>
            <div class="align-items-center">
                <span class="sl-date text-muted fs-1">{{ $comment->created_at }}</span>
                <h5 class="mb-0 font-weight-medium">
                    @can('users_view_profile')<a href="{{ route('users.view', $comment->user) }}" class="link">@endcan
                        {{ $comment->user->fullname }}
                        @can('users_view_profile')</a>@endcan
                </h5>
            </div>

            <p class="fs-3 mt-1 mb-1">
                {{ $comment->text }}
            </p>
            <div class="dates mb-1 @if(empty($mode) && (empty($loop) || $loop->first)) d-none @endif">
                @if(!empty($comment->control_first))
                    <x-ui.badge.default type="secondary" class="me-2">{{ $comment->control_first }}</x-ui.badge.default>
                @endif
                @if(!empty($comment->control_second))
                    <x-ui.badge.default type="secondary">{{ $comment->control_second }}</x-ui.badge.default>
                @endif
            </div>
        </div>
    </div>
</div>
