<div class="d-inline">
    @if($checked)
        <x-ui.badge.default class="p-2 fs-2 font-weight-medium" type="{{$notificator['color']}}">
            <x-ui.icon.blank class="me-1" family="{{ $notificator['icon_family'] }}" icon="{{ $notificator['icon'] }}"></x-ui.icon.blank>
            {{ $notificator['name'] }}
        </x-ui.badge.default>
    @else
    @endif
</div>
