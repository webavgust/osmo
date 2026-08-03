    @if($point->isAllMeasuresAsseted())
        <x-ui.badge.default :type="$success" title="Кол-во показателей для измерения на этой точке">
            <span class="text-success">
                <x-ui.icon.solid icon="fa-check"></x-ui.icon.solid>
                <x-ui.icon.solid icon="fa-flask"></x-ui.icon.solid>
                {{ $point->measures->count() }}
            </span>
        </x-ui.badge.default>
    @else
        <x-ui.badge.default :type="$failed" style="opacity: .4" title="Кол-во показателей для измерения на этой точке" >
            <span class="text-dark">
                <x-ui.icon.solid icon="fa-flask"></x-ui.icon.solid>
                {{ $point->measures->count() }}
            </span>
        </x-ui.badge.default>
    @endif
