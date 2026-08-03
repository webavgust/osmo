<button href="javascript:void(0);" onclick="javascript:sidebar({href:'{{$href}}'})" {{ $attributes->class(['btn', 'waves-effect', 'waves-light', 'btn-'.$attributes['btn_type']]) }} type="button">
    {{ $slot }}
</button>
