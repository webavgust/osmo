{{--
    Админ-панель: константы портала (patch v28, этап C).
    Констант немного, поэтому таблица серверная, без пагинации.
--}}
@extends('layouts.layout')

@section('styles')
    @parent
    <style>
        #admin_consts_table .admin-const-value { max-width: 360px; }
        #admin_consts_table .admin-const-note { max-width: 420px; }
    </style>
@endsection

@section('breadcrumb_right')
    <button type="button" class="btn btn-primary"
            onclick="javascript:box({href: '{{ route('admin.consts.box_form') }}'});">
        <i class="fa-light fa-plus fs-5 me-2"></i>
        Новая константа
    </button>
@endsection

@section('content')
    @include('admin.partials.nav')

    <div class="card">
        <div class="card-header pt-4 pb-3 min-h-auto">
            <div class="card-title m-0 d-flex align-items-center gap-3">
                <h3 class="m-0">Константы</h3>
                <span class="fs-7 text-muted">
                    @if($params['q'] !== '')
                        {{ $consts->count() }} из {{ $total }}
                    @else
                        {{ $total }}
                    @endif
                </span>
            </div>

            <div class="card-toolbar m-0">
                <form method="get" action="{{ route('admin.consts.index') }}" class="d-flex flex-wrap align-items-center gap-3">
                    <div class="position-relative">
                        <i class="fa-light fa-magnifying-glass position-absolute top-50 translate-middle-y ms-3 text-gray-500"></i>
                        <input type="search" name="q" value="{{ $params['q'] }}"
                               class="form-control form-control-sm w-250px ps-9"
                               placeholder="Код, название или примечание" autocomplete="off"/>
                    </div>

                    @if($params['q'] !== '')
                        <a href="{{ route('admin.consts.index') }}" class="text-dark-500 text-hover-dark fs-7">
                            <i class="fa-light fa-xmark me-1" aria-hidden="true"></i> Убрать
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle gs-4 mb-0" id="admin_consts_table">
                    <thead>
                        <tr class="fw-semibold fs-7 text-gray-600">
                            <th>Код</th>
                            <th>Название</th>
                            <th>Значение</th>
                            <th>Примечание</th>
                            <th class="text-center">Системная</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consts as $row)
                            @php
                                $row_value = (string) $row->value;
                                $row_json = \App\Modules\Admin\Consts\Services\AdminConstService::isJson($row_value);
                            @endphp
                            <tr>
                                <td class="text-nowrap">
                                    <span class="font-monospace fs-7 fw-semibold text-gray-900">{{ $row->key }}</span>
                                </td>
                                <td class="fw-semibold text-gray-800">{{ $row->name }}</td>
                                <td>
                                    @if(trim($row_value) === '')
                                        <span class="text-muted" title="Код берёт значение по умолчанию">—</span>
                                    @else
                                        <div class="admin-const-value d-flex align-items-start gap-2">
                                            @if($row_json)
                                                <span class="badge badge-light-info fs-9 flex-shrink-0">JSON</span>
                                            @endif
                                            <span class="font-monospace fs-7 text-gray-700 text-break" title="{{ $row_value }}">{{ \Illuminate\Support\Str::limit($row_value, 120) }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($row->note)
                                        <div class="admin-const-note fs-7 text-gray-700">{{ $row->note }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($row->system)
                                        <span class="badge badge-light-warning" title="Значение пишет код портала">системная</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-icon btn-light-primary" title="Редактировать"
                                            onclick="javascript:box({href: '{{ route('admin.consts.box_form', $row->id) }}'});">
                                        <i class="fa-light fa-pen"></i>
                                    </button>

                                    @unless($row->system)
                                        <button type="button" class="btn btn-sm btn-icon btn-light-danger ms-1" title="Удалить"
                                                data-url="{{ route('admin.api.consts.delete', $row->id) }}"
                                                data-code="{{ $row->key }}"
                                                onclick="javascript:admin_const_delete(this);">
                                            <i class="fa-light fa-trash-can"></i>
                                        </button>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-10">
                                    @if($params['q'] !== '')
                                        Ничего не нашли
                                    @else
                                        Констант пока нет
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer py-3 fs-7 text-muted">
            Значения читает код портала, изменения действуют сразу. Если значение пустое или константы нет,
            код работает со значением по умолчанию, зашитым в нём самом. Значение системной константы пишет код —
            здесь меняются только её название и примечание.
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        /** Удалить константу — с подтверждением */
        function admin_const_delete(el) {
            var code = $(el).data('code');

            if (!confirm('Удалить константу «' + code + '»?\n\n'
                + 'Код портала, который её читает, вернётся к значению по умолчанию, зашитому в коде. '
                + 'Вернуть удалённую константу можно, только заведя её заново.')) {
                return;
            }

            body_block();

            $.ajax({
                url: $(el).data('url'),
                type: 'POST',
                data: {_token: csrf_token()},
                dataType: 'json',
                success: function (response) {
                    body_unblock();

                    if (!response || response.result !== 'success') {
                        toastr.error((response && response.message) || 'Не получилось удалить константу', 'Это провал!', {progressBar: true, timeOut: 6000});
                        return;
                    }

                    toastr.success(response.message, 'Это успех!', {progressBar: true, timeOut: 3000});
                    setTimeout(function () { location.reload(); }, 600);
                },
                error: function (xhr) {
                    body_unblock();
                    toastr.error('Ошибка запроса (' + xhr.status + ')', 'Это провал!', {progressBar: true, timeOut: 6000});
                }
            });
        }
    </script>
@endsection
