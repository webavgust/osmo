<tr unbind>
    <td class="text-center px-0">{{ $num }}</td>
    <td class="text-center px-0">
        <div class="form-check">
            <input  class="form-check-input" type="checkbox" value="1" id="flexCheckDefault" checked name="massive[lessons][{{$code}}][active]">
        </div>
    </td>
    <td class=" ps-2">
        <div class="input-group" style="max-width: 300px">
            <div class="input-group-text">
                <i class="fa-regular fa-calendar" icon="fa-calendar"></i>
            </div>
            <input type="text" class="form-control" name="massive[lessons][{{$code}}][date]" id="datepicker" style="width: 80px" value="">
            <div class="input-group-text">
                Время:
            </div>
            <input type="text" class="form-control flex-grow-0 text-center" name="massive[lessons][{{$code}}][from]" id="create_from" style="width: 70px" value="">
        </div>
    </td>
    <td class="text-center" align="center">
        <input type="text" class="form-control flex-grow-0 text-center" name="massive[lessons][{{$code}}][duration]" style="width: 70px" value="{{ $duration }}">
    </td>
    <td>
        <x-ui.select.single :items="$classes" id="id" name="massive[lessons][{{$code}}][class]" value-name="number" blank-ignore="1"> </x-ui.select.single>
    </td>
</tr>
