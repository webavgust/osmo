<?php


namespace App\Modules\Pub\UserDepartment\Repositories;


use App\Services\Portal\Repository\AbstractPortalRepository;

class UserDepartmentPortalRepository extends AbstractPortalRepository
{
    /**
     * Получение списка пользователей из портала
     *
     * @return array
     */
    public function getAll(): array
    {
        $url = env('PORTAL_URL') . '/api/?token=' . env('API_TOKEN') . '&qr=dep';
        return $this->getData($url);
    }

    /**
     * Получение одной записи
     *
     * @param int $id
     * @return array
     */
    public function getOne(int $id): array
    {
        return [];
    }
}
