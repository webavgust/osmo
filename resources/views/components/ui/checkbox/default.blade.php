<div class="form-check">
    <input type="checkbox" id="flexCheckChecked" @checked((bool)($checked ?? false)) {{ $attributes->class(['form-check-input', $attributes['type'] ?? ''])->except(['checked']) }}>
    @if(!empty($slot) && trim($slot) !== '')
        <label class="form-check-label" for="flexCheckChecked">
            {{ $slot }}
        </label>
    @endif
</div>
