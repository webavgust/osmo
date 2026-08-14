@extends('components.box.box-static-extralarge')

@section('body')
    {{-- Какое КП прикрепляем и по какому правилу отобраны спецификации --}}
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <div>
            <div class="fs-8 text-muted text-uppercase">КП</div>
            <div class="fw-bold fs-4">{{ $proposal->number ?: 'б/н' }}</div>
            <div class="fs-8 text-muted">{{ $proposal->name }}</div>
        </div>

        <div>
            <div class="fs-8 text-muted text-uppercase">Компания</div>
            <div class="fw-bold">{{ $proposal->company->name ?? '—' }}</div>
        </div>

        <div>
            <div class="fs-8 text-muted text-uppercase">Сумма</div>
            <div class="fw-bold">
                {{ tools()->cost_normalize(round($proposal->cost_total)) }} {{ $proposal->currency->symbol ?? '' }}
            </div>
        </div>

        <div class="ms-auto text-end fs-8 text-muted" style="max-width: 320px">
            @if(!empty($block_data))
                Показаны спецификации этой компании по рамочным договорам, закрывающим блок
                <span class="fw-bold text-dark">«{{ $block_data['label'] }}»</span>.
            @else
                Показаны все спецификации этой компании.
            @endif
        </div>
    </div>

    <div class="mb-3">
        <input type="text" class="form-control" id="spec_search" placeholder="Поиск: спецификация, договор, сумма" autocomplete="off">
    </div>

    <table class="table table-bordered align-middle m-0" id="spec_table">
        @if($specs->count() > 0)
            <tr>
                <th width="220">Рамочный договор</th>
                <th>Спецификация</th>
                <th width="100" class="text-center">Дата</th>
                <th width="110" class="text-center">Статус</th>
                <th width="100" class="text-center">Подписана</th>
                <th width="150" class="text-end">Сумма</th>
                <th width="1"></th>
            </tr>
            @foreach($specs as $spec)
                @php
                    $type = \App\Modules\Pub\Contract\Models\ContractType::tryFrom((string) $spec->contract?->type)?->data();
                    $status = \App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus::tryFrom((string) $spec->status)?->data();
                @endphp
                <tr class="spec_row" data-search="{{ mb_strtolower($spec->name . ' ' . ($spec->contract->number ?? '') . ' ' . ($type['label'] ?? '') . ' ' . $spec->amount) }}">
                    <td>
                        <div class="fw-bold text-{{ $type['color'] ?? 'dark' }}">
                            @if(!empty($type))
                                <x-ui.icon.regular :icon="$type['icon']" class="me-1"/>{{ $type['label'] }}
                            @endif
                        </div>
                        <code>{{ $spec->contract->number ?: 'б/н' }}</code>
                    </td>
                    <td>{{ $spec->name }}</td>
                    <td class="text-center text-nowrap">
                        {{ $spec->date_create?->format('d.m.Y') ?? $spec->contract?->date?->format('d.m.Y') ?? '—' }}
                    </td>
                    <td class="text-center">
                        <x-ui.badge.light :type="$status['color'] ?? 'secondary'">
                            {{ $status['label'] ?? $spec->status }}
                        </x-ui.badge.light>
                    </td>
                    <td class="text-center">
                        @if($spec->is_signed)
                            <x-ui.icon.regular icon="fa-check" class="text-success"/>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        {{ tools()->cost_normalize(round($spec->amount)) }} {{ $spec->currency->symbol ?? '' }}
                    </td>
                    <td>
                        <x-ui.button.default btn_type="info" onclick="choose({{ $spec->id }})">Прикрепить</x-ui.button.default>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td class="text-center text-muted py-8">
                    Подходящих спецификаций нет: у компании {{ $proposal->company->name ?? '—' }} не нашлось
                    @if(!empty($block_data))
                        спецификаций по договорам, закрывающим блок «{{ $block_data['label'] }}»
                    @else
                        спецификаций
                    @endif
                    @if($attached->isNotEmpty())
                        кроме уже прикреплённых
                    @endif
                </td>
            </tr>
        @endif

        <tr>
            <th colspan="7">Уже прикреплено</th>
        </tr>

        @forelse($attached as $spec)
            @php
                $type = \App\Modules\Pub\Contract\Models\ContractType::tryFrom((string) $spec->contract?->type)?->data();
                $status = \App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus::tryFrom((string) $spec->status)?->data();
            @endphp
            <tr>
                <td>
                    <div class="fw-bold text-{{ $type['color'] ?? 'dark' }}">
                        @if(!empty($type))
                            <x-ui.icon.regular :icon="$type['icon']" class="me-1"/>{{ $type['label'] }}
                        @endif
                    </div>
                    <code>{{ $spec->contract->number ?: 'б/н' }}</code>
                </td>
                <td>{{ $spec->name }}</td>
                <td class="text-center text-nowrap">
                    {{ $spec->date_create?->format('d.m.Y') ?? $spec->contract?->date?->format('d.m.Y') ?? '—' }}
                </td>
                <td class="text-center">
                    <x-ui.badge.light :type="$status['color'] ?? 'secondary'">
                        {{ $status['label'] ?? $spec->status }}
                    </x-ui.badge.light>
                </td>
                <td class="text-center">
                    @if($spec->is_signed)
                        <x-ui.icon.regular icon="fa-check" class="text-success"/>
                    @endif
                </td>
                <td class="text-end text-nowrap">
                    {{ tools()->cost_normalize(round($spec->amount)) }} {{ $spec->currency->symbol ?? '' }}
                </td>
                <td>
                    <x-ui.button.default btn_type="danger" onclick="choose({{ $spec->id }}, 1)">Открепить</x-ui.button.default>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-muted">КП пока не прикреплено ни к одной спецификации</td>
            </tr>
        @endforelse
    </table>

    <div class="fs-8 text-muted mt-3">
        КП из статусов «В работе» и «Заморожено» при прикреплении автоматически становится
        «Выиграно». Статусы «Проиграно» и «Отменено» не меняются — их нужно править руками.
    </div>

    <script>
        var spec_routes = @json($specs->mapWithKeys(fn($spec) => [$spec->id => route('api.contract_spec.set_proposal', [$spec, '_token' => _token()])])->merge($attached->mapWithKeys(fn($spec) => [$spec->id => route('api.contract_spec.set_proposal', [$spec, '_token' => _token()])])));

        function choose(spec_id, unbind) {
            body_block();
            $.ajax({
                url: spec_routes[spec_id],
                type: "POST",
                dataType: "json",
                data: { group: "{{ $proposal->group }}", unbind: unbind },
                success: function (response) {
                    if (response.result == 'success') {
                        location.reload();
                    } else {
                        toastr.error(response.message || "Не получилось сохранить данные", "Это провал!", {
                            progressBar: true,
                            "timeOut": 4000,
                        });
                        body_unblock();
                    }
                },
                error: function () {
                    toastr.error("Не получилось сохранить данные", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    body_unblock();
                }
            });
        }

        $(document).ready(function () {
            $("#spec_search").on("keyup", function () {
                var query = $(this).val().toLowerCase().trim();

                $("#spec_table tr.spec_row").each(function () {
                    var match = !query || $(this).attr("data-search").indexOf(query) >= 0;
                    $(this).toggle(match);
                });
            });
        });
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>
    </div>
@endsection
