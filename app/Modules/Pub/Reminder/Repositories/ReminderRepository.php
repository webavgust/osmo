<?php

namespace App\Modules\Pub\Reminder\Repositories;

use App\Jobs\Reminders\Remind;
use App\Models\ModuleModel;
use App\Modules\Pub\Reminder\Models\Reminder;
use App\Modules\Pub\Reminder\Requests\CreateRequest;
use App\Modules\Pub\ReminderTime\Models\ReminderTime;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Services\Notificator\Notificator;
use App\View\Components\Reminder\Row;
use Carbon\Carbon;
use Illuminate\Foundation\Bus\DispatchesJobs;

class ReminderRepository
{
    use DispatchesJobs;
    public function create($data, $times, ModuleModel $model = null)
    {
        // создаём напоминание
        $user = $data['user'] ?? auth()->user();
        $uuid = $data['uuid'] ?? \Str::uuid();
        $remind = new Reminder();
        $remind->fill([
            'title' => $data['title'],
            'message' => $data['message'] ?? null,
            'group' => $uuid,
            'hide' => $data['hide']  ?? false
        ]);
        if(!empty($model))
            $remind->target()->associate($model);

        $remind
        ->user()->associate($user)
        ->save();

        $remind
        ->creator()->associate(auth()->user())
        ->save();


        foreach($times as $time) {
            $reminder_time = new ReminderTime();
            $reminder_time->fill(collect($time)->only(['notify_at', 'notificators'])->toArray());
            $reminder_time->reminder()->associate($remind)->save();


            $job = (new Remind($reminder_time))->onQueue('database')->delay($reminder_time->notify_at);
            $job_id = $this->dispatch($job);

            $reminder_time->update(['job_id' => $job_id]);
        }

        return $remind;
    }

    public function createFromRequest(CreateRequest $request, $uuid = null)
    {
        $times = [];
        $notificators = collect(Notificator::getAvailableNotificators());
        foreach($request->input('time') as $time) {
            $times[] = [
                'notify_at' => Carbon::createFromTimestamp(strtotime($time['date'].' '.$time['time'])),
                'notificators' => $notificators->whereIn('type', $time['notificator'])->pluck('class')->toArray()
            ];
        }

        if(!empty($request->validated('module'))) {
            $class = ModuleModel::getClassName($request->validated('module')['name']);
            $module_attach = $class::findOrFail($request->validated('module')['id']);
        }

        if(count($request->input('user') ?? []) > 0) {
            if(empty($uuid)) $uuid = \Str::uuid();
            $user_repo = new UserRepository();
            foreach($request->input('user') as $user_id) {
                if(!empty($user_repo->getSubUsers()->keyBy('id')[$user_id])) {
                    $reminder = $this->create($request->only(['title', 'message', 'hide']) + ['user' => $user_repo->getSubUsers()->keyBy('id')[$user_id], 'uuid' => $uuid], $times);
                    if(!empty($module_attach))
                        $reminder->target()->associate($module_attach)->save();

                    if($user_id == auth()->id())
                        $created = $reminder;
                }
            }
        } else {
            $created = $this->create($request->only(['title', 'message']), $times);
            if(!empty($module_attach))
                $created->target()->associate($module_attach)->save();
        }

        if(!empty($created)) {
            $row = new Row($created);
            return $row->render()->with($row->data());
        }
    }
    public function getReminders(User $user = null)
    {
        if(empty($user)) $user = auth()->user();

        return Reminder::hasAccess($user)->groupBy('group')->orderBy('id', 'desc')->get();
    }


    public function getForObject(ModuleModel $object)
    {
        return $object->reminders;
    }

    public function forceDelete(Reminder $reminder)
    {
        if($reminder->canDelete()) {
            Reminder::where('group', $reminder->group)->forceDelete();
        }
    }

    public function getByGroup($group, User $user = null)
    {
        if(empty($user)) $user = auth()->user();
        return Reminder::hasAccess($user)->where('group', $group)->get();
    }

    public function getUserByGroup($group)
    {
        return Reminder::where('group', $group)->pluck('user_id')->toArray();
    }

    public function forceDeleteByGroup($group)
    {
        $reminders = $this->getByGroup($group);
        foreach($reminders as $reminder)
        {
            if($reminder->canDelete())
                $reminder->forceDelete();
        }
    }
    public function deleteFutureTimes($group)
    {
        $reminders = $this->getByGroup($group);
        foreach($reminders as $reminder)
        {
            $reminder->reminder_times()->where('notified', 0)->get()->each->delete();
        }
    }

    public function addTimes($group, $times_source)
    {
        $times = [];
        $notificators = collect(Notificator::getAvailableNotificators());
        foreach($times_source as $time) {
            $times[] = [
                'notify_at' => Carbon::createFromTimestamp(strtotime($time['date'].' '.$time['time'])),
                'notificators' => $notificators->whereIn('type', $time['notificator'])->pluck('class')->toArray()
            ];
        }

        $reminders = $this->getByGroup($group);
        foreach($reminders as $reminder) {
            foreach($times as $time) {
                $reminder_time = new ReminderTime();
                $reminder_time->fill(collect($time)->only(['notify_at', 'notificators'])->toArray());
                $reminder_time->reminder()->associate($reminder)->save();

                $job = (new Remind($reminder_time))->onQueue('database')->delay($reminder_time->notify_at);
                $job_id = $this->dispatch($job);
                $reminder_time->update(['job_id' => $job_id]);
            }
        }
        return true;
    }

    // проверка уведомлений
    public function check($group)
    {
        foreach(Reminder::where('group', $group)->get() as $reminder)
        {

            if(!$reminder->hasFutureJobs()) {
                $reminder->delete();
            }
        }
    }
}
