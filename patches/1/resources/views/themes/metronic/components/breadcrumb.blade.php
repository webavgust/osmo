<ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 mb-1">
    <li class="breadcrumb-item text-gray-600 lh-1">
        <a href="/" class="text-gray-600 text-hover-primary">
            <i class="ki-duotone ki-home fs-6 text-gray-500"></i>
        </a>
    </li>
    @foreach($data->getList() as $item)
        <li class="breadcrumb-item">
            <i class="ki-duotone ki-right fs-8 text-gray-500 mx-n1 lh-0"></i>
        </li>
        <x-breadcrumb_item :item="$item"></x-breadcrumb_item>
    @endforeach
</ul>
