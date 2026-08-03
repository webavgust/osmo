@foreach($address->points as $point)
    @if($loop->iteration > 1)
        <tr>
            <td colspan="12" class="p-0" style="border: 0; padding-top: 15px; height: 2px; background: #CCC"></td>
        </tr>
    @endif
    <tr>
        <th class="p-0">
            <div class="p-1">
                <x-ui.icon.solid icon="fa-location-dot" class="me-1"></x-ui.icon.solid>
                <strong>{{ $point->name }}</strong>
            </div>

            <x-order-task.progress :progress="$point->getProgress()" short="1"></x-order-task.progress>
        </th>
        <th class="text-center border-left-3 py-1">
            <strong>ТЗ</strong>
        </th>
        <th colspan="5" class="border-left-3 py-1">
            <strong>Отборы</strong>
        </th>
        <th colspan="4" class="border-left-3 py-1">
            <strong>Анализ</strong>
        </th>
    </tr>
    <tr>
        <th class="p-2 font-12">Параметр для измерения</th>
        <th class="px-0 py-2 font-12 text-center border-left-3">Кол-во</th>
        <th class="px-0 py-2 font-12 text-center border-left-3">Всего</th>
        <th class="p-2 font-12 text-center">Акт</th>
        <th colspan="3" class="p-2 font-12 text-center">Контейнеры</th>

        <th colspan="4" class="px-0 py-2 font-12 text-center border-left-3">По контейнерам</th>
        <th class="px-0 py-2 font-12 text-center">Всего</th>
    </tr>
    @foreach($point->measures as $measure)
        @php
            $rows_count = 0;
            if(!empty($data[$point->id][$measure->id])) {
                $rows_count = $data[$point->id][$measure->id]->sum(function($container) {
                    return max(1, $container['works']->count());
                });
            }
            $rows_count = max(1, $rows_count);
        @endphp
        @for($container_i = 0; $container_i < (!empty($data[$point->id][$measure->id]) ? max(1, $data[$point->id][$measure->id]->count()) : 1); $container_i++)
            @php
                $container = $data[$point->id][$measure->id][$container_i] ?? [];
            @endphp
            @for($work_i = 0; $work_i < (!empty($container['works']) ? max(1, count($container['works'])) : 1); $work_i++)

                <tr container_i="{{ $container_i }}" work_i="{{ $work_i }}" rows_count="{{ $rows_count }}">
                    {{-- первые три колонки --}}
                    @if($container_i == 0 && $work_i == 0)
                        <td rowspan="{{ $rows_count }}" class="p-1 ps-2 font-14">
                            <span class="text-wrap">
                                {{ \Illuminate\Support\Str::ucfirst($measure->measure->name) }}
                            </span>
                        </td>

                        @php
                            if(!empty($data[$point->id][$measure->id])) {
                                $percent = round(min($data[$point->id][$measure->id]->sum('count') / $measure->count, 1), 2);
                            } else {
                                $percent = 0;
                            }

                            match(true) {
                                $percent == 1 => $color = '#f5fff5',
                                $percent > 0 => $color= '#fffaeb',
                                default => $color = '#F5F5F5'
                            }
                        @endphp

                        <td rowspan="{{ $rows_count }}" class="p-1 font-12 text-center border-left-3"
                            style="background-image: -webkit-gradient(linear, left top, right top, from({{ $color }}), to(white), color-stop({{ $percent-0.01 }}, {{ $color }}), color-stop({{ $percent }}, white));"
                        >
                            {{ $measure->count }} шт.
                        </td>


                        @php
                            if(!empty($data[$point->id][$measure->id])) {
                                $works_count = $data[$point->id][$measure->id]->sum(function($container) {
                                    return $container['works']->sum('count');
                                });
                                $percent = round(min($works_count / $data[$point->id][$measure->id]->sum('count'), 1), 2);
                            } else {
                                $percent = 0;
                            }

                            match(true) {
                                $percent == 1 => $color = '#f5fff5',
                                $percent > 0 => $color= '#fffaeb',
                                default => $color = '#F5F5F5'
                            }
                        @endphp

                        <td rowspan="{{ $rows_count }}" class="p-1 font-12 text-center border-left-3"
                            style="background-image: -webkit-gradient(linear, left top, right top, from({{ $color }}), to(white), color-stop({{ $percent-0.01 }}, {{ $color }}), color-stop({{ $percent }}, white));"
                        >
                            @if(!empty($container))
                                = {{ !empty($data[$point->id][$measure->id]) ? $data[$point->id][$measure->id]->sum('count') : 0 }} шт.
                            @else
                                -
                            @endif
                        </td>
                    @endif

                    {{-- выводим контейнер --}}
                    @if($work_i == 0)
                        @if(!empty($container))
                            <td class="p-1 font-12 text-center" rowspan="{{ !empty($container['works']) ? max(1, $container['works']->count()) : 1 }}">
                                <a href="{{ route('visit.view', $container['container']->visit->number->number) }}">
                                    {{ $container['container']->visit->number->number }}
                                </a>
                            </td>
                            <td class="p-1 font-12 text-center " rowspan="{{ !empty($container['works']) ? max(1, $container['works']->count()) : 1 }}">
                                {{ $container['count'] }} шт.
                            </td>
                            <td class="p-1" rowspan="{{ max(1, $container['works']->count()) }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="font-14"><code>{{ $container['container']->mark }}</code></div>
                                    <div class="font-12 ps-2">{{ $container['container']->creator->last_name }}</div>
                                </div>
                            </td>
                            <td class="p-1 font-12 text-center" rowspan="{{ !empty($container['works']) ? max(1, $container['works']->count()) : 1 }}">
                                {{ _date($container['container']->visit->users()->where('user_id', $container['container']->created_by)->first()->pivot['finished_at']) }}
                            </td>
                        @else
                            <td class="p-1 font-12 text-center">-</td>
                            <td class="p-1 font-12 text-center">-</td>
                            <td class="p-1 font-12 text-center">-</td>
                            <td class="p-1 font-12 text-center">-</td>
                        @endif
                    @endif




                    @if(!empty($container) && !empty($container['works'][$work_i]))
                        @if($work_i == 0)
                            <td class="p-1 font-12 text-center border-left-3" rowspan="{{  !empty($container['works']) ? max(1, $container['works']->count()) : 1 }}">
                                {{ !empty($container['works']) ? $container['works']->sum('count') : 0 }} шт.
                            </td>
                        @endif

                        <td class="p-1 font-12 text-center">
                            {{ $container['works'][$work_i]['count'] }} шт.
                        </td>
                        <td class="p-1 text-center ps-2">
                            <div class="font-12">{{ $container['works'][$work_i]->creator->last_name }}</div>
                        </td>
                        <td class="p-1 font-12 text-center">
                            {{ _date($container['works'][$work_i]['finished_at']) }}
                        </td>

                        @if($container_i == 0 && $work_i == 0)
                            <td rowspan="{{ $rows_count }}" class="p-1 ps-2 font-12 text-center">
                                = {{ $works_count }} шт.
                            </td>
                        @endif
                    @else
                            @if(!empty($container))
                                <td class="p-1 font-12 text-center border-left-3" colspan="4">
                                    В работе
                                </td>
                            @else
                                <td class="p-1 font-12 text-center border-left-3">-</td>
                                <td class="p-1 font-12 text-center">-</td>
                                <td class="p-1 font-12 text-center">-</td>
                                <td class="p-1 font-12 text-center">-</td>
                                <td class="p-1 font-12 text-center">-</td>
                           @endif
                    @endif
                </tr>
            @endfor
        @endfor

    @endforeach
    <tr>
        <td/>
        @php
            $measures_count = $point->measures->sum('count');
            $visits_count = $data[$point->id]->sum(function($measure) {
                return $measure->sum('count');
            });
            $analytics_count = $data[$point->id]->sum(function($containers) {
                return $containers->sum(function($container) {
                    return ($container['works'] ?? collect())->sum('count');
                });
            });
        @endphp
        <td class="p-1 font-16 text-center fw-bold border-left-3">{{ $measures_count }} шт.</td>
        <td class="p-1 font-16 text-center fw-bold border-left-3">{{ $visits_count }} шт.</td>
        <td colspan="4" style="border-bottom: 0"></td>
        <td colspan="4" class="border-left-3" style="border-bottom: 0"></td>
        <td class="p-1 font-16 text-center fw-bold ">{{ $analytics_count }} шт.</td>
    </tr>
@endforeach
