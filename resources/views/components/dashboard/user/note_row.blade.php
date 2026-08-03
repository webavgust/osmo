<div class="single-note-item @if($note->favorite) note-important @endif" id="{{ $note->id }}" type="note">
    <div class="card card-body mb-0  mt-1">
        <span class="side-stick"></span>
        <div class="d-flex">
            <h5 class="note-title text-truncate w-75 mb-0">
                {{ $note->title }}
            </h5>
            <div class="ms-auto">
                <div class="dropdown-action">
                    <div class="dropdown todo-action-dropdown">
                        <button class=" btn btn-link text-dark p-1 text-decoration-none todo-action-dropdown" type="button" id="more-action-2" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="more-action-2">
                            <x-ui.a.sidebar href="{{ route('user-notes.sidebar_edit', $note) }}" class="edit dropdown-item"><i class="fas fa-edit text-info me-1"></i> Редактировать</x-ui.a.sidebar>
                            <a class="remove dropdown-item" href="javascript:note_delete({{ $note->id }})"><i class="far fa-trash-alt text-danger me-2"></i>Удалить</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="note-date fs-2 text-muted">{{ _date($note->created_at) }}</p>
        @if(!empty($note->text))
            <div class="note-content">
                {!! nl2br($note->text) !!}
            </div>
        @endif
        <div class="d-flex align-items-center mt-2">
            <a href="javascript:note_favorite({{ $note->id }})" class="link me-1"><i class="@if($note->favorite) fa-solid text-warning @else fa-light text-secondary @endif  fa-star favourite-note "></i></a>
            <div class="ms-auto">
                @if(!empty($note->reminder()))
                    @include('components.reminder.header', ['reminder' => $note->reminder()])
                @endif
            </div>
        </div>
    </div>
</div>
