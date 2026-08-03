<?php

namespace App\Modules\Pub\Reminder\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModuleModel;
use App\Modules\Pub\Reminder\Models\Reminder;
use App\Modules\Pub\Reminder\Repositories\ReminderRepository;
use App\Modules\Pub\Reminder\Requests\CreateRequest;
use App\Modules\Pub\Reminder\Requests\EditTimeRequest;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Services\Notificator\Notificator;
use App\View\Components\Reminder\Row;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class ApiReminderController extends Controller
{
    private $repo;

    public function __construct()
    {
        $this->repo = new ReminderRepository();
    }

    public function create(CreateRequest $request, $uuid = null)
    {
        return $this->repo->createFromRequest($request, $uuid = null);
    }

    public function full_edit(Reminder $reminder, CreateRequest $request)
    {
        if(!$reminder->canFullEdit()) abort(404);
        $uuid = $reminder->group;
        $this->repo->forceDeleteByGroup($uuid);
        $this->create($request, $uuid);
        return Response::json(['result' => 'success']);
    }

    public function edit(Reminder $reminder, EditTimeRequest $request)
    {
        if($reminder->canFullEdit() || !$reminder->canEdit()) abort(404);
        // если есть полный доступ, просто пересоздадим с ид группы

        $this->repo->deleteFutureTimes($reminder->group);
        if($request->validated('time')) $this->repo->addTimes($reminder->group, $request->validated('time'));
        $this->repo->check($reminder->group);

        return Response::json(['result' => 'success']);
    }

    public function delete(Request $request, $group)
    {
        $this->repo->forceDeleteByGroup($group);
        return Response::json(['result' => 'success']);
    }
}
