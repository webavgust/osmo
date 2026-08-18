<div class="card mb-0">
    <div class="card-header min-h-auto py-5 border-bottom d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Воронка в разрезе сферы деятельности и ответственных менеджеров</h4>

        <div class="d-flex d-print-none">
            <div class="reports ms-3 d-flex align-items-center pt-1">
                <a href="{{ route('report-download.tbl_industry_name', ['mode' => 'pdf']) }}" class="ms-2">
                    <x-ui.icon.regular icon="fa-file-pdf" class="ms-2 text-danger fs-1"></x-ui.icon.regular>
                </a>
                <a href="{{ route('report-download.tbl_industry_name', ['mode' => 'excel']) }}" class="ms-2">
                    <x-ui.icon.regular icon="fa-file-excel" class="ms-2 text-success fs-1"></x-ui.icon.regular>
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-0 text-center text-dark fw-bolder py-4 pt-0 pb-0 fs-6">
        <table class="table table-bordered mb-0">
        <thead>
        <tr>
            <th class="text-center">
            </th>
            @foreach($data['columns'] as $column)
                <th class="py-1 fs-6">{{ $column }}</th>
            @endforeach
            <th class="text-end" style="background: #F0F0F0">Итого</th>
        </tr>
        </thead>
        <tbody>
            @php
                $column_total = collect();
            @endphp
            @foreach($data['rows'] as $row)
                @continue($data['matrix'][$row]->isEmpty())
                @php
                    $row_total = 0;
                @endphp
                <tr>
                    <td class="text-start text-nowrap">{{ $row }}</td>
                    @foreach($data['columns'] as $column)
                        <td class="text-end text-nowrap  fw-bolder">
                            @if(!empty($data['matrix'][$row][$column]))
                                @php
                                    $row_total += $data['matrix'][$row][$column]['amount'];
                                    if(empty($column_total[$column])) $column_total[$column] = 0;
                                    $column_total[$column] += $data['matrix'][$row][$column]['amount'];
                                @endphp
                                <a href="javascript:void(0);" onclick="javascript:box({href: '{{ route('dashboard.box.industry_name', [
                                    'row' => \Illuminate\Support\Str::replace("/", "_", base64_encode($row)),
                                    'column' => \Illuminate\Support\Str::replace("/", "_", base64_encode($column)),
                                 ]) }}'})" class="p-0">
                                    {{ tools()->cost_normalize(
                                         round($data['matrix'][$row][$column]['amount']),
                                         mode: 'M',
                                         precision: 2
                                      ) }}
                                </a>
                            @endif
                        </td>
                    @endforeach
                    <td class="text-end text-nowrap  fw-bolder" style="font-weight: 600;; background: #F0F0F0">
                        <a href="javascript:void(0);" onclick="javascript:box({href: '{{ route('dashboard.box.industry_name', [
                                    'row' => \Illuminate\Support\Str::replace("/", "_", base64_encode($row)),
                                    'column' => 'all',
                                 ]) }}'})" class="p-0">
                            {{ tools()->cost_normalize(
                                 round($row_total),
                                 mode: 'M',
                                 precision: 2
                              ) }}
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tr>
            <td class="text-end p-1" style="background: #F0F0F0"></td>
            @foreach($data['columns'] as $column)
                <td class="text-end fs-2 text-nowrap" style="font-weight: 600; background: #F0F0F0">
                    <a href="javascript:void(0);" onclick="javascript:box({href: '{{ route('dashboard.box.industry_name', [
                                    'row' => 'all',
                                    'column' => \Illuminate\Support\Str::replace("/", "_", base64_encode($column)),
                                 ]) }}'})" class="p-0">
                        {{ tools()->cost_normalize(
                             round($column_total[$column]),
                             mode: 'M',
                             precision: 2
                          ) }}
                    </a>
                </td>
            @endforeach
            <td class="text-end fs-2 text-nowrap" style="font-weight: 600; background: #E5E5E5">
                <a href="javascript:void(0);" onclick="javascript:box({href: '{{ route('dashboard.box.industry_name', [
                                    'row' => 'all',
                                    'column' => 'all',
                                 ]) }}'})" class="p-0">
                    {{ tools()->cost_normalize(
                         round($column_total->sum()),
                         mode: 'M',
                         precision: 2
                      ) }}
                </a>
            </td>
        </tr>
    </table>
    </div>
</div>
