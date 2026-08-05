<?php

namespace App\Modules\Pub\Proposal\Services;

use App\Modules\Pub\Education\Requests\ListFilterRequest;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as BuilderAlias;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ProposalListFilterService
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
        $this->filter = Cache::get('Proposal.list.filter:' . $this->token);

        # INIT FIRST FILTER STATE
        if (empty($this->filter) && !Session::get('is_ajax') && !Session::get('Proposal.list.filter.init')) {

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

            // пустой массив статусов — это «не фильтруем»
            if(is_array($value) && empty($value))
                unset($arFilter[$field]);
        }
        $this->filter = $arFilter;
        Cache::set('Proposal.list.filter:' . $this->token, $this->filter, 86400);

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
        Cache::forget('Proposal.list.filter:' . $this->token);
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
                        case "company":
                            $builder->whereHas("company", function($builder) use ($value) {
                                $builder->where('id', $value);
                            });
                        break;
                        case "partner":
                            $builder->whereHas("partner", function($builder) use ($value) {
                                $builder->where('id', $value);
                            });
                        break;
                        case "scenario":
                            $builder->whereHas("variants", function($builder) use ($value) {
                                $builder->whereHas("proposal_scenarios", function($builder) use ($value) {
                                    $builder->where('scenario_id', $value);
                                });
                            });
                        break;
                        case "neuroservice":
                            $builder->whereHas("variants", function($builder) use ($value) {
                                $builder->whereHas("proposal_scenarios", function($builder) use ($value) {
                                    $builder->whereHas("neuroservices", function($builder) use ($value) {
                                        $builder->where('neuroservices.id', $value);
                                    });
                                });
                            });
                        break;
                        case "sended_at":
                            $dates = explode(" - ", $value);
                            $from = Carbon::createFromFormat("d.m.Y", $dates[0]);
                            $to = Carbon::createFromFormat("d.m.Y", $dates[1]);
                            $builder->whereBetween('sended_at', [$from, $to]);
                        break;
                        case "cost_from":
                            $builder->whereHas("variants", function($builder) use ($value) {
                                $builder->where('cost_total', '>=', $value);
                            });
                        break;
                        case "cost_to":
                            $builder->whereHas("variants", function($builder) use ($value) {
                                $builder->where('cost_total', '<=', $value);
                            });
                        break;
                        case "hasEmptyScenario":
                            $builder->whereHas("variants", function($builder) use ($value) {
                                $builder->whereHas('proposal_scenarios', function($builder) {
                                    $builder->whereHas('scenario', function ($builder) {
                                        $builder->whereDoesntHave('neuroservices');
                                    });
                                });
                            });
                        break;

                        // --- статус КП -------------------------------------
                        case "status":
                            $builder->whereIn('status', (array) $value);
                        break;

                        // --- наличие привязанной сделки Битрикса ------------
                        case "crm_deal":
                            if($value === 'linked') {
                                $builder->whereNotNull('crm_deal_id');
                            } elseif($value === 'empty') {
                                $builder->whereNull('crm_deal_id');
                            }
                        break;

                        // --- конкретные ID сделок (можно несколько через запятую)
                        case "crm_deal_id":
                            $ids = collect(preg_split('/[^0-9]+/', (string) $value))
                                ->filter(fn($id) => $id !== '')
                                ->map(fn($id) => (int) $id)
                                ->unique()
                                ->values();

                            if($ids->isNotEmpty())
                                $builder->whereIn('crm_deal_id', $ids);
                        break;
                    }
                }
            });
        }

        return $builder;
    }
}
