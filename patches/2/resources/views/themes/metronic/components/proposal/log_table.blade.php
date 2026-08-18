<div class="card">
    <div class="card-body p-0" id="proposal_table">
        <div class="
              invoice-header
              d-flex
              align-items-center
              border-bottom
              px-4 py-3
            " style="padding-bottom: 12px!important">
            <h3 class="fw-semibold text-uppercase mb-0">
                Журналирование
            </h3>

            <x-ui.a.box href="{{ route('log.box_fast', [$proposal, $proposal->iteration]) }}">
                <i class="fas fa-add text-warning"></i>
            </x-ui.a.box>

        </div>

        @if($proposal->logs->isNotEmpty())
            <div class="p-3">
                <table id="table-summary" class="table no-wrap w-100">
                    <tr class="caption">
                        <th class="text-center text-dark fw-bold" valign="top" width="30">Дата</th>
                        <th class="text-center text-dark fw-bold" valign="top">Запись</th>
                    </tr>

                    @foreach($proposal->logs as $log)
                        <tr>
                            <td class="align-top">{{ _date($log->date) }}</td>
                            <td class="align-top">
                                {!! $log->text !!}
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @else
            <div class="px-4 py-3">Тут пока нет записей</div>
        @endif
    </div>
</div>
