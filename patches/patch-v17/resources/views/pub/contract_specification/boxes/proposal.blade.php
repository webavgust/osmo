@extends('components.box.box-static-extralarge')

@section('body')
    {{-- Что это за спецификация и по какому правилу отобраны КП --}}
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <div>
            <div class="fs-8 text-muted text-uppercase">Спецификация</div>
            <div class="fw-bold fs-4">{{ $spec->name }}</div>
            @if(!empty($spec->date_create))
                <div class="fs-8 text-muted">от {{ $spec->date_create->format('d.m.Y') }}</div>
            @endif
        </div>

        @php $type = \App\Modules\Pub\Contract\Models\ContractType::from($spec->contract->type)->data(); @endphp
        <div>
            <div class="fs-8 text-muted text-uppercase">Рамочный договор</div>
            <div class="fw-bold text-{{ $type['color'] }}">
                <x-ui.icon.regular :icon="$type['icon']" class="me-1"/>{{ $type['label'] }}
                <code class="ms-1">{{ $spec->contract->number ?? 'б/н' }}</code>
            </div>
        </div>

        <div>
            <div class="fs-8 text-muted text-uppercase">Компания</div>
            <div class="fw-bold">{{ $spec->company->name }}</div>
        </div>

        <div class="ms-auto text-end" style="max-width: 320px">
            <div class="fs-8 text-muted">
                @if(!empty($block))
                    Показаны КП этой компании, в которых есть блок
                    <span class="fw-bold text-dark">«{{ $block['label'] }}»</span> —
                    по типу рамочного договора.
                @else
                    У этого типа договора блок не задан — показаны все КП компании.
                @endif
            </div>
        </div>
    </div>

    <table class="table table-bordered align-middle m-0">
        @if($proposals->count() > 0)
            <tr>
                <th width="130">Номер</th>
                <th>Название</th>
                <th width="180">Партнёр</th>
                <th width="140" class="text-center">Статус</th>
                <th width="150" class="text-end">Сумма</th>
                <th width="1"></th>
            </tr>
            @foreach($proposals as $proposal)
                <tr>
                    <td>
                        <a href="{{ route('proposal.detail', [$proposal, $proposal->iteration]) }}" target="_blank" class="fw-bold">
                            {{ $proposal->number ?: 'б/н' }}
                        </a>
                        <div class="fs-8 text-muted">{{ tools()->date($proposal->sended_at) }}</div>
                    </td>
                    <td>
                        {{ $proposal->name }}
                        @if($used->has($proposal->group))
                            <div class="fs-8 text-warning">
                                Уже прикреплено:
                                @foreach($used->get($proposal->group) as $link)
                                    <span class="text-nowrap">{{ $link->specification->name ?? '—' }}@if(!$loop->last), @endif</span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="fs-7">{{ $proposal->partner->name ?? '—' }}</td>
                    <td class="text-center"><x-proposal.status :proposal="$proposal"/></td>
                    <td class="text-end text-nowrap">
                        {{ tools()->cost_normalize(round($proposal->cost_total)) }} {{ $proposal->currency->symbol ?? '' }}
                    </td>
                    <td>
                        <x-ui.button.default btn_type="info" onclick="choose('{{ $proposal->group }}')">Прикрепить</x-ui.button.default>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td class="text-center text-muted py-8">
                    Подходящих КП нет: у компании {{ $spec->company->name }} не нашлось предложений
                    @if(!empty($block))
                        с блоком «{{ $block['label'] }}»
                    @endif
                    @if($attached->isNotEmpty())
                        кроме уже прикреплённых
                    @endif
                </td>
            </tr>
        @endif

        <tr>
            <th colspan="6">Прикреплённые КП</th>
        </tr>

        @forelse($attached as $proposal)
            @php $link = $links->get($proposal->group); @endphp
            <tr>
                <td>
                    <a href="{{ route('proposal.detail', [$proposal, $proposal->iteration]) }}" target="_blank" class="fw-bold">
                        {{ $proposal->number ?: 'б/н' }}
                    </a>
                    <div class="fs-8 text-muted">{{ tools()->date($proposal->sended_at) }}</div>
                </td>
                <td>
                    {{ $proposal->name }}
                    @php
                        $days = $link
                            ? \App\Modules\Pub\ContractSpecification\Services\SpecProposalService::daysToSpec($link, $proposal)
                            : null;
                    @endphp
                    @if($days !== null)
                        <div class="fs-8 text-muted">
                            срок сделки
                            {{ \App\Modules\Pub\ContractSpecification\Services\SpecProposalService::humanPeriod($days) }}
                            — от даты КП до даты спецификации
                        </div>
                    @endif
                </td>
                <td class="fs-7">{{ $proposal->partner->name ?? '—' }}</td>
                <td class="text-center"><x-proposal.status :proposal="$proposal"/></td>
                <td class="text-end text-nowrap">
                    {{ tools()->cost_normalize(round($proposal->cost_total)) }} {{ $proposal->currency->symbol ?? '' }}
                </td>
                <td>
                    <x-ui.button.default btn_type="danger" onclick="choose('{{ $proposal->group }}', 1)">Открепить</x-ui.button.default>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-muted">Пока ни одного КП не прикреплено</td>
            </tr>
        @endforelse
    </table>

    <div class="fs-8 text-muted mt-3">
        КП из статусов «В работе» и «Заморожено» при прикреплении автоматически становится
        «Выиграно». Статусы «Проиграно» и «Отменено» не меняются — их нужно править руками.
    </div>

    <script>
        function choose(group, unbind) {
            body_block();
            $.ajax({
                url: "{{ route('api.contract_spec.set_proposal', [$spec, '_token' => _token() ]) }}",
                type: "POST",
                dataType: "json",
                data: { group, unbind },
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
