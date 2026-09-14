{{-- Виджет «Уведомления» (patch v30): App\Modules\Pub\Desktop\Widgets\Personal\NotificationsWidget --}}
@php
    // строки с запасом: не влезшие целиком спрячет .desk-fit
    $list = array_slice($data['rows'], 0, $rows_max);

    // места вдвое больше, чем уведомлений, — текст уведомления второй строкой под заголовком
    $roomy = $rows >= 2 * max(1, count($list));

    $href = fn($row) => $preview || empty($row['url']) ? 'javascript:void(0)' : $row['url'];
@endphp
@if(empty($data['rows']))
    @php $empty = $data['only_unread'] ? 'Непрочитанных уведомлений нет' : 'Уведомлений нет'; @endphp
    {{-- в узком блоке слово шире тела и выталкивает значок — там только значок, текст в title --}}
    <div class="desk-empty" title="{{ $empty }}">
        <i class="fa-light fa-bell"></i><span class="desk-hide-narrow">{{ $empty }}</span>
    </div>
@else
    <div class="desk-stack">
        @if($data['unread'] > 0)
            {{-- обёртка: d-flex с !important перебил бы desk-only-h-lg --}}
            <div class="flex-shrink-0 desk-only-h-lg desk-hide-narrow">
                <span class="badge badge-light-primary">
                    {{ $data['unread'] }} {{ \App\Facades\Tools::morph($data['unread'], 'непрочитанное', 'непрочитанных', 'непрочитанных') }}
                </span>
            </div>
        @endif

        <ul class="desk-list desk-stack-grow desk-fit">
            @foreach($list as $row)
                @php $hint = trim($row['title'] . ($row['message'] !== '' ? ' — ' . $row['message'] : '')); @endphp
                <li>
                    <i @class([
                            'fa-light', $row['icon'], 'flex-shrink-0', 'desk-hide-narrow',
                            'text-primary' => $row['unread'],
                            'desk-muted' => !$row['unread'],
                       ])></i>
                    @if($roomy)
                        <div class="desk-grow">
                            <a href="{{ $href($row) }}"
                               @class(['desk-link', 'd-block', 'desk-nowrap', 'text-hover-primary', 'fw-bold' => $row['unread']])
                               title="{{ $hint }}">{{ $row['title'] }}</a>
                            @if($row['message'] !== '')
                                <div class="desk-muted desk-nowrap fs-7" title="{{ $hint }}">{{ $row['message'] }}</div>
                            @endif
                        </div>
                    @else
                        <a href="{{ $href($row) }}"
                           @class(['desk-link', 'desk-grow', 'text-hover-primary', 'fw-bold' => $row['unread']])
                           title="{{ $hint }}">{{ $row['title'] }}</a>
                        @if($row['message'] !== '')
                            <span class="desk-muted desk-grow desk-only-w-lg" title="{{ $hint }}">{{ $row['message'] }}</span>
                        @endif
                    @endif
                    <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-md" title="{{ $row['ago'] }}">{{ $row['time'] }}</span>
                    @if($row['unread'])
                        <span class="bullet bullet-dot bg-primary flex-shrink-0" title="Не прочитано"></span>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="desk-muted fs-8 flex-shrink-0 desk-hide-short" data-fit-more="ещё {n}"></div>
    </div>
@endif
