<?php

namespace App\Modules\Pub\Partner\Services;

use App\Modules\Bitrix\CrmCompany\Models\CrmCompany;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\Constant\Models\Constant;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Models\PartnerCrmCompany;
use Illuminate\Support\Carbon;
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
    /**
     * С какой даты считаем сделки партнёра (решение владельца по ТЗ).
     * По умолчанию; рабочее значение — consts.partner_deals_from (читать через dealsFrom())
     */
    public const DEALS_FROM = '2025-01-01';

    /**
     * Дата создания сделки, с которой считаются сделки партнёра, Y-m-d
     * (consts.partner_deals_from, по умолчанию DEALS_FROM). Не дата — DEALS_FROM.
     *
     * @return string
     */
    public static function dealsFrom(): string
    {
        try {
            return Carbon::parse(Constant::value('partner_deals_from', self::DEALS_FROM))->format('Y-m-d');
        } catch (\Throwable) {
            return self::DEALS_FROM;
        }
    }

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
     * Добавить партнёру одну компанию Битрикса, не трогая остальные (patch v24).
     *
     * `sync()` заменяет весь набор — он для формы партнёра. Здесь нужно именно
     * добавление: попап проекта сопоставляет компанию сделки на ходу, а у
     * партнёра компаний Битрикса может быть несколько (в Битриксе партнёр —
     * текстовое поле, названия расходятся).
     *
     * @param Partner $partner
     * @param int $company_id компания Битрикса из сделки
     * @return void
     * @throws ValidationException компании нет в зеркале либо она занята другим партнёром
     */
    public function attach(Partner $partner, int $company_id): void
    {
        if (empty($company_id)) {
            throw ValidationException::withMessages([
                'crm_companies' => 'У сделки не указана компания Битрикс24 — сопоставлять нечего.',
            ]);
        }

        $company = CrmCompany::find($company_id);
        if (empty($company)) {
            throw ValidationException::withMessages([
                'crm_companies' => 'В Битрикс24 нет компании с id ' . $company_id . '.',
            ]);
        }

        $taken = $this->takenByOthers($partner);
        if (isset($taken[$company_id])) {
            throw ValidationException::withMessages([
                'crm_companies' => 'Компания «' . $company->title . '» уже сопоставлена партнёру '
                    . $taken[$company_id] . '.',
            ]);
        }

        // уже сопоставлена этому же партнёру — повторять нечего
        if (in_array($company_id, $partner->crmCompanyIds(), true)) return;

        PartnerCrmCompany::create([
            'partner_id' => $partner->id,
            'crm_company_id' => $company_id,
            'created_at' => now(),
        ]);

        $partner->unsetRelation('crm_companies');
    }

    /**
     * Число сделок по каждой компании с dealsFrom(): crm_company_id => количество
     *
     * @param array $company_ids
     * @return array
     */
    public function dealsCounts(array $company_ids): array
    {
        if (empty($company_ids)) return [];

        return CrmDeal::whereIn('company_id', $company_ids)
            ->where('date_create', '>=', self::dealsFrom())
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
