<?php


namespace App\Modules\Pub\User\Services;


use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\UserDepartment\Models\UserDepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserService
{
    private $repo;

    public function __construct()
    {
        $this->repo = new UserRepository();
    }

    /**
     * Получить по логину
     *
     */
    public function getByLogin($login)
    {
        $user = User::where('login', $login)->orWhere("email", $login)->first();

        return $user;
    }

    /**
     * Флаг: заблокировано?
     *
     * @param User $user
     * @return mixed
     */
    public function isLocked(User $user)
    {
        return $user->isLocked();
    }

    /**
     * Данные для таблицы
     *
     * @param Request $request
     * @return array
     */
    public function tableDefault(Request $request)
    {
        $data = $this->repo->getTable($request);
        $data['rows']->map(function ($item) {
            $item->personal_birthday = !empty($item->personal_birthday) ? Carbon::createFromFormat("Y-m-d", $item->personal_birthday)->format('d.m.Y') : null;
            $item->work_department = Str::ucfirst($item->work_department);
            $item->work_position = Str::ucfirst($item->work_position);
        });

        return $data;
    }

    /**
     * Группировка по подразделению
     *
     * @param $users
     * @return \App\Models\ModuleModel[]|UserDepartment[]|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Models\_IH_ModuleModel_C|\LaravelIdea\Helper\App\Models\_IH_ModuleModel_QB[]|\LaravelIdea\Helper\App\Modules\Pub\UserDepartment\Models\_IH_UserDepartment_C|\LaravelIdea\Helper\App\Modules\Pub\UserDepartment\Models\_IH_UserDepartment_QB[]
     */
    public function groupByDepartment($users)
    {
        // TODO: оптимизировать

        // получим модель + связь
        $result = UserDepartment::whereHas('users', function ($query) use ($users) {
            $query->whereIn('users.id', $users->pluck('id'));
        })->with('users', function ($builder) use ($users) {
            $builder->whereIn('id', $users->pluck('id'));
        })->get();

        return $result;
    }




    public function analytic_bind(User $user, Request $request)
    {
        $set = $request->input('set');
        $user->lab_objects()->sync($set);

        return true;
    }

}
