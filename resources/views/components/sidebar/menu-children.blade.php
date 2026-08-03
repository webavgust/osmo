@if($tree)
    @foreach($tree as $item)
        <x-sidebar.menu-item :item="$item" level="{{$level}}"></x-sidebar.menu-item>
    @endforeach
@endif
