<?php

namespace App\Modules\Pub\Proposal\Services;

use App\Modules\Pub\Partner\Models\PartnerGrade;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use App\Modules\Pub\User\Models\User;

class ProposalService
{
    private $repo;
    private $repo_calc_lessons;

    public function __construct()
    {
        $this->repo = new ProposalRepository();
    }

    public static function getLastIteration(\App\Modules\Pub\Proposal\Models\Proposal $proposal)
    {
        return Proposal::where('group', $proposal->group)->max('iteration');
    }

    public static function getMatrix(\App\Models\ModuleModel $proposal)
    {
        $scenario_matrix = $platform_matrix = [];
        foreach($proposal->variants as $variant) {

            $offset_vector = [];
            foreach($variant->proposal_scenarios as $proposal_scenario) {
                $unique = md5(collect([$proposal_scenario->scenario_id,$proposal_scenario->cb_process,$proposal_scenario->mnemonic_name, $proposal_scenario->comment])->join("|"));

                if(empty($offset_vector[$unique]))
                    $offset_vector[$unique] = 0;

                // определим смещение
                $offset = $offset_vector[$unique];


                // опрелим, есть ли в матрице нужный сценарий
                $pointer = 0;
                $processed = false;
                foreach($scenario_matrix as $i => $scenario) {

                    if(
                        $scenario['scenario_id'] !== $proposal_scenario->scenario_id
                        || $scenario['cb_process'] !== $proposal_scenario->cb_process
                        || $scenario['mnemonic_name'] !== $proposal_scenario->mnemonic_name
                        || $scenario['comment'] !== $proposal_scenario->comment
                    ) continue;


//                    if($proposal_scenario->id == 1501) dd($i, $scenario, $pointer, $offset);

                    if($pointer < $offset) {
                        $pointer++;
                        continue;
                    } else {
                        $scenario_matrix[$i]['cells'][$variant->id] = $proposal_scenario;
                        $processed = true;
                        break;
                    }
                }

                // не нашли, добавим
                if(!$processed) {
                    $scenario_matrix[] = [
                        'scenario_id' => $proposal_scenario->scenario_id,
                        'cb_process' => $proposal_scenario->cb_process,
                        'real_name' => $proposal_scenario->real_name,
                        'mnemonic_name' => $proposal_scenario->mnemonic_name,
                        'comment' => $proposal_scenario->comment,
                        'cells' => [$variant->id => $proposal_scenario]
                    ];
                }

                $offset_vector[$unique]++;
            }


            foreach($variant->proposal_platforms as $proposal_platform) {
//              $unique = md5(collect([$proposal_platform->cb_process,$proposal_platform->mnemonic_name, $proposal_platform->comment])->join("|"));
                $unique = md5(collect([$proposal_platform->cb_process,$proposal_platform->description, $proposal_platform->notice])->join("|"));

                if(empty($offset_vector[$unique]))
                    $offset_vector[$unique] = 0;

                // определим смещение
                $offset = $offset_vector[$unique];

                // опрелим, есть ли в матрице нужный сценарий
                $pointer = 0;
                $processed = false;
                foreach($platform_matrix as $i => $platform) {
                    if(
                        $platform['cb_process'] !== $proposal_platform->cb_process
                        || $platform['description'] !== $proposal_platform->description
                        || $platform['notice'] !== $proposal_platform->notice
                    ) continue;


                    if($pointer < $offset) {
                        $pointer++;
                        continue;
                    } else {
                        $platform_matrix[$i]['cells'][$variant->id] = $proposal_platform;
                        $processed = true;
                        break;
                    }
                }

                // не нашли, добавим

                if(!$processed) {
                    $platform_matrix[] = [
                        'cb_process' => $proposal_platform->cb_process,
                        'description' => $proposal_platform->description,
                        'notice' => $proposal_platform->notice,
                        'count' => $proposal_platform->count,
                        'cost' => $proposal_platform->cost,
                        'cost_discount' => $proposal_platform->cost_discount,
                        'cells' => [$variant->id => $proposal_platform]
                    ];
                }

                $offset_vector[$unique]++;
            }
        }

        return ['scenario_matrix' => $scenario_matrix, 'platform_matrix' => $platform_matrix];
    }


