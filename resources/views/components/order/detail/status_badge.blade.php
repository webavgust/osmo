@if($order->is_archived)
    <x-ui.badge.default type="danger">Архивная</x-ui.badge.default>
@elseif($order->is_finished)
    <x-ui.badge.default type="secondary">Завершённая</x-ui.badge.default>
@else
    <x-ui.badge.default type="success">Незавершённая</x-ui.badge.default>
@endif
