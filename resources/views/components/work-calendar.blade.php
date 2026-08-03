<div class="card">
    <div class="card-body p-3 d-flex justify-content-between">

        <h4 class="m-0">{{ \App\Facades\Tools::MONTH_NAME[$date->month] }} {{ $date->year }}</h4>
    </div>
    <div class="card-body p-0">
        <table class="month">
            <thead>
            <tr>
                <th/>
                <th>ПН</th>
                <th>ВТ</th>
                <th>СР</th>
                <th>ЧТ</th>
                <th>ПТ</th>
                <th>СБ</th>
                <th>ВС</th>
            </tr>
            @foreach($data as $week => $days)
                <tr>
                    <td class="week_num">{{ $week }}</td>
                        @if($loop->iteration == 1 && count($days) < 7) <td colspan="{{  7 - count($days) }}"></td> @endif
                        @foreach($days as $day_i => $day)
                            <td>

                                <div class="control" date="{{$day['date']}}">

                                    <i class="fa fa-circle font-10 text-warning @unless($day['custom'])d-none @endif" style="margin-left: -13px"></i>

                                    <input name="date[]" value="{{ $day['date'] }}" type="checkbox" id="day{{ $day['day_num'] }}" @if($day['checked'])checked="checked" @endif disabled="disabled" >
                                    <label for="day{{$day['day_num']}}">{{$day['num']}}</label>
                                </div>
                            </td>
                        @endforeach

                </tr>
            @endforeach
        </table>
    </div>
</div>
