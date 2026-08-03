<div class="tr" field="{{ $row['field'] }}">
    <span class="th">
        <a href="javascript:filter('{{ $row['field'] }}')">{{ $row['name'] }}</a>
    </span>
    <span class="td">
        <div class="ms-2">
            <x-logger.cell :value="$row['new']"></x-logger.cell>
        </div>
    </span>
</div>



