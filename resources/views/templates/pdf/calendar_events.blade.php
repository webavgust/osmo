<style>
    @page { margin:10px; font-size: 12px; margin-left: 50px; }


    h1 { margin-top: 20px; margin-bottom: 40px; }
    h3 { margin-bottom: 10px; }
    .ta-right { text-align: right; }
    .ta-center { text-align: center; }
    .valign { vertical-align: middle; }

    .fs10 { font-size: 10px; }
    .fs11 { font-size: 11px; }
    .fs12 { font-size: 12px; }
    .fs13 { font-size: 13px; }
    .fs14 { font-size: 14px; }
    .fs15 { font-size: 15px; }
    .fs16 { font-size: 16px; }
    .fs20 { font-size: 20px; }
    .fs21 { font-size: 21px; }
    .fs22 { font-size: 22px; }
    .fs23 { font-size: 23px; }
    .fs24 { font-size: 24px; }
    .fs25 { font-size: 25px; }
    .fs26 { font-size: 26px; }
    .mb-20 { margin-bottom: 20px; }
    .mb-30 { margin-bottom: 30px; }
    .mb-10 { margin-bottom: 10px; }
    table { border: 0; border-collapse: collapse; }
    table td { padding: 5px 10px 20px 5px; }
    table th { padding: 5px 30px 20px 5px; }

</style>


<div class="ta-right" style="padding-top: 30px">
    <strong class="fs12">Список будущих событий для пользователя "{{ $user->fullName }}"</strong>
</div>

@foreach($further as $block => $events)
    <h1>{{ $block }}</h1>
    <table class="">
    @foreach($events as $event)
    <tr>
        <th width="170">
            @switch($event->mode)
                @case('day')
                    {{ $event->start->format('d.m.Y') }}
                @break
                @case('dates')
                    {{ $event->start->format('d.m.Y') }}<br/>
                    {{ $event->end->format('d.m.Y') }}
                @break
                @case('time')
                    {{ $event->start->format('d.m.Y H:i') }}<br/>
                    {{ $event->end->format('d.m.Y H:i') }}
                @break
            @endswitch
        </th>
        <td>
            <h3 class="mb-5">{{ $event->title }}</h3>
            <div class="mb-20">
                {!! $event->text !!}
            </div>
        </td>
    </tr>
    @endforeach
    </table>
@endforeach
