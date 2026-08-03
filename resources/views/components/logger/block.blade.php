<div data-search="{{ $search }}" {{ $attributes->class(['sl-item', 'mt-2', 'mb-3', 'float-left me-3' => $loop->first, 'my-4 border-top pt-4' => $loop->iteration > 1])->only(['class']) }}>
    <div class="sl-left float-left me-3">
        <img src="{{ $log->creator->avatar() }}" alt="user" class="rounded-circle">
    </div>
    <div class="sl-right w-100">
        <div>
            <div class="align-items-center">
                <h5 class="mb-0 font-weight-medium">
                    @can('users_view_profile')<a href="{{ route('users.view', $log->creator) }}" class="link">@endcan
                        {{ $log->creator->fullname }}
                        @can('users_view_profile')</a>@endcan
                </h5>
                <span class="sl-date fs-2">
                    <span class="{{ \App\Modules\Pub\ChangeLogger\Models\ChangeLogger::STATUS_DATA[$log->action]['color']['text'] }} fw-bold">{{ \App\Modules\Pub\ChangeLogger\Models\ChangeLogger::STATUS_DATA[$log->action]['name'] }}</span>
                    <span class="text-muted">{{ tools()->date_full($log->created_at) }} в {{ tools()->date($log->created_at, 'H:i') }}</span>
                </span>
            </div>


            <div class="row">
                <div class="col-12">
                    <div class="card-table mt-2">
                        @foreach($log->output as $row)
                            <x-logger.row :row="$row" :action="$log->action"></x-logger.row>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
