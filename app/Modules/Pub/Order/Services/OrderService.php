<?php

namespace App\Modules\Pub\Order\Services;

use App\Modules\Pub\Calendar\Repositories\CalendarRepository;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Repositories\OrderRepository;
use App\Modules\Pub\WorkCalendar\Models\WorkCalendar;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OrderService
{

    private $repo;
    public $portal;

    public function __construct()
    {
        $this->repo = new OrderRepository();
    }

    public function tableDefault($params)
    {
        $data = $this->repo->getTable($params);

        // Преобразование
        // TODO: переделать на Resource
        $data['rows']->map(function($item) {
            $item->contract_conclusion = _date($item->contract_conclusion);
            $item->order_sent_to_techdep = _date($item->order_sent_to_techdep);
            $item->md_specify_finaldate = _date($item->md_specify_finaldate);
            $item->last_control_date = _date($item->last_control_date);
            $item->second_control_date = _date($item->second_control_date);

            if(!$item->is_finished && !$item->is_archived && !empty($item->md_specify_finaldate)) {
                $carbon = Carbon::createFromTimestamp(strtotime($item->md_specify_finaldate));
                if($carbon->isPast()) {
                    $item->is_danger = true;
                    $item->is_warning = true;
                } else {
                    $diff = $carbon->diffInDays();
                    $item->is_danger = $diff <= 0;
                    $item->is_warning = $diff < 7;
                    $item->diff = $diff;
                }
            }
        });
        return $data;
    }

    public function daysCalc(array $data)
    {
        $wc = new WorkCalendar();
        switch($data['type']) {
            case 'date':
                if(empty($data['date'])) return [];

                if(empty($data['order_date'])) {
                    $from = now()->startOfDay();
                } else {
                    $from = Carbon::createFromTimestamp(strtotime($data['order_date']));
                }
                $to = Carbon::createFromTimestamp(strtotime($data['date']));

                $daysFree = $wc->untilDate($from->format('Y-m-d'), $to->format('Y-m-d'));

                $carbonEnd = Carbon::createFromTimestamp(strtotime($data['date']));
                $days = max(0, $from->startOfDay()->diffInDays($to) - $daysFree) ;
                return ['date' => $data['date'], 'days' => $days];
                break;

            case 'days':
                $date = $wc->calcDate($data['days'], $data['order_date']);
                return ['date' => $date, 'days' => $data['days']];
                break;
        }
        abort(500);
    }

    public function getTree(Order $order)
    {
        if(empty($order->order_task->objects))
            return null;

        $tree = [];
        foreach($order->order_task->objects as $object) {
            $object_row = [
                'text' => $object->name ?? 'Без названия',
                'icon' => 'fa-regular fa-industry me-3'
            ];
            foreach($object->addresses as $address) {
                $address_row = [
                    'text' => $address->address,
                    'icon' => 'fa-solid fa-location-dot me-3'
                ];
                foreach($address->points as $point) {
                    $point_row = [
                        'text' => $point->name,
                        'icon' => 'fa-solid fa-map-pin me-3 text-danger'
                    ];
                    foreach($point->measures as $measure) {
                        $point_row['nodes'][] = [
                            'text' => $measure->measure->name . ' x ' . $measure->count,
                            'icon' => 'fa-regular fa-flask me-3'
                        ];
                    }
                    $address_row['nodes'][] = $point_row;
                }
                $object_row['nodes'][] = $address_row;
            }

            if($object->services) {
                $service_node = [];
                foreach($object->services()->withPivot('count')->get() as $service) {
                    $service_node[] = [
                        'text' => $service->name . ' x ' . $service->pivot->count,
                        'icon' => 'fa-light fa-coin me-3 text-primary'
                    ];
                }
                $object_row['nodes'][] = [
                    'text' => 'Услуги',
                    'icon' => 'fa-light fa-coin me-3',
                    'nodes' => $service_node
                ];
            }
            $tree[] = $object_row;
        }
        return $tree;
    }

    public function syncEvent(Order $order)
    {
        $calendar_repo = new CalendarRepository();

        $order->event_dc1()->forceDelete();
        $order->event_dc2()->forceDelete();

        if($order->curator_id) {
            if(!empty($order->last_control_date))
                $order->event_dc1()->save($calendar_repo->add([
                    'start' => $order->last_control_date,
                    'end' => $order->last_control_date,
                    'title_icon' => 'fa-solid fa-square-1 fs-5',
                    'title' => $order->id,
                    'text' => view('templates.calendar.order_dc', ['num' => 1, 'order_id' => $order->id])->render(),
                    'user_id' => $order->curator_id,
                    'target_sub' => 'order_dc1'
                ]));

            if(!empty($order->second_control_date))
                $order->event_dc1()->save($calendar_repo->add([
                    'start' => $order->second_control_date,
                    'end' => $order->second_control_date,
                    'title_icon' => 'fa-solid fa-square-2 fs-5',
                    'title' => $order->id,
                    'text' => view('templates.calendar.order_dc', ['num' => 2, 'order_id' => $order->id])->render(),
                    'user_id' => $order->curator_id,
                    'color' => 'danger',
                    'target_sub' => 'order_dc2'
                ]));

        }
    }

}
