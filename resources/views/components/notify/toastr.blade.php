<div class="toast" role="alert" aria-live="assertive" aria-atomic="true" id="{{ $notify->id }}">
    @if(!empty($notify->title))
        <div class="toast-header">
            @if(!empty($notify->icon))
                <x-ui.icon.regular icon="{{ $notify->icon }}" class="me-2"></x-ui.icon.regular>
            @endif
            <strong class="me-auto">{{ $notify->title }}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close" onclick="javascript:$(this).parents('.toast').remove()"></button>
        </div>
    @endif

    @if(!empty($notify->message))
        <div class="toast-body">
            {!! $notify->message !!}
        </div>
    @endif
</div>

