<?php


namespace App\Modules\Pub\UserGroup\Services;


use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\UserGroup\Repositories\UserGroupRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class UserGroupService
{
    private $repo;

    public function __construct()
    {
        $this->repo = new UserGroupRepository();
    }

}
