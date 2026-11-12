<?php

namespace App\Modules\Pub\LicenseKey\Repository;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecificationScenario\Models\ContractSpecificationScenario;
use App\Modules\Pub\LicenseKey\Models\LicenseKey;
use App\Modules\Pub\LicenseKey\Models\LicenseKeyPeriod;
use App\Modules\Pub\Organization\Repositories\OrganizationRepository;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LicenseKeyRepository
{

    /**
     * Новый ключ компании с одним периодом
     *
     * @param Company $company
     * @param array $data active, key, active_from, active_to, specification
     * @return LicenseKey
     */
    public static function create(Company $company, array $data)
    {
        return DB::transaction(function () use ($company, $data) {
            $key = LicenseKey::create([
                'active' => $data['active'] ?? false,
                'key' => $data['key'],
                'active_from' => $data['active_from'],
                'active_to' => $data['active_to'],
            ])
            ->company()->associate($company)
            ;

            $spec = static::companySpec($key, $data['specification'] ?? null);
            $key->specification()->associate($spec);

            $key->save();

            // patch v49: у ключа всегда есть хотя бы один период
            $period = new LicenseKeyPeriod([
                'contract_specification_id' => $spec?->id,
                'active_from' => $data['active_from'],
                'active_to' => $data['active_to'],
            ]);
            $period->license_key()->associate($key)->save();

            return $key->syncFromPeriods();
        });
    }


    /**
     * Сохранить ключ и его периоды (patch v49: продление ключа по новой спецификации)
     *
     * @param LicenseKey $key
     * @param array $data active, key, periods[uuid] => [id, specification, active_from, active_to]
     * @return LicenseKey
     */
    public static function update(LicenseKey $key, array $data)
    {
        return DB::transaction(function () use ($key, $data) {
            $key->update([
                'active' => $data['active'] ?? false,
                'key' => $data['key'],
            ]);

            static::syncPeriods($key, (array) ($data['periods'] ?? []));

            return $key->syncFromPeriods();
        });
    }

    /**
     * Периоды ключа по данным формы: строки с id обновляются, без id — создаются,
     * пропавшие из формы — удаляются. Пустая форма периоды не трогает.
     *
     * @param LicenseKey $key
     * @param array $rows
     * @return void
     */
    public static function syncPeriods(LicenseKey $key, array $rows): void
    {
        if (empty($rows)) return;

        $existing = $key->periods()->get()->keyBy('id');
        $kept = [];

        foreach ($rows as $row) {
            $spec = static::companySpec($key, $row['specification'] ?? null);
            $values = [
                'contract_specification_id' => $spec?->id,
                'active_from' => $row['active_from'],
                'active_to' => $row['active_to'],
            ];

            $id = (int) ($row['id'] ?? 0);
            $period = $id ? $existing->get($id) : null;

            if ($period) {
                $period->update($values);
            } else {
                $period = new LicenseKeyPeriod($values);
                $period->license_key()->associate($key)->save();
            }

            $kept[] = $period->id;
        }

        // по одному, а не delete() запросом: журнал изменений ловит событие deleted
        $existing->except($kept)->each(fn(LicenseKeyPeriod $period) => $period->delete());
    }

    /**
     * Спецификация из формы — только своей компании
     *
     * @param LicenseKey $key
     * @param mixed $id
     * @return ContractSpecification|null
     */
    protected static function companySpec(LicenseKey $key, $id): ?ContractSpecification
    {
        if (empty($id)) return null;

        return $key->company->specifications->where('id', $id)->first();
    }

    public static function delete(LicenseKey $key)
    {
        $key->delete();
    }
}
