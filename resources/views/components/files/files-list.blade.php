@foreach($files as $file)
    <x-files.file-row :file="$file"></x-files.file-row>
@endforeach
