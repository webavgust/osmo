@if($deals->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card bg-danger w-100 cursor-pointer" onclick="javsacript:box({href: '{{ route('crm-deal.box.issues') }}'})">
                <div class="card-body">
                    <div class="d-flex fs-7 text-white py-1 justify-content-center">
                        Найдено <span class="fw-bold px-2 text-decoration-underline">{{ tools()->num_rus($deals->count(), ['сделки', 'сделка', 'сделок'], 1) }}</span> с проблемами
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

