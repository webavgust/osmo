<?php

namespace App\Modules\Pub\Dashboard\Services;

use App\Modules\Pub\Dashboard\Models\Dashboard;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\User\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    public function getAvailable()
    {
        $arRet = collect();
        foreach(Dashboard::orderBy('sort', 'asc')->get() as $dashboard)
        {
            if(empty($dashboard->access) || _can($dashboard->access)) {
                $arRet->push($dashboard);
            }
        }

        return $arRet;
    }


    public function setDate(Carbon $start, Carbon $end)
    {
        session()->put('dashboard_date', [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d')
        ]);
        session()->save();
    }

    public function setMode(string $string, $params = [])
    {
        $user_repo = new UserRepository();
        $save = [
            'mode' => $string,
            'data' => Dashboard::MODES[$string]
        ];
        if(!_can('users_have_sub'))
            $string = 'self';

        switch($string) {
            case 'self':
                $save['users'] = collect([auth()->user()]);
                break;
            case 'all':
                $save['users'] = $user_repo->getSubUsers();
                break;
            case 'select':
                $save['users'] = User::findMany($params['users']);
                break;
        }

        session()->put('dashboard_user_mode', $save);
        session()->save();
    }

    public function setSubUser($user_id)
    {

        if(Auth::id() != $user_id && !Auth::user()->sub_users->contains($user_id))
            abort(404);

        session()->put('dashboard_sub_user', $user_id);
        session()->save();
    }

    public function initDate()
    {
        $this->setDate(
            Carbon::now()->day > 25 ? Carbon::now()->startOfMonth() : Carbon::now()->previous('month')->startOfMonth(),
            Carbon::now()->endOfDay()
        );
    }
}
