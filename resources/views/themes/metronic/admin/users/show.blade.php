{{--
    Админ-панель: карточка пользователя (patch v28, этап B).
    Открывается и для мягко удалённого — с пометкой и кнопкой «Восстановить».
--}}
@extends('layouts.layout')

@php
    $user_name = $user->full_name ?: $user->login;
    $trashed = $user->trashed();
@endphp

@section('breadcrumb_right')
    <a href="{{ route('admin.users.index') }}" class="btn btn-light">
        <i class="fa-light fa-arrow-left fs-5 me-2"></i>
        К списку
    </a>

    @if($trashed)
        <button type="button" class="btn btn-success"
                data-url="{{ route('admin.api.users.restore', $user->id) }}"
                onclick="javascript:admin_user_restore(this);">
            <i class="fa-light fa-trash-arrow-up fs-5 me-2"></i>
            Восстановить
        </button>
    @else
        @unless($is_self)
            <button type="button" class="btn btn-light-danger"
                    data-url="{{ route('admin.api.users.delete', $user->id) }}"
                    data-name="{{ $user_name }}"
                    onclick="javascript:admin_user_delete(this);">
                <i class="fa-light fa-trash-can fs-5 me-2"></i>
                Удалить
            </button>
        @endunless

        <button type="button" class="btn btn-primary"
                onclick="javascript:box({href: '{{ route('admin.users.box_form', $user->id) }}'});">
            <i class="fa-light fa-pen fs-5 me-2"></i>
            Редактировать
        </button>
    @endif
@endsection

