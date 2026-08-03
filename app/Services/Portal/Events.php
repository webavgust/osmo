<?php

namespace App\Services\Portal;


use App\Jobs\Portal\OrderSync;
use App\Jobs\Portal\UserSync;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\User\Models\User;
use App\Services\Portal\Repository\AbstractPortalRepository;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Events extends AbstractPortalRepository
{
    use DispatchesJobs;

    // проверка событий на портале
    public function listen()
    {
        $url = env('PORTAL_URL') . '/api/?token=' . env('API_TOKEN') . '&qr=get_fresh_change_events';
        $data = $this->getData($url);
        $sended = [];
        foreach($data as $event) {
            $chr = $event['event_sbkind'] . '_' . $event['event_sbid'];
            switch($event['event_sbkind']) {
                // пользователь
                case 'user':
                    $user = User::find($event['event_sbid']);
                    UserSync::dispatchIf(empty($sended[$chr]), $user);
                    break;
                case 'eco_order':
                    $order = Order::find($event['event_sbid']);
                    OrderSync::dispatchIf(empty($sended[$chr]), $order);

                    break;
            }
            $sended[$chr] = true;
        }
    }

    public function hook($mode, $arData = [], $arParams = [])
    {
        $url = env('PORTAL_URL') . '/api/?token=' . env('API_TOKEN') . '&qr=' . $mode;
        if(!empty($arData)) {
            $url .= '&' . http_build_query($arData);
        }

        Log::info($url);
        $response = Http::get($url)->json();
        return $response;
    }


    // функция для отправки хука в портал
    public function avg_hook($mode, $arData = [], $arParams = [])
    {
        $url = env('PORTAL_URL') . '/avg/hook/hal/' . $mode . '.php?token=' . env('API_TOKEN');

        if(!empty($arData)) {
            $url .= '&' . http_build_query($arData);
        }
        $response = Http::asForm()->post($url, $arParams)->body();

        return $response;
    }

}
