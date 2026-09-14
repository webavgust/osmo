{{--
    Платежи спецификации: та же табличка, что в ячейке «Оплаты» на карточке
    партнёра — строка на платёж, слева значок состояния и даты (план → факт),
    справа суммы (план → факт).

    Компонент общий для обеих тем, поэтому иконки берём через x-ui.icon.*,
    а оформление — классом .card-table из public/css/app.css.

    Параметры:
      $payments — платежи: модели Payment или строки из БД, лишь бы были поля
                  date_plan, date_fact, delay, amount_plan, amount_fact, is_unknown;
      $symbol   — символ валюты спецификации;
      $class    — что добавить к обёртке (по умолчанию отступы как у партнёра).
--}}
@props(['payments', 'symbol' => '', 'class' => 'm-3 ms-4 mt-0'])

@if(!empty($payments) && count($payments))
    <div class="card-table {{ $class }}">
        @foreach($payments as $payment)
            @php
                // у модели Payment состояние уже посчитано, у строки из БД — нет,
                // поэтому повторяем ту же логику (см. Payment::getStatusAttribute)
                $status = $payment->status ?? \App\Modules\Pub\Payment\Models\PaymentStatus::from(match (true) {
                    !empty($payment->date_fact) => empty($payment->date_plan)
                        || $payment->date_plan->greaterThan($payment->date_fact)
                        || $payment->date_plan->isSameDay($payment->date_fact)
                            ? 'success'
                            : 'expired',
                    $payment->date_plan?->isFuture() ?? false => 'waiting',
                    default => 'delayed',
                })->data();
            @endphp

            <div class="tr">
                <span class="th align-items-center">
                    <span class="me-1 text-center text-{{ $status['color'] }}" style="width: 20px">
                        <x-ui.icon.solid :icon="$status['icon']"/>
                    </span>

                    @if($payment->is_unknown)
                        (неизвестно)
                    @endif

                    @if(!empty($payment->date_plan))
                        {{ $payment->date_plan?->format('d.m.Y') ?? '-' }}
                    @endif

                    @if(!empty($payment->date_fact) && !$payment->date_fact->isSameDay($payment->date_plan))
                        @if(!empty($payment->date_plan))
                            <x-ui.icon.regular icon="fa-arrow-right" class="mx-2"/>
                        @endif

                        {{ $payment->date_fact->format('d.m.Y') }}

                        @if(!empty($payment->delay))
                            (+ {{ tools()->num_rus($payment->delay, ['дня', 'день', 'дней'], true) }})
                        @endif
                    @endif
                </span>

                {{-- сумма с валютой не переносится: «142 500 ₽» ломалось на две строки --}}
                <span class="td text-nowrap">
                    @if(!empty($payment->amount_plan))
                        {{ tools()->cost_normalize($payment->amount_plan) }} {{ $symbol }}
                    @endif

                    @if(!empty($payment->amount_fact) && $payment->amount_plan != $payment->amount_fact)
                        @if(!empty($payment->amount_plan))
                            <x-ui.icon.regular icon="fa-arrow-right" class="mx-2"/>
                        @endif

                        {{ tools()->cost_normalize($payment->amount_fact) }} {{ $symbol }}
                    @endif
                </span>
            </div>
        @endforeach
    </div>
@endif
