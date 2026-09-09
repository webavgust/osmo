<?php


namespace App\Modules\Pub\UserDepartment\Services;

use App\Interfaces\PortalRepositoryInterface;
use App\Interfaces\PortalSyncInterface;
use App\Modules\Pub\UserDepartment\Models\UserDepartment;
use App\Modules\Pub\UserDepartment\Repositories\UserDepartmentPortalRepository;
use App\Services\Portal\Repository\AbstractPortalService;

class UserDepartmentSyncPortalService extends AbstractPortalService implements PortalSyncInterface
{
    private $repo;
    protected $rules = [
        'id' => 'required|int',
        'active' => 'bool',
        'name' => 'string'
    ];

    public function __construct()
    {
        $this->repo = new UserDepartmentPortalRepository();
    }

    /**
     * Синхронизировать всех
     *
     * @return void
     */
    public function syncAll(): void
    {
        $rows = $this->repo->getAll();
        foreach ($rows as $row) {
            if (!$this->validate($row)) continue;

            $group = UserDepartment::find($row['id']);
            if (empty($group)) {
                $group = new UserDepartment();
                $group->fill($row);
                $group->save();
            } else {
                $group->update($row);
            }
        }
    }

    /**
     * Синхронизировать одну запись
     *
     * @param int $id
     * @return void
     */
    public function syncOne(int $id): void
    {
        $row = $this->repo->getOne($id);
        if (!$this->validate($row)) abort(418);
        UserDepartment::findOrFail($id)->update($row);
    }
}
