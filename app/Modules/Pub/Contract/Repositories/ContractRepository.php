<?php

namespace App\Modules\Pub\Contract\Repositories;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\Organization\Repositories\OrganizationRepository;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Services\ProposalLinkService;
use Illuminate\Support\Str;

class ContractRepository
{

    public static function create(Partner $partner, array $data)
    {
        // создадим договор
        $contract = Contract::create([
            'type' => $data['type'],
            'uuid' => empty($data['proposal']) ? Str::uuid() : null,
            'number' => $data['number'],
            'date' => $data['date'],
            'cb_signed' => $data['cb_signed'] ?? false,

        ])
        ->partner()->associate($partner)
        ->manager()->associate(($data['manager'] ?? null) ?: null)
        ->organization()->associate(OrganizationRepository::getOnce($data['organization']));

        $contract->save();

        // создадим спецификацию
        //        ContractSpecification::create([
        //            'name' => 'Спецификация',
        //            'amount' => 0
        //        ])
        //        ->contract()->associate($contract)
        //        ->save();



        return $contract;
    }

    public static function update(Contract $contract, array $data)
    {
        $contract->fill([
            'type' => $data['type'],
            'number' => $data['number'],
            'date' => $data['date'],
            'cb_signed' => $data['cb_signed'] ?? false,
        ])
        ->manager()->associate(($data['manager'] ?? null) ?: null)
        ->organization()->associate(OrganizationRepository::getOnce($data['organization']));

        // patch v46: форма редактирования КП не передаёт — привязка и uuid остаются как были
        if (array_key_exists('proposal', $data)) {
            // patch v33: договор к второстепенному КП не привязывается (403 с текстом, как abort() в контроллере)
            $proposal = Proposal::find($data['proposal'] ?? 0);
            if ($proposal) ProposalLinkService::assertEditable($proposal);

            $contract->uuid = empty($data['proposal']) ? Str::uuid() : null;
            $contract->proposal()->associate($proposal);
        }

        $contract->save();

        return $contract;
    }

    public static function getGroupedProposal(Company $company)
    {
        $return = [];

        foreach ($company->contracts as $contract) {
            // Используем proposal_id, если он существует, иначе используем uuid
            $key = $contract->proposal->id ?? $contract->uuid;

            // Инициализируем массив для текущего ключа, если он еще не существует
            if (!isset($return[$key])) {
                $return[$key] = [
                    'proposal' => $contract->proposal ?? null,
                    'proposal_name' => $contract->proposal_name ?? '',
                    'rows' => collect()
                ];
            }

            // Добавляем контракт в соответствующий массив
            $contract->type_decorate = ContractType::from($contract->type)->data();
            $return[$key]['rows'][] = $contract;
        }




        return $return;
    }

    public static function delete(Contract $contract)
    {
        $contract->delete();
    }
}
