<?php


namespace App\Modules\Pub\Order\Repositories;


use App\Facades\Tools;
use App\Services\Portal\Repository\AbstractPortalRepository;

class OrderPortalRepository extends AbstractPortalRepository
{
    /**
     * Получение списка пользователей из портала
     * @return array
     */
    public function getAll($from = null, $to = null): array
    {
        if(!$from) $from = now()->subDays(60)->startOfDay()->format('d.m.Y');
        if(!$to) $to = now()->format('d.m.Y');


        $url = env('PORTAL_URL') . '/api/?token=' . env('API_TOKEN') . '&qr=eco_orders&from=' . $from . '&to=' . $to;
        return $this->getData($url);
    }

    public function getOne(int $id): array
    {
        $url = env('PORTAL_URL') . '/api/?token=' . env('API_TOKEN') . '&qr=eco_orders&id=' . $id;
        return $this->getData($url)[0];
    }

    public function getOneFast(int $id): array
    {
        $url = env('PORTAL_URL') . '/api/?token=' . env('API_TOKEN') . '&qr=eco_orders&fast=1&id=' . $id;
        return $this->getData($url)[0];
    }


}
