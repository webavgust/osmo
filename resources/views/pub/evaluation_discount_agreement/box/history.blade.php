@extends('components.box.box-static-large')

@section('body')
    @foreach($evaluation->discount_agreement_all()->get() as $agreement)
        @php
            $responsible = $agreement->getResponsible();
        @endphp
        <div class="once mb-4">
            <div class="d-flex justify-content-between font-16">
                <span>{{ $agreement->created_at->format('d.m.Y H:i:s') }}</span>
                <strong>{{ tools()->cost_normalize($agreement->discount) }} ₽</strong>
            </div>

            @if($agreement->status !== \App\Modules\Pub\EvaluationDiscountAgreement\Models\EvaluationDiscountAgreement::STATUS_CREATED)
                <div class="d-flex align-items-center">
                    <span>
                        Решение
                        <x-ui.badge.default
                            :type="$agreement->isSubmitted() ? 'success' : 'danger'">{{ \App\Modules\Pub\EvaluationDiscountAgreement\Models\EvaluationDiscountAgreement::STATUS_LANG[$agreement->status] }}</x-ui.badge.default>

                        принял
                        <x-ui.badge.default type="secondary">
                            {{ $responsible->fullName }}
                        </x-ui.badge.default>
                        @if(!empty($responsible->pivot->comment))
                            с комментарием:
                        @endif
                    </span>
                </div>
                @if(!empty($responsible->pivot->comment))
                    <x-ui.notification.light type="secondary-light" class="px-2 py-1 mt-1 font-12">
                        {!! $responsible->pivot->comment !!}
                    </x-ui.notification.light>
                @endif
            @else
                <div class="d-flex align-items-center">
                    <span>
                        Ожидает решения
                    </span>
                </div>
            @endif
        </div>
    @endforeach
@endsection

@section('footer')
@endsection

