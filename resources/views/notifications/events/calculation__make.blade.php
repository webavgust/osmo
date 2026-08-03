@section('email_title')
    Произведён расчёт заработной платы
@endsection

@section('email_message')
    @foreach(\App\Modules\Pub\Salary\Models\Salary::TYPES as $chr => $ar)
        @if(empty($salaries[$chr]))
            @continue;
        @endif

        <h2 style="margin-top: 30px;">{{ $ar['name'] }}</h2>

        <table border="1">
            @foreach($salaries[$chr] as $user_id => $periods)
                <tr>
                    <td colspan="2" style="padding: 5px 8px"><h3 style="margin: 5px">{{ \App\Modules\Pub\User\Services\UserService::getName($user_id) }}</h3></td>
                </tr>
                @foreach($periods as $period => $sum)
                    @php
                        list($month, $year) = explode(".", $period);

                    @endphp
                    <tr>
                        <td style="padding: 4px">{{ \App\Services\Tools\Tools::MONTH_NAME[(int)$month] }} 20{{ $year }}</td>
                        <td style="padding: 4px; text-align: right">{{ $sum }} р.</td>
                    </tr>
                @endforeach
            @endforeach
        </table>
    @endforeach

@endsection

