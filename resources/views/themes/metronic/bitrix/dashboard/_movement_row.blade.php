{{-- Строка таблицы «По менеджерам» отчёта «Движение сделок» (patch v50):
     $c — когорта (или null), $m — движения (или null), $bold — итоговая строка --}}
<tr @class(['fw-bold bg-light' => $bold])>
    <td>{{ $name }}</td>
    <td class="text-end">{{ $c['total'] ?? 0 }}</td>
    @foreach(['work', 'lost', 'done'] as $key)
        <td class="text-end">
            {{ $c[$key] ?? 0 }}
            @if(!empty($c['total']))
                <span class="text-muted fw-normal fs-8">{{ $c[$key . '_pct'] }}%</span>
            @endif
        </td>
    @endforeach
    @foreach(['lost', 'done'] as $key)
        <td class="text-end">
            {{ $m[$key]['all'] ?? 0 }}
            <span class="text-muted fw-normal fs-8">({{ $m[$key]['past'] ?? 0 }})</span>
        </td>
    @endforeach
</tr>
