<?php


namespace App\Modules\Pub\Order\Services;


use App\Interfaces\PortalSyncInterface;
use App\Modules\Pub\AjaxProgress\Traits\AjaxLoader;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Repositories\OrderPortalRepository;
use App\Modules\Pub\User\Models\User;
use App\Services\Portal\Repository\AbstractPortalService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OrderSyncPortalService extends AbstractPortalService implements PortalSyncInterface
{
    use AjaxLoader;

    private $repo;

    public function __construct()
    {
        $this->repo = new OrderPortalRepository();
        $this->service = new OrderService();
    }

    public function syncAll($from = null, $to = null): void
    {

        $rows = collect($this->repo->getAll($from, $to));
        if(!empty($this->ajax)) $this->ajax->total($rows->count());

        foreach ($rows as $tick => $row) {
            if(!empty($this->ajax)) $this->ajax->step($tick + 1);
            $order = Order::find($row['order_id']);
            $row_collect = $this->row_prepare($row);

            if (empty($order)) {
                $faker = \Faker\Factory::create();
                $order = new Order();
                $order->id = $row['order_id'];
                $order->fill($row_collect->toArray());

                $order->created_at = Carbon::createFromTimestamp($row['order_created_mtime'])->format("Y-m-d H:i:s");
                $order->save();

                // PERIOD
                $order->periods()->create([
                    'user_id' => $order->manager_id,
                    'location' => $order->md_specify_locationplace,
                    'period' => $order->md_specify_periodicity,
                    'date' => $order->md_specify_finaldate,
                    'days' => $order->md_specify_days,
                ]);

                // COMMENT

                if(is_array($row['comments'])) {
                    foreach($row['comments'] as $comment) {
                        $order->comments()->create([
                            'user_id' => $comment[0],
                            'text' => $comment[2],
                            'control_first' => Carbon::createFromTimestamp($comment[1])->format('Y-m-d'),
                            'created_at' => Carbon::createFromTimestamp($comment[1])->format('Y-m-d H:i:s')
                        ]);
                    }
                }

            } else {
                $order->update($row_collect->only([
                    'curator_id',
                    'customer_id',
                    'customer_name',
                    'contract_conclusion',
                    'order_sent_to_techdep'
                ])->toArray());
            }

            $this->service->syncEvent($order);
        }
        if(!empty($this->ajax)) $this->ajax->finish();
    }

    public function syncOne(int $id): void
    {
        $row = $this->repo->getOne($id);
        $row_collect = $this->row_prepare($row);
        $order = Order::findOrFail($id);
        $order->update($row_collect->only([
            'customer_id',
            'customer_name',
            'order_name',
            'contract_conclusion',
            'curator_id',
            'order_sent_to_techdep'
        ])->toArray());

        $this->service->syncEvent($order);
    }



    private function row_prepare($row)
    {
        $row_collect = collect($row)->only(Order::getPortalFillable());
        $row_collect['order_sent_to_techdep'] = !empty($row_collect['order_sent_to_techdep']) ? Carbon::createFromTimestamp($row_collect['order_sent_to_techdep'])->format("Y-m-d H:i:s") : null;
        $row_collect['last_control_date'] = !empty($row_collect['last_control_date']) ? Carbon::createFromTimestamp($row_collect['last_control_date'])->format("Y-m-d H:i:s") : null;
        $row_collect['second_control_date'] = !empty($row_collect['second_control_date']) ? Carbon::createFromTimestamp($row_collect['second_control_date'])->format("Y-m-d H:i:s") : null;
        $row_collect['contract_conclusion'] = !empty($row_collect['contract_conclusion']) ? Carbon::createFromTimestamp($row_collect['contract_conclusion'])->format("Y-m-d H:i:s") : null;
        $row_collect['md_specify_finaldate'] = !empty($row_collect['md_specify_finaldate']) ? Carbon::createFromTimestamp($row_collect['md_specify_finaldate'])->format("Y-m-d") : null;
        $row_collect['manager_id'] = $row_collect['manager_id'] > 0 ? $row_collect['manager_id'] : null;
        $row_collect['curator_id'] = $row_collect['curator_id'] > 0 ? $row_collect['curator_id'] : null;
        $row_collect['md_specify_days'] = $row_collect['md_specify_days'] > 0 ? $row_collect['md_specify_days'] : null;
        $row_collect['md_specify_locationplace'] = $row_collect['md_specify_locationplace'] != '' ? $row_collect['md_specify_locationplace'] : null;
        $row_collect['is_archived'] = $row['md_archived'];
        $row_collect['is_finished'] = $row['md_finished'];

        return $row_collect;
    }
}
