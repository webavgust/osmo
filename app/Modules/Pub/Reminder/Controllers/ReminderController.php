<?php

namespace App\Modules\Pub\Reminder\Controllers;

use App\Models\ModuleModel;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Http\Controllers\Controller;
use App\Modules\Pub\Reminder\Models\Reminder;
use App\Modules\Pub\Reminder\Repositories\ReminderRepository;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Services\Notificator\Notificator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\View;

class ReminderController extends Controller
{
    use HasBreadcrumb;
    private $repo;
    public function __construct()
    {
        $this->repo = new ReminderRepository();
        $this->breadcrumb_add('', 'Напоминания');
    }


    public function index()
    {
        $reminders = $this->repo->getReminders();
        return view('pub.reminder.index', [
            'reminders' => $reminders
        ]);
    }


    public function filter($module_name, $target_id)
    {
        $class = ModuleModel::getClassName($module_name);
        $object = $class::findOrFail($target_id);

        $reminders = $this->repo->getForObject($object);
        if($reminders->count() == 0) return \Redirect::route('reminder.index');

        return view('pub.reminder.filter', [
            'object' => $object,
            'reminders' => $reminders
        ]);
    }

    public function sidebar_add(\Illuminate\Http\Request $request)
    {
        $notificators = Notificator::getAvailableNotificators();
        $user_repo = new UserRepository();
        $subUsers = $user_repo->getSubUsers();
        if(!empty($request->module) && !empty($request->id))
        {
            $class = ModuleModel::getClassName($request->module);
            $obj = $class::findOrFail($request->id);
            $module = [
                'name' => $class::$module_name,
                'module' => $request->module,
                'id' => $request->id
            ];
        }
        $template = View::make('pub.reminder.sidebars.add', ['title' => 'Создание напоминания', 'notificators' => $notificators, 'params' => $request->all(), 'subUsers' => $subUsers, 'module' => $module ?? null]);
        return $template;
    }

    public function sidebar_edit(\Illuminate\Http\Request $request, Reminder $reminder)
    {
        if(!$reminder->canEdit()) abort(404);

        $notificators = Notificator::getAvailableNotificators();
        $user_repo = new UserRepository();
        $subUsers = $user_repo->getSubUsers();
        $subUsersCreated = $this->repo->getUserByGroup($reminder->group);

        if(!empty($reminder->target_type))
        {
            $class = $reminder->target_type;
            $obj = $class::findOrFail($reminder->target_id);
            $module = [
                'name' => $class::$module_name,
                'module' => $obj->getModuleSlug(),
                'id' => $reminder->target_id
            ];
        }
        if($reminder->canFullEdit()) {
            $template = View::make('pub.reminder.sidebars.full_edit', ['reminder' => $reminder, 'title' => 'Редактирование напоминания', 'notificators' => $notificators, 'params' => $request->all(), 'subUsers' => $subUsers, 'usersSelected' => $subUsersCreated, 'module' => $module ?? null]);
        } else {
            $subUsers = $subUsers->filter(fn($user, $index) => in_array($index, $subUsersCreated));
            $template = View::make('pub.reminder.sidebars.edit', ['reminder' => $reminder, 'title' => 'Редактирование напоминания', 'notificators' => $notificators, 'params' => $request->all(), 'subUsers' => $subUsers, 'module' => $module ?? null]);
        }
        return $template;
    }

    public function component_time()
    {
        $notificators = Notificator::getAvailableNotificators();
        $template = View::make('components.reminder.add_time', ['notificators' => $notificators] );
        return $template;
    }
}
