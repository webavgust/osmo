<div class="tr">
    <span class="th">
        @if(!empty($file))
            <a href="{{ $file->url }}" class="file" title="Сгенерировано: {{ _datetime($file->created_at) }}">
                <x-ui.icon.solid icon="fa-arrow-down-to-line" class="me-1"></x-ui.icon.solid>
                {{ $report->name }}
            </a>
            @if(!empty($uploaded))
                <span class="ms-1">→</span>
                <a href="{{ $uploaded->url }}" target="_blank" title="Сгенерировано: {{ _datetime($uploaded->created_at) }}">
                    <x-ui.badge.default type="danger" class="ms-1">
                        <x-ui.icon.solid icon="fa-scanner-image"></x-ui.icon.solid>
                    </x-ui.badge.default>
                </a>
            @endif
        @else
            <x-ui.icon.light icon="fa-clock" class="me-2"></x-ui.icon.light>
            {{ $report->name }}
        @endif
    </span>
    <span class="td">
        @if($task_course->canGenerateReports())
            <x-ui.a.box href="{{ route('report.box_generate', [$task_course, $report->slug]) }}" btn_type="info" class="py-0 px-2" style="width: 50px"  >
                <x-ui.icon.light icon="fa-chevrons-right" class="font-12"></x-ui.icon.light>
            </x-ui.a.box>

            @if(!empty($file))
                <x-ui.a.box href="{{ route('report.box_upload', [$task_course, $report->slug]) }}" btn_type="success" class="py-0 px-2" style="width: 50px">
                    <x-ui.icon.light icon="fa-cloud-arrow-up" class="font-12"></x-ui.icon.light>
                </x-ui.a.box>
            @endif
        @endif
    </span>
</div>
