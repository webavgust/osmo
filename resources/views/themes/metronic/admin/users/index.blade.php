{{--
    Админ-панель: список пользователей (patch v28, этап B).
    Пользователей мало, поэтому таблица серверная, без пагинации.
--}}
@extends('layouts.layout')

@section('styles')
    @parent
    <style>
        #admin_users_table tr.is-trashed td { background: var(--bs-gray-100); }
        #admin_users_table tr.is-inactive .admin-user-name { color: var(--bs-gray-600) !important; }
    </style>
@endsection

@section('breadcrumb_right')
    <button type="button" class="btn btn-primary"
            onclick="javascript:box({href: '{{ route('admin.users.box_form') }}'});">
        <i class="fa-light fa-user-plus fs-5 me-2"></i>
        Новый пользователь
    </button>
@endsection

@section('content')
    @include('admin.partials.nav')

    <div class="card">
        <div class="card-header pt-4 pb-3 min-h-auto">
            <div class="card-title m-0 d-flex align-items-center gap-3">
                <h3 class="m-0">Пользователи</h3>
                <span class="fs-7 text-muted">{{ $users->count() }}</span>
            </div>

            <div class="card-toolbar m-0">
                <form method="get" action="{{ route('admin.users.index') }}" class="d-flex flex-wrap align-items-center gap-3">
                    {{-- Отбор: все / активные / отключённые / удалённые --}}
                    <div class="btn-group">
                        @foreach($states as $key => $label)
                            <a href="{{ route('admin.users.index', array_filter(['state' => $key, 'q' => $params['q']])) }}"
                               class="btn btn-sm {{ $params['state'] === $key ? 'btn-primary' : 'btn-light' }}">
                                {{ $label }}
                                @if($key === 'trashed' && $trashed_count)
                                    <span class="badge badge-circle badge-light-danger ms-1">{{ $trashed_count }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    <input type="hidden" name="state" value="{{ $params['state'] }}"/>

                    <div class="position-relative">
                        <i class="fa-light fa-magnifying-glass position-absolute top-50 translate-middle-y ms-3 text-gray-500"></i>
                        <input type="search" name="q" value="{{ $params['q'] }}"
                               class="form-control form-control-sm w-250px ps-9"
                               placeholder="ФИО, логин или email" autocomplete="off"/>
                    </div>

                    @if($params['q'] !== '')
                        <a href="{{ route('admin.users.index', array_filter(['state' => $params['state']])) }}"
                           class="text-dark-500 text-hover-dark fs-7">
                            <i class="fa-light fa-xmark me-1" aria-hidden="true"></i> Убрать
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle gs-4 mb-0" id="admin_users_table">
                    <thead>
                        <tr class="fw-semibold fs-7 text-gray-600">
                            <th>ФИО</th>
                            <th>Логин</th>
                            <th>Email</th>
                            <th>Должность</th>
                            <th class="text-center">Активен</th>
                            <th class="text-center">Админ</th>
                            <th class="text-center">Тема</th>
                            <th class="text-nowrap">Последний визит</th>
                            <th class="text-nowrap">Последний вход</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $row)
                            @php
                                $trashed = $row->trashed();
                                $is_self = (int) $row->id === (int) auth()->id();
                                $last_login = $last_logins->get($row->id);
                                $row_name = $row->full_name ?: $row->login;
                            @endphp
                            <tr class="@if($trashed) is-trashed @endif @if(!$row->active) is-inactive @endif">
                                <td>
                                    <a href="{{ route('admin.users.show', $row->id) }}"
                                       class="fw-semibold text-gray-900 text-hover-primary admin-user-name">{{ $row->full_name ?: '—' }}</a>

                                    @if($is_self)
                                        <span class="badge badge-light-info fs-9 ms-1">это вы</span>
                                    @endif
                                    @if($row->is_hidden)
                                        <span class="badge badge-light fs-9 ms-1" title="Скрыт из выпадающих списков портала">скрыт</span>
                                    @endif
                                    @if($trashed)
                                        <span class="badge badge-light-danger fs-9 ms-1">удалён {{ $row->deleted_at?->format('d.m.Y') }}</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">{{ $row->login }}</td>
                                <td>{{ trim((string) $row->email) ?: '—' }}</td>
                                <td>{{ $row->work_position ?: '—' }}</td>
                                <td class="text-center">
                                    @if($row->active)
                                        <span class="badge badge-light-success">да</span>
                                    @else
                                        <span class="badge badge-light-danger">отключён</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($row->is_admin)
                                        <span class="badge badge-light-warning">админ</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    @if($row->ui_theme_switch)
                                        <span class="badge badge-light-primary" title="Может переключаться на старую тему">переключает</span>
                                    @else
                                        <span class="badge badge-light-dark" title="Всегда работает в Metronic">только Metronic</span>
                                    @endif
                                </td>
                                <td class="text-nowrap fs-7">
                                    @if($row->last_hit_at)
                                        <span title="{{ $row->last_hit_at->diffForHumans() }}">{{ $row->last_hit_at->format('d.m.Y H:i') }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-nowrap fs-7">
                                    @if($last_login)
                                        {{ \Carbon\Carbon::parse($last_login)->format('d.m.Y H:i') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @if($trashed)
                                        <button type="button" class="btn btn-sm btn-light-success"
                                                data-url="{{ route('admin.api.users.restore', $row->id) }}"
                                                onclick="javascript:admin_user_restore(this);">
                                            <i class="fa-light fa-trash-arrow-up me-1"></i> Восстановить
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-icon btn-light-primary" title="Редактировать"
                                                onclick="javascript:box({href: '{{ route('admin.users.box_form', $row->id) }}'});">
                                            <i class="fa-light fa-pen"></i>
                                        </button>

                                        @unless($is_self)
                                            <button type="button" class="btn btn-sm btn-icon btn-light-danger ms-1" title="Удалить"
                                                    data-url="{{ route('admin.api.users.delete', $row->id) }}"
                                                    data-name="{{ $row_name }}"
                                                    onclick="javascript:admin_user_delete(this);">
                                                <i class="fa-light fa-trash-can"></i>
                                            </button>
                                        @endunless
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-10">
                                    @if($params['state'] === 'trashed')
                                        Удалённых пользователей нет
                                    @else
                                        Никого не нашли
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
    @include('admin.users.partials.js')
@endsection
