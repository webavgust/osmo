<div class="card">
    <div class="card-header d-flex justify-content-between px-3 align-items-center">
        <h4 class="card-title mb-0">Воронка по сделкам</h4>
    </div>
    <div class="card-body p-0 text-center text-dark fw-bolder py-4">
        <x-ui.a.box href="{{ route('dashboard.box.table', ['mode' => 'sales']) }}" class="p-0 ms-3 text-dark" style="margin-top: 4px; font-size: 40px;">
            {{ tools()->cost_normalize(round($data['amount']), mode: 'M', precision: 2) }} {{ $currency->symbol }}
        </x-ui.a.box>
    </div>
</div>
