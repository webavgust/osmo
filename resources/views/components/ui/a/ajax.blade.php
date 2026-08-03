<a href="javascript:void(0);" onclick="javascript:ajax('{{ $uuid }}')" {{ $attributes->class(['btn', 'waves-effect', 'waves-light', 'btn-'.$attributes['btn_type']]) }}>
    {{ $slot  }}
</a>
<script>
    window.{{ $uuid }} = @json($params)
</script>
