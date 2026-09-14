<ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('desktop.index') }}" class="text-muted text-hover-primary">
            <i class="fa-light fa-house fs-8"></i>
        </a>
    </li>
    {{-- patch v30: первый пункт цепочки — служебный «Рабочий стол» из конструктора Breadcrumb,
         его заменяет домик; страница стола добавляет свой пункт сама --}}
    @foreach($data->getList()->slice(1) as $item)
        <li class="breadcrumb-item">
            <i class="fa-light fa-angle-right fs-8 text-muted mx-n1"></i>
        </li>
        <x-breadcrumb_item :item="$item"></x-breadcrumb_item>
    @endforeach
</ul>
