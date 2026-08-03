<?php

namespace App\Modules\Pub\Partner\Services;

use App\Modules\Pub\Education\Requests\ListFilterRequest;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as BuilderAlias;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class PartnerListFilterService
{
    private $filter;
    private $token;

    /**
     * При инициализации проверяется первичность захода и устанавливается фильтр по умоланию
     *
     * @param $token
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function __construct($token = false)
    {
        if (empty($token))
            $token = auth()->user()->ajax_token;

        $this->token = $token;
        $this->filter = Cache::get('partner.list.filter:' . $this->token);

        # INIT FIRST FILTER STATE
        if (empty($this->filter) && !Session::get('is_ajax') && !Session::get('partner.list.filter.init')) {

        }
    }

    /**
     *  Присваивание фильтра
     *
     * @param $arFilter
     * @return int
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function setFilter($arFilter)
    {
        foreach($arFilter as $field => $value) {
            if($value === null)
                unset($arFilter[$field]);
        }
        $this->filter = $arFilter;
        Cache::set('partner.list.filter:' . $this->token, $this->filter, 86400);

        return count($this->filter);
    }

    /**
     * Получение контейнера фильтра
     *
     * @param $request
     * @return mixed|string[]
     */
    public function getFilter($request = false)
    {
        return $this->filter;
    }

    /**
     * Кол-во правил в фильтре
     *
     * @return int
     */
    public function getFilterCount()
    {
        return collect($this->filter)->filter(fn($item, $key) => !Str::startsWith($key, 'cb_'))->count();
    }

    /**
     * Пользователи для фильтра
     *
     * @return array
     */
    public function getFilterUsers()
    {
        return [];
    }

    /**
     * Очищение контейнера фильтра
     *
     * @return void
     */
    public function clearFilter()
    {
        Cache::forget('partner.list.filter:' . $this->token);
    }

    /**
     * Фильтрация результатов
     *
     * @param BuilderAlias $builder
     * @return BuilderAlias
     */
    public function filter(BuilderAlias $builder)
    {

        if (!empty($this->filter)) {
            $filter = $this->filter;
            $builder->where(function ($builder) use ($filter) {
                foreach ($this->filter as $field => $value) {
                    switch ($field) {
                        case "type":
                        case "grade":
                            $builder->where($field, $value);
                            break;
                    }
                }
            });
        }

        return $builder;
    }
}
