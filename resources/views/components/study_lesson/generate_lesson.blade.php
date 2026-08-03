<tr unbind>
    <td class="text-center px-0">{{ $iteration }}</td>
    <td class="text-center px-0">
        <div class="form-check">
            <input  class="form-check-input" type="checkbox" value="1" id="flexCheckDefault" checked name="massive[lessons][{{$iteration}}][active]">
        </div>
    </td>
    <td class=" ps-2">
        <div class="input-group" style="max-width: 300px">
            <div class="input-group-text">
                <i class="fa-regular fa-calendar" icon="fa-calendar"></i>
            </div>
            <input type="text" class="form-control" name="massive[lessons][{{$iteration}}][date]" id="datepicker" style="width: 80px" value="{{ \Carbon\Carbon::createFromTimestamp(strtotime($date))->format("d.m.Y") }}">
            <div class="input-group-text">
                Время:
            </div>
            <input type="text" class="form-control flex-grow-0 text-center" name="massive[lessons][{{$iteration}}][from]" id="create_from" style="width: 70px" value="{{ now()->startOfDay()->addMinutes($lesson['from'])->format('H:i') }}">
        </div>
    </td>
    <td class="text-center" align="center">
        <input type="text" class="form-control flex-grow-0 text-center" name="massive[lessons][{{$iteration}}][duration]" style="width: 70px" value="{{ $lesson['duration_ah'] }}">
    </td>
    <td>
        <x-ui.select.single :items="$classes" value="{{ $lesson['class_id'] ?? 0 }}" id="id" name="massive[lessons][{{$iteration}}][class]" value-name="number" blank-ignore="1"> </x-ui.select.single>
    </td>
</tr>
