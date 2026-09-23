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

        $data = [
            'title' => $request->validated('title'),
            'text' => $request->validated('text'),
            'favorite' => $request->boolean('favorite'),
        ];
        if ($request->has('done')) {
            $data['done'] = $request->boolean('done');
        }
        $this->repo->update($note, $data);

        return View::make('components.dashboard.user.note_block', ['notes' => auth()->user()->notes()->get()]);
    }

    /**
     * Отметить задачу выполненной или вернуть в работу (виджет «Блокнот»)
     *
     * @param UserNote $note
     * @return \Illuminate\Http\JsonResponse
     */
    public function done(UserNote $note)
    {
        if(!$note->canEdit()) abort(404);
        $this->repo->toggleDone($note);

        return Response::json(['result' => 'success', 'done' => $note->isDone()]);
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
