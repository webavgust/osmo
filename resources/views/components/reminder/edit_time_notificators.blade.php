@php $uuid_time = \Str::uuid(); @endphp
<div class="d-inline" uuid="{{ $uuid_time }}">
    <input name="time[{{$uuid}}][notificator][]" value="{{ $notificator['type'] }}" type="checkbox" @if($checked == true) checked @endif class="btn-check" id="cb_{{$uuid_time}}_{{$notificator['type']}}" autocomplete="off">
    <label class="btn btn-outline-{{$notificator['color']}} p-1 fs-2 ps-2 pe-2 font-weight-medium" for="cb_{{$uuid_time}}_{{$notificator['type']}}">
        <x-ui.icon.blank class="me-1" family="{{ $notificator['icon_family'] }}" icon="{{ $notificator['icon'] }}"></x-ui.icon.blank>
        {{ $notificator['name'] }}
    </label>
</div>
