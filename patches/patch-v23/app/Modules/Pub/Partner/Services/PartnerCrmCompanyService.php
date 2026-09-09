<?php

namespace App\Modules\Pub\Partner\Services;

use App\Modules\Bitrix\CrmCompany\Models\CrmCompany;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Models\PartnerCrmCompany;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Сопоставление партнёров портала с компаниями Битрикс24 (patch v23).
 *
 * Компании лежат в отдельной базе (соединение bitrix), сопоставление —
 * в avgmom.partner_crm_companies. Кросс-базовых JOIN'ов здесь нет: списки
 * тянутся отдельными запросами и склеиваются в PHP.
 *
 * Одна компания принадлежит не более чем одному партнёру — это гарантирует
 * UNIQUE в таблице, а понятную ошибку вместо 500-й даёт проверка в sync().
 */
class PartnerCrmCompanyService
{
    /** С какой даты считаем сделки партнёра (решение владельца по ТЗ) */
    public const DEALS_FROM = '2025-01-01';

    /**
     * Компании Битрикса для select2 в форме партнёра.
     *
     * Возвращает все компании зеркала, отсортированные по названию. У каждой:
     * id, title, selected (уже привязана к этому партнёру),
     * taken_by (имя другого партнёра, если компания занята — такую в форме
     * помечаем и не даём выбрать).
     *
     * @param Partner|null $partner партнёр, для которого открыта форма
     * @return Collection
     */
    public function options(?Partner $partner = null): Collection
    {
        $selected = $partner ? $partner->crmCompanyIds() : [];
        $taken = $this->takenByOthers($partner);

        return CrmCompany::orderBy('title')
            ->get(['id', 'title'])
            ->map(fn(CrmCompany $company) => [
                'id' => $company->id,
                'title' => $company->title,
                'selected' => in_array($company->id, $selected),
                'taken_by' => $taken[$company->id] ?? null,
            ]);
    }

    /**
     * Компании, занятые другими партнёрами: crm_company_id => имя партнёра
     *
     * @param Partner|null $partner партнёр, чьи привязки не считаются занятыми
     * @return array
     */
    public function takenByOthers(?Partner $partner = null): array
    {
        $links = PartnerCrmCompany::query()
            ->when($partner?->id, fn($builder) => $builder->where('partner_id', '!=', $partner->id))
            ->get(['partner_id', 'crm_company_id']);

        if ($links->isEmpty()) return [];

        $partners = Partner::whereIn('id', $links->pluck('partner_id')->unique())
            ->pluck('name', 'id');

        return $links
            ->mapWithKeys(fn($link) => [
                $link->crm_company_id => $partners[$link->partner_id] ?? ('#' . $link->partner_id),
            ])
            ->all();
    }

    /**
     * Сохранение выбора компаний: лишние привязки снимаются, новые добавляются.
     *
     * Клиентский disabled в select2 обходится подделкой запроса, поэтому
     * занятость и существование компании проверяются здесь заново.
     *
     * @param Partner $partner
     * @param array $company_ids id компаний Битрикса из формы
     * @return void
     * @throws ValidationException компания занята другим партнёром или не найдена
     */
    public function sync(Partner $partner, array $company_ids): void
    {
        $company_ids = collect($company_ids)
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int)$id)
            ->filter()
            ->unique()
            ->values();

        if ($company_ids->isNotEmpty()) {
            // компания должна быть в зеркале Битрикса
            $exists = CrmCompany::whereIn('id', $company_ids)->pluck('id');
            $missed = $company_ids->diff($exists);
            if ($missed->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'crm_companies' => 'В Битрикс24 нет компаний с id: ' . $missed->implode(', '),
                ]);
            }

            // и не должна быть занята другим партнёром
            $taken = $this->takenByOthers($partner);
            $conflicts = $company_ids
                ->filter(fn($id) => isset($taken[$id]))
                ->map(fn($id) => $taken[$id] . ' (#' . $id . ')');
            if ($conflicts->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'crm_companies' => 'Компании уже сопоставлены другим партнёрам: ' . $conflicts->implode(', '),
                ]);
            }
        }

        $current = $partner->crm_companies()->pluck('crm_company_id');

        $partner->crm_companies()
            ->whereIn('crm_company_id', $current->diff($company_ids))
            ->delete();

        foreach ($company_ids->diff($current) as $company_id) {
            PartnerCrmCompany::create([
                'partner_id' => $partner->id,
                'crm_company_id' => $company_id,
                'created_at' => now(),
            ]);
        }

        $partner->unsetRelation('crm_companies');
    }

    /**
     * Число сделок по каждой компании с DEALS_FROM: crm_company_id => количество
     *
     * @param array $company_ids
     * @return array
     */
    public function dealsCounts(array $company_ids): array
    {
        if (empty($company_ids)) return [];

        return CrmDeal::whereIn('company_id', $company_ids)
            ->where('date_create', '>=', self::DEALS_FROM)
            ->groupBy('company_id')
            ->selectRaw('company_id, COUNT(*) as deals_count')
            ->pluck('deals_count', 'company_id')
            ->all();
    }

    /**
     * Привязанные компании партнёра для карточки: id, title, deals_count.
     *
     * Компании, которой уже нет в зеркале Битрикса (дамп перезаливается
     * целиком), остаются в списке без названия — привязку видно и её можно
     * снять в форме.
     *
     * @param Partner $partner
     * @return Collection
     */
    public function linked(Partner $partner): Collection
    {
        $company_ids = $partner->crmCompanyIds();
        if (empty($company_ids)) return collect();

        $titles = CrmCompany::whereIn('id', $company_ids)->pluck('title', 'id');
        $counts = $this->dealsCounts($company_ids);

        return collect($company_ids)
            ->map(fn($id) => [
                'id' => $id,
                'title' => $titles[$id] ?? null,
                'deals_count' => (int)($counts[$id] ?? 0),
            ])
            ->sortBy(fn($row) => mb_strtolower((string)$row['title']))
            ->values();
    }
}
