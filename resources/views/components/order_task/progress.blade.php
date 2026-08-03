
<div class="progress w-100" style="height: {{ $short ? 5 : 13 }}px;">
    @foreach($blocks as $block)
        <div class="progress-bar bg-{{ $block['color'] }} cursor-help" role="progressbar" style="width: {{ $block['width'] }}%" aria-valuenow="5" aria-valuemin="0" aria-valuemax="100"
            title="{{ $block['name'] }} ({{ $block['count'] }} / {{ $all }}) = {{ $block['percent'] }}%"
        >
            @unless($short)
                <span class="name py-1 font-10">{{ $block['name'] }}</span>
                <span class="percent font-10">{{ $block['count'] }} / {{ $all }} = {{ $block['percent'] }}%</span>
            @endunless
        </div>
    @endforeach

    <div class="flex-grow-1 text-center align-items-center d-inline-flex justify-content-center cursor-help" style="font-size: 9px"
         title="+ {{ $blank }}"
    >
        @if(!$short && $blank)
            <span>+ {{ $blank }}</span>
        @endif
    </div>
</div>
