WORK CALENDAR
{{ $chr }}
{{--<div class="card">--}}
{{--    <div class="card-body p-3 d-flex justify-content-between">--}}
{{--        <h4 class="m-0">{{ \App\Facades\Tools::MONTH_NAME[$i] }} {{ $year }}</h4>--}}
{{--    </div>--}}
{{--    <div class="card-body p-3">--}}
{{--        <table class="month" cellspacing="0" cellpadding="0">--}}
{{--            <thead>--}}
{{--            <tr>--}}
{{--                <th/>--}}
{{--                <th>ПН</th>--}}
{{--                <th>ВТ</th>--}}
{{--                <th>СР</th>--}}
{{--                <th>ЧТ</th>--}}
{{--                <th>ПТ</th>--}}
{{--                <th>СБ</th>--}}
{{--                <th>ВС</th>--}}
{{--            </tr>--}}
{{--            </thead>--}}
{{--            <tbody>--}}
{{--            @foreach($arData as $week => $days):--}}
{{--            <tr>--}}
{{--                <td class="week_num">{{ $week }}</td>--}}
{{--                @if($loop->iteration == 1 && count($days) < 7) <td colspan="{{  7 - count($days) }}"></td> @endif--}}
{{--                @foreach($days as $day_i => $day)--}}
{{--                    <td>--}}
{{--                        <div class="control @if($day['week_start'])week_start @endif">--}}
{{--                            <input name="save[date][]" value="{{ $day['date'] }}" type="checkbox" id="day{{ $day['day_num'] }}" @if($day['checked'])checked="checked" @endif> disabled="disabled">--}}
{{--                            <label for="day{{ $day['day_num'] }}">{{ $day['num'] }}</label>--}}
{{--                        </div>--}}
{{--                    </td>--}}
{{--                @endforeach--}}

{{--                @if($loop->iteration == count($arData) && count($days) < 7)<td colspan="{{7 - count($days)}}"></td> @endif--}}
{{--            </tr>--}}
{{--            @endforeach;--}}
{{--            </tbody>--}}
{{--        </table>--}}
{{--    </div>--}}
{{--</div>--}}
