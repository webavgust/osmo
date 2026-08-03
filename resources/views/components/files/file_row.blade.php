@if($type == 'new')
    <div class="file position-relative p-2 d-flex align-items-center">
        <i class="{{ $icon }} text-{{ $color }} me-1"></i>
        <span class="fs-3 me-1 flex-grow-1">{{ $file->realname }}</span>
        <span class="badge bg-secondary fs-1 p-1">{{ $size }} Мб</span>
        <x-ui.icon.solid icon="fa-delete-left" class="text-danger" delete="{{ $file->id }}"></x-ui.icon.solid>
    </div>
@else
    <div class="file position-relative p-2 d-flex align-items-center exists">
        <i class="{{ $icon }} text-{{ $color }} me-1"></i>
        <a href="{{ $file->url }}" target="_blank" class="fs-3 me-1 flex-grow-1 text-info fw-bold">
            {{ $file->filename }}
            @if($file->is_locked)
                <i class="fa-solid fa-lock text-secondary ms-1"></i>
            @endif
        </a>
        <span class="badge bg-secondary fs-1 p-1">{{ $size }} Мб</span>

        @if(empty($file->is_locked))
            <x-ui.icon.solid icon="fa-delete-left" class="text-danger" delete="{{ $file->id }}"></x-ui.icon.solid>
        @endif
    </div>
@endif
