{{-- Виджет «Внешние КП» (patch v30): App\Modules\Pub\Desktop\Widgets\Proposal\ExternalProposalsWidget --}}
@php
    $pending = (int) $data['pending'];
    $fresh_days = (int) $data['fresh_days'];
    $days_word = \App\Facades\Tools::morph($fresh_days, 'день', 'дня', 'дней');

    // высота 1–2 ячейки — только счётчик; дальше список, режется подгоном по высоте;
    // высокий блок — счётчики плитками и двухстрочные записи
    $tall = $dh === 'xl' || ($dh === 'lg' && !in_array($dw, ['xs', 'sm'], true));
    $list = in_array($dh, ['xs', 'sm'], true) ? [] : array_slice($data['rows'], 0, $rows_max);
    $value_class = $dh === 'xl' && $dw !== 'xs' ? 'desk-value-lg' : 'desk-value';

    // попапы те же, что в колонке действий страницы «Внешние КП»
    $box = fn($url) => $preview || empty($url) ? null : $url;
    $row_title = fn($row) => trim($row['number'] . ' · ' . $row['name']
        . ($row['customer'] !== '' ? ' · ' . $row['customer'] : '')
        . ($row['cameras'] ? ' · камер: ' . $row['cameras'] : '')
        . ($row['date'] ? ' · ' . $row['date'] : ''));
    $fresh_title = 'Записи с датой КП за последние ' . $fresh_days . ' ' . $days_word;
    $transferred_title = 'Перенесено в КП портала из ' . $data['total'] . ' записей';
