<?php

namespace App\Modules\Pub\DealProject\Services;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\EntityLog\Services\EntityLogService;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Constant\Models\Constant;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecificationProposal;
use App\Modules\Pub\DealProject\Models\DealProject;
use App\Modules\Pub\DealProject\Models\DealProjectDeal;
use App\Modules\Pub\DealProject\Models\DealProjectSpecification;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Models\PartnerCrmCompany;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalCrmDeal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Проекты по сделкам (patch v24).
 *
 * Одно место, где живут правила сущности «Проект»:
 *  - к сделке прикрепляется ровно один проект (UNIQUE в deal_project_deals);
 *  - к проекту — несколько сделок, но одного партнёра и одной компании;
 *  - партнёр сделки берётся из сопоставления partner_crm_companies (v23),
 *    компания — из КП сделки, иначе по названию конечного заказчика;
 *  - спецификации, пришедшие из КП сделок, отмечены и не снимаются.
 *
 * Сделки лежат в зеркале Битрикса (соединение bitrix), проекты — в портале,
 * поэтому кросс-базовых JOIN'ов здесь нет: карты собираются в PHP.
 */
class DealProjectService
{
    /** Стадии, в которых сделка считается проектной (автосоздание, patch v24) */
    public const PROJECT_STAGES = [
        'Invoice + Specification',
        'Execution (PRE-PAYMENT)',
        'Execution (POST-PAYMENT)',
        'Acceptance tests',
        'Closing documents',
        'Completed',
    ];

    /**
     * Поле сделки «Плановый квартал исполнения».
     * По умолчанию; рабочее значение — consts.bitrix_uf_quarter (читать через ufQuarter())
     */
    public const UF_QUARTER = 'uf_crm_1722255711522';

    /**
     * Поле сделки «Конечный заказчик» (название текстом).
     * По умолчанию; рабочее значение — consts.bitrix_uf_customer (читать через ufCustomer())
     */
    public const UF_CUSTOMER = 'uf_crm_1717755645';

    /** Карта «сделка → проект» на время запроса */
    protected static ?Collection $projects = null;

    /** Карта «компания Битрикса → партнёр» на время запроса */
    protected static ?array $partners = null;

    /**
     * Поле crm_deal_uf «Плановый квартал исполнения» (consts.bitrix_uf_quarter, по умолчанию UF_QUARTER).
     * Имя подставляется в SQL как колонка, поэтому допускаются только латиница, цифры и `_`.
     *
     * @return string
     */
    public static function ufQuarter(): string
    {
        $field = trim((string) Constant::value('bitrix_uf_quarter', static::UF_QUARTER));

        return preg_match('/^[a-z0-9_]+$/i', $field) ? $field : static::UF_QUARTER;
    }

    /**
     * Поле crm_deal_uf «Конечный заказчик» (consts.bitrix_uf_customer, по умолчанию UF_CUSTOMER).
     * Та же константа, что у реестра сделок (CrmDealRegistryService::ufCustomer()).
     *
     * @return string
     */
    public static function ufCustomer(): string
    {
        $field = trim((string) Constant::value('bitrix_uf_customer', static::UF_CUSTOMER));

        return preg_match('/^[a-z0-9_]+$/i', $field) ? $field : static::UF_CUSTOMER;
    }

    /**
     * Карта «id сделки → проект».
     *
     * Таблица маленькая, поэтому тянем её один раз за запрос и раздаём всем:
     * реестру, вкладкам партнёра и значку сделки (patch v25). Без списка
     * возвращает карту целиком.
     *
     * @param iterable $deal_ids
     * @return Collection ключ — id сделки, значение — DealProject
     */
    public static function forDeals(iterable $deal_ids = []): Collection
    {
        if (static::$projects === null) {
            $links = DealProjectDeal::query()->get(['crm_deal_id', 'deal_project_id']);

            $projects = $links->isEmpty()
                ? collect()
                : DealProject::whereIn('id', $links->pluck('deal_project_id')->unique()->all())
                    ->get()
                    ->keyBy('id');

            static::$projects = $links
                ->mapWithKeys(fn(DealProjectDeal $link) => [
                    (int) $link->crm_deal_id => $projects->get($link->deal_project_id),
                ])
                ->filter();
        }

        $ids = collect($deal_ids)->map(fn($id) => (int) $id)->all();

        return empty($ids) ? static::$projects : static::$projects->only($ids);
    }

