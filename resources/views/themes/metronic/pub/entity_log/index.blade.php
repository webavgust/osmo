@extends('layouts.layout')

{{-- Журнал изменений сущностей (patch v29): лента событий объекта по дням --}}

@section('breadcrumb_right')
    <a href="{{ $root->logUrl() }}" class="btn btn-light">
        <i class="fa-light fa-arrow-left fs-5 me-2"></i>К объекту
    </a>

    <button type="button" id="entity_log_filter_btn" class="btn btn-light-info fw-bold d-flex align-items-center">
        <i class="fa-light fa-filter"></i>
        Фильтр
        @if($rules_count)
            <span class="count filter-count">{{ $rules_count }}</span>
        @endif
    </button>

    @if($rules_count)
        <a href="{{ route('entity_log.index', [$type, $key]) }}" class="me-2 text-dark-500 text-hover-dark">
            <i class="fa-light fa-xmark fs-5 me-2" aria-hidden="true"></i> Убрать
        </a>
    @endif
@endsection

@section('content')
    <div class="container-fluid">

        {{-- Отбор: живёт в модалке, в адресе остаётся обычной GET-строкой --}}
        <div id="entity_log_filter_modal" class="modal fade" tabindex="-1" aria-hidden="true">
            <form method="get" action="{{ route('entity_log.index', [$type, $key]) }}">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title fw-bold">Фильтр</h3>
                            <button type="button" class="btn btn-icon btn-sm btn-active-light-primary"
                                    data-bs-dismiss="modal" aria-label="Закрыть">
                                <i class="fa-light fa-xmark fs-2"></i>
                            </button>
                        </div>

                        <div class="modal-body py-8">
                            <div class="row mb-5">
                                <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Поле</label>
                                <div class="col-sm-9">
                                    <select name="field" class="form-select entity_log_select" data-control="select2"
                                            data-dropdown-parent="#entity_log_filter_modal" data-placeholder="Все поля">
                                        <option value="">Все поля</option>
                                        @foreach($field_options as $group)
                                            <optgroup label="{{ $group['group'] }}">
                                                @foreach($group['options'] as $option)
                                                    <option value="{{ $option['value'] }}" @selected(($filter['field'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-5">
                                <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Пользователь</label>
                                <div class="col-sm-9">
                                    <select name="user" class="form-select entity_log_select" data-control="select2"
                                            data-dropdown-parent="#entity_log_filter_modal" data-placeholder="Все пользователи">
                                        <option value="">Все пользователи</option>
                                        @foreach($user_options as $user)
                                            <option value="{{ $user['id'] }}" @selected((string) ($filter['user'] ?? '') === (string) $user['id'])>{{ $user['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Событие</label>
                                <div class="col-sm-9">
                                    <select name="event" class="form-select entity_log_select" data-control="select2"
                                            data-dropdown-parent="#entity_log_filter_modal" data-placeholder="Все события" data-hide-search="true">
                                        <option value="">Все события</option>
                                        @foreach($events as $event => $event_label)
                                            <option value="{{ $event }}" @selected(($filter['event'] ?? '') === $event)>{{ $event_label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Закрыть</button>
                            <button type="submit" class="btn btn-primary">Применить</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-body">
                @if($logs->isEmpty())
                    <div class="text-muted fs-6 py-5 text-center">
                        {{ $rules_count ? 'По этому фильтру ничего не найдено' : 'Изменений пока нет' }}
                    </div>
                @else
                    @php
                        // цвет и иконка события в таймлайне
                        $styles = [
                            'created' => ['success', 'fa-plus'],
                            'updated' => ['primary', 'fa-pen'],
                            'deleted' => ['danger', 'fa-trash-can'],
                            'baseline' => ['secondary', 'fa-camera'],
                        ];
                        // длинные значения обрезаются, полное — в подсказке
                        $cell = function ($value) {
                            $value = (string) $value;

                            return mb_strlen($value) > 120
                                ? '<span title="' . e($value) . '">' . e(\Illuminate\Support\Str::limit($value, 120)) . '</span>'
                                : e($value);
                        };
                    @endphp

                    @foreach($days as $day)
                        <div class="d-flex align-items-center justify-content-between mb-5 @if(!$loop->first) mt-5 @endif">
                            <div class="fs-4 fw-bold">{{ $day['label'] }}</div>
                            @if($day['state_url'])
                                <a class="badge badge-light-warning" href="{{ $day['state_url'] }}">
                                    <i class="fa-light fa-clock-rotate-left me-1"></i>Состояние на конец дня
                                </a>
                            @endif
                        </div>

                        <div class="timeline timeline-border-dashed">
                            @foreach($day['logs'] as $log)
                                @php [$color, $icon] = $styles[$log->event] ?? ['secondary', 'fa-circle-info']; @endphp
                                <div class="timeline-item">
                                    <div class="timeline-line w-40px"></div>
                                    <div class="timeline-icon symbol symbol-circle symbol-40px">
                                        <div class="symbol-label bg-light-{{ $color }}">
                                            <i class="fa-light {{ $icon }} text-{{ $color }}"></i>
                                        </div>
                                    </div>
                                    <div class="timeline-content mb-10 mt-n1">
                                        <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                                            <span class="fw-bold">{{ $log->created_at->format('H:i') }}</span>
                                            @if($log->user)
                                                <div class="symbol symbol-circle symbol-25px">
                                                    <img src="{{ asset($log->user->avatar()) }}" alt=""/>
                                                </div>
                                                <span class="fw-semibold">{{ $log->user->full_name }}</span>
                                            @else
                                                <span class="fw-semibold text-muted">система</span>
                                            @endif
                                            <span class="badge badge-light-{{ $color }}">{{ $log->event_label }}</span>
                                            <span class="text-muted fs-7">{{ $log->title }}</span>
                                            <span class="fs-7 text-muted ms-auto">{{ $log->changes_count }} изм.</span>
                                        </div>

                                        @if($log->changes->isEmpty())
                                            @if($log->event === 'baseline')
                                                <div class="text-muted fs-7">Начальный слепок</div>
                                            @elseif($log->event === 'deleted')
                                                <div class="text-muted fs-7">Объект удалён</div>
                                            @endif
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-row-bordered align-middle fs-7 gs-4 mb-0">
                                                    <thead>
                                                        <tr class="fw-bold text-muted">
                                                            <th>Объект</th>
                                                            <th>Поле</th>
                                                            <th>Было</th>
                                                            <th>Стало</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php
                                                            // основные изменения (правка пользователя) сверху жирным, косвенные
                                                            // (итоги и суммы, пересчитанные порталом) — под ними со сдвигом и курсивом;
                                                            // если в событии только косвенные — показываем их как обычные
                                                            $primary = $log->changes->reject(fn($change) => $change->derived)->values();
                                                            $derived = $log->changes->filter(fn($change) => $change->derived)->values();
                                                            $has_primary = $primary->isNotEmpty();
                                                        @endphp
                                                        @foreach($primary->concat($derived) as $change)
                                                            @php $secondary = $has_primary && $change->derived; @endphp
                                                            @if($change->kind === 'changed')
                                                                <tr @class(['fst-italic text-gray-600' => $secondary, 'fw-bold' => $has_primary && !$secondary])>
                                                                    <td @class(['ps-12' => $secondary])>{{ $change->path ?: '—' }}</td>
                                                                    <td>{{ $change->label }}</td>
                                                                    <td>{!! $cell($change->old_label) !!}</td>
                                                                    <td>{!! $cell($change->new_label) !!}</td>
                                                                </tr>
                                                            @else
                                                                <tr>
                                                                    <td colspan="4">
                                                                        {{ $change->path }}
                                                                        @if($change->kind === 'added')
                                                                            <span class="badge badge-light-success ms-2">добавлено</span>
                                                                        @else
                                                                            <span class="badge badge-light-danger ms-2">удалено</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            @if($log->changes->count() < $log->changes_count)
                                                <div class="text-muted fs-8 mt-2">показаны {{ $log->changes->count() }} из {{ $log->changes_count }} изменений</div>
                                            @endif
                                        @endif

                                        @if(!empty($state_urls[$log->id]))
                                            <div class="mt-3">
                                                <a href="{{ $state_urls[$log->id] }}" class="fs-7">
                                                    <i class="fa-light fa-clock-rotate-left me-1"></i>Открыть состояние на этот момент
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif

                {{-- Пагинация по 30, разметка Metronic (withQueryString — в сервисе) --}}
                @if($logs->hasPages())
                    @php
                        $page = $logs->currentPage();
                        $pages = $logs->getUrlRange(max(1, $page - 3), min($logs->lastPage(), $page + 3));
                    @endphp
                    <div class="d-flex flex-stack flex-wrap gap-3 pt-4">
                        <div class="fs-7 text-muted">
                            События {{ $logs->firstItem() }}–{{ $logs->lastItem() }} из {{ $logs->total() }}
                        </div>

                        <ul class="pagination">
                            <li class="page-item previous @if($logs->onFirstPage()) disabled @endif">
                                <a href="{{ $logs->previousPageUrl() ?? 'javascript:void(0);' }}" class="page-link"><i class="previous"></i></a>
                            </li>

                            @foreach($pages as $number => $url)
                                <li class="page-item @if($number === $page) active @endif">
                                    <a href="{{ $url }}" class="page-link">{{ $number }}</a>
                                </li>
                            @endforeach

                            <li class="page-item next @unless($logs->hasMorePages()) disabled @endunless">
                                <a href="{{ $logs->nextPageUrl() ?? 'javascript:void(0);' }}" class="page-link"><i class="next"></i></a>
                            </li>
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            // модалка живёт в контенте — уводим в body, чтобы её не обрезал контекст наложения
            var $modal = $('#entity_log_filter_modal').appendTo('body');

            // select2 внутри модалки — с dropdownParent, иначе список уезжает под затемнение
            $modal.find('select.entity_log_select').each(function () {
                $(this).select2({
                    width: '100%',
                    dropdownParent: $modal,
                    placeholder: $(this).data('placeholder'),
                    minimumResultsForSearch: $(this).data('hide-search') ? Infinity : 0
                });
            });

            $('#entity_log_filter_btn').on('click', function () {
                $modal.modal('show');
            });
        });
    </script>
@endsection
