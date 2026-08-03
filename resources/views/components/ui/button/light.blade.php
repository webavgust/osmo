<button type="button" {{ $attributes->class(['btn', 'waves-effect', 'waves-light', 'btn-light-'.$attributes['btn_type'], 'text-'.$attributes['btn_type']]) }} >
    {{ $slot }}
</button>
