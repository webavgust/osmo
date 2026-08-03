<?php

namespace App\Modules\Pub\UserNote\Controllers\Api;

use App\Modules\Pub\UserNote\Models\UserNote;
use App\Modules\Pub\UserNote\Repositories\UserNotesRepository;
use App\Modules\Pub\UserNote\Requests\CreateRequest;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;


class ApiUserNoteController
{
    private $repo;

    public function __construct()
    {
        $this->repo = new UserNotesRepository();
    }

    public function create(CreateRequest $request)
    {
        if($this->repo->createFromRequest($request)) {
            $template = View::make('components.dashboard.user.note_block', ['notes' => auth()->user()->notes()->get()]);
            return $template;
        } else {
            abort(404);
        }
    }

    public function edit(CreateRequest $request, UserNote $note)
    {
        if(!$note->canEdit()) abort(404);
        $this->repo->delete($note);
        return $this->create($request);
    }

    public function delete(UserNote $note)
    {
        if(!$note->canEdit()) abort(404);
        $this->repo->delete($note);

        return Response::json(['result' => 'success']);
    }

    public function favorite(UserNote $note)
    {
        if(!$note->canEdit()) abort(404);
        $note->update(['favorite' => !$note->favorite]);
        $note->refresh();

        return View::make('components.dashboard.user.note_row', ['note' => $note]);
    }

}
