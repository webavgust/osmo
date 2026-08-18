<div class="card mb-0 border-0">
    <div class="border-bottom title-part-padding d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Менеджеры и статусы поквартально</h4>

        <div class="d-flex d-print-none">
            <div class="reports ms-3 d-flex align-items-center">
                <a href="{{ route('report-download.tbl_manager_status__quarter', ['mode' => 'pdf']) }}" class="ms-2">
                    <x-ui.icon.regular icon="fa-file-pdf" class="ms-2 text-danger fs-1"></x-ui.icon.regular>
                </a>
                <a href="{{ route('report-download.tbl_manager_status__quarter', ['mode' => 'excel']) }}" class="ms-2">
                    <x-ui.icon.regular icon="fa-file-excel" class="ms-2 text-success fs-1"></x-ui.icon.regular>
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-0 text-center text-dark fw-bolder py-4 pt-0 pb-0 fs-6">
        <table class="table table-bordered mb-0">
            <thead>
            <tr>
                <th class="text-start" rowspan="2">Ответственный менеджер</th>
                <th class="text-start" rowspan="2">Статус</th>
                @foreach($data['columns'] as $column)
                    <th class="py-1 align-content-center">{{ $column }}</th>
                @endforeach
                <th class="text-end" style="background: #F0F0F0">Итого</th>
            </tr>
            </thead>
            <tbody>
            @php
                $column_total = collect();
            @endphp
            @foreach($data['matrix'] as $manager => $line1)
                @php
                    $subtotal = collect();
                    foreach($data['columns'] as $column)
                        $subtotal[$column] = 0;
                @endphp
                @foreach($line1 as $status => $line2)
                    @php
                        $row_total = 0;
                        $loop_block = $loop;
                    @endphp
                    <tr>
                        @if($loop->first)
                            <td class="text-start py-1" rowspan="{{ count($line1) + ($loop_block->count > 1 ? 1 : 0) }}">
                                {{ $manager }}
                            </td>
                        @endif

                            <td class="text-start py-1"
                                @if($loop_block->iteration > 1 && $loop_block->last) rowspan="2" @endif
                            >{{ $status }}</td>
                        @foreach($data['columns'] as $column)
                            <td class="py-1 text-end">
                                @if(!empty($line2[$column]))
                                    @php
                                        $row_total += $line2[$column]['amount'];
                                        if(empty($column_total[$column])) $column_total[$column] = 0;
                                        $column_total[$column] += $line2[$column]['amount'];
                                        $subtotal[$column] += $line2[$column]['amount'];
                                    @endphp

                                    <a href="javascript:void(0);" onclick="javascript:box({href: '{{ route('dashboard.box.manager_status_quarter', [
                                        'r1' => \Illuminate\Support\Str::replace("/", "_", base64_encode($manager)),
                                        'r2' => \Illuminate\Support\Str::replace("/", "_", base64_encode($status)),
                                        'column' => \Illuminate\Support\Str::replace("/", "_", base64_encode($column)),
                                     ]) }}'})" class="p-0">
                                        {{ tools()->cost_normalize(
                                             round($line2[$column]['amount']),
                                             mode: 'M',
                                             precision: 2
                                          ) }}
                                    </a>
                                @endif
                            </td>
                        @endforeach
                        <td class="text-end text-nowrap monospace fs-4" style="font-weight: 600;; background: #F0F0F0">
                            <a href="javascript:void(0);" onclick="javascript:box({href: '{{ route('dashboard.box.manager_status_quarter', [
                                        'r1' => \Illuminate\Support\Str::replace("/", "_", base64_encode($manager)),
                                        'r2' => \Illuminate\Support\Str::replace("/", "_", base64_encode($status)),
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

                @if($loop_block->count > 1)
                    <tr>
                        @foreach($data['columns'] as $column)
                            <td class="p-1 fs-4 text-end border-top-0 fw-bold bg-light-info" style="padding-right: 12px!important">
                                @if($subtotal[$column] > 0)
                                    {{ tools()->cost_normalize(
                                     round($subtotal[$column] ?? 0),
                                     mode: 'M',
                                     precision: 2
                                  ) }}
                                @endif
                            </td>
                        @endforeach
                        <td class="p-1 pe-3 fs-2 text-end fw-bold bg-light-info" style="padding-right: 12px!important">
                            {{ tools()->cost_normalize(
                             round($subtotal->sum()),
                             mode: 'M',
                             precision: 2
                          ) }}
                        </td>
                    </tr>
                @endif
            @endforeach
            <tr>
                <td class="text-end p-1" colspan=2 style="background: #F0F0F0"></td>
                @foreach($data['columns'] as $column)
                    <td class="text-end monospace text-nowrap fs-2" style="font-weight: 600; background: #F0F0F0">
                        <a href="javascript:void(0);" onclick="javascript:box({href: '{{ route('dashboard.box.manager_status_quarter', [
                                    'r1' => 'all',
                                    'r2' => 'all',
                                    'column' => \Illuminate\Support\Str::replace("/", "_", base64_encode($column)),
                                 ]) }}'})" class="p-0">
                            {{ tools()->cost_normalize(
                                 round($column_total[$column] ?? 0),
                                 mode: 'M',
                                 precision: 2
                              ) }}
                        </a>
                    </td>
                @endforeach
                <td class="text-end monospace text-nowrap fs-2" style="font-weight: 600; background: #E5E5E5">
                    <a href="javascript:void(0);" onclick="javascript:box({href: '{{ route('dashboard.box.manager_status_quarter', [
                                    'r1' => 'all',
                                    'r2' => 'all',
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

            </tbody>

        </table>
    </div>
</div>
