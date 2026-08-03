<?php

namespace App\Services\Spider;

use App\Modules\Pub\Notify\Models\Notify;
use App\Modules\Pub\Notify\Repositories\NotifyRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

class Spider
{
    public static function getStatus($data = [])
    {
        $return = [
            'is_live' => auth()->check()
        ];

        // авторизован
        if($return['is_live']) {
            // активная вкладка
            if(!empty($data['global']) || (!empty($data['is_active']) && $data['is_active'])) {


                $repo = new NotifyRepository();

                // toasts
                if(isset($data['toasts']) && $data['toasts'] < config('settings.toasts')) {
                    $toast = $repo->getToast();

                    if(!empty($toast))
                        $return['toast'] = $toast;
                }

                // notifies
                $return['notifies'] = $repo->getForSpider();
            }
            return $return;
        }
    }
}
