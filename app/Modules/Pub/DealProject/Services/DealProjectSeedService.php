<?php

namespace App\Modules\Pub\DealProject\Services;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\DealProject\Models\DealProject;
use App\Modules\Pub\DealProject\Models\DealProjectDeal;

/**
 * Разовое автосоздание проектов по сделкам Битрикса (patch v24).
 *
 * Решение владельца: проекты заводятся только по сделкам с 2025 года и только
 * в проектных стадиях (DealProjectService::PROJECT_STAGES). Одна сделка — один
 * проект: сделки не группируются, объединять их в общий проект пользователь
 * будет руками, прикрепляя к существующему.
 *
 * Запускается один раз на проде командой `deal-project:seed`. Повторный запуск
 * безопасен: сделки, у которых проект уже есть, пропускаются.
 */
class DealProjectSeedService
{
    /**
     * Пройтись по проектным сделкам и завести им проекты
     *
     * @param string $from дата создания сделки, с которой смотрим
     * @param bool $dry только посчитать, ничего не писать
     * @return array отчёт: created, skipped, rows[]
     */
    public static function run(string $from = '2025-01-01', bool $dry = false): array
    {
        $deals = CrmDeal::query()
            ->leftJoin('crm_deal_uf', 'crm_deal.id', '=', 'crm_deal_uf.deal_id')
            ->select([
                'crm_deal.*',
                'crm_deal_uf.' . DealProjectService::ufCustomer() . ' as customer_name',
                'crm_deal_uf.' . DealProjectService::ufQuarter() . ' as plan_quarter',
            ])
            ->where('crm_deal.date_create', '>=', $from)
            ->whereIn('crm_deal.stage_name', DealProjectService::PROJECT_STAGES)
            ->orderBy('crm_deal.id')
            ->get();

        $report = ['created' => 0, 'skipped' => 0, 'rows' => []];

        foreach ($deals as $deal) {
            $row = static::one($deal, $dry);

            $report['rows'][] = $row;
            $row['status'] === 'created' ? $report['created']++ : $report['skipped']++;
        }

        return $report;
    }

    /**
     * Обработать одну сделку
     *
     * @param CrmDeal $deal
     * @param bool $dry
     * @return array ['deal' => id, 'title' => …, 'status' => created|exists|no_partner, 'reason' => …]
     */
    protected static function one(CrmDeal $deal, bool $dry): array
    {
        $row = [
            'deal' => (int) $deal->id,
            'title' => (string) $deal->title,
            'stage' => (string) $deal->stage_name,
            'status' => 'created',
            'reason' => '',
            'partner' => '',
            'company' => '',
            'date_start' => '',
            'specs' => 0,
        ];

        if (DealProjectService::forDeal($deal)) {
            return array_merge($row, ['status' => 'exists', 'reason' => 'у сделки уже есть проект']);
        }

        $partner = DealProjectService::resolvePartner($deal);
        if (empty($partner)) {
            return array_merge($row, [
                'status' => 'no_partner',
                'reason' => 'компания Битрикса #' . ($deal->company_id ?: '—')
                    . ' (' . ($deal->company_name ?: 'без названия') . ') не сопоставлена с партнёром',
            ]);
        }

        $company = DealProjectService::resolveCompany($deal, $partner);
        $date_start = DealProjectService::dateStartFrom($deal->plan_quarter, $deal->begindate, $deal->date_create);

        $row['partner'] = $partner->name;
        $row['company'] = $company?->name ?: '—';
        $row['date_start'] = $date_start;
        $row['specs'] = DealProjectService::lockedSpecsForDeals([$deal->id])->count();

        if ($dry) return $row;

        $project = new DealProject([
            'partner_id' => $partner->id,
            'company_id' => $company?->id,
            'date_start' => $date_start,
            // пилот отмечается руками: в Битриксе такого признака нет
            'is_pilot' => false,
            'comment' => null,
            'created_by' => auth()->id(),
        ]);
        $project->save();

        DealProjectDeal::create([
            'deal_project_id' => $project->id,
            'crm_deal_id' => $deal->id,
            'attached_at' => now(),
            'attached_by' => auth()->id(),
        ]);

        DealProjectService::flush();

        // спецификации из КП сделки прибиваются сразу (from_proposal)
        DealProjectService::syncSpecs($project, []);

        return $row;
    }
}
