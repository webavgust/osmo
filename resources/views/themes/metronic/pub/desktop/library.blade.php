{{--
    Библиотека виджетов рабочего стола (patch v30) — фрагмент панели.

    Разметка — контракт для public/metronic/js/osmo-desktop-library.js:
    .desk-lib-search — поиск по data-search карточек; .desk-lib-tab[data-cat] — вкладки
    («all» — все); .desk-lib-cat[data-cat] — секции категорий; .desk-lib-item — карточка,
    её перетаскивают на сетку (обработчик dropped в osmo-desktop.js читает data-widget,
    gs-w, gs-h); .desk-lib-add[data-widget] — добавить в размере по умолчанию;
    .desk-lib-size[data-widget][data-size] — добавить в выбранном размере;
    .desk-lib-preview — превью (ячейка задана --desk-cell), .desk-lib-film — плёнка поверх
    превью; .desk-lib-empty — «ничего не найдено»; .desk-lib-close — закрыть;
    .desk-lib-grip — верхний край панели, за него меняют высоту.
--}}
<div class="desk-lib" id="desk_lib">
    <div class="desk-lib-grip" title="Потяните, чтобы изменить высоту"></div>

    <div class="desk-lib-head">
        <div class="desk-lib-title">
            <i class="fa-light fa-grid-2-plus me-2 text-primary"></i>Библиотека виджетов
        </div>

        <div class="desk-lib-search-wrap">
            <i class="fa-light fa-magnifying-glass position-absolute top-50 translate-middle-y ms-3 text-gray-500"></i>
            <input type="search" class="form-control form-control-sm form-control-solid ps-9 desk-lib-search"
                   placeholder="Найти виджет" autocomplete="off"/>
        </div>

        <div class="desk-lib-tabs">
            <button type="button" class="desk-lib-tab active" data-cat="all">
                Все <span class="desk-lib-count">{{ $total }}</span>
            </button>
            @foreach($categories as $key => $category)
                <button type="button" class="desk-lib-tab" data-cat="{{ $key }}">
                    <i class="fa-light {{ $category['icon'] }}"></i>{{ $category['name'] }}
                    <span class="desk-lib-count">{{ count($category['widgets']) }}</span>
                </button>
            @endforeach
        </div>

        <button type="button" class="btn btn-icon btn-sm btn-light desk-lib-close" title="Закрыть (Esc)">
            <i class="fa-light fa-xmark fs-3"></i>
        </button>
    </div>

    <div class="desk-lib-body">
        @foreach($categories as $key => $category)
            <section class="desk-lib-cat" data-cat="{{ $key }}">
                <div class="desk-lib-cat-head">
                    <div class="desk-lib-cat-name">
                        <i class="fa-light {{ $category['icon'] }} me-2"></i>{{ $category['name'] }}
                    </div>
                    @if(!empty($category['description']))
                        <div class="desk-lib-cat-desc">{{ $category['description'] }}</div>
                    @endif
                </div>

                @foreach($category['widgets'] as $meta)
                    @php
                        $preview = $previews[$meta['id']];
                        $search = mb_strtolower($meta['name'] . ' ' . $meta['description'] . ' ' . $category['name'] . ' ' . $meta['id']);
                    @endphp
                    <div class="desk-lib-item" data-widget="{{ $meta['id'] }}" data-cat="{{ $key }}"
                         data-search="{{ $search }}" gs-w="{{ $preview['w'] }}" gs-h="{{ $preview['h'] }}">
                        <div class="desk-lib-preview-wrap">
                            <div class="desk-lib-preview"
                                 style="--desk-cell: {{ $preview['cell'] }}px; width: {{ $preview['w'] * $preview['cell'] }}px; height: {{ $preview['h'] * $preview['cell'] }}px;">
                                {!! $preview['html'] !!}
                            </div>
                            <div class="desk-lib-film" title="Перетащите на стол или нажмите «Добавить»"></div>
                        </div>

                        <div class="desk-lib-info">
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div class="min-w-0">
                                    <div class="desk-lib-name">
                                        <i class="fa-light {{ $meta['icon'] }} me-2 text-gray-500"></i>{{ $meta['name'] }}
                                    </div>
                                    @if($meta['description'] !== '')
                                        <div class="desk-lib-desc">{{ $meta['description'] }}</div>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-sm btn-light-primary desk-lib-add flex-shrink-0"
                                        data-widget="{{ $meta['id'] }}">
                                    <i class="fa-light fa-plus"></i> Добавить
                                </button>
                            </div>

                            <div class="desk-lib-sizes">
                                @foreach($meta['sizes'] as $size)
                                    <button type="button" data-widget="{{ $meta['id'] }}" data-size="{{ $size }}"
                                            title="Добавить размером {{ str_replace('x', '×', $size) }}"
                                            @class(['badge desk-lib-size', 'badge-light-primary' => $size === $meta['default_size'], 'badge-light' => $size !== $meta['default_size']])>
                                        {{ str_replace('x', '×', $size) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </section>
        @endforeach

        <div class="desk-lib-empty d-none">
            <i class="fa-light fa-magnifying-glass fs-2x text-gray-400 mb-3"></i>
            <div>Ничего не найдено</div>
        </div>
    </div>
</div>
