@php $uuid = \Str::uuid(); @endphp
<div mode="time" class="date-select-inputs mt-3  pb-2 border-1 border p-2 shadow position-relative" style="border-radius: 5px;">
    <x-ui.icon.solid class="position-absolute text-danger fs-5 time_delete" icon="fa-circle-trash" onclick="javascript:$(this).parents('[mode]').remove()"></x-ui.icon.solid>
    <div class="input-group mb-3">
        <input type="date" class="form-control" value=""
               name="time[{{$uuid}}][date]" required>
        <input type="time" class="form-control" value=""
               name="time[{{$uuid}}][time]" required>
    </div>
    <div>
        @foreach($notificators as $notificator)
            <x-reminder.add_time_notificators uuid="{{$uuid}}" :notificator="$notificator"></x-reminder.add_time_notificators>
        @endforeach
    </div>
</div>
