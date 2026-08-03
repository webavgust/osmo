@can('documents_number_change')
    <x-ui.a.sidebar href="{{route('document-number.edit', $row)}}">
        <span class="mb-1 badge bg-light text-dark" doc-number="{{$row->number}}">{!! _docnumber($row->number) !!}</span>
    </x-ui.a.sidebar>
@else
    <span class="mb-1 badge bg-light text-dark" doc-number="{{$row->number}}">{!! _docnumber($row->number) !!}</span>
@endcan