    /**
     * Сбросить кэш карт (после любой записи)
     *
     * @return void
     */
    public static function flush(): void
    {
        static::$projects = null;
        static::$partners = null;
    }

    /**
     * Проект сделки, если он есть
     *
     * @param CrmDeal|int $deal
     * @return DealProject|null
     */
    public static function forDeal($deal): ?DealProject
    {
        $id = (int) ($deal instanceof CrmDeal ? $deal->id : $deal);

        return static::forDeals()->get($id);
    }

    /**
     * Id сделок, у которых есть действующий (mode = projects) либо
     * архивный (mode = archive) проект.
     *
     * @param string $mode projects|archive
     * @return array
     */
    public static function dealIdsByMode(string $mode): array
    {
        return static::forDeals()
            ->filter(fn(DealProject $project) => $mode === 'archive' ? $project->is_archived : !$project->is_archived)
            ->keys()
            ->all();
    }

    /**
     * Карта «компания Битрикса → id партнёра» (сопоставление patch v23)
     *
     * @return array
     */
    public static function partnerByCompany(): array
    {
        if (static::$partners !== null) return static::$partners;

        return static::$partners = PartnerCrmCompany::query()
            ->pluck('partner_id', 'crm_company_id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    /**
     * Партнёр сделки — по сопоставлению компании Битрикса с партнёром портала.
     *
     * Без сопоставления партнёра у сделки нет: проект такой сделке создать
     * нельзя, пока компанию не сопоставят в форме партнёра.
     *
     * @param CrmDeal $deal
     * @return Partner|null
     */
    public static function resolvePartner(CrmDeal $deal): ?Partner
    {
        $partner_id = static::partnerByCompany()[(int) $deal->company_id] ?? null;

        return $partner_id ? Partner::find($partner_id) : null;
    }

    /**
     * Компания сделки.
     *
     * Порядок: компания прикреплённого КП → компания портала с названием
     * конечного заказчика (uf_crm_1717755645) → ничего. При совпадении по
     * названию предпочитаем компанию нужного партнёра: названия заказчиков
     * в портале повторяются у разных партнёров.
     *
     * @param CrmDeal $deal
     * @param Partner|null $partner партнёр сделки, если уже известен
     * @return Company|null
     */
    public static function resolveCompany(CrmDeal $deal, ?Partner $partner = null): ?Company
    {
        $proposal = static::proposalFor($deal);
        if ($proposal && $proposal->company_id) {
            $company = Company::find($proposal->company_id);
            if ($company) return $company;
        }

        $customer = trim((string) static::customerName($deal));
        if ($customer === '') return null;

        $partner = $partner ?: static::resolvePartner($deal);

        $matched = Company::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($customer)])
            ->orderBy('id')
            ->get();

        if ($matched->isEmpty()) return null;

        return $matched->firstWhere('partner_id', $partner?->id) ?? $matched->first();
    }

    /**
     * Название конечного заказчика из сделки
     *
     * @param CrmDeal $deal
     * @return string|null
     */
    public static function customerName(CrmDeal $deal): ?string
    {
        // в реестре поле уже выбрано алиасом customer_name, иначе тянем UF
        return $deal->customer_name ?? $deal->dealUf?->{static::ufCustomer()};
    }

    /**
     * Последняя редакция КП, прикреплённого к сделке
     *
     * @param CrmDeal|int $deal
     * @return Proposal|null
     */
    public static function proposalFor($deal): ?Proposal
    {
        $id = (int) ($deal instanceof CrmDeal ? $deal->id : $deal);

        $groups = ProposalCrmDeal::where('crm_deal_id', $id)->pluck('proposal_group');
        if ($groups->isEmpty()) return null;

        return Proposal::whereIn('group', $groups->all())->latestIteration()->first();
    }