@endphp
<div @class(['desk-stack', 'desk-center' => empty($list)])>
    <div>
        <div class="desk-label desk-nowrap" title="Не перенесено в портал">
            {{ $dw === 'xs' ? 'не перенесено' : 'не перенесено в портал' }}
        </div>
        <div @class([$value_class, 'text-warning' => $pending > 0])>{{ $pending }}</div>

        @unless($tall)
            <div class="d-flex column-gap-2 align-items-baseline flex-wrap desk-hide-short">
                @if($data['fresh'] > 0)
                    <span class="desk-muted text-nowrap" title="{{ $fresh_title }}">
                        новых<span class="desk-hide-narrow"> за {{ $fresh_days }} {{ $days_word }}</span>: {{ $data['fresh'] }}
                    </span>
                @endif
                <span class="desk-muted fs-8 text-nowrap desk-only-w-md" title="{{ $transferred_title }}">
                    перенесено: {{ $data['transferred'] }} из {{ $data['total'] }}
                </span>
                @if($data['without_payload'] > 0)
                    <span class="text-danger fs-8 text-nowrap desk-only-w-lg" title="У этих записей не загружен detail: не видно ни валюты, ни лицензий, перенос невозможен">
                        без detail: {{ $data['without_payload'] }}
                    </span>
                @endif
                @if(!$data['configured'])
                    <span class="badge badge-light-warning fs-8 text-nowrap desk-only-w-md" title="Не заданы адрес и ключ API OSMOVIEW CP — синхронизация со страницы не работает">API не настроен</span>
                @endif
            </div>
        @endunless
    </div>

    @if($tall)
        <div class="desk-tiles desk-extprop-tiles">
            <div title="{{ $fresh_title }}">
                <div class="desk-label">новых за {{ $fresh_days }} {{ $days_word }}</div>
                <div class="fw-bold">{{ $data['fresh'] }}</div>
            </div>
            <div title="{{ $transferred_title }}">
                <div class="desk-label">перенесено</div>
                <div class="fw-bold">{{ $data['transferred'] }} из {{ $data['total'] }}</div>
            </div>
            @if($data['without_payload'] > 0)
                <div title="У этих записей не загружен detail: не видно ни валюты, ни лицензий, перенос невозможен">
                    <div class="desk-label">без detail</div>
                    <div class="fw-bold text-danger">{{ $data['without_payload'] }}</div>
                </div>
            @endif
            @if(!$data['configured'])
                <div title="Не заданы адрес и ключ API OSMOVIEW CP — синхронизация со страницы не работает">
                    <div class="desk-label">синхронизация</div>
                    <div class="fw-bold text-warning">API не настроен</div>
                </div>
            @endif
        </div>
    @endif

    @if(!in_array($dh, ['xs', 'sm'], true) && empty($data['rows']))
        <div class="desk-muted fs-8 desk-hide-short">
            {{ $settings['only_pending'] ? 'Все внешние КП перенесены' : 'Записей внешней системы нет' }}
        </div>
    @endif

    @if(!empty($list))
        <ul class="desk-list desk-stack-grow desk-fit desk-extprop-list" data-fit-min="0">
            @foreach($list as $row)
                <li>
                    {{-- каждый кусок текста — свой элемент флекса: номер и дата не режутся, название уходит в многоточие --}}
                    <a @if($box($row['detail_url'])) href="javascript:box({href: '{{ $box($row['detail_url']) }}'})" @else href="javascript:void(0)" @endif
                       class="desk-link desk-grow text-hover-primary" title="{{ $row_title($row) }}">
                        <span class="d-flex gap-1 align-items-baseline min-w-0">
                            <span class="fw-semibold flex-shrink-0">{{ $row['number'] }}</span>
                            <span class="desk-muted desk-nowrap desk-extprop-who">{{ $tall ? $row['name'] : ($row['customer'] !== '' ? $row['customer'] : $row['name']) }}</span>
                        </span>
                        @if($tall)
                            <span class="d-flex gap-1 align-items-baseline min-w-0 desk-muted fs-8">
                                {{-- число камер — своим элементом: в многоточие уходит только заказчик; на узком блоке камер нет (они в подсказке) --}}
                                @if($row['customer'] !== '')
                                    <span class="desk-nowrap">{{ $row['customer'] }}</span>
                                @endif
                                @if($row['cameras'])
                                    <span class="text-nowrap flex-shrink-0 desk-hide-narrow">{{ $row['customer'] !== '' ? '· ' : '' }}{{ $row['cameras'] }} кам.</span>
                                @endif
                                @if($row['date'])
                                    <span class="flex-shrink-0 desk-only-w-md">{{ $row['date'] }}</span>
                                @endif
                            </span>
                        @endif
                    </a>

                    @if($row['fresh'])
                        <i class="fa-light fa-sparkles text-primary flex-shrink-0 fs-8 desk-only-w-md" title="Новое: {{ $row['date'] }}"></i>
                    @endif

                    @if($row['cameras'] && !$tall)
                        <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-lg" title="Камер в расчёте">{{ $row['cameras'] }} кам.</span>
                    @endif

                    @if($settings['show_license'] && $row['license'] !== '')
                        <span class="badge badge-light-{{ $row['license_color'] }} fs-8 flex-shrink-0 desk-only-w-lg" title="Лицензии {{ $row['license'] }}">
                            {{ $row['currency'] !== '' ? $row['currency'] . ' · ' : '' }}{{ $row['license'] }}
                        </span>
                    @endif

                    @unless($tall)
                        <span class="desk-muted fs-8 text-nowrap flex-shrink-0 desk-only-w-md">{{ $row['date'] ?? '—' }}</span>
                    @endunless

                    @if($row['transferred'])
                        <a href="{{ $preview || empty($row['proposal_url']) ? 'javascript:void(0)' : $row['proposal_url'] }}"
                           class="badge badge-light-success text-decoration-none flex-shrink-0" title="Перенесено: открыть КП портала">
                            <i class="fa-light fa-check fs-8"></i>
                        </a>
                    @else
                        <a @if($box($row['transfer_url'])) href="javascript:box({href: '{{ $box($row['transfer_url']) }}'})" @else href="javascript:void(0)" @endif
                           class="badge badge-light-secondary text-decoration-none bg-hover-light-primary text-hover-primary flex-shrink-0"
                           title="Перенести в наше КП">
                            <i class="fa-light fa-right-to-bracket fs-8"></i>
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
