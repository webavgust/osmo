
@unless($item->isLast())
    <li class="breadcrumb-item">
        @if(!$item->isMuted() && !empty($item->getLink()))
            <a href="{{ $item->getLink() }}">{{ $item->getName() }}</a>
        @else
            {{ $item->getName() }}
        @endunless
    </li>
@else
    <li class="breadcrumb-item active">{{ $item->getName() }}</li>
@endunless


