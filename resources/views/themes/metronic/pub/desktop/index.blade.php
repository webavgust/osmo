{{--
    Рабочий стол (patch v30): сетка виджетов GridStack.

    Просмотр / редактирование переключает Desk.edit(): класс desk-edit-mode на body
    (кнопки тулбара .desk-view-only / .desk-edit-only) и desk-editing на #desk_root.
    Скрипт — public/metronic/js/osmo-desktop.js, стили сетки — osmo-desktop-grid.css,
    стили виджетов — osmo-desktop.css.
--}}
@extends('layouts.layout')

@php
    $gridstack = 'https://cdn.jsdelivr.net/npm/gridstack@' . $config['gridstack'] . '/dist/';

    $desk_init = [
        'desktop' => $summary,
        'items' => $items,
        'meta' => (object) $meta,
        'context' => $context,
        'config' => $config,
        'urls' => [
            'render' => route('api.desktop.render', $desktop),
            'save' => route('api.desktop.save', $desktop),
            'context' => route('api.desktop.context', $desktop),
            'store' => route('api.desktop.store'),
            'copy' => route('api.desktop.copy', $desktop),
            'update' => route('api.desktop.update', $desktop),
            'delete' => route('api.desktop.delete', $desktop),
            'default' => route('api.desktop.default', $desktop),
            'apply_source' => route('api.desktop.apply_source', $desktop),
            'render_batch' => route('api.desktop.render_batch', $desktop),
            'settings_form' => route('api.desktop.settings_form', $desktop),
            'search' => route('api.desktop.search'),
            'library' => route('desktop.library', $desktop),
            'box_desktop' => route('desktop.box_desktop', $desktop),
        ],
    ];
@endphp

@section('styles')
    @parent
    <link href="{{ $gridstack }}gridstack.min.css" rel="stylesheet" type="text/css"/>
    <link href="/metronic/css/osmo-desktop.css" rel="stylesheet" type="text/css"/>
    <link href="/metronic/css/osmo-desktop-grid.css" rel="stylesheet" type="text/css"/>
    <link href="/metronic/css/osmo-desktop-columns.css" rel="stylesheet" type="text/css"/>
    <link href="/metronic/css/osmo-desktop-library.css" rel="stylesheet" type="text/css"/>
    {{-- стили отдельных виджетов: по файлу на группу (до сборки патча, затем один файл) --}}
    @foreach(glob(public_path('metronic/css/osmo-desktop-widgets/*.css')) ?: [] as $widget_css)
        <link href="/metronic/css/osmo-desktop-widgets/{{ basename($widget_css) }}?v={{ filemtime($widget_css) }}" rel="stylesheet" type="text/css"/>
    @endforeach
@endsection

@section('breadcrumb_right')
    <div class="d-flex flex-wrap align-items-center gap-2" id="desk_toolbar">
        {{-- Просмотр: переключатель столов --}}
        <div class="dropdown desk-view-only">
            <button type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-light fa-grid-2 fs-5 me-2"></i>
                {{ $summary['name'] }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end desk-switch-menu">
                <li><h6 class="dropdown-header">Мои столы</h6></li>
                @forelse($desktops as $item)
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('desktop.index', $item) }}">
                            <i class="fa-light fa-star w-15px {{ $item->is_default ? 'text-warning' : 'invisible' }}"></i>
                            <span class="flex-grow-1 text-truncate">{{ $item->name }}</span>
                            @if((int) $item->id === (int) $desktop->id)
                                <i class="fa-light fa-check text-primary"></i>
                            @endif
                        </a>
                    </li>
                @empty
                    <li><span class="dropdown-item-text text-muted fs-7">Личных столов нет</span></li>
                @endforelse

                @if($systems->isNotEmpty())
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header">Системные пресеты</h6></li>
                    @foreach($systems as $item)
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('desktop.index', $item) }}">
                                <i class="fa-light fa-shield w-15px text-gray-500"></i>
                                <span class="flex-grow-1 text-truncate">{{ $item->name }}</span>
                                @if((int) $item->id === (int) $desktop->id)
                                    <i class="fa-light fa-check text-primary"></i>
                                @endif
                            </a>
                        </li>
                    @endforeach
                @endif

                {{-- Действия со столами: Desk.desks из osmo-desktop-desks.js --}}
                <li><hr class="dropdown-divider"></li>
                <li>
                    <button type="button" class="dropdown-item d-flex align-items-center gap-2" onclick="Desk.desks.create();">
                        <i class="fa-light fa-plus w-15px"></i>
                        <span>Новый стол</span>
                    </button>
                </li>
                <li>
                    <button type="button" class="dropdown-item d-flex align-items-center gap-2" onclick="Desk.desks.copy();">
                        <i class="fa-light fa-clone w-15px"></i>
                        <span>Копировать этот стол</span>
                    </button>
                </li>
                @if($summary['can_edit'])
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2" onclick="Desk.desks.rename();">
                            <i class="fa-light fa-pen w-15px"></i>
                            <span>Переименовать</span>
                        </button>
                    </li>
                @endif
                {{-- личный — владелец, системный — админ (can_edit); основной уже основной --}}
                @if($summary['can_edit'] && !$summary['is_default'])
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2" onclick="Desk.desks.makeDefault();">
                            <i class="fa-light fa-star w-15px"></i>
                            <span>Сделать основным</span>
                        </button>
                    </li>
                @endif
                @if(auth()->user()->isPanelAdmin())
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2" onclick="Desk.desks.saveAsSystem();">
                            <i class="fa-light fa-shield w-15px"></i>
                            <span>Сохранить как системный пресет</span>
                        </button>
                    </li>
                @endif
                @if($summary['can_edit'])
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger" onclick="Desk.desks.remove();">
                            <i class="fa-light fa-trash-can w-15px text-danger"></i>
                            <span>Удалить стол</span>
                        </button>
                    </li>
                @endif
            </ul>
        </div>

        @if($summary['can_edit'])
            <button type="button" class="btn btn-light-primary desk-view-only" onclick="Desk.edit(true);">
                <i class="fa-light fa-pen fs-5 me-2"></i>
                Редактировать
            </button>

            {{-- Редактирование --}}
            <button type="button" class="btn btn-light-info desk-edit-only" onclick="Desk.openLibrary();">
                <i class="fa-light fa-grid-2-plus fs-5 me-2"></i>
                Библиотека
            </button>
            <button type="button" class="btn btn-light desk-edit-only" onclick="Desk.compact();" title="Поднять блоки вверх, убрать пустые места">
                <i class="fa-light fa-arrow-up-to-line fs-5 me-2"></i>
                Уплотнить
            </button>
            <button type="button" class="btn btn-light desk-edit-only" onclick="Desk.cancel();">
                Отмена
            </button>
            <button type="button" class="btn btn-primary desk-edit-only" id="desk_save" onclick="Desk.save();" disabled>
                <i class="fa-light fa-floppy-disk fs-5 me-2"></i>
                Сохранить
            </button>
        @endif
    </div>