    /**
     * Группы КП, прикреплённых к перечисленным сделкам
     *
     * @param iterable $deal_ids
     * @return array
     */
    public static function proposalGroups(iterable $deal_ids): array
    {
        $ids = collect($deal_ids)->map(fn($id) => (int) $id)->filter()->all();
        if (empty($ids)) return [];

        return ProposalCrmDeal::whereIn('crm_deal_id', $ids)
            ->pluck('proposal_group')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Спецификации, «прибитые» к проекту через КП его сделок.
     *
     * Цепочка: сделка → КП (proposal_crm_deals) → спецификация
     * (contract_specification_proposals). Такие спецификации в попапе отмечены
     * и readonly — снять их руками нельзя.
     *
     * @param DealProject $project
     * @return Collection ключ — id спецификации, значение — ['spec' => ..., 'proposals' => Collection]
     */
    public static function lockedSpecs(DealProject $project): Collection
    {
        return static::lockedSpecsForDeals($project->dealIds());
    }

    /**
     * То же для набора сделок: нужно ещё до создания проекта — попап показывает
     * спецификации будущего проекта сразу.
     *
     * @param iterable $deal_ids
     * @return Collection
     */
    public static function lockedSpecsForDeals(iterable $deal_ids): Collection
    {
        $groups = static::proposalGroups($deal_ids);
        if (empty($groups)) return collect();

        $links = ContractSpecificationProposal::whereIn('proposal_group', $groups)->get();
        if ($links->isEmpty()) return collect();

        $specs = ContractSpecification::whereIn('id', $links->pluck('contract_specification_id')->unique()->all())
            ->with(['contract', 'company', 'currency'])
            ->get()
            ->keyBy('id');

        $proposals = Proposal::whereIn('group', $links->pluck('proposal_group')->unique()->all())
            ->latestIteration()
            ->get()
            ->keyBy('group');

        return $links
            ->groupBy('contract_specification_id')
            ->mapWithKeys(function (Collection $group, $spec_id) use ($specs, $proposals) {
                $spec = $specs->get($spec_id);
                if (empty($spec)) return [];

                return [(int) $spec_id => [
                    'spec' => $spec,
                    'proposals' => $group->map(fn($link) => $proposals->get($link->proposal_group))->filter()->values(),
                ]];
            });
    }

    /**
     * Спецификации партнёра, которые можно отнести к проекту.
     *
     * Партнёр определяется через рамочный договор (contracts.partner_id).
     * Компания сужает список: у проекта она одна.
     *
     * @param Partner $partner
     * @param Company|null $company
     * @return Collection
     */
    public static function availableSpecs(Partner $partner, ?Company $company = null): Collection
    {
        return ContractSpecification::query()
            ->whereHas('contract', fn($builder) => $builder->where('partner_id', $partner->id))
            ->when($company, fn($builder) => $builder->where('company_id', $company->id))
            ->with(['contract', 'company', 'currency'])
            ->get()
            ->sortByDesc(fn(ContractSpecification $spec) => $spec->date_create ?? $spec->contract?->date)
            ->values();
    }

    /**
     * Действующие проекты партнёра, к которым можно прикрепить сделку.
     *
     * Компания у проекта и у сделки должна совпадать — иначе это другой проект;
     * проекты без компании показываем всегда, их компанию ещё не определили.
     *
     * @param Partner $partner
     * @param Company|null $company
     * @param int|null $except_deal сделка, чей проект из списка убираем
     * @return Collection
     */
    public static function activeProjects(Partner $partner, ?Company $company = null, ?int $except_deal = null): Collection
    {
        $projects = DealProject::query()
            ->active()
            ->where('partner_id', $partner->id)
            ->when($company, fn($builder) => $builder->where(function ($builder) use ($company) {
                $builder->where('company_id', $company->id)->orWhereNull('company_id');
            }))
            ->with(['company', 'deals'])
            ->orderByDesc('date_start')
            ->get();

        if ($except_deal) {
            $own = static::forDeal($except_deal);
            if ($own) $projects = $projects->reject(fn(DealProject $item) => $item->id === $own->id)->values();
        }

        return $projects;
    }

    /**
     * Создать проект для сделки.
     *
     * @param CrmDeal $deal сделка, ради которой создаётся проект
     * @param array $data date_start, is_pilot, company_id, deadline, comment, specs[]
     * @return DealProject
     * @throws ValidationException сделка уже в проекте либо партнёр не сопоставлен
     */
    public static function createForDeal(CrmDeal $deal, array $data): DealProject
    {
        if (static::forDeal($deal)) {
            static::fail('У сделки уже есть проект.');
        }

        $partner = static::resolvePartner($deal);
        if (empty($partner)) {
            static::fail('Компания сделки не сопоставлена ни с одним партнёром — сопоставьте её в форме партнёра.');
        }

        $company = static::companyFromInput($partner, $data);

        $project = new DealProject([
            'partner_id' => $partner->id,
            'company_id' => $company?->id,
            'date_start' => static::dateOrNull($data['date_start'] ?? null) ?? now()->toDateString(),
            'is_pilot' => !empty($data['is_pilot']),
            // срок ставится только пилоту (правка владельца 11.09.2026)
            'deadline' => !empty($data['is_pilot']) ? static::dateOrNull($data['deadline'] ?? null) : null,
            'comment' => static::text($data['comment'] ?? null),
            'created_by' => auth()->id(),
        ]);
        $project->save();

        DealProjectDeal::create([
            'deal_project_id' => $project->id,
            'crm_deal_id' => $deal->id,
            'attached_at' => now(),
            'attached_by' => auth()->id(),
        ]);

        static::flush();

        static::syncSpecs($project, (array) ($data['specs'] ?? []));

        return $project->fresh();
    }

    /**
     * Изменить проект
     *
     * @param DealProject $project
     * @param array $data date_start, is_pilot, company_id, deadline, comment, specs[]
     * @return DealProject
     * @throws ValidationException
     */
    public static function update(DealProject $project, array $data): DealProject
    {
        $partner = $project->partner;
        if (empty($partner)) static::fail('У проекта не указан партнёр.');

        $company = array_key_exists('company_id', $data)
            ? static::companyFromInput($partner, $data)
            : $project->company;

        $is_pilot = array_key_exists('is_pilot', $data) ? !empty($data['is_pilot']) : $project->is_pilot;

        $project->fill([
            'company_id' => $company?->id,
            'date_start' => static::dateOrNull($data['date_start'] ?? null) ?? $project->date_start,
            'is_pilot' => $is_pilot,
            // срок ставится только пилоту (правка владельца 11.09.2026)
            'deadline' => $is_pilot ? static::dateOrNull($data['deadline'] ?? null) : null,
            'comment' => static::text($data['comment'] ?? null),
        ])->save();

        static::flush();

        if (array_key_exists('specs', $data)) static::syncSpecs($project, (array) $data['specs']);

        return $project->fresh();
    }

    /**
     * Прикрепить сделку к существующему проекту.
     *
     * Проверяем то же, что и при создании: сделка свободна, партнёр совпадает,
     * компания совпадает (если она известна и у сделки, и у проекта).
     *
     * @param DealProject $project
     * @param CrmDeal $deal
     * @return DealProjectDeal
     * @throws ValidationException
     */
    public static function attachDeal(DealProject $project, CrmDeal $deal): DealProjectDeal
    {
        $existing = static::forDeal($deal);
        if ($existing && (int) $existing->id === (int) $project->id) {
            static::fail('Сделка уже прикреплена к этому проекту.');
        }
        if ($existing) {
            static::fail('Сделка уже относится к проекту от ' . $existing->date_start?->format('d.m.Y') . '.');
        }

        $partner = static::resolvePartner($deal);
        if (empty($partner)) {
            static::fail('Компания сделки не сопоставлена ни с одним партнёром — сопоставьте её в форме партнёра.');
        }
        if ((int) $partner->id !== (int) $project->partner_id) {
            static::fail('У сделки другой партнёр: ' . $partner->name . '.');
        }

        $company = static::resolveCompany($deal, $partner);
        if ($company && $project->company_id && (int) $company->id !== (int) $project->company_id) {
            static::fail('У сделки другая компания: ' . $company->name . '.');
        }

        $link = DealProjectDeal::create([
            'deal_project_id' => $project->id,
            'crm_deal_id' => $deal->id,
            'attached_at' => now(),
            'attached_by' => auth()->id(),
        ]);

        static::flush();

        // КП новой сделки могло принести свои спецификации
        static::syncSpecs($project, static::manualSpecIds($project));

        return $link;
    }

    /**
     * Открепить сделку от проекта.
     *
     * Проект остаётся, даже если сделок в нём больше не осталось: он мог быть
     * заведён руками и ждать другую сделку.
     *
     * @param CrmDeal|int $deal
     * @return DealProject|null проект, от которого открепили
     */
    public static function detachDeal($deal): ?DealProject
    {
        $id = (int) ($deal instanceof CrmDeal ? $deal->id : $deal);
        $project = static::forDeal($id);

        // массовое удаление не вызывает событий модели — журнал проекта отмечаем сами
        EntityLogService::around($project, fn() => DealProjectDeal::where('crm_deal_id', $id)->delete());
        static::flush();

        // спецификации из КП открепившейся сделки больше не «прибиты»
        if ($project) static::syncSpecs($project, static::manualSpecIds($project));

        return $project;
    }

    /**
     * Отправить проект в архив
     *
     * @param DealProject $project
     * @return DealProject
     */
    public static function archive(DealProject $project): DealProject
    {
        $project->forceFill([
            'archived_at' => now(),
            'archived_by' => auth()->id(),
        ])->save();

        static::flush();

        return $project;
    }

    /**
     * Вернуть проект из архива
     *
     * @param DealProject $project
     * @return DealProject
     */
    public static function unarchive(DealProject $project): DealProject
    {
        $project->forceFill([
            'archived_at' => null,
            'archived_by' => null,
        ])->save();

        static::flush();

        return $project;
    }

    /**
     * Пересобрать спецификации проекта.
     *
     * Спецификации из КП (from_proposal) ставятся всегда и не зависят от формы:
     * связь «сделка → КП → спецификация» уже существует. Остальные —
     * ровно те, что отмечены руками.
     *
     * @param DealProject $project
     * @param array $spec_ids отмеченные руками
     * @return void
     * @throws ValidationException в списке чужая спецификация
     */
    public static function syncSpecs(DealProject $project, array $spec_ids): void
    {
        $locked = static::lockedSpecs($project)->keys()->map(fn($id) => (int) $id);

        $manual = collect($spec_ids)
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->diff($locked)
            ->values();

        if ($manual->isNotEmpty() && $project->partner) {
            // подделанный запрос: спецификация должна принадлежать партнёру проекта
            $allowed = static::availableSpecs($project->partner, $project->company)
                ->pluck('id')
                ->map(fn($id) => (int) $id);

            $alien = $manual->diff($allowed);

            if ($alien->isNotEmpty()) {
                static::fail('Спецификации не относятся к партнёру и компании проекта: ' . $alien->implode(', '));
            }
        }

        $keep = $locked->merge($manual)->unique()->values();

        // массовые delete/update не вызывают событий модели — журнал проекта отмечаем сами
        EntityLogService::around($project, fn() => DealProjectSpecification::where('deal_project_id', $project->id)
            ->whereNotIn('contract_specification_id', $keep->all() ?: [0])
            ->delete());

        $exists = DealProjectSpecification::where('deal_project_id', $project->id)
            ->pluck('from_proposal', 'contract_specification_id');

        foreach ($keep as $spec_id) {
            $from_proposal = $locked->contains($spec_id);

            if (!$exists->has($spec_id)) {
                DealProjectSpecification::create([
                    'deal_project_id' => $project->id,
                    'contract_specification_id' => $spec_id,
                    'from_proposal' => $from_proposal,
                ]);
                continue;
            }

            // признак мог смениться: КП прикрепили или открепили
            if ((bool) $exists->get($spec_id) !== $from_proposal) {
                EntityLogService::around($project, fn() => DealProjectSpecification::where('deal_project_id', $project->id)
                    ->where('contract_specification_id', $spec_id)
                    ->update(['from_proposal' => $from_proposal]));
            }
        }
    }

    /**
     * Спецификации проекта, отмеченные руками (не из КП)
     *
     * @param DealProject $project
     * @return array
     */
    public static function manualSpecIds(DealProject $project): array
    {
        return DealProjectSpecification::where('deal_project_id', $project->id)
            ->where('from_proposal', false)
            ->pluck('contract_specification_id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    /**
     * Спецификации проекта для карточки: модель + признак «из КП»
     *
     * @param DealProject $project
     * @return Collection
     */
    public static function specifications(DealProject $project): Collection
    {
        $links = DealProjectSpecification::where('deal_project_id', $project->id)->get();
        if ($links->isEmpty()) return collect();

        $specs = ContractSpecification::whereIn('id', $links->pluck('contract_specification_id')->all())
            // платежи нужны карточке проекта: она показывает оплаты по каждой
            // спецификации отдельно (patch v25)
            ->with(['contract', 'company', 'currency', 'payments'])
            ->get()
            ->keyBy('id');

        return $links
            ->map(fn(DealProjectSpecification $link) => [
                'spec' => $specs->get($link->contract_specification_id),
                'from_proposal' => (bool) $link->from_proposal,
            ])
            ->filter(fn($row) => !empty($row['spec']))
            ->sortByDesc(fn($row) => $row['spec']->date_create ?? $row['spec']->contract?->date)
            ->values();
    }

    /**
     * Дата начала проекта по плановому кварталу сделки.
     *
     * `2025q3` → первый день квартала (2025-07-01); «не выбрано» и мусор →
     * дата начала сделки (begindate), а если и её нет — запасное значение.
     *
     * @param string|null $quarter значение uf_crm_1722255711522
     * @param mixed $begindate
     * @param mixed $fallback
     * @return string дата в формате Y-m-d
     */
    public static function dateStartFrom(?string $quarter, $begindate = null, $fallback = null): string
    {
        $quarter = mb_strtolower(trim((string) $quarter));

        if (preg_match('/^(\d{4})q([1-4])$/', $quarter, $m)) {
            return Carbon::create((int) $m[1], ((int) $m[2] - 1) * 3 + 1, 1)->toDateString();
        }

        foreach ([$begindate, $fallback] as $value) {
            $date = static::dateOrNull($value);
            if ($date) return $date;
        }

        return now()->toDateString();
    }

    /**
     * Компания из формы: должна принадлежать партнёру проекта
     *
     * @param Partner $partner
     * @param array $data
     * @return Company|null
     * @throws ValidationException
     */
    protected static function companyFromInput(Partner $partner, array $data): ?Company
    {
        $company_id = $data['company_id'] ?? null;
        if (empty($company_id)) return null;

        $company = Company::find((int) $company_id);
        if (empty($company) || (int) $company->partner_id !== (int) $partner->id) {
            static::fail('Компания не относится к партнёру ' . $partner->name . '.');
        }

        return $company;
    }

    /**
     * Дата в формате Y-m-d либо null
     *
     * @param mixed $value
     * @return string|null
     */
    protected static function dateOrNull($value): ?string
    {
        if (empty($value)) return null;

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Текст комментария: пустая строка — это null
     *
     * @param mixed $value
     * @return string|null
     */
    protected static function text($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Понятная ошибка вместо 500-й
     *
     * @param string $message
     * @return void
     * @throws ValidationException
     */
    protected static function fail(string $message): void
    {
        throw ValidationException::withMessages(['deal_project' => $message]);
    }
}
