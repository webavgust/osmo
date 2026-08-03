<a href="javascript:void(0);" onclick="javascript:box({href:'{{$href}}'})" type="button" {{ $attributes->class(['btn', 'waves-effect', 'waves-light', 'btn-'.$attributes['btn_type'] => !$attributes['outline'], 'btn-outline-'.$attributes['btn_type'] => $attributes['outline']]) }}>
    {{ $slot  }}
</a>
