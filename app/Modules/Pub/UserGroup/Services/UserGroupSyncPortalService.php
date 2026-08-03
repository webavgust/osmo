<?php


namespace App\Modules\Pub\UserGroup\Services;

use App\Interfaces\PortalRepositoryInterface;
use App\Interfaces\PortalSyncInterface;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use App\Modules\Pub\UserGroup\Repositories\UserGroupPortalRepository;
use App\Services\Portal\Repository\AbstractPortalService;

class UserGroupSyncPortalService extends AbstractPortalService implements PortalSyncInterface
{
    private  $repo;
    protected $rules = [
        'id' => 'required|int',
        'active' => 'bool',
        'name' => 'string',
        'description' => 'string|nullable',
    ];

    public function __construct()
    {
        $this->repo = new UserGroupPortalRepository();
    }
    public function syncAll(): void
    {
        $rows = $this->repo->getAll();
        foreach ($rows as $row) {
            if(!$this->validate($row)) continue;

            $group = UserGroup::find($row['id']);
            if (empty($group)) {
                $group = new UserGroup();
                $group->fill($row);
                $group->save();
            } else {
                $group->update($row);
            }
        }
    }

    public function syncOne(int $id): void
    {
        $row = $this->repo->getOne($id);
        if(!$this->validate($row)) abort(418);
        UserGroup::findOrFail($id)->update($row);
    }
}
