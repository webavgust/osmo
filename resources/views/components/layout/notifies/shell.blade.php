<ul class="list-style-none">
    <li>
        <div class="border-bottom rounded-topp-1 p-2">
            <div class="mb-0 font-weight-medium justify-content-between d-flex">
                <span class="fs-2">Уведомления (<span class="count">{{ $notifies->count() }}</span>)</span>
                <x-ui.button.default btn_type="danger" onclick="javascript:notify_truncate()"
                                     class="p-1 pt-0 pb-0 fs-2">Очистить
                </x-ui.button.default>
            </div>
        </div>
    </li>
    <li>
        <div id="message_center" class="message-center notifications position-relative ps-container ps-theme-default ps-active-y"
             style="height: auto; max-height: 350px"
             data-count="{{ $notifies->count() }}">
            @foreach($notifies as $notify)
            <!-- Message -->
                <x-layout.notifies.notify :notify="$notify"></x-layout.notifies.notify>
            @endforeach
            <div class="ps-scrollbar-x-rail" style="left: 0px; bottom: 0px;">
                <div class="ps-scrollbar-x" tabindex="0" style="left: 0px; width: 0px;"></div>
            </div>
            <div class="ps-scrollbar-y-rail" style="top: 0px; right: 3px;">
                <div class="ps-scrollbar-y" tabindex="0" style="top: 0px; height: 100%"></div>
            </div>
        </div>
    </li>
</ul>
<script>
    $(".message-center").perfectScrollbar({
        wheelPropagation: !0,
    });
</script>
