<?php

namespace App\Modules\Pub\UserNote\Repositories;

use App\Jobs\Reminders\Remind;
use App\Models\ModuleModel;
use App\Modules\Pub\Reminder\Models\Reminder;
use App\Modules\Pub\ReminderTime\Models\ReminderTime;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\UserNote\Models\UserNote;
use App\Modules\Pub\UserNote\Requests\CreateRequest;
use App\Services\Notificator\Notificator;
use App\View\Components\Reminder\Row;
use Carbon\Carbon;
use Illuminate\Foundation\Bus\DispatchesJobs;

class UserNotesRepository
{

    public function create($data)
    {
        // создаём напоминание
        $user = auth()->user();

        $note = new UserNote();
        $note->fill([
            'title' => $data['title'],
            'text' => $data['text'] ?? null,
            'favorite' => $data['favorite']  ?? false
        ]);

        $note->user()->associate($user)
        ->save();

        return $note;
    }

    public function createFromRequest(CreateRequest $request)
    {
        $created = $this->create($request->only(['title', 'text', 'favorite']));
        return true;
    }


    /**
     * Правка заметки на месте: id, дата создания, напоминание и отметка «выполнено»
     * сохраняются (раньше правка удаляла заметку и создавала новую)
     *
     * @param UserNote $note
     * @param array $data ['title', 'text', 'favorite', 'done' — необязательно]
     * @return UserNote
     */
    public function update(UserNote $note, array $data): UserNote
    {
        $note->fill([
            'title' => $data['title'],
            'text' => $data['text'] ?? null,
            'favorite' => (bool) ($data['favorite'] ?? false),
        ]);

        // флажок «Задача выполнена» есть только в сайдбаре правки; время первой отметки не сбиваем
        if (array_key_exists('done', $data)) {
            $note->done_at = $data['done'] ? ($note->done_at ?? now()) : null;
        }

        $note->save();

        return $note;
    }

    /**
     * Переключить отметку «выполнено»
     *
     * @param UserNote $note
     * @return UserNote
     */
    public function toggleDone(UserNote $note): UserNote
    {
        $note->done_at = $note->done_at ? null : now();
        $note->save();

        return $note;
    }

    public function delete(UserNote $note)
    {
        if($note->canEdit()) {
            $note->forceDelete();
        }
    }

}
