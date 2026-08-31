<div class="card">
    <div class="card-header d-flex justify-content-center align-items-center">
        <h4 class="card-title mb-0">Услуги</h4>
    </div>
    <div class="card-body p-0 text-center text-dark fw-bolder py-4" style="font-size: 35px;">
        <x-ui.a.box href="{{ route('dashboard.box.table', ['mode' => 'services']) }}" class="p-0 text-dark" style="font-size: 30px;">
            {{ tools()->cost_normalize(round($data['amount']), mode: 'M', precision: 2) }} {{ $currency->symbol }}
        </x-ui.a.box>
    </div>
</div>
