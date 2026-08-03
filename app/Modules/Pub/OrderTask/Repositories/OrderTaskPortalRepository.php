<?php

namespace App\Modules\Pub\OrderTask\Repositories;

use App\Modules\Pub\EducationApplication\Models\EducationApplication;
use App\Modules\Pub\EducationApplication\Requests\EducationApplicationSaveRequest;
use App\Modules\Pub\Files\Services\FileService;
use App\Services\Portal\Repository\AbstractPortalRepository;

class OrderTaskPortalRepository extends AbstractPortalRepository
{
    /**
     * Получить информацию о приложении
     *
     * @param $key
     * @return array
     */
    public function getInfo($key): array
    {
        $url = env('PORTAL_URL') . '/avg/get/hal/order_task/info.php?token=' . env('API_TOKEN') . '&annex=' . $key;
        return $this->getData($url);
    }

    /**
     * Получить оплаты
     *
     * @return array|mixed
     */
    public function getStatusesForAll()
    {
        $url = env('PORTAL_URL') . '/avg/get/hal/order_task/statuses.php?token=' . env('API_TOKEN');

        return $this->getData($url);
    }
}
