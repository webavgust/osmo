@unless($item->isLast())
    <li class="breadcrumb-item text-gray-600 lh-1">
        @if(!$item->isMuted() && !empty($item->getLink()))
            <a href="{{ $item->getLink() }}" class="text-gray-600 text-hover-primary">{{ $item->getName() }}</a>
        @else
            {{ $item->getName() }}
        @endunless
    </li>
@else
    <li class="breadcrumb-item text-gray-800 fw-bold lh-1">{{ $item->getName() }}</li>
@endunless
