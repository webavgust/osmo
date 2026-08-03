<div class="btn-group ms-2" role="group">
    <x-ui.a.light href="{{ route('logger.detail', ['module' =>$logger['module'], 'id' => $logger['target_id']]) }}" target="_blank" btn_type="danger" class="text-danger py-1">
        <x-ui.icon.regular icon="fa-timeline" class="fill-white text-danger"></x-ui.icon.regular>
    </x-ui.a.light>
</div>
