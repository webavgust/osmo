<?php

namespace App\Modules\Pub\OrderTask\Services;

use App\Modules\Pub\Order\Requests\ListFilterRequest;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as BuilderAlias;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class OrderTaskListFilterService
{
    private $filter;
    private $token;

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
        $this->filter = Cache::get('order_task.list.filter:' . $this->token);

        # INIT FIRST FILTER STATE
        if(empty($this->filter) && !Session::get('is_ajax') && !Session::get('order_task.list.filter.init')) {
        //            $this->filter = [
        //                "order_sent_to_techdep" => Carbon::now()->startOfYear()->format("d.m.Y") . ' - ' . Carbon::now()->format("d.m.Y")
        //            ];
        //            $users = UserGroup::find(UserGroup::GROUP_CURATOR_DEFAULT)->users()->get()->pluck('id')->toArray();
        //            if(!empty($users)) $this->filter['curator'] = $users;
        //
        //            $this->setFilter($this->filter);
        //            Session::put('order_task.list.filter.init', 1);
        }
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
        $this->filter = $arFilter;
        Cache::set('order_task.list.filter:' . $this->token, $this->filter, 86400);

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
        Cache::forget('order_task.list.filter:' . $this->token);
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
                        case "status":
                            $builder->where(function($builder) use ($value) {
                                $builder->whereIn('status', $value);
                            });
                            break;
                        case "created_at":
                            if(empty($value)) continue(2);
                            list($from, $to) = explode(" - ", $value);
                            $from =  Carbon::createFromFormat("d.m.Y", $from)->format('Y-m-d 00:00:00');
                            $to =  Carbon::createFromFormat("d.m.Y", $to)->format('Y-m-d 23:59:59');
                            $builder->whereBetween($field, [$from, $to]);
                            break;
                        case "creator":
                            $builder->whereIn('creator', $value);
                            break;
                        case "client_name":
                        case "contract_name":
                            $builder->whereHas('evaluation', function($builder) use ($field, $value) {
                                $value = Str::lower($value);
                                $builder->whereRaw("LOWER(portal_data->'$.{$field}') LIKE '%{$value}%'");

                            });
                        break;
                    }
                }
            });
        }
        return $builder;
    }

}
