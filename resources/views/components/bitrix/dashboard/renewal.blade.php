@php
    $urgent = ($data['horizons'][30]['count'] ?? 0) + ($data['expired'] ?? 0);
@endphp

<div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center px-2">
        <span class="card-title mb-0 fs-5">Продление лицензий</span>

        @if($urgent > 0)
            <span class="badge badge-light-danger fw-bold">{{ $urgent }}</span>
        @endif
    </div>

    <div class="card-body p-0 text-center text-dark fw-bolder d-flex align-items-center justify-content-center">
        <x-ui.a.box href="{{ route('dashboard.box.license_renewal') }}" class="p-0 text-dark text-hover-danger" style="font-size: 30px;">
            {{ $data['total'] }}


            @if($data['amount'] > 0)
                <span class="fs-3 text-nowrap">
                    ({{ tools()->cost_normalize(round($data['amount'])) }} ₽)
                </span>
            @endif

        </x-ui.a.box>
    </div>


</div>

@section('footer') &nbsp; @endsection
