<div {{ $attributes->class(['fade', 'show', 'remove-close-icon', 'alert', 'customize-alert', 'alert-dismissible', 'border', 'border-'.$attributes['type'], 'text-'.$attributes['type'], 'text-'.$attributes['type'], 'font-weight-medium']) }}>
     @if(!empty($close))
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x fill-white text-primary feather-sm"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    @endif
    {{ $slot }}
</div>
