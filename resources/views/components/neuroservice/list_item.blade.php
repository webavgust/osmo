<div id="{{$neuroservice->id}}" type="neuroservice" class="todo-item p-3 border-bottom position-relative group-{{$groupId}} " @if($groupOut != $groupId) style="display: none;" @endif>
    <div class="inner-item d-flex align-items-start">
        <div class="w-100">
            <div class=" checkbox checkbox-info d-flex align-items-start form-check">
                <div>
                    <div class="content-todo">
                        <h5
                            class="font-weight-medium fs-4 todo-header"
                            data-todo-header="{{ $neuroservice->name }}"
                        >
                            {{ $neuroservice->name }}

                        </h5>
                        <div>
                            <x-ui.badge.default type="info" class="p-0 d-flex-inline align-items-center py-1">
                                <span class="px-2 fs-3 fw-bold me-2 border-end border-1 border-white">1</span>
                                <span class="fs-2 me-2">{{ tools()->cost_normalize($neuroservice->cost['year'] ?? '0') }} ₽</span>
                            </x-ui.badge.default>

                            <x-ui.badge.default type="primary" class="p-0 d-flex-inline align-items-center py-1">
                                <span class="px-2 fs-3 fw-bold me-2 border-end border-1 border-white">
                                    <i class="fa-solid fa-infinity"></i>
                                </span>

                                @if($neuroservice->cost['unlimited'] ?? 0 > 0)
                                    <span class="fs-2 me-2">{{ tools()->cost_normalize($neuroservice->cost['unlimited'] ?? '0') }} ₽</span>
                                @else
                                    <span class="fs-2 me-2">
                                        {{ \App\Modules\Pub\Constant\Models\Constant::get('neuroservice_unlimited_multiplier') * 100 }} %
                                    </span>
                                @endif
                            </x-ui.badge.default>
                        </div>
                        <div
                            class="todo-subtext text-muted fs-3"
                        >
                            {{ $neuroservice->description }}
                        </div>
                    </div>
                </div>
                        <div class="ms-auto">

                        </div>
            </div>
            <!-- Content -->
        </div>
    </div>
</div>
