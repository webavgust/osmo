<?php

namespace App\Services\Portal;

use App\Jobs\Portal\Orders\SyncAll;
use App\Jobs\Portal\Orders\SyncOne;
use App\Jobs\Portal\OrderSync;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\OrderTask\Services\OrderTaskService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Hook
{
    public static function hook(Request $request)
    {
        if($request->get('_token') !== env('API_TOKEN'))
            return ['status' => 'error', 'message' => 'Wrong API key'];

        switch($request->post('action')) {
            case 'order_update':
            case 'order_create':
                if(empty($request->post('target_id')))
                    return ['status' => 'error', 'message' => 'Wrong action target_id'];

                    $order = Order::find($request->post('target_id'));
                    if(empty($order)) {
                        SyncAll::dispatch(Carbon::now()->subDays(3)->format('d.m.Y'))->onQueue('database');
                    } else {
                        SyncOne::dispatch($order->id)->onQueue('database');
                    }
                break;
            case "order_clone":
                    $result = OrderTaskService::clone(
                        $request->post('project_id'),
                        $request->post('src_doc_key'),
                        $request->post('src_doc_ver_key'),
                        $request->post('clone_doc_key'),
                        $request->post('clone_doc_ver_key'),
                    );
                    return $result;
               break;
            default:
                return ['status' => 'error', 'message' => 'Wrong action field'];
        }

        return ['status' => 'success'];
    }
}
