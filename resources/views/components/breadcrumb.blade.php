<ol class="breadcrumb mb-0 mt-1">
    @foreach($data->getList() as $item)
        <x-breadcrumb_item :item="$item"></x-breadcrumb_item>
    @endforeach
</ol>
