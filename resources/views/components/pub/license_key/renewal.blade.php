@php
    $urgent = ($data['horizons'][30]['count'] ?? 0) + ($data['expired'] ?? 0);
@endphp

<div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center px-2">
        <h5 class="mb-0">Продление лицензий</h5>

        @if($urgent > 0)
            <span class="badge badge-light-danger fw-bold">{{ $urgent }}</span>
        @endif
    </div>

    <div class="card-body p-0 text-center text-dark fw-bolder" style="font-size: 40px;">
        <x-ui.a.box href="{{ route('dashboard.box.license_renewal') }}"
                    class="p-0 m-0 text-dark"
                    style="font-size: 30px;">
            {{ $data['total'] }}
        </x-ui.a.box>
    </div>

    @if($data['amount'] > 0)
        <div class="border-top px-3 py-2 text-center">
            <span class="text-muted fs-8">Оценка</span>
            <span class="fw-bold ms-2 text-nowrap">{{ tools()->cost_normalize(round($data['amount'])) }} ₽</span>
        </div>
    @endif
</div>
