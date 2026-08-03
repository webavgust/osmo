<table class="table customize-table mb-0 v-middle">
    <tr>
        <td class="text-center px-2">ID</td>
        <td class="px-1">Урок</td>
        <td class="text-right px-1">Длительность</td>
        <td class="text-right px-1">Расчётная</td>
        <td class="text-right px-1">Ставка</td>
        <td class="text-right px-1">Стоимость</td>
        <td class="text-right px-1">Статус</td>
    </tr>
    @foreach($lessons as $lesson)
        <tr>
            <td class="p-1 font-12 text-center">
                {{ $lesson->id }}
            </td>
            <td class="ps-0 p-1">
                <strong>{{ $lesson->start_at->format('d.m') }}</strong>
                <span class="font-12">{{ $lesson->start_at->format('H:i') }}-{{ $lesson->end_at->format('H:i') }}</span>
            </td>
            <td style="text-align: right" class="p-1">
                @if($lesson->trashed())
                    <div>
                        <x-ui.badge.light  type="danger" class="font-12" >
                            {{ $lesson->duration }} а.ч.
                        </x-ui.badge.light>
                    </div>
                @elseif(empty($calculatedLessons[$lesson->id]))
                    <div>
                        <x-ui.badge.light  type="warning" class="font-12" >
                            {{ $lesson->duration }} а.ч.
                        </x-ui.badge.light>
                    </div>
                @else
                    <div>
                        <x-ui.badge.light  type="success" class="font-12" >
                            {{ $lesson->duration }} а.ч.
                        </x-ui.badge.light>
                    </div>
                @endif
            </td>
            <td style="text-align: right" class="p-1">
                @if(!empty($calculatedLessons[$lesson->id]))
                    @if($calculatedLessons[$lesson->id]['duration'] == $lesson->duration)
                        <div>
                            <x-ui.badge.light  type="success" class="font-12" >
                                {{ $calculatedLessons[$lesson->id]['duration'] }} а.ч.
                            </x-ui.badge.light>
                        </div>
                    @else
                        <div>
                            <x-ui.badge.light  type="warning" class="font-12" >
                                {{ $calculatedLessons[$lesson->id]['duration'] }} а.ч.
                            </x-ui.badge.light>
                        </div>
                    @endif
                @endif
            </td>
            <td class="p-1 text-right">
                @if(!($lesson->trashed() || empty($calculatedLessons[$lesson->id])))
                    <span class="font-12">* {{ tools()->cost_normalize($calculatedLessons[$lesson->id]->rate) }} ₽</span>
                @endif
            </td>
            <td class="p-1 text-right">
                @if(!($lesson->trashed() || empty($calculatedLessons[$lesson->id])))
                    <span class="font-12">= {{ tools()->cost_normalize($calculatedLessons[$lesson->id]->amount) }} ₽</span>
                @endif
            </td>
            <td class="p-1 text-right">
                @if($lesson->trashed())
                    <x-ui.badge.light type="danger">
                        <x-ui.icon.regular icon="fa-xmark" class="text-danger" class="me-1"></x-ui.icon.regular>
                        Удалён
                    </x-ui.badge.light>
                @elseif(!empty($calculatedLessons[$lesson->id]) && $lesson->duration !== $calculatedLessons[$lesson->id]['duration'])
                    <x-ui.badge.light type="warning" class="mt-1">
                        <x-ui.icon.regular icon="fa-not-equal" class="text-warning me-1"></x-ui.icon.regular>
                        Будет пересчитано
                    </x-ui.badge.light>
                @elseif(empty($calculatedLessons[$lesson->id]))
                    @if(now()->startOfMonth()->greaterThan($pointer))
                        <x-ui.badge.light type="warning">
                            <div class="mt-1">Ближайший расчёт</div>
                        </x-ui.badge.light>
                    @else
                        <x-ui.badge.light type="warning">
                            <div class="mt-1">1 {{ \Illuminate\Support\Str::lower(tools()::MONTH_NAME_D[$pointer->clone()->addMonth()->format('n')]) }} {{ $pointer->clone()->addMonth()->format('Y') }} года</div>
                        </x-ui.badge.light>
                    @endif

                @endif
            </td>
        </tr>
    @endforeach
    @if($calculatedLessons->count())
        <tr>
            <td class="p-1 border-bottom-0 font-14 text-center">
                <strong>{{ $lessons->count() }} </strong>
            </td>
            <td class="p-1 border-bottom-0">
            <td class="p-1 border-bottom-0 font-14 text-right">
                <strong>{{ $lessons->sum('duration') }} а.ч.</strong>
            </td>
            <td class="p-1 border-bottom-0 font-14 text-right">
                <strong>{{ $calculatedLessons->sum('duration') }} а.ч.</strong>
            </td>

            <td class="p-1 border-bottom-0 font-14 text-right">
                @if($calculatedLessons->sum('duration') > 0)
                    <strong>{{ round($calculatedLessons->sum('amount') / $calculatedLessons->sum('duration') , 2) }} ₽</strong>
                @else
                    ?
                @endif
            </td>
            <td class="p-1 border-bottom-0 font-14 text-right">
                <strong>{{ round($calculatedLessons->sum('amount'), 2) }} ₽</strong>
            </td>
        </tr>
@endif
</table>

