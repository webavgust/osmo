
@section('telegram_title')
    {{ "\u{274C}" }} A.: <a href="{{ route('order_task.detail', $order_task) }}">ТЗ #{{ $order_task->id }}</a> не одобрено
@endsection

@section('telegram_message')
    @foreach($order_task->agreement->users as $user)
        @switch($user->pivot->agreed)
            @case(0)
            {{ "\u{2B1C}" }} {{ $loop->iteration }}. {{ $user->fullName }}
            @break
            @case(1)
                {{ "\u{1F7E9}" }} {{ $loop->iteration }}. {{ $user->fullName }}
            @break
            @case(-1)
                {{ "\u{1F7E5}" }} {{ $loop->iteration }}. {{ $user->fullName }}
            @break
        @endswitch
        @if(!empty($user->pivot->comment))
            <i>{{ $user->pivot->comment }}</i>
        @endif

    @endforeach
@endsection






@section('site_title')
    ТЗ # {{ $order_task->id }}
@endsection

@section('site_message')
    В техническом задании #{{$order_task->id}} произошли изменения!
@endsection
