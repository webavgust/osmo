<div
    class="message-item flex-column border-bottom px-2 py-1 position-relative overflow-hidden w-100"
    id="{{ $notify->id }}">
        <span class="notify_once btn btn-light-danger text-danger btn-circle position-absolute delete">
            <x-ui.icon.regular icon="fa-xmark"></x-ui.icon.regular>
        </span>

         @if(!empty($notify->link)) <a href="{{ $notify->link }}" class="w-100"> @endif
            <div class="d-inline-block v-middle ps-1 w-100">
                <h5 class="message-title mb-0 mt-1 fs-3 fw-bold">
                        @if(!empty($notify->icon))
                            <x-ui.icon.regular icon="{{$notify->icon}}"
                                               class="text-info me-1"></x-ui.icon.regular>
                        @endif
                        {!! $notify->title !!}
                </h5>
                <span class=" fs-3 d-block time mt-2">
                    {!! $notify->message !!}
                </span>
            </div>
        @if(!empty($notify->link)) </a> @endif

        <div class=" fs-2 text-nowrap d-block subtext text-muted cursor-help me-2 text-right  "
             title="{{ _datetime($notify->created_at) }}">
            {{ _time_human($notify->created_at) }}
        </div>
</div>
