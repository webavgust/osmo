@if($evaluations->isNotEmpty())
    <table class="table customize-table mb-0 v-middle">
        <thead class="table-light">
        <tr>
            <th class="border-bottom border-top text-center" width="1">ID</th>
            <th class="border-bottom border-top text-center">Заказчик</th>
            <th class="border-bottom border-top text-center">Договор</th>
            <th class="border-bottom border-top text-center" width="120">Статус</th>
            <th class="border-bottom border-top" width="1"></th>
        </tr>
        </thead>
        <tbody>
            @foreach($evaluations as $evaluation)
                <tr>
                    <td class="text-center p-0">
                        <a href="{{ route('evaluation.detail', $evaluation) }}">{{ $evaluation->id }}</a>
                        <div class="fs-1">{{ _date($evaluation->created_at) }}</div>
                    </td>
                    <td class="py-1 text-start">
                        <a href="{{ env('PORTAL_URL') }}/projects/clients/{{ $evaluation->portal->client_id }}/">
                            {{ $evaluation->portal?->client_name }} ({{ $evaluation->sub_contract->contract_id }})
                        </a>

                        @if($evaluation->sub_contract->slug !== '0') {
                            <div class='font-10 mt-1'>{{ $evaluation->sub_contract->slug }}</div>
                        @endif

                        <div class='font-10 mt-1'>
                                <x-ui.icon.duotone icon="fa-diagram-subtask"/> {{ $evaluation->portal?->annex_name ?? $evaluation->block_id }}
                        </div>
                    </td>
                    <td>
                        <a href="{{ env('PORTAL_URL') }}/projects/contracts/{{ $evaluation->sub_contract->contract_id }}/">{{ $evaluation->portal->contract_name }}</a>
                    </td>
                    <td class="py-0 text-center">
                        <x-evaluation.status :evaluation="$evaluation"/>
                    </td>
                    <td class="py-0 pe-0">
                        <x-ui.button.default btn_type="primary" onclick="javascript:copy_submit({{ $evaluation->id }})">
                            Скопировать
                        </x-ui.button.default>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <span class="text-danger fs-5 fw-bold ms-1">Ничего не найдено</span>
@endif
