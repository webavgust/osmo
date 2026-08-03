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


    public function delete(UserNote $note)
    {
        if($note->canEdit()) {
            $note->forceDelete();
        }
    }

}
