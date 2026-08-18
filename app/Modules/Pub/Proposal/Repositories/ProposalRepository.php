<?php

namespace App\Modules\Pub\Proposal\Repositories;

use App\Models\ModuleModel;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Constant\Models\Constant;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Proposal\Services\ProposalService;
use App\Modules\Pub\ProposalSoftware\Models\ProposalSoftware;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\ProposalVariantPlatform\Models\ProposalVariantPlatform;
use App\Modules\Pub\ProposalVariantScenario\Models\ProposalVariantScenario;
use App\Modules\Pub\ProposalVariantWork\Models\ProposalVariantWork;
use App\Modules\Pub\ProposalVariantSoftware\Models\ProposalVariantSoftware;
use App\Modules\Pub\ProposalWork\Models\ProposalWork;
use App\Modules\Pub\Scenario\Models\Scenario;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use App\Modules\Pub\Sector\Models\Sector;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use App\Modules\Pub\Proposal\Models\Proposal;
use http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProposalRepository
{
//    public static function getLastIteration(Proposal $proposal)
//    {
//        $result = DB::selectOne('SELECT MAX(iteration) AS max_iteration FROM proposals WHERE `group` = ?', [$proposal->group]);
//
//        return $result ? $result->max_iteration : null;
//    }

    public static function getLast(\App\Modules\Pub\Proposal\Models\Proposal $proposal)
    {
        return Proposal::where('group', $proposal->group)->orderBy('iteration', 'desc')->first();
    }



    public static function create_variants_new(\Illuminate\Http\Request $request, Proposal $proposal, array $params = [])
    {
        $nds_rate = $proposal->nds;

        $data = $request->all();

        // если создаём новый вариант КП - удаляем старые привязки к ID
        if('create' == ($params['mode'] ?? null)) {
            unset($data['variant_id']);
        }

        $arSoftware = $arServices = [];
        // СОЗДАЁМ ПЕРИОДЫ
        foreach($data['period_active'] as $period_num => $flag) {
            $period_type = $data['period'][$period_num];
            $period_value = $period_type == 'unlimited' ? null : $data['period_value'][$period_num];

            $partner_platform_discount = $data['partner_platform_discount'][$period_num] ?? 0;
            $partner_neuro_discount = $data['partner_neuro_discount'][$period_num] ?? 0;
            $partner_soft_discount = $data['partner_soft_discount'][$period_num] ?? 0;


            $platform_cost_total =
            $platform_cost_total_base =
            $neuro_cost_total =
            $scenario_cost_total_base =
            $discount_customer =
            $software_cost_total_base =
            $software_discount =
            $software_discount_customer =
            $software_cost_total =
            $work_cost_total_base =
            $work_discount_customer =
            $work_discount =
            $work_cost_total =
            $neuro_nds =
            $soft_nds =
            $work_nds =
            $platform_nds =
                0;

            $variant_have = !empty($data['variant_id'][$period_num]) && (int)$data['variant_id'][$period_num] > 0 ? ProposalVariant::find((int)$data['variant_id'][$period_num]) : null;

            // если мы работаем с уже существующим вариантом, то просто обновим его и удалим все вложенные сущности
            if(!empty($variant_have)) {
                $variant = $variant_have;
                $variant->update([
                    'is_main' => $data['period_main'] == $period_num,
                    'period_type' => $period_type,
                    'period_value' => $period_value,
                    'platform_discount_partner_p' => $partner_platform_discount,
                    'neuro_discount_partner_p' => $partner_neuro_discount,
                    'soft_discount_partner_p' => $partner_neuro_discount,
                ]);

                $variant->proposal_scenarios->each(function($sub_instance) {
                    $sub_instance->delete();
                });
                $variant->proposal_works->each(function($sub_instance) {
                    $sub_instance->delete();
                });
                $variant->proposal_software->each(function($sub_instance) {
                    $sub_instance->delete();
                });

            } else {
                $variant = ProposalVariant::make([
                    'is_main' => $data['period_main'] == $period_num,
                    'period_type' => $period_type,
                    'period_value' => $period_value,
                    'platform_discount_partner_p' => $partner_platform_discount,
                    'neuro_discount_partner_p' => $partner_neuro_discount,
                    'soft_discount_partner_p' => $partner_neuro_discount,
                ]);
                $variant->proposal()->associate($proposal)->save();
            }

            // ПЛАТФОРМА
            $discount_customer = 0;
            if(!empty($data['platform'])) {
                foreach ($data['platform'] as $num => $ar) {
                    $cell = $data['platform_cell'][$num][$period_num];
                    if (empty($cell['active']) || !$cell['active']) {
                        $cell['nds'] = false;
                        $cell['count'] = 0;
                        $cell['discount'] = 0;
                    }

                    $sort = $ar['sort'] ?? 0;

                    $process = $ar['cb_process'] ?? null;
                    $description = $ar['extended'] ?? null;
                    $notice = $ar['notice'] ?? null;

                    $count = (int)$cell['count'];
                    if ($count < 0) abort(404);

                    $discount = (int)$cell['discount'];
                    if ($discount < 0 || $discount > 100) abort(404);

                    $cost = $data['platform_cost'][$num][$period_num];
                    switch ($period_type) {
                        case "pilot":
                        case "year":
                            $cost *= $period_value;
                        break;
                    }

                    $cost_total = $cost * $count;
                    $discount = round($cost_total / 100 * (int)$cell['discount']);  // размер точечной скидки

                    $cost_discount = $cost_total - $discount;    // цена с учётом точечной скидки
                    $cost_final = $cost_discount * (1 - $partner_platform_discount / 100);

                    $cb_nds = $cell['nds'] ?? false;
                    $nds = $cb_nds ? ($cost_final / 100 * $nds_rate) : 0;


                    if($process) {
                        $platform_cost_total_base += $cost_total;
                        $discount_customer += $cost_discount;
                        $platform_nds += $nds;
                    }

                    $variant_platform = ProposalVariantPlatform::make([
                        'cb_process' => $process,
                        'sort' => $sort,
                        'description' => $description,
                        'notice' => $notice,
                        'cb_nds' => $cb_nds,
                        'nds' => $nds,
                        'cost' => $cost ?? 0,
                        'count' => $cell['count'] ?? 0,
                        'discount' => $cell['discount'] ?? 0, // скидка точечная
                        'cost_discount' => $count ? $cost_final / $count : 0,   // цена с учётом всех скидок
                    ]);
                    $variant_platform->proposal_variant()->associate($variant);
                    $variant_platform->save();
                }

                $discount_partner = round($discount_customer * ($partner_platform_discount / 100));
                $platform_cost_total = $discount_customer - $discount_partner;
            }
            $variant->update([
                'platform_cost_total_base' => $platform_cost_total_base,
                'platform_discount_customer' => $discount_customer,
                'platform_discount_partner' => $discount_partner ?? 0,
                'platform_cost_total' => $platform_cost_total,
                'platform_nds_cost_total' => $platform_nds,
            ]);


            // СЦЕНАРИИ
            $discount_customer = 0;
            if(!empty($data['scenario'])) {
                foreach ($data['scenario'] as $num => $ar) {
                    $scenario_id = $ar['scenario'];
                    if (empty($scenario_id)) continue;

                    $scenario = Scenario::findOrFail($scenario_id);

                    // получим стоимость и сделаем расчёт
                    $cell = $data['cell'][$num][$period_num];
                    if (empty($cell['active']) || !$cell['active']) {
                        $cell['nds'] = false;
                        $cell['count'] = 0;
                        $cell['discount'] = 0;
                    }

                    $sort = $ar['sort'] ?? 0;
                    $process = $ar['cb_process'] ?? false;
                    $real_name = ($ar['real_sync'] ?? false) ? $scenario->name : $ar['real_name'] ?? $scenario->name;
                    $mnemonic_name = $ar['mnemonic_name'] ?? null;
                    $comment = $ar['comment'] ?? null;

                    $count = (int)$cell['count'];
                    if ($count < 0) abort(404);

                    $discount = (int)$cell['discount'];
                    if ($discount < 0 || $discount > 100) abort(404);


                    // трансформируем цену
                    $cost = $data['cost'][$num][$period_num];
                    switch ($period_type) {
                        case "pilot":
                        case "year":
                            $cost *= $period_value;
                        break;
                    }

                    $cost_total = $cost * $count;
                    $discount = round($cost_total / 100 * (int)$cell['discount']);  // размер точечной скидки

                    $cost_discount = $cost_total - $discount;    // цена с учётом точечной скидки
                    $cost_final = $cost_discount * (1 - $partner_neuro_discount / 100);


                    $cb_nds = $cell['nds'] ?? false;

                    $nds = $cb_nds ? ($cost_final / 100 * $nds_rate) : 0;
                    if($process) {
                        $scenario_cost_total_base += $cost_total;
                        $discount_customer += $cost_discount;
                        $neuro_nds += $nds;
                    }


                    $variant_scenario = ProposalVariantScenario::make([
                        'cb_process' => $process,
                        'sort' => $sort,
                        'real_name' => $real_name,
                        'mnemonic_name' => $mnemonic_name,
                        'comment' => $comment,
                        'cb_nds' => $cb_nds,
                        'nds' => $nds,
                        'cost' => $cost ?? 0,
                        'count' => $cell['count'] ?? 0,
                        'discount' => $cell['discount'] ?? 0, // скидка точечная
                        'cost_discount' => $count ? $cost_final / $count : 0,   // цена с учётом всех скидок
                        'default_cost_year' => $scenario->cost_year,
                        'default_cost_unlimited' => $scenario->cost_unlimited,
                    ]);
                    $variant_scenario->proposal_variant()->associate($variant);
                    $variant_scenario->scenario()->associate($scenario);
                    $variant_scenario->save();

                    // создадим привязку нейросервиса
                    foreach ($scenario->neuroservices as $neuroservice) {
                        $variant_scenario->neuroservices()->attach($neuroservice, ['cost' => $cost]);
                    }
                }

                $discount_partner = round($discount_customer * ($partner_neuro_discount / 100));
                $neuro_cost_total = $discount_customer - $discount_partner;
            }
            $variant->update([
                'neuro_cost_total_base' => $scenario_cost_total_base,
                'neuro_discount_customer' => $discount_customer,
                'neuro_discount_partner' => $discount_partner ?? 0,
                'neuro_cost_total' => $neuro_cost_total,
                'neuro_nds_cost_total' => $neuro_nds,
            ]);


            // ПО
            $discount_customer = 0;
            if(!empty($data['soft'])) {
                foreach ($data['soft'] as $num => $soft) {

                    $cell = $data['soft_cell'][$num][$period_num];
                    if(empty($cell['discount'])) $cell['discount'] = 0;

                    $process = $soft['cb_process'] ?? false;
                    if(empty($arSoftware[$num])) {
                        $instance = ProposalSoftware::make([
                            'cb_process' => $process,
                            'description' => $soft['extended'],
                            'notice' => $soft['notice'],
                            'sort' => $soft['sort'] ?? $num * 100,
                        ]);
                        $instance->proposal()->associate($proposal)->save();
                        $arSoftware[$num] = $instance;
                    }
                    $soft = $arSoftware[$num];
                    if (empty($cell['active']) || !$cell['active']) continue;


                    $cb_partner = !empty($cell['partner']) && $cell['partner'];
                    $total = $cell['cost'] * $cell['count'];
                    $discount_customer = $cell['discount'] > 0 ? $total * ($cell['discount'] / 100) : 0;

                    $discount = $cb_partner ? ($total - $discount_customer) * ($partner_soft_discount / 100) : 0;

                    $temp = $total - $discount - $discount_customer;

                    $cb_nds = $cell['nds'] ?? false;
                    $nds = $cb_nds ? ($temp / 100 * $nds_rate) : 0;


                    if($process) {
                        $software_cost_total_base += $total;
                        $software_discount += $discount;
                        $software_discount_customer += $discount_customer;
                        $soft_nds += $nds;
                    }

                    $variant_soft = ProposalVariantSoftware::make([
                        'cb_nds' => $cb_nds,
                        'nds' => $nds,
                        'cost' => $cell['cost'] ?? 0,
                        'count' => $cell['count'] ?? 0,
                        'discount_customer' => $cell['discount'] ?? 0,
                        'cb_partner_discount' => $cb_partner,
                        'discount' => $discount + $discount_customer,
                        'total' => $total - $discount - $discount_customer,
                    ]);

                    $variant_soft->proposal_variant()->associate($variant);
                    $variant_soft->proposal_software()->associate($soft);
                    $variant_soft->save();

                }


                $software_cost_total += ($software_cost_total_base - $software_discount - $software_discount_customer);
            }
            $variant->update([
                'soft_cost_total_base' => $software_cost_total_base,
                'soft_discount_partner' => $software_discount,
                'soft_discount_customer' => $software_discount_customer,
                'soft_cost_total' => $software_cost_total_base - $software_discount - $software_discount_customer,
                'soft_nds_cost_total' => $soft_nds,
            ]);

            // РАБОТЫ
            $discount_customer = 0;
            if(!empty($data['work'])) {
                foreach ($data['work'] as $num => $work) {
                    $process = $work['cb_process'] ?? false;

                    if(empty($arWork[$num])) {
                        $instance = ProposalWork::make([
                            'cb_process' => $process,
                            'description' => $work['extended'],
                            'notice' => $work['notice'],
                            'group' => !empty($work['group']) && $work['group'] ? $work['group'] : null,
                            'sort' => $work['sort'] ?? $num * 100,
                        ]);
                        $instance->proposal()->associate($proposal)->save();
                        $arWork[$num] = $instance;
                    }
                    $work = $arWork[$num];
                    $cell = $data['work_cell'][$num][$period_num];
                    if (empty($cell['active']) || !$cell['active']) continue;


                    $discount_partner_p = $cell['discount_partner'] ?? 0;
                    $total = $cell['cost'] * $cell['count'];
                    $discount_customer = $cell['discount'] > 0 ? $total * ($cell['discount'] / 100) : 0;

                    $discount = $discount_partner_p > 0 ? ($total - $discount_customer) * ($discount_partner_p / 100) : 0;

                    $temp = $total - $discount - $discount_customer;
                    $cb_nds = $cell['nds'] ?? false;
                    $nds = $cb_nds ? ($temp / 100 * $nds_rate) : 0;

                    if($process) {
                        $work_cost_total_base += $total;
                        $work_discount += $discount;
                        $work_discount_customer += $discount_customer;
                        $work_nds += $nds;
                    }

                    $variant_work = ProposalVariantWork::make([
                        'cb_nds' => $cb_nds,
                        'nds' => $nds,
                        'cost' => $cell['cost'] ?? 0,
                        'count' => $cell['count'] ?? 0,
                        'discount_customer' => $cell['discount'] ?? 0,
                        'discount_partner' => $discount_partner_p ?? 0,
//                        'cb_partner_discount' => $cb_partner,
                        'discount' => $discount + $discount_customer,
                        'total' => $total - $discount - $discount_customer,
                    ]);

                    $variant_work->proposal_variant()->associate($variant);
                    $variant_work->proposal_work()->associate($work);
                    $variant_work->save();

                }



                $work_cost_total += ($work_cost_total_base - $work_discount - $work_discount_customer);
            }
            $variant->update([
                'work_cost_total_base' => $work_cost_total_base,
                'work_discount_partner' => $work_discount,
                'work_discount_customer' => $work_discount_customer,
                'work_cost_total' => $work_cost_total_base - $work_discount - $work_discount_customer,
                'work_nds_cost_total' => $work_nds,
            ]);

            $cost_nds_total = $work_nds + $neuro_nds + $soft_nds + $platform_nds;

            $variant->update([
                'nds_cost_total' => $cost_nds_total,
                'cost_total' => $platform_cost_total + $neuro_cost_total + $software_cost_total + $work_cost_total + $cost_nds_total,
            ]);

        }
    }



    public static function update(\Illuminate\Http\Request $request, Proposal $proposal)
    {
        // если стоит флажок, создать новый, то создадим через create
        $create_new = !empty($request->input('new_iteration')) ? (bool)$request->input('new_iteration') : false;

        if($create_new) {
            return static::create(request: $request, parent: $proposal);
        }
        DB::beginTransaction();

        // обновим proposal
        $data = $request->all();
        preg_match('/\d+$/', $data['number'], $matches);
        $data['number_int'] = $matches[0] ?? 0;

        $proposal->update([
            'name' => $data['name'],
            'name_alt' => $data['name_alt'],
            'sended_at' => $data['date'],
            'number' => $data['number'],
            'number_int' => $data['number_int'],
            'lang' => $data['lang'] ?? 'ru',
            'nds' => $data['nds'],
        ]);

        $proposal->company()->associate(Company::findOrFail($data['company']));
        $proposal->partner()->associate(Partner::findOrFail($data['partner']));
        $proposal->manager()->associate(User::findOrFail($data['manager']));
        $proposal->save();

        $proposal->software()->delete();
        $proposal->works()->delete();
        $proposal->variants->flatMap->proposal_platforms->each(function($instance) {
            $instance->delete();
        });

        // определим, какие варианты нам надо оставить, а какие удалить
        $variants_store = collect($data['variant_id'])->map(function ($item) {
            return (int) $item; // Convert to integer
        })
        ->filter(function ($item) {
            return $item !== 0; // Filter out zeros
        });
        $proposal->variants->each(function($instance) use ($variants_store) {
            if(!$variants_store->contains($instance->id))
                $instance->delete();
        });

//        static::create_variants($request, $proposal);
        static::create_variants_new($request, $proposal);
        DB::commit();



        // пересчитаем extra_pays
        $proposal->refresh();
        foreach($proposal->variants as $variant) {
            if ($variant->extra_pays->isNotEmpty())
                \App\Modules\Pub\ProposalVariantExtraPay\Services\ProposalVariantExtraPayService::recalc(variant: $variant);
        }

        return $proposal;
    }

    // если передан parent, то создаётся его новая итерация
    public static function create(\Illuminate\Http\Request $request, Proposal $parent = null)
    {

        DB::beginTransaction();
        try {
        $data = $request->all();
//        $data['neuroForceCost'] = json_decode($data['neuroForceCost'], 1);

        preg_match('/\d+$/', $data['number'], $matches);
        $data['number_int'] = $matches[0] ?? 0;

        // EDIT
        if(!empty($parent)) {
            $iteration = \App\Modules\Pub\Proposal\Models\Proposal::where("group", $parent->group)->max('iteration') + 1;
        } else {
            $iteration = 1;
        }


        // СОЗДАЁМ КП
        $proposal = Proposal::make([
            'group' => $parent->group ?? Str::uuid(),
            'iteration' => $iteration,
            'name' => $data['name'],
            'name_alt' => $data['name_alt'],
            'sended_at' => $data['date'],
            'rate_unlimited' => Constant::get('neuroservice_unlimited_multiplier') ?? 0,
            'number' => $data['number'],
            'number_int' => $data['number_int'],
            'currency_rate' => $parent->currency_rate ?? 1,
            'currency_slug' => $parent->currency_slug ?? 'RUB',
            'nds' => $data['nds'],
            'lang' => $data['lang'] ?? 'ru',
        ]);

        $proposal->company()->associate(Company::findOrFail($data['company']));
        $proposal->partner()->associate(Partner::findOrFail($data['partner']));
        $proposal->manager()->associate(User::findOrFail($data['manager']));
        $proposal->currency()->associate(CurrencyRepository::get($parent->currency_slug ?? Currency::CURRENCY_DEFAULT));
        $proposal->save();

        static::create_variants_new($request, $proposal, ['mode' => 'create']);
            \Log::info('Before commit', ['proposal_id' => $proposal->id]);
            DB::commit();
            \Log::info('After commit', ['proposal_id' => $proposal->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e; // или обработайте ошибку по-другому
        }

        $proposal->refresh();
        return $proposal;
    }


    public static function delete(Proposal $company)
    {
        $company->delete();
    }

    public static function convert(\Illuminate\Http\Request $request, Proposal $proposal)
    {
        DB::beginTransaction();
        $rate = (float)Str::replace(',', '.', $request->input('rate'));

        // КП
        $new_proposal = $proposal->replicate();
        $new_proposal->fill([
            'iteration' => ProposalService::getLastIteration($proposal) + 1,
            'currency_rate' => $rate,
            'currency_rate_cumulative' => ($proposal->rate ?? 1) * $rate,
            'name_alt' => $request->input('name_alt'),
        ])
        ->proposal_parent()->associate($proposal)
        ->currency()->associate(CurrencyRepository::get($request->input('currency')))
        ->save();

        // ПО
        $transferSoftware = [];
        foreach($proposal->software as $software) {
            $new_software = $software->replicate();
            $new_proposal->software()->save($new_software);

            $transferSoftware[$software->id] = $new_software;
        }

        // РАБОТЫ
        $transferWork = [];
        foreach($proposal->works as $work) {
            $new_work = $work->replicate();
            $new_proposal->works()->save($new_work);

            $transferWork[$work->id] = $new_work;
        }

        // ВАРИАНТЫ
        foreach($proposal->variants as $variant) {
            $new_variant = $variant->replicate();
            $fields = [
                    "platform_cost_total_base",
                    "platform_discount_customer",
                    "platform_discount_partner",
                    "platform_cost_total",
                "neuro_cost_total_base",
                "neuro_discount_customer",
                "neuro_discount_partner",
                "neuro_nds_cost_total",
                "neuro_cost_total",
                "soft_cost_total_base",
                "soft_discount_partner",
                "soft_nds_cost_total",
                "soft_cost_total",
                "soft_discount_customer",
                "work_cost_total_base",
                "work_discount_partner",
                "work_nds_cost_total",
                "work_cost_total",
                "work_discount_customer",
                "nds_cost_total",
                "cost_total"];
            foreach($fields as $field) {
                $new_variant->$field  = round($variant->$field /= $rate, 2);
            }

            $new_proposal->variants()->save($new_variant);


            // ПЛАТФОРМА
            foreach($variant->proposal_platforms as $platform) {
                $new_platform = $platform->replicate();
                $fields = ["cost", "cost_discount"];
                foreach($fields as $field) {
                    $new_platform->$field = round($platform->$field /= $rate, 2);
                }
                $new_variant->proposal_platforms()->save($new_platform);
            }

            // СЦЕНАРИИ
            foreach($variant->proposal_scenarios as $scenario) {
                $new_scenario = $scenario->replicate();
                $fields = ["cost", "nds", "cost_discount", "default_cost_year", "default_cost_unlimited"];
                foreach($fields as $field) {
                    $new_scenario->$field = round($scenario->$field /= $rate, 2);
                }
                $new_variant->proposal_scenarios()->save($new_scenario);
            }

            // РАБОТЫ
            foreach($variant->proposal_works as $work) {
                $new_work = $work->replicate();
                $fields = ["cost", "discount", "nds", "total",];
                foreach($fields as $field) {
                    $new_work->$field = round($work->$field /= $rate, 2);
                }
                $new_work->proposal_work()->associate($transferWork[$work->proposal_work_id]);
                $new_variant->proposal_works()->save($new_work);
            }


            // ПО
            foreach($variant->proposal_software as $software) {
                $new_software = $software->replicate();
                $fields = ["cost", "discount", "nds", "total",];
                foreach($fields as $field) {
                    $new_software->$field = round($software->$field /= $rate, 2);
                }
                $new_software->proposal_software()->associate($transferSoftware[$software->proposal_software_id]);
                $new_variant->proposal_software()->save($new_software);
            }

            // ВЫЧИСЛИТЕЛЬНЫЕ РЕСУРСЫ
            foreach($variant->hardware as $hardware) {
                $new_hardware = $hardware->replicate();
                $new_variant->hardware()->save($new_hardware);
            }
        }

        DB::commit();

        return $new_proposal;
    }

    public static function getID(int $id)
    {
        return Proposal::find($id);
    }

    public static function getIterations(string $group)
    {
        return Proposal::where('group', $group)->orderBy('iteration', 'desc')->get();
    }

    public static function getOnce(mixed $group, int $iteration)
    {
        return Proposal::where('group', $group)
            ->where('iteration', $iteration)->first();
    }

    public static function getForCompany(Company $company)
    {
        return static::getForCompanies()[$company->id] ?? collect();
    }

    public static function getAll()
    {
        return Proposal::select(['id', 'number', 'name', 'company_id'])->get();
    }

    public static function getForCompanies()
    {
        return Proposal::select(['id', 'group', 'company_id', 'name',])->get()->groupBy('company_id');
    }

    public static function getByGroup(string $group)
    {
        return Proposal::where('group', $group)->first();
    }


    /**
     * Получение данных для таблицы
     *
     * @param $params
     * @return array
     */
    public function getTable($params = [])
    {
        $filterService = new ProposalListFilterService($params['_token'] ?? null);
        $builder = Proposal::where('proposals.id', '>', 0);
        if(!empty($params['manager'])) {
            $builder->whereHas('manager', function ($builder) use ($params) {
                $builder->where('id', $params['manager']);
            });
        }
        $builder_full = clone $builder;

        # Filter
        $builder = $filterService->filter($builder);
        $count = $count_filtered = $builder->count();

        # Search
        if (!empty($params['search'])) {
            $builder->search($params['search']);
            $count_filtered = $builder->count();
        }

        if (!empty($params['sort']) && !empty($params['order'])) {
            switch($params['sort']) {
                case "grade":
                    $builder->join('partners', 'proposals.partner_id', '=', 'partners.id')
                        ->orderBy('partners.name', $params['order']);
                    break;
                case "company":
                    $builder->join('companies', 'proposals.company_id', '=', 'companies.id')
                    ->orderBy('companies.name', $params['order']);
                    break;
                case "date":
                    $builder->orderBy('created_at', $params['order']);
                    break;
                case "cost":
                    $builder->whereHas('last_variant', function($builder) use ($params) {
                        $builder->orderBy('cost_total', $params['order']);
                    });
                    break;
                default:
                    $builder->orderBy($params['sort'], $params['order']);
            }
        } else {
            $builder->orderBy('sended_at', 'desc');
        }

        $builder->with(['company', 'partner', 'variants'])
        ->withCount('variants');


        $builder->groupBy(['group']);
        $subquery = Proposal::select('group', DB::raw('MAX(iteration) as max_iteration'))
            ->groupBy('group');

        $builder->joinSub($subquery, 'max_iterations', function ($join) {
            $join->on('proposals.group', '=', 'max_iterations.group')
                ->on('proposals.iteration', '=', 'max_iterations.max_iteration');
        });




        $all = $builder->get();
        $limit = !empty($params['limit']) ? (int)$params['limit'] : null;
        $offset = !empty($params['offset']) ? (int)$params['offset'] : 0;
        $rows = $all->slice($offset, $limit);


        return [
            'count' => $all->count(),
            'count_filter' => $all->count(),
            'temp' => [$all->count(), $rows->count()],
            'rows' => $rows,
            'filter' => [
                'company' => $builder_full->pluck('company_id')->unique()->toArray(),
                'partner' => $builder_full->pluck('partner_id')->unique()->toArray(),
            ]
        ];
    }


}
