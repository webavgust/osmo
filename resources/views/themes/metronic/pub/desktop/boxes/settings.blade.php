{{--
    Попап настроек виджета рабочего стола (patch v30, этап A4b).

    Поля — по схеме Widget::schema(): группы «Данные» (data) и «Оформление» (view).
    select2 и поиск объектов включает desk_settings_init(), значения собирает и проверяет
    desk_settings_apply() — обе в public/metronic/js/osmo-desktop-settings.js.
    При $error вместо формы — плашка с текстом отказа.
--}}
@extends('components.box.box-static-large')

@section('body')
    @if(!empty($error))
        <div class="alert alert-danger d-flex align-items-center p-4 mb-0 fs-6">
            <i class="fa-light fa-lock fs-3 text-danger me-3"></i>
            <span>{{ $error }}</span>
        </div>
    @else
        <div class="d-flex align-items-start gap-3 mb-6 fs-7">
            <i class="fa-light {{ $meta['icon'] }} fs-2 text-primary mt-1"></i>
            <div>
                <div class="text-gray-800 fw-semibold">{{ $meta['name'] }} <span class="text-muted fw-normal ms-2">Размер: {{ $w }}×{{ $h }}</span></div>
                @if($meta['description'])
                    <div class="text-muted">{{ $meta['description'] }}</div>
                @endif
            </div>
        </div>

        <form id="desk_settings_form" data-uid="{{ $uid }}" onsubmit="return false;" autocomplete="off">
            @foreach($groups as $group => $fields)
                <div class="fw-bold text-gray-700 text-uppercase fs-7 {{ $loop->first ? '' : 'mt-8' }} mb-2">{{ $group === 'data' ? 'Данные' : 'Оформление' }}</div>
                <div class="separator separator-dashed mb-5"></div>

                <div class="row g-5">
                    @foreach($fields as $field)
                        @php
                            $key = $field['key'];
                            $type = $field['type'];
                            $value = $values[$key] ?? null;
                            $id = 'desk_set_' . $key;
                            $required = !empty($field['required']);
                        @endphp
                        <div class="{{ in_array($type, ['textarea', 'entity', 'list'], true) ? 'col-12' : 'col-md-6' }} desk-set-field"
                             data-key="{{ $key }}" data-type="{{ $type }}" data-label="{{ $field['label'] }}" @if($required) data-required="1" @endif>
                            @if($type === 'bool')
                                <label class="form-check form-switch form-check-custom form-check-solid pt-md-9">
                                    <input class="form-check-input" type="checkbox" name="{{ $key }}" id="{{ $id }}" value="1" @checked($value)/>
                                    <span class="form-check-label fw-semibold text-gray-800">{{ $field['label'] }}</span>
                                </label>
                            @else
                                <label class="form-label fw-semibold" for="{{ $id }}">
                                    {{ $field['label'] }} @if($required)<span class="text-danger">*</span>@endif
                                </label>

                                @if($type === 'textarea')
                                    <textarea name="{{ $key }}" id="{{ $id }}" class="form-control" rows="4"
                                              @isset($field['max']) maxlength="{{ $field['max'] }}" @endisset>{{ $value }}</textarea>
                                @elseif($type === 'number')
                                    <input type="number" name="{{ $key }}" id="{{ $id }}" class="form-control" value="{{ $value }}"
                                           @isset($field['min']) min="{{ $field['min'] }}" @endisset @isset($field['max']) max="{{ $field['max'] }}" @endisset/>
                                @elseif(in_array($type, ['select', 'currency', 'period'], true))
                                    @php $list = $options[$key] ?? []; @endphp
                                    <select name="{{ $key }}" id="{{ $id }}" class="form-select {{ count($list) > 8 ? 'desk-set-select2' : '' }}">
                                        @foreach($list as $option_value => $option_label)
                                            <option value="{{ $option_value }}" @selected((string) $option_value === (string) $value)>{{ $option_label }}</option>
                                        @endforeach
                                    </select>
                                @elseif($type === 'entity')
                                    @php
                                        $allowed = array_intersect_key($entities, array_flip((array) ($field['entities'] ?? [])));
                                        $current_type = is_array($value) && isset($allowed[$value['type'] ?? '']) ? $value['type'] : array_key_first($allowed);
                                        $current_id = is_array($value) ? trim((string) ($value['id'] ?? '')) : '';
                                    @endphp
                                    <div class="d-flex gap-3">
                                        <select name="{{ $key }}__type" class="form-select w-175px flex-shrink-0" data-desk-entity-type>
                                            @foreach($allowed as $entity => $entity_label)
                                                <option value="{{ $entity }}" @selected($entity === $current_type)>{{ $entity_label }}</option>
                                            @endforeach
                                        </select>
                                        <div class="flex-grow-1 min-w-0">
                                            <select name="{{ $key }}" id="{{ $id }}" class="form-select" data-desk-entity>
                                                @if($current_id !== '')
                                                    <option value="{{ $current_id }}" selected>{{ $captions[$key] ?? ('Не найден: ' . $current_id) }}</option>
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                @elseif($type === 'list')
                                    @php
                                        $list = $options[$key] ?? [];
                                        $chosen = array_map('strval', array_filter((array) $value, 'is_scalar'));
                                    @endphp
                                    <select name="{{ $key }}" id="{{ $id }}" class="form-select" multiple data-desk-list data-tags="{{ $list ? 0 : 1 }}">
                                        @foreach($list as $option_value => $option_label)
                                            <option value="{{ $option_value }}" @selected(in_array((string) $option_value, $chosen, true))>{{ $option_label }}</option>
                                        @endforeach
                                        @if(!$list)
                                            @foreach($chosen as $item)
                                                <option value="{{ $item }}" selected>{{ $item }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                @else
                                    <input type="text" name="{{ $key }}" id="{{ $id }}" class="form-control" value="{{ $value }}"
                                           @isset($field['max']) maxlength="{{ $field['max'] }}" @endisset/>
                                @endif
                            @endif

                            @if(!empty($field['hint']))
                                <div class="form-text {{ $type === 'bool' ? 'ms-14' : '' }}">{{ $field['hint'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </form>

        <script>
            if (typeof window.desk_settings_init === 'function') window.desk_settings_init();
        </script>
    @endif
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <button type="button" class="btn btn-light" onclick="javascript:box_close();">Закрыть</button>

        @if(empty($error))
            <button type="button" class="btn btn-primary" id="desk_settings_apply" data-uid="{{ $uid }}"
                    onclick="javascript:desk_settings_apply(this.getAttribute('data-uid'));">
                <i class="fa-light fa-check me-2"></i>Применить
            </button>
        @endif
    </div>
@endsection
