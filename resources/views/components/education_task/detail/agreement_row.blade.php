<div id="{{ $user->id }}" num="{{$loop->iteration}}" class="mt-2">
    <div class="tr">
        <span class="th">{{ $loop->iteration }}) {{ $user->full_name }}</span>
        <span class="td">
             @switch($user->pivot->agreed)
                @case(0)
                <i class="fa-regular fa-clock text-warning"></i>
                @break
                @case(1)
                <i class="fa-duotone fa-check text-success cursor-help"
                   title="{{ $user->pivot->updated_at->format('d.m.Y H:i:s') }}"></i>
                @break
                @case(-1)
                <i class="fa-solid fa-xmark text-danger cursor-help"
                   title="{{ $user->pivot->updated_at->format('d.m.Y H:i:s') }}"></i>
                @break
            @endswitch
        </span>
    </div>

    @if($user->pivot->agreed === 0 && ($user->id == auth()->user()->id || auth()->user()->isAdmin()))
        <div class="mt-3 mb-3 d-flex">
            <x-ui.button.default btn_type="danger flex-grow-1 me-1" onclick="javascript:discount_agreement_decline({{$user->id}})">
                <x-ui.icon.regular icon="fa-xmark" class="me-1"></x-ui.icon.regular>
                Отказать
            </x-ui.button.default>

            <x-ui.a.default btn_type="success flex-grow-1 ms-1" onclick="javascript:discount_agreement_confirm({{$user->id}})">
                <x-ui.icon.regular icon="fa-check" class="me-1"></x-ui.icon.regular>
                Согласовать
            </x-ui.a.default>
        </div>
    @endif


    @if(!empty($user->pivot->comment))
        @if($user->pivot->agreed == 1)
            <div class="alert customize-alert alert-dismissible text-secondary alert-light-secondary fade show remove-close-icon mt-2 p-1 ps-2 pe-2 ms-3 font-12" role="alert">
                {!! nl2br($user->pivot->comment) !!}
            </div>
        @else
            <div class="alert customize-alert alert-dismissible text-secondary alert-light-secondary fade show remove-close-icon mt-2 p-1 ps-2 pe-2 ms-3" role="alert">
                {!! nl2br($user->pivot->comment) !!}
            </div>
        @endif
    @endif
</div>
