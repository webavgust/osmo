<?php

namespace App\Modules\Pub\Order\Services;

use App\Modules\Pub\Order\Requests\ListFilterRequest;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as BuilderAlias;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class OrderListFilterService
{
    private $filter;
    private $token;
    private $presets;
    /**
     * При инициализации проверяется первичность захода и устанавливается фильтр по умоланию
     * @param $token
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function __construct($token = false)
    {
        if(empty($token))
            $token = auth()->user()->ajax_token;
        $this->token = $token;
        $this->filter = Cache::get('order.list.filter:' . $this->token);

        # INIT FIRST FILTER STATE
        if(empty($this->filter) && !Session::get('is_ajax') && !Session::get('order.list.filter.init')) {
            $this->filter = [
                "order_sent_to_techdep" => Carbon::now()->startOfYear()->format("d.m.Y") . ' - ' . Carbon::now()->format("d.m.Y"),
                "is_finished" => false
            ];
            $users = UserGroup::find(UserGroup::GROUP_CURATOR_DEFAULT)->users()->get()->pluck('id')->toArray();
            if(!empty($users)) $this->filter['curator'] = $users;

            $this->setFilter($this->filter);
            Session::put('order.list.filter.init', 1);
        }

        $this->presets = [
            '1day' => [
                'name' => '1 день до завершения',
                'filter' => [
                    'md_specify_finaldate' => now()->year(2020)->startOfYear()->format('d.m.Y') . ' - ' . now()->addDays(1)->format('d.m.Y'),
                    "cb_md_specify_finaldate" => "1",
                    'status' => [
                        'unfinished' => true
                    ]
                ]
            ],
            '3days' => [
                'name' => '3 дня до завершения',
                'filter' => [
                    'md_specify_finaldate' => now()->format('d.m.Y') . ' - ' . now()->addDays(3)->format('d.m.Y'),
                    "cb_md_specify_finaldate" => "1",
                    'status' => [
                        'unfinished' => true
                    ]
                ]
            ],
            '14days' => [
                'name' => '14 дней до завершения',
                'filter' => [
                    'md_specify_finaldate' => now()->format('d.m.Y') . ' - ' . now()->addDays(14)->format('d.m.Y'),
                    "cb_md_specify_finaldate" => "1",
                    'status' => [
                        'unfinished' => true
                    ]
                ]
            ],
        ];
    }


    /**
     *
     *  Присваивание фильтра через полученные значения
     * @param $arFilter
     * @return int
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function setFilter($arFilter)
    {
        # check finish/unfinish;
        if(!empty($arFilter['status']))
        {
            $status = collect($arFilter['status']);
            $status_values = $status->keys();
            if($status_values->contains('finished') && $status_values->contains('unfinished'))
            {
                unset($arFilter['status']);
                unset($arFilter['is_finished']);
            } else {
                $arFilter['is_finished'] = $status_values->contains('finished');
            }
            unset($arFilter['status']);
        }

        if(isset($arFilter['order_id']) && empty($arFilter['order_id'])) unset($arFilter['order_id']);
        if(isset($arFilter['order_name']) && empty($arFilter['order_name'])) unset($arFilter['order_name']);

        $this->filter = $arFilter;
        Cache::set('order.list.filter:' . $this->token, $this->filter, 86400);

        return count($this->filter);
    }


    /**
     * Получение контейнера фильтр
     * @param $request
     * @return mixed|string[]
     */
    public function getFilter($request = false)
    {
        return $this->filter;
    }

    public function getFilterCount()
    {
        return collect($this->filter)->filter(fn($item, $key) => !Str::startsWith($key, 'cb_'))->count();
    }

    public function getFilterUsers()
    {
        return [];
    }


    /**
     * Очищение контейнера фильтра
     * @return void
     */
    public function clearFilter()
    {
        Cache::forget('order.list.filter:' . $this->token);
    }


    /**
     * Метод фильтрации результатов
     * @param BuilderAlias $builder
     * @return BuilderAlias
     */
    public function filter(BuilderAlias $builder)
    {

        if(!empty($this->filter))
        {
            $filter = $this->filter;
            $builder->where(function($builder) use ($filter) {
                foreach($this->filter as $field => $value) {
                    switch($field) {
                        case "contract_conclusion":
                        case "order_sent_to_techdep":
                        case "md_specify_finaldate":
                            if(empty($value)) continue(2);
                            if(\Str($value)->contains(' - ')) {
                                list($from, $to) = explode(" - ", $value);
                                $from =  Carbon::createFromFormat("d.m.Y", $from)->format('Y-m-d 00:00:00');
                                $to =  Carbon::createFromFormat("d.m.Y", $to)->format('Y-m-d 23:59:59');
                            } else {
                                $from = now()->startOfDay()->format('Y-m-d h:i:s');
                                $to = Carbon::createFromTimestamp(strtotime($value))->format('Y-m-d 23:59:59');
                            }
                            $builder->whereBetween('orders.'.$field, [$from, $to]);
                            break;
                        case "author":
                            $builder->whereIn('orders.author_id', $value);
                            break;
                        case "manager":
                            $builder->whereIn('orders.manager_id', $value);
                            break;
                        case "curator":
                            $builder->where(function($builder) use ($value) {
                                $builder->whereIn('orders.curator_id', $value);
                                if(in_array(0, $value)) $builder->orWhereNull('curator_id');
                            });
                            break;
                        case "is_finished":
                            $builder->where(function($builder) use ($value) {
                                $builder->where('orders.is_finished', $value);
                            });
                            break;
                        case "order_id":
                            $builder->where(function($builder) use ($value) {
                                $builder->where('orders.id', $value);
                            });
                            break;
                        case "order_name":
                            $builder->where(function($builder) use ($value) {
                                $builder->where('orders.order_name', 'like', "%{$value}%");
                            });
                            break;
                    }
                }
            });
        }

        $builder->where(function($builder) {
            $builder->where('orders.is_archived', $this->filter['is_archived'] ?? 0);
        });
//        dd($builder->toSql());
        return $builder;
    }


    public function getPresets()
    {
        return $this->presets;
    }

    public function getPreset($chr)
    {
        return $this->presets[$chr] ?? null;
    }
}
