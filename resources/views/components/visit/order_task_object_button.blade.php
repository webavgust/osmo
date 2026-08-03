<div class="d-inline-block border-warning mb-1 align-items-center me-1 " style="width: 135px">
    <x-ui.notification.regular :type="$color" class="d-flex-inline p-2 px-1 border-2 text-center mb-0">
        <div class="text-align mt-1">
            <div class="font-14">
                <x-ui.icon.duotone :icon="$icon" class="me-2"></x-ui.icon.duotone>
                <span>{{ _date($visit->fact_visit_at ?? $visit->plan_visit_at) }}</span>
            </div>
            <div class="font-10">{{ $preset['name'] }}</div>
        </div>

        @if(!empty($visit->number))
            @if($visit->canViewDetail())
            <a href="{{ route('visit.lab', $visit->number->number) }}">
                <x-ui.badge.light_rounded :type="$color" class="font-12 bg-white border-type-solid border-1 position-absolute" style="top:  -10px; left: calc(50% - 35px); border-style: solid">
                    {{ $visit->number->number }}</x-ui.badge.light_rounded>
            </a>
            @else
                <x-ui.badge.light_rounded :type="$color" class="font-12 bg-white border-type-solid border-1 position-absolute" style="top:  -10px; left: calc(50% - 35px); border-style: solid">
                    {{ $visit->number->number }}</x-ui.badge.light_rounded>
            @endif
        @endif
    </x-ui.notification.regular>

    @if(!empty($button))
        @if(!empty($box))
            <x-ui.a.box :href="$box" :outline="$is_outline" :btn_type="$color" class="font-10 d-flex justify-content-center" style="margin-top: -1px">
                {!! $button  !!}
            </x-ui.a.box>
        @elseif(!empty($href))
            <x-ui.a.default :href="$href" :outline="$is_outline" :btn_type="$color" class="font-10 d-flex justify-content-center" style="margin-top: -1px">
                {!! $button  !!}
            </x-ui.a.default>
        @endif
    @endif


    @if(!empty($button_add))
        @if(!empty($button_add['box']))
            <x-ui.a.box :href="$button_add['box']" :outline="$is_outline" :btn_type="$color" class="font-12 d-flex justify-content-center" style="margin-top: 0px">
                {!! $button_add['name']  !!}
            </x-ui.a.box>
        @elseif(!empty($button_add['href']))
            <x-ui.a.default :href="$button_add['href']" :outline="$is_outline" :btn_type="$color" class="font-12 d-flex justify-content-center" style="margin-top: 0px">
                {!! $button_add['name']  !!}
            </x-ui.a.default>
        @endif
    @endif


</div>
