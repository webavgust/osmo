<div class="note-has-grid">
    <div class="mb-2">
        <div class="fs-2 fw-bold mb-1">Открыть журнал:</div>
        <div class="d-flex justify-content-between">
            @if($rows->isNotEmpty())
                <x-ui.a.outline href="{{ route('log.day', $date->format('d.m.Y')) }}" btn_type="secondary" class="w-100 mb-2">
                    <x-ui.icon.regular icon="fa-timeline" class="me-2"/>
                    за день
                </x-ui.a.outline>
            @endif
            <x-ui.a.outline href="{{ route('log.all') }}" btn_type="info" class="w-100 mb-2 ms-2">
                <x-ui.icon.regular icon="fa-timeline" class="me-2"/>
                за всё время
            </x-ui.a.outline>
        </div>
    </div>

    @foreach($rows as $row)
        <a class="log_once card card-body single-note-item note-business pt-2 pb-1 px-3 pe-2 mb-0" onclick="javascript:box({ href: '{{ route('log.box_detail', $row) }}' })">
            <span class="side-stick"></span>

            <div class="position-relative">
                <h5 class="note-title text-truncate mb-0" data-noteheading="Go for lunch">
                    {{ $row->company->name }}
                </h5>

                <span class="
                        btn btn-light-secondary btn-circle btn-sm
                        d-inline-flex
                        align-items-center
                        justify-content-center
                        position-absolute
                        p-1
                        fs-3
                      " style="top: -5px; right: -6px; width: 24px; height: 24px;">
                    {{ $rows->count() - $loop->iteration + 1 }}
                </span>
            </div>

            @if(!empty($row->proposal))
                <div class="fs-2 mt-1 text-info">
                    {{ $row->proposal->name }}
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-end">
                <div class="note-date fs-2 mt-1 fw-bold text-success">{{ $row->created_at->isToday() ? $row->created_at->format("H:i") : $row->created_at->format("d.m.Y") }}</div>
                <x-ui.icon.regular icon="fa-paperclip" class="text-light-secondary"/>
            </div>
        </a>
    @endforeach
</div>

