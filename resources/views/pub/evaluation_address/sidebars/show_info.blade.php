@extends('components.sidebar.offcanvas-right')

@section('body')
    <x-ui.notification.light>
        {{ $address->address }}
    </x-ui.notification.light>
    <div class="card">
        <x-ui.card.card_table>
            <x-evaluation_address.info.table_row field="Город" value="{{ $address->city }}"></x-evaluation_address.info.table_row>
            <x-evaluation_address.info.table_row field="Улица" value="{{ $address->street }}"></x-evaluation_address.info.table_row>
            <x-evaluation_address.info.table_row field="Индекс" value="{{ $address->index }}"></x-evaluation_address.info.table_row>
            <x-evaluation_address.info.table_row field="Дом" value="{{ $address->house }}"></x-evaluation_address.info.table_row>
            <x-evaluation_address.info.table_row field="Корпус" value="{{ $address->housing }}"></x-evaluation_address.info.table_row>
            <x-evaluation_address.info.table_row field="Строение" value="{{ $address->building }}"></x-evaluation_address.info.table_row>
            <x-evaluation_address.info.table_row field="Подъезд" value="{{ $address->entrance }}"></x-evaluation_address.info.table_row>
            <x-evaluation_address.info.table_row field="Этаж" value="{{ $address->floor }}"></x-evaluation_address.info.table_row>
            <x-evaluation_address.info.table_row field="Домофон" value="{{ $address->intercom }}"></x-evaluation_address.info.table_row>
            <x-evaluation_address.info.table_row field="Помещение" value="{{ $address->number }}"></x-evaluation_address.info.table_row>

        </x-ui.card.card_table>
    </div>
@endsection
