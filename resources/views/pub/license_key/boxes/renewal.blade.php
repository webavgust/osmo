@extends('components.box.box-static-large')

@section('title')
    Продление лицензий
    <span class="text-muted fs-7 ms-2">горизонт {{ $days }} дней</span>
@endsection

@section('body')
    {{-- Сводка --}}
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="javascript:box({href: '{{ route('dashboard.box.license_renewal') }}?days=30'})"
           class="btn btn-sm @if($days == 30) btn-danger @else btn-light-danger @endif">
            30 дней
            <span class="badge badge-circle badge-light ms-2">{{ $summary['horizons'][30]['count'] ?? 0 }}</span>
        </a>
        <a href="javascript:box({href: '{{ route('dashboard.box.license_renewal') }}?days=60'})"
           class="btn btn-sm @if($days == 60) btn-warning @else btn-light-warning @endif">
            60 дней
            <span class="badge badge-circle badge-light ms-2">{{ $summary['horizons'][60]['count'] ?? 0 }}</span>
        </a>
        <a href="javascript:box({href: '{{ route('dashboard.box.license_renewal') }}?days=90'})"
           class="btn btn-sm @if($days == 90) btn-info @else btn-light-info @endif">
            90 дней
            <span class="badge badge-circle badge-light ms-2">{{ $summary['horizons'][90]['count'] ?? 0 }}</span>
        </a>

        <div class="ms-auto d-flex align-items-center">
            <span class="text-muted fs-7 me-2">Оценка продлений:</span>
            <span class="fw-bold fs-4">{{ tools()->cost_normalize(round($summary['amount'])) }} ₽</span>
        </div>
    </div>

    @if($keys->isEmpty())
        <div class="alert alert-info mb-0">
            В выбранном горизонте нет истекающих лицензий.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-row-dashed table-row-gray-300 align-middle mb-0">
                <thead>
                <tr class="fw-bold text-muted bg-light">
                    <th class="ps-3">Компания</th>
                    <th>Ключ</th>
                    <th class="text-center" width="90">Кол-во</th>
                    <th class="text-center" width="120">Действует до</th>
                    <th class="text-center" width="120">Осталось</th>
                    <th class="text-end pe-3" width="140">Оценка</th>
                </tr>
                </thead>
                <tbody>
                @foreach($keys as $key)
                    <tr>
                        <td class="ps-3">
                            @if($key->company)
                                <a href="{{ route('company.detail', $key->company) }}" class="fw-semibold">
                                    {{ $key->company->name }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif

                            @if($key->specification)
                                <div class="fs-8 text-muted">
                                    {{ $key->specification->name }}
                                </div>
                            @endif
                        </td>

                        <td>
                            <span class="font-monospace fs-8">{{ $key->key }}</span>
                            @if($key->comment)
                                <div class="fs-8 text-muted">{{ $key->comment }}</div>
                            @endif
                        </td>

                        <td class="text-center">{{ $key->count ?? '—' }}</td>

                        <td class="text-center text-nowrap">
                            {{ $key->active_to?->format('d.m.Y') }}
                        </td>

                        <td class="text-center">
                            <span class="badge badge-light-{{ $key->urgency['color'] }}">
                                @if($key->is_expired)
                                    {{ $key->urgency['label'] }} {{ abs($key->days_left) }} дн назад
                                @else
                                    {{ $key->days_left }} дн
                                @endif
                            </span>
                        </td>

                        <td class="text-end pe-3 text-nowrap">
                            @if($key->renewal_amount > 0)
                                {{ tools()->cost_normalize(round($key->renewal_amount)) }} ₽
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-muted fs-8 mt-3">
            Оценка продления — сумма спецификации, по которой был выдан ключ,
            делённая на количество ключей в ней. Отдельной цены продления в базе нет.
        </div>
    @endif
@endsection