@section('content')
    @include('admin.partials.nav')

    @if($trashed)
        <div class="alert alert-danger d-flex align-items-center p-5 mb-6">
            <i class="fa-light fa-trash-can fs-2 text-danger me-4"></i>
            <div>
                <div class="fw-bold">Пользователь удалён {{ $user->deleted_at?->format('d.m.Y H:i') }}</div>
                <div class="fs-7">Войти он не может. Данные, права и журнал сохранены — «Восстановить» вернёт всё как было.</div>
            </div>
        </div>
    @endif

    {{-- Шапка-карточка --}}
    <div class="card mb-6">
        <div class="card-body pt-8 pb-6">
            <div class="d-flex flex-wrap flex-sm-nowrap gap-6">
                <div class="symbol symbol-100px symbol-circle flex-shrink-0">
                    <img src="{{ asset($user->avatar()) }}" alt="{{ $user_name }}"/>
                </div>

                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="text-gray-900 fs-2 fw-bold me-2">{{ $user_name }}</span>

                        @if($user->active)
                            <span class="badge badge-light-success">активен</span>
                        @else
                            <span class="badge badge-light-danger">отключён</span>
                        @endif

                        @if($user->is_admin)
                            <span class="badge badge-light-warning">админ-панель</span>
                        @endif

                        <span id="admin_user_theme_badge"
                              class="badge {{ $user->ui_theme_switch ? 'badge-light-primary' : 'badge-light-dark' }}">
                            {{ $user->ui_theme_switch ? 'переключает тему' : 'только Metronic' }}
                        </span>

                        {{-- patch v29: доступ к журналу изменений; скрытый бейдж показывает JS при включении --}}
                        <span id="admin_user_log_badge"
                              class="badge badge-light-info {{ $user->is_admin || $user->log_view ? '' : 'd-none' }}">
                            {{ $user->is_admin ? 'журнал изменений (админ)' : 'журнал изменений' }}
                        </span>

                        @if($user->is_hidden)
                            <span class="badge badge-light" title="Скрыт из выпадающих списков портала">скрыт</span>
                        @endif
                        @if($is_self)
                            <span class="badge badge-light-info">это вы</span>
                        @endif
                        @if($trashed)
                            <span class="badge badge-light-danger">удалён {{ $user->deleted_at?->format('d.m.Y') }}</span>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap fw-semibold fs-6 text-gray-600 gap-5 mb-5">
                        <span><i class="fa-light fa-user me-1"></i> {{ $user->login }}</span>
                        @if(trim((string) $user->email) !== '')
                            <span><i class="fa-light fa-envelope me-1"></i> {{ $user->email }}</span>
                        @endif
                        @if($user->work_position)
                            <span><i class="fa-light fa-briefcase me-1"></i> {{ $user->work_position }}</span>
                        @endif
                        @if($user->work_department)
                            <span><i class="fa-light fa-sitemap me-1"></i> {{ $user->work_department }}</span>
                        @endif
                        @if($user->personal_mobile)
                            <span><i class="fa-light fa-phone me-1"></i> {{ $user->personal_mobile }}</span>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap gap-4">
                        <div class="border border-gray-300 border-dashed rounded min-w-150px py-3 px-4">
                            <div class="fs-5 fw-bold text-gray-800">{{ $user->created_at?->format('d.m.Y') ?? '—' }}</div>
                            <div class="fw-semibold fs-7 text-gray-500">Создан</div>
                        </div>
                        <div class="border border-gray-300 border-dashed rounded min-w-150px py-3 px-4">
                            <div class="fs-5 fw-bold text-gray-800" @if($user->last_hit_at) title="{{ $user->last_hit_at->diffForHumans() }}" @endif>
                                {{ $user->last_hit_at?->format('d.m.Y H:i') ?? '—' }}
                            </div>
                            <div class="fw-semibold fs-7 text-gray-500">Последний визит</div>
                        </div>
                        <div class="border border-gray-300 border-dashed rounded min-w-150px py-3 px-4">
                            <div class="fs-5 fw-bold text-gray-800">
                                {{ $last_login ? \Carbon\Carbon::parse($last_login)->format('d.m.Y H:i') : '—' }}
                            </div>
                            <div class="fw-semibold fs-7 text-gray-500">Последний вход</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-6 mb-6">
        {{-- Статистика: заглушка, владелец хочет видеть её позже --}}
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header min-h-auto py-4">
                    <h3 class="card-title m-0">Статистика</h3>
                </div>
                <div class="card-body d-flex align-items-center gap-4">
                    <i class="fa-light fa-chart-mixed fs-2x text-gray-400"></i>
                    <div class="text-muted">
                        Здесь появится статистика работы пользователя на портале.
                        Раздел в разработке.
                    </div>
                </div>
            </div>
        </div>

        {{-- Оформление: право переключаться на старую тему, сохраняется сразу --}}
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header min-h-auto py-4">
                    <h3 class="card-title m-0">Оформление</h3>
                </div>
                <div class="card-body">
                    <label class="form-check form-switch form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" id="admin_user_theme_switch"
                               data-url="{{ route('admin.api.users.theme', $user->id) }}"
                               @checked($user->ui_theme_switch)/>
                        <span class="form-check-label fw-semibold text-gray-800">Разрешить переключение на старую тему</span>
                    </label>
                    <div class="form-text mt-3">
                        Если выключить, пользователь всегда работает в Metronic: переключатель оформления
                        у него пропадает, а выбранная раньше старая тема больше не включится.
                    </div>

                    {{-- patch v29: доступ к журналу изменений сущностей --}}
                    <div class="separator separator-dashed my-5"></div>
                    <label class="form-check form-switch form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" id="admin_user_log_switch"
                               data-url="{{ route('admin.api.users.log_view', $user->id) }}"
                               @checked($user->is_admin || $user->log_view) @disabled($user->is_admin)/>
                        <span class="form-check-label fw-semibold text-gray-800">Видит журнал изменений</span>
                    </label>
                    <div class="form-text mt-3">
                        @if($user->is_admin)
                            Админ видит журнал всегда.
                        @else
                            Кнопка журнала на карточках КП, партнёра и компании и просмотр состояния на дату.
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Журнал авторизаций --}}
    <div class="card">
        <div class="card-header min-h-auto py-4">
            <h3 class="card-title m-0">Журнал авторизаций</h3>
            <div class="card-toolbar m-0 fs-7 text-muted">
                Всего {{ $attempts->total() }} · неудачные попытки ищутся по логину и email
            </div>
        </div>

        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle gs-4 mb-0">
                    <thead>
                        <tr class="fw-semibold fs-7 text-gray-600">
                            <th class="text-nowrap">Дата и время</th>
                            <th>Результат</th>
                            <th>Логин</th>
                            <th>IP</th>
                            <th>Браузер</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attempts as $row)
                            <tr>
                                <td class="text-nowrap">{{ \Carbon\Carbon::parse($row->attempted_at)->format('d.m.Y H:i:s') }}</td>
                                <td>
                                    @if($row->success)
                                        <span class="badge badge-light-success">вход</span>
                                    @else
                                        <span class="badge badge-light-danger">ошибка</span>
                                    @endif
                                </td>
                                <td>{{ $row->login }}</td>
                                <td class="text-nowrap">{{ $row->ip }}</td>
                                <td title="{{ $row->user_agent }}">
                                    {{ \App\Modules\Admin\Users\Services\AdminUserService::browser($row->user_agent) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-10">Попыток входа не было</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Пагинация по 50, разметка Metronic --}}
            @if($attempts->hasPages())
                @php
                    $page = $attempts->currentPage();
                    $pages = $attempts->getUrlRange(max(1, $page - 3), min($attempts->lastPage(), $page + 3));
                @endphp
                <div class="d-flex flex-stack flex-wrap gap-3 px-4 py-4">
                    <div class="fs-7 text-muted">
                        Записи {{ $attempts->firstItem() }}–{{ $attempts->lastItem() }} из {{ $attempts->total() }}
                    </div>

                    <ul class="pagination">
                        <li class="page-item previous @if($attempts->onFirstPage()) disabled @endif">
                            <a href="{{ $attempts->previousPageUrl() ?? 'javascript:void(0);' }}" class="page-link"><i class="previous"></i></a>
                        </li>

                        @foreach($pages as $number => $url)
                            <li class="page-item @if($number === $page) active @endif">
                                <a href="{{ $url }}" class="page-link">{{ $number }}</a>
                            </li>
                        @endforeach

                        <li class="page-item next @unless($attempts->hasMorePages()) disabled @endunless">
                            <a href="{{ $attempts->nextPageUrl() ?? 'javascript:void(0);' }}" class="page-link"><i class="next"></i></a>
                        </li>
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('js')
    @parent
    @include('admin.users.partials.js')

    <script>
        $(document).ready(function () {
            // «Оформление»: сохраняем сразу, при ошибке возвращаем переключатель назад
            $('#admin_user_theme_switch').on('change', function () {
                var $input = $(this);
                var value = $input.is(':checked');

                $input.prop('disabled', true);

                admin_user_request($input.data('url'), {value: value ? 1 : 0}, function () {
                    $input.prop('disabled', false);
                    $('#admin_user_theme_badge')
                        .toggleClass('badge-light-primary', value)
                        .toggleClass('badge-light-dark', !value)
                        .text(value ? 'переключает тему' : 'только Metronic');
                }, function () {
                    $input.prop('disabled', false).prop('checked', !value);
                });
            });

            // patch v29: «Видит журнал изменений» — сохраняем сразу, при ошибке возвращаем назад
            $('#admin_user_log_switch').on('change', function () {
                var $input = $(this);
                var value = $input.is(':checked');

                $input.prop('disabled', true);

                admin_user_request($input.data('url'), {value: value ? 1 : 0}, function () {
                    $input.prop('disabled', false);
                    $('#admin_user_log_badge').toggleClass('d-none', !value);
                }, function () {
                    $input.prop('disabled', false).prop('checked', !value);
                });
            });
        });
    </script>
@endsection
