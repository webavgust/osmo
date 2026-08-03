<?php


namespace App\Modules\Pub\UserGroup\Repositories;


use App\Services\Portal\Repository\AbstractPortalRepository;

class UserGroupPortalRepository extends AbstractPortalRepository
{
    /**
     * Получение списка пользователей из портала
     * @return array
     */
    public function getAll(): array
    {
        $url = env('PORTAL_URL') . '/api/?token=' . env('API_TOKEN') . '&qr=grp';
        return $this->getData($url);
    }

    public function getOne(int $id): array
    {
        $url = env('PORTAL_URL') . '/api/?token=' . env('API_TOKEN') . '&qr=grp&id=' . $id;
        return $this->getData($url)[0];
    }
}