    /**
     * Данные для таблицы
     *
     * Статус, привязка к сделке и переход в сводную карточку — три отдельные
     * колонки: раньше всё жило внутри статуса и мешалось.
     *
     * @param $params
     * @return array
     */
    public function tableDefault(array $params, User $manager = null)
    {
        if(!empty($manager))
            $params['manager'] = $manager->id;
        $data = $this->repo->getTable($params);


        $return = collect();
        foreach($data['rows'] as $row) {
            $return[] = [
                'number' => view('components.proposal.table.main.number', ['row' => $row])->render(),
                'partner' => view('components.proposal.table.main.partner', ['row' => $row])->render(),
                'company' => view('components.proposal.table.main.company', ['row' => $row])->render(),
                'name' => view('components.proposal.table.main.name', ['row' => $row])->render(),
                'cost' => view('components.proposal.table.main.cost', ['row' => $row])->render(),
                'date' => view('components.proposal.table.main.date', ['row' => $row])->render(),
                'status' => view('components.proposal.table.main.status', ['row' => $row])->render(),
                'deal' => view('components.proposal.table.main.deal', ['row' => $row])->render(),
                'updated_at' => view('components.proposal.table.main.updated_at', ['row' => $row])->render(),
                'summary' => view('components.proposal.table.main.summary', ['row' => $row])->render(),
                'actions' => view('components.proposal.table.main.actions', ['row' => $row])->render(),
            ];
        };

        $data['rows'] = $return;

        return $data;
    }


    public static function getNeuroForceCost(Proposal $proposal)
    {
        $cost_rules = ScenarioRepository::getCostRules($proposal->currency_rate_cumulative);
        $matrixes = ProposalService::getMatrix($proposal);
        $neuroForceCost = [];

        foreach($matrixes['scenario_matrix'] as $i => $row) {
            $variant_i = 0;
            foreach(array_values($row['cells']) as $j => $cell) {
                $variant = $proposal->variants[$variant_i];
                $mode = match($variant->period_type) {
                     'year' => 'y',
                     'pilot' => 'p',
                     'unlimited' => 'u'
                };

                // определим номер правила
                $wearNum = 0;
                foreach($cost_rules[$cell['scenario_id']]->keys() as $ruleCount) {
                    if($cell['count'] >= $ruleCount) $wearNum = $ruleCount;
                }

                switch($mode) {
                    case 'y':
                        $forceYear = $cell->cost;
                        break;
                    case 'p':
                        $forceYear = $cell->cost * 12;
                        break;
                    case 'u':
                        $forceYear = $cell->cost / 3;
                        break;
                }
                $neuroForceCost[$i + 1][$j + 1][$cell->scenario_id][$wearNum] = [
                    'y' => $forceYear,
                    'p' => $forceYear / 12,
                    'u' => $forceYear * 3
                ];

                $variant_i++;
            }
        }

        // добавим платформу
        foreach($matrixes['platform_matrix'] as $i => $row) {
            $variant_i = 0;
            foreach(array_values($row['cells']) as $j => $cell) {
                $cell['scenario_id'] = 0;

                $variant = $proposal->variants[$variant_i];
                $mode = match($variant->period_type) {
                    'year' => 'y',
                    'pilot' => 'p',
                    'unlimited' => 'u'
                };

                // определим номер правила
                $wearNum = 0;
                foreach($cost_rules[$cell['scenario_id']]->keys() as $ruleCount) {
                    if($cell['count'] >= $ruleCount) $wearNum = $ruleCount;
                }

                switch($mode) {
                    case 'y':
                        $forceYear = $cell->cost;
                        break;
                    case 'p':
                        $forceYear = $cell->cost * 12;
                        break;
                    case 'u':
                        $forceYear = $cell->cost / 3;
                        break;
                }
                $neuroForceCost[$i + 1][$j + 1][$cell->scenario_id][$wearNum] = [
                    'y' => $forceYear,
                    'p' => $forceYear / 12,
                    'u' => $forceYear * 3
                ];

                $variant_i++;
            }
        }


        return $neuroForceCost;
    }

}
