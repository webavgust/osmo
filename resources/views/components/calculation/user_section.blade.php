<div class="accordion-item">
    <h2 class="accordion-header" id="flush-heading{{ $type }}{{ $user->id }}">
        <button class="bg-white accordion-button collapsed" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#flush-collapse{{ $type }}{{ $user->id }}"
                aria-expanded="false"
                aria-controls="flush-collapse{{ $type }}{{ $user->id }}">
            <div class="d-flex align-items-center justify-content-between w-100">
                <div class="d-flex align-items-center">
                    <img src="{{ $user->avatar() }}" class="rounded-circle" width="40">
                    <span class="ms-3 fw-normal">{{ $user->full_name }}</span>
                </div>


                <div class="me-3">
                    @if($amounts)
                        <x-ui.badge.default type="primary">{{ tools()->cost_normalize($amounts) }} ₽</x-ui.badge.default>
                    @endif
                    @if($corrections)
                        @if($corrections > 0)
                            <x-ui.badge.default type="success">+ {{ tools()->cost_normalize($corrections) }} ₽</x-ui.badge.default>
                            <x-ui.badge.light type="secondary">= {{ tools()->cost_normalize($amounts + $corrections) }} ₽</x-ui.badge.light>
                        @else
                            <x-ui.badge.default type="danger">&ndash; {{ tools()->cost_normalize(abs($corrections)) }} ₽</x-ui.badge.default>
                                <x-ui.badge.light type="secondary">= {{ tools()->cost_normalize($amounts + $corrections) }} ₽</x-ui.badge.light>
                        @endif
                    @endif
                </div>
            </div>
        </button>
    </h2>
    <div id="flush-collapse{{ $type }}{{ $user->id }}"
         class="border-top bg-white accordion-collapse collapse"
         aria-labelledby="flush-heading{{ $type }}{{ $user->id }}"
         data-bs-parent="#accordionFlush{{ $type }}s">
        <div class="accordion-body p-0 table-responsive ">
            <table class="table customize-table mb-0 v-middle">
                <thead class="table-light">
                <tr>
                    <th class="border-bottom border-top no-wrap"><nobr>Объект начисления</nobr></th>
                    <th width="200" class="border-bottom border-top text-center">Окончание работы</th>
                    <th width="120" class="border-bottom border-top text-right">Сумма</th>
                    <th width="120" class="border-bottom border-top text-right">Корректировка</th>
                    <th width="200" class="border-bottom border-top text-center">Дата расчёта</th>
                    <th width="32"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($rows as $salary)
                    <x-calculation.salary_row :type="$type" :salary="$salary"></x-calculation.salary_row>
                @endforeach
                </tbody>
                <tr>
                    <th/>
                    <th/>
                    <th class="border-top text-right fw-bold"><nobr>{{ tools()->cost_normalize($amounts) }} ₽</nobr></th>
                    <th class="border-top text-right fw-bold"><nobr>{{ tools()->cost_normalize($corrections) }} ₽</nobr></th>
                    <th class="border-top text-right fw-bold" colspan=2 >
                        <x-ui.badge.default type="secondary" class="font-16">
                            <nobr>= {{ tools()->cost_normalize($amounts + $corrections) }} ₽</nobr>
                        </x-ui.badge.default>
                    </th>
                </tr>

            </table>
        </div>
    </div>
</div>
