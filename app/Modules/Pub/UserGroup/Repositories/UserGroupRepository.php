<?php


namespace App\Modules\Pub\UserGroup\Repositories;


use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Services\OrderListFilterService;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Illuminate\Http\Request;

class UserGroupRepository
{
    /**
     * Получить пользователей для согласования
     *
     * @param int $group_id
     * @return User[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\HigherOrderCollectionProxy|\LaravelIdea\Helper\App\Modules\Pub\User\Models\_IH_User_C|mixed
     */
    public static function getAgreementers()
    {
        return User::all();
    }

    /**
     * Получить пользователей для группы
     *
     * @param int $group_id
     * @return User[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\HigherOrderCollectionProxy|\LaravelIdea\Helper\App\Modules\Pub\User\Models\_IH_User_C|mixed
     */
    public static function getUsers(int $group_id)
    {
        return UserGroup::find($group_id)->users;
    }


    /**
     * Получить фин.директоров
     *
     * @return User[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\HigherOrderCollectionProxy|\LaravelIdea\Helper\App\Modules\Pub\User\Models\_IH_User_C|mixed
     */
    public static function getFinSupervisors()
    {
        return UserGroup::find(UserGroup::GROUP_FIN_SUPERVISOR)->users;
    }

    /**
     * Получить все группы с счётчиком пользователей
     *
     * @return \App\Models\ModuleModel[]|UserGroup[]|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection|\LaravelIdea\Helper\App\Models\_IH_ModuleModel_C|\LaravelIdea\Helper\App\Modules\Pub\UserGroup\Models\_IH_UserGroup_C
     */
    public function getAllWithUsersCount()
    {
        return UserGroup::withCount('users')->orderBy('active', 'desc')->orderBy('name', 'asc')->get();
    }
}
