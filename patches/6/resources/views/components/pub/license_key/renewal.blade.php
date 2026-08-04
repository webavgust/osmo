@php
    $urgent = ($data['horizons'][30]['count'] ?? 0) + ($data['expired'] ?? 0);
@endphp

<div class="card h-100">
    <div class="border-bottom title-part-padding d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Продление лицензий</h4>

        @if($urgent > 0)
            <span class="badge badge-light-danger fw-bold">{{ $urgent }}</span>
        @endif
    </div>

    <div class="card-body p-0 text-center text-dark fw-bolder py-4" style="font-size: 40px;">
        <x-ui.a.box href="{{ route('dashboard.box.license_renewal') }}"
                    class="p-0 ms-3 text-dark"
                    style="margin-top: 4px; font-size: 40px;">
            {{ $data['total'] }}
        </x-ui.a.box>
    </div>

    <div class="px-3 pb-3 d-flex justify-content-center gap-2 flex-wrap">
        @if($data['expired'] > 0)
            <span class="badge badge-light-dark" title="Срок уже истёк">
                истекли: {{ $data['expired'] }}
            </span>
        @endif

        @foreach($data['horizons'] as $days => $horizon)
            @continue(!$horizon['count'])
            <span class="badge badge-light-{{ $days <= 30 ? 'danger' : ($days <= 60 ? 'warning' : 'info') }}"
                  title="Истекает в ближайшие {{ $days }} дней">
                {{ $days }} дн: {{ $horizon['count'] }}
            </span>
        @endforeach

        @if(!$data['total'])
            <span class="text-muted fs-8">Ничего не истекает в ближайшие 90 дней</span>
        @endif
    </div>

    @if($data['amount'] > 0)
        <div class="border-top px-3 py-2 text-center">
            <span class="text-muted fs-8">Оценка продлений</span>
            <span class="fw-bold ms-2">{{ tools()->cost_normalize(round($data['amount'])) }} ₽</span>
        </div>
    @endif
</div>
