<?php


namespace App\Modules\Pub\UserDepartment\Repositories;


use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Services\OrderListFilterService;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserDepartment\Models\UserDepartment;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Illuminate\Http\Request;

class UserDepartmentRepository
{
    /**
     * Получить все с счётчиком пользователей
     *
     * @return \App\Models\ModuleModel[]|UserDepartment[]|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection|\LaravelIdea\Helper\App\Models\_IH_ModuleModel_C|\LaravelIdea\Helper\App\Modules\Pub\UserDepartment\Models\_IH_UserDepartment_C
     */
    public function getAllWithUsersCount()
    {
        return UserDepartment::withCount('users')->orderBy('active', 'desc')->orderBy('name', 'asc')->get();
    }
}
