@if(empty($ignore_header))
    <div class="d-flex justify-content-between align-items-center">
        <h4 class="m-0">{{ $name }}</h4>
        @if(!empty($data))
            <div>
                @if($amounts)
                    <x-ui.badge.default type="primary" class="font-16">{{ tools()->cost_normalize($amounts) }} ₽</x-ui.badge.default>
                @endif
                @if($corrections)
                    @if($corrections > 0)
                        <x-ui.badge.default type="success" class="font-16">+ {{ tools()->cost_normalize($corrections) }} ₽</x-ui.badge.default>
                        <x-ui.badge.light type="secondary" class="font-16">= {{ tools()->cost_normalize($amounts + $corrections) }} ₽</x-ui.badge.light>
                    @else
                        <x-ui.badge.default type="danger" class="font-16">&ndash; {{ tools()->cost_normalize(abs($corrections)) }} ₽</x-ui.badge.default>
                        <x-ui.badge.light type="secondary" class="font-16">= {{ tools()->cost_normalize($amounts + $corrections) }} ₽</x-ui.badge.light>
                    @endif
                @endif
            </div>
        @endif
    </div>
@endif

@if(!empty($data))
    <div class="card mt-3 mb-4">
        <div class="card-body p-0">
            <div class="card-table">
                <div class="accordion accordion-flush" id="accordionFlushSupervisors">
                    @foreach($data as $user_id => $rows)
                        <x-calculation.user_section :type="$type" :user-id="$user_id" :rows="$rows"></x-calculation.user_section>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@else
    <x-ui.notification.light type="info" class="p-2 mt-3 mb-5 background-white">Начислений не найдено</x-ui.notification.light>
@endif
