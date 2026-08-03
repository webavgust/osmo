<?php

namespace App\Modules\Pub\Scenario\Repository;

use App\Modules\Pub\Constant\Models\Constant;
use App\Modules\Pub\EducationTask\Models\EducationTask;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Services\OrderListFilterService;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\OrderTask\Services\OrderTaskListFilterService;
use App\Modules\Pub\OrderTask\Services\OrderTaskService;
use App\Modules\Pub\OrderTaskAgreement\Models\OrderTaskAgreement;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Scenario\Models\Scenario;
use App\Modules\Pub\ScenarioGroup\Models\ScenarioGroup;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\UserDepartment\Models\UserDepartment;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScenarioRepository
{

    public static function getByID($id)
    {
        if(is_array($id)) {
            return Scenario::whereIn('id', $id)->get();
        } else {
            return Scenario::find($id);
        }
    }
    public static function getAll()
    {
        return Scenario::orderBy('scenario_group_id')->get()->keyBy('id');
    }

    public static function getActive(array $include = [])
    {
        // инициализируем конструктор
        $builder = Scenario::where('active', 1);

        // добавляем точечно ID
        if(count($include) > 0)
            $builder->orWhereIn('id', $include);

        // возвращаем результат
        return $builder->orderBy('scenario_group_id')->get()->keyBy('id');
    }

    public static function create(array $data, ScenarioGroup $group)
    {
        if(!empty($data['cost_force']))
            if(empty($data['cost_force']['year']) && empty($data['cost_force']['unlimited'])) $data['cost_force'] = null;


        // сформируем таблицу
        $arRules = [];
        foreach($data['cost_rules'] as $rule) {
            if(empty($rule['c'])) continue;
            if(empty($rule['y']) || empty($rule['u'])) abort(404);

            $rule['c'] = (int)str_replace([' ', ',', '\''], '', $rule['c']);
            $rule['y'] = (int)str_replace([' ', ',', '\''], '', $rule['y']);
            $rule['u'] = (int)str_replace([' ', ',', '\''], '', $rule['u']);

            $arRules[$rule['c']] = ['y' => (int)$rule['y'], 'u' => (int)$rule['u']];
        }
        if(count($arRules) == 0) abort(404);
        $data['cost_rules'] = $arRules;

        $scenario = Scenario::make($data);
        $scenario->scenario_group()->associate($group)->save();

        foreach($data['neuro'] as $neuro_id) {
            if(!$neuro_id) continue;
            $neuro = Neuroservice::find($neuro_id);
            if(!empty($neuro))
                $scenario->neuroservices()->attach($neuro);
        }

        return $scenario;
    }
    public static function update(Scenario $scenario, array $data)
    {
        if(!isset($data['cb_registered']))
            $data['cb_registered'] = false;

        $data['active'] = (bool)($data['active'] ?? false);


        // сформируем таблицу
        $arRules = [];
        foreach($data['cost_rules'] as $rule) {
            if(empty($rule['c'])) continue;
            if(empty($rule['y']) || empty($rule['u'])) abort(404);

            $rule['c'] = (int)str_replace([' ', ',', '\''], '', $rule['c']);
            $rule['y'] = (int)str_replace([' ', ',', '\''], '', $rule['y']);
            $rule['u'] = (int)str_replace([' ', ',', '\''], '', $rule['u']);

            $arRules[$rule['c']] = ['y' => (int)$rule['y'], 'u' => (int)$rule['u']];
        }
        if(count($arRules) == 0) abort(404);
        $data['cost_rules'] = $arRules;
        $scenario->update($data);


        $scenario->neuroservices()->sync([]);
        foreach($data['neuro'] as $neuro_id) {
            if(!$neuro_id) continue;
            $neuro = Neuroservice::find($neuro_id);
            if(!empty($neuro))
                $scenario->neuroservices()->attach($neuro);
        }

        if($scenario->scenario_group->id !== $data['group']) {
            $sort = Scenario::where('scenario_group_id', $data['group'])->max('sort') + 100;
            $scenario
                ->fill(['sort' => $sort])
                ->scenario_group()->associate($data['group'])
                ->save();
        }

        return $scenario;
    }

    public static function getCosts(float $rate = 1)
    {
        $ret = [
            'year' => [],
            'unlimited' => []
        ];
        $scenarios = static::getAll();
        foreach($scenarios as $scenario) {
            $ret['year'][$scenario->id] = round($scenario->cost_year / $rate);
            $ret['unlimited'][$scenario->id] = round($scenario->cost_unlimited / $rate);
        }

        // добавим стоимость на платформу
        $platform_costs = json_decode(Constant::get('platform_cost_per_year'), 1);

        $ret['year']['platform'] = $platform_costs[1]['y'];
        $ret['unlimited']['platform'] = $platform_costs[1]['u'];


        return $ret;
    }

    public static function getCostRules(float $rate = 1)
    {

        $ret = collect(Scenario::pluck('cost_rules', 'id'))->map(function($rules) use ($rate) {
            $rules = collect($rules)->map(function($rule) use ($rate) {
                $rule['p']  = $rule['y'] / 12;
                $rule['p'] /= $rate;
                $rule['y'] /= $rate;
                $rule['u'] /= $rate;
                return $rule;
            });

            return $rules;
        });

        $ret[0] = collect(json_decode(Constant::get('platform_cost_per_year'), 1));


        return $ret;
    }
}
