<div>
    <div class="d-flex justify-content-center">
        <x-ui.a.box href="{{ route('sample-works.box_bad_count', $asset) }}"
                    class="asset_check btn btn-danger text-nowrap py-0 px-1 me-1 fs-2">
            <x-ui.icon.regular icon="fa-xmark"
                               class="me-1"/>
            Ошибка
        </x-ui.a.box>

        <x-ui.button.default btn_type="success" onclick="javascript:asset_check({{ $asset->id }})"
                             class="asset_check text-nowrap py-0 px-1 fs-2">
            <x-ui.icon.regular icon="fa-check"
                               class="me-1"/>
            ОК
        </x-ui.button.default>
    </div>
</div>
