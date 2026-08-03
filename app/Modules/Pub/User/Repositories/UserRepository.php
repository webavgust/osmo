<?php


namespace App\Modules\Pub\User\Repositories;


use App\Modules\Pub\Access\Models\Access;
use App\Modules\Pub\Access\Services\AccessUserService;
use App\Modules\Pub\EducationApplication\Models\EducationApplication;
use App\Modules\Pub\EducationTask\Models\EducationTask;
use App\Modules\Pub\Evaluation\Models\Evaluation;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Services\OrderListFilterService;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


class UserRepository
{

    /**
     * Получить данные для таблицы
     *
     * @param Request $request
     * @return array
     */
    public function getTable(Request $request)
    {
        $builder = User::where('id', '>', 0);
        if (!empty($request->input('sort')) && !empty($request->input('order'))) {
            $builder->orderBy($request->input('sort'), $request->input('order'));
        } else {
            $builder->orderBy('active', 'desc')
                ->orderBy('last_name', 'asc');
        }

        if (!empty($request->get('group_id')))
            $builder->whereHas('groups', function ($query) use ($request) {
                $query->where('id', $request->get('group_id'));
            });

        if (!empty($request->get('department_id')))
            $builder->whereHas('departments', function ($query) use ($request) {
                $query->where('id', $request->get('department_id'));
            });

        $count_filtered = $count = $builder->count();
        # Search
        if (!empty($request->input('search'))) {
            $builder->search($request->input('search'));
            $count_filtered = $builder->count();
        }

        # Paginate
        if ($request->input('limit'))
            $builder->limit($request->input('limit'));

        if ($request->input('offset'))
            $builder->skip($request->input('offset'));

        $builder
            ->select(User::getShowFields())
            ->withCount('groups', 'departments')
            ->with('groups', 'departments');

        return [
            'count' => $count,
            'count_filter' => $count_filtered,
            'rows' => $builder->get()
        ];
    }

    /**
     * Найти по ID
     *
     * @param $id
     * @return User|User[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|\LaravelIdea\Helper\App\Modules\Pub\User\Models\_IH_User_C|null
     */
    public static function getById($id)
    {
        if(is_array($id)) {
            return User::whereIn('id', $id)->get();
        } else {
            return User::find($id);
        }
    }

    /**
     * Получить подчиненных
     *
     * @param User|null $user
     * @return User[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection|\LaravelIdea\Helper\App\Modules\Pub\User\Models\_IH_User_C
     */
    public function getSubUsers(User $user = null)
    {
        if (empty($user))
            $user = auth()->user();

        if ($user->isAdmin())
            return User::orderBy('last_name', 'asc')->get()->keyBy('id');

        return collect($user->sub_users)->push($user)->keyBy('id');
    }

    /**
     * Получить всех
     *
     * @return User[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection|\LaravelIdea\Helper\App\Modules\Pub\User\Models\_IH_User_C
     */
    public static function getAll()
    {
        return User::select(User::$showFields)
            ->whereNot('is_hidden', 1)
            ->orderBy('full_name', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->keyBy('id');
    }

    public static function getAllWithTrashed()
    {
        return User::select(User::$showFields)
            ->orderBy('full_name', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->keyBy('id');
    }

    public static function getProposalAuthors()
    {
        return User::whereHas('proposals')
            ->orderBy('full_name', 'asc')
            ->orderBy('name', 'asc')
            ->withCount('proposals')
            ->get()
            ->keyBy('id');
    }

}
