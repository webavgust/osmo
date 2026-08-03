<?php

namespace App\Modules\Pub\UserNote\Controllers;

use App\Models\ModuleModel;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Reminder\Models\Reminder;
use App\Modules\Pub\Reminder\Repositories\ReminderRepository;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\UserNote\Models\UserNote;
use App\Modules\Pub\UserNote\Repositories\UserNotesRepository;
use App\Services\Notificator\Notificator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;

class UserNoteController extends Controller
{
    use HasBreadcrumb;
    private $repo;
    public function __construct()
    {
        $this->repo = new UserNotesRepository();
        $this->breadcrumb_add('', 'Заметки');
    }

    public function sidebar_add(\Illuminate\Http\Request $request)
    {
        $template = View::make('pub.user_note.sidebars.add', ['title' => 'Создание заметки', 'user' => auth()->user()]);
        return $template;
    }

    public function sidebar_edit(\Illuminate\Http\Request $request, UserNote $note)
    {
        if(!$note->canEdit()) abort(404);
        $template = View::make('pub.user_note.sidebars.edit', ['note' => $note, 'title' => 'Редактирование заметки', 'user' => auth()->user()]);

        return $template;
    }

}