@endsection

@section('content')
    <div id="desk_root" class="desk-root">
        @if($summary['is_system'])
            <div class="notice d-flex align-items-center gap-3 bg-light-warning rounded border-warning border border-dashed px-5 py-3 mb-5 desk-notice">
                <i class="fa-light fa-shield fs-2 text-warning"></i>
                <div class="flex-grow-1 fs-6 text-gray-800">
                    @if($summary['can_edit'])
                        <span class="fw-bold">Системный пресет:</span> изменения увидят все, у кого стол не менялся
                    @else
                        <span class="fw-bold">Системный пресет</span> — только просмотр.
                    @endif
                </div>
                @unless($summary['can_edit'])
                    <button type="button" class="btn btn-sm btn-warning" onclick="Desk.copyDesktop();">
                        <i class="fa-light fa-copy me-2"></i>
                        Сделать своим
                    </button>
                @endunless
            </div>
        @endif

        @if($summary['update_available'])
            <div class="notice d-flex flex-wrap align-items-center gap-3 bg-light-primary rounded border-primary border border-dashed px-5 py-3 mb-5 desk-notice" id="desk_source_notice">
                <i class="fa-light fa-arrows-rotate fs-2 text-primary"></i>
                <div class="flex-grow-1 fs-6 text-gray-800">
                    Системный пресет «<span class="fw-bold">{{ $desktop->source?->name }}</span>», из которого создан этот стол, обновился
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="Desk.desks.applySource();">
                    <i class="fa-light fa-arrows-rotate me-2"></i>
                    Применить обновление
                </button>
                <button type="button" class="btn btn-sm btn-light" onclick="Desk.desks.dismissSource();">
                    Оставить как есть
                </button>
            </div>
        @endif

        <div class="grid-stack" id="desk_grid"></div>

        <div class="desk-empty-state" id="desk_empty" @if(count($items)) hidden @endif>
            <div class="desk-empty-state-icon"><i class="fa-light fa-grid-2-plus"></i></div>
            <div class="fs-3 fw-bold text-gray-800 mb-2">Стол пуст</div>
            @if($summary['can_edit'])
                <div class="fs-6 text-gray-600 mb-6">Нажмите «Редактировать» и добавьте виджеты из библиотеки</div>
                <button type="button" class="btn btn-light-info" onclick="if (Desk.edit(true)) Desk.openLibrary();">
                    <i class="fa-light fa-grid-2-plus fs-5 me-2"></i>
                    Открыть библиотеку
                </button>
            @else
                <div class="fs-6 text-gray-600">На этом столе пока нет виджетов</div>
            @endif
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script src="{{ $gridstack }}gridstack-all.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
    <script src="/metronic/js/osmo-desktop-charts.js"></script>
    <script src="/metronic/js/osmo-desktop-fit.js"></script>
    <script src="/metronic/js/osmo-desktop.js"></script>
    <script src="/metronic/js/osmo-desktop-library.js"></script>
    <script src="/metronic/js/osmo-desktop-settings.js"></script>
    <script src="/metronic/js/osmo-desktop-desks.js"></script>
    <script>
        $(function () {
            Desk.init(@json($desk_init, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));
        });
    </script>
@endsection
