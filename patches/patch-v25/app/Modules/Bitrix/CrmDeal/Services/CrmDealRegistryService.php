<?php

namespace App\Modules\Bitrix\CrmDeal\Services;

use App\Modules\Bitrix\CrmCompany\Models\CrmCompanyUf;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Bitrix\CrmDeal\Repositories\CrmDealRepository;
use App\Modules\Pub\DealProject\Services\DealProjectService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalCrmDeal;
use App\Modules\Pub\Proposal\Services\ProposalDealService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Реестр сделок Битрикса (patch v22).
 *
 * Одна выборка на все места, где показывается список сделок: страница
 * /bitrix/deal и вкладка «Сделки Битрикс» на карточке партнёра. Отсюда и
 * необязательный $partner — область партнёра ограничивает выборку его
 * компаниями Битрикса (partner_crm_companies), всё остальное работает
 * одинаково.
 *
 * Смотрим только на сделки с 2025 года: раньше в Битриксе велась другая
 * методика, сверять их с порталом бессмысленно.
 *
 * Сделки лежат в отдельной БД (соединение bitrix), КП — в основной,
 * поэтому связь «сделка → КП» собирается в PHP: привязок несколько
 * десятков, отдельный запрос дешевле кросс-базового join.
 */
class CrmDealRegistryService
{
    /** Реестр начинается с этой даты создания сделки */
    public const SINCE = '2025-01-01';

    /** Ссылка на карточку сделки в Битриксе */
    public const DEAL_URL = 'https://osmoview.bitrix24.ru/crm/deal/details/%s/';

    /** Подпись для сделок, у компании которых не заполнена страна */
    public const COUNTRY_EMPTY = 'Неизвестно';

    /** Отбор по наличию привязанного КП (поле фильтра «Привязано КП») */
    public const HAS_PROPOSAL = [
        'no' => 'нет',
        'yes' => 'да',
        'all' => 'не важно',
    ];

    /** Режимы выборки: все сделки, только с действующим проектом, только с архивным */
    public const MODE_ALL = 'all';
    public const MODE_PROJECTS = 'projects';
    public const MODE_ARCHIVE = 'archive';

    /** Значения фильтра по умолчанию: интересуют сделки, которые ещё не посчитаны */
    public const DEFAULTS = [
        'stage' => [],
        'has_proposal' => 'no',
        'manager' => [],
        'country' => [],
        'customer' => [],
        'q' => '',
    ];

    /** Поле сделки с конечным заказчиком (в Битриксе это текст, не справочник) */
    public const UF_CUSTOMER = 'uf_crm_1717755645';

    /** Карта «сделка → КП» на время запроса */
    protected static ?Collection $proposals = null;

    /**
     * Привести фильтр к нормальному виду
     *
     * @param Request|array|null $input
     * @param array $defaults Значения по умолчанию поверх DEFAULTS (см. modeDefaults())
     * @return array
     */
    public static function params($input = null, array $defaults = []): array
    {
        $input = $input instanceof Request ? $input->all() : (array) $input;
        $defaults = array_merge(static::DEFAULTS, $defaults);

        $has_proposal = (string) ($input['has_proposal'] ?? $defaults['has_proposal']);
        if (!array_key_exists($has_proposal, static::HAS_PROPOSAL)) {
            $has_proposal = $defaults['has_proposal'];
        }

        return [
            'stage' => static::listOf($input['stage'] ?? []),
            'has_proposal' => $has_proposal,
            'manager' => static::listOf($input['manager'] ?? []),
            'country' => static::listOf($input['country'] ?? []),
            'customer' => static::listOf($input['customer'] ?? []),
            'q' => trim((string) ($input['q'] ?? '')),
        ];
    }

    /**
     * Фильтр отличается от значений по умолчанию
     *
     * @param array $params
     * @param array $defaults Значения по умолчанию поверх DEFAULTS
     * @return bool
     */
    public static function filtered(array $params, array $defaults = []): bool
    {
        foreach (array_merge(static::DEFAULTS, $defaults) as $key => $default) {
            if (($params[$key] ?? $default) !== $default) return true;
        }

        return false;
    }

    /**
     * Значения фильтра по умолчанию для режима (patch v24).
     *
     * На вкладках «Проекты» и «Архив проектов» отбор «сделки без КП» бессмыслен:
     * у проектных сделок КП как раз обычно есть, — поэтому там по умолчанию
     * показываем все.
     *
     * @param string $mode
     * @return array
     */
    public static function modeDefaults(string $mode): array
    {
        return $mode === static::MODE_ALL ? [] : ['has_proposal' => 'all'];
    }

    /**
     * Привести режим к известному значению
     *
     * @param mixed $mode
     * @return string
     */
    public static function mode($mode): string
    {
        $mode = (string) $mode;
        $known = [static::MODE_ALL, static::MODE_PROJECTS, static::MODE_ARCHIVE];

        return in_array($mode, $known, true) ? $mode : static::MODE_ALL;
    }

    /**
     * Фильтр в виде параметров ссылки (пустое не тащим в URL)
     *
     * @param array $params
     * @return array
     */
    public static function query(array $params): array
    {
        return array_filter($params, fn($value) => $value !== '' && $value !== [] && $value !== null);
    }

    /**
     * Сделки реестра
     *
     * @param array $params Фильтр (см. params())
     * @param Partner|null $partner Область партнёра — только его сделки
     * @param string $mode all|projects|archive (patch v24)
     * @return Collection
     */
    public static function rows(array $params = [], ?Partner $partner = null, string $mode = self::MODE_ALL): Collection
    {
        $params = static::params($params);
        $mode = static::mode($mode);
        $country_field = 'crm_company_uf.' . CrmDealRepository::UF_COUNTRY;

        // партнёр без сопоставления с Битриксом — сделок у него нет
        if ($partner) {
            $company_ids = static::partnerCompanyIds($partner);
            if (empty($company_ids)) return collect();
        }

        $builder = CrmDeal::query()
            ->leftJoin('crm_deal_uf', 'crm_deal.id', '=', 'crm_deal_uf.deal_id')
            ->leftJoin('crm_company_uf', 'crm_deal.company_id', '=', 'crm_company_uf.company_id')
            ->select([
                'crm_deal.*',
                // конечный заказчик
                'crm_deal_uf.uf_crm_1717755645 as customer_name',
                // плановый квартал и месяц исполнения
                'crm_deal_uf.uf_crm_1722255711522 as plan_quarter',
                'crm_deal_uf.uf_crm_1736778153503 as plan_month',
                // деньги по видам
                'crm_deal_uf.uf_crm_1718977752420 as amount_licenses',
                'crm_deal_uf.uf_crm_1718977763677 as amount_services',
                'crm_deal_uf.uf_crm_1723814702122 as amount_development',
                'crm_deal_uf.uf_crm_1725019324602 as amount_platform',
                // страна получения средств — поле компании, а не сделки
                $country_field . ' as country',
            ])
            ->where('crm_deal.date_create', '>=', static::SINCE);

        if ($partner) {
            $builder->whereIn('crm_deal.company_id', $company_ids);
        }

        if (!empty($params['stage'])) {
            $builder->whereIn('crm_deal.stage_name', $params['stage']);
        }

        if (!empty($params['manager'])) {
            $builder->whereIn('crm_deal.assigned_by', $params['manager']);
        }

        if (!empty($params['country'])) {
            $builder->where(function ($builder) use ($params, $country_field) {
                $known = array_values(array_diff($params['country'], [static::COUNTRY_EMPTY]));

                if (!empty($known)) $builder->whereIn($country_field, $known);

                // «Неизвестно» — у компании страна не заполнена либо компании нет
                if (in_array(static::COUNTRY_EMPTY, $params['country'], true)) {
                    $builder->orWhereNull($country_field)->orWhere($country_field, '');
                }
            });
        }

        if (!empty($params['customer'])) {
            $builder->whereIn('crm_deal_uf.' . static::UF_CUSTOMER, $params['customer']);
        }

        if ($params['q'] !== '') {
            $like = '%' . $params['q'] . '%';

            $builder->where(function ($builder) use ($params, $like) {
                if (ctype_digit($params['q'])) $builder->orWhere('crm_deal.id', (int) $params['q']);

                $builder->orWhere('crm_deal.title', 'like', $like)
                    ->orWhere('crm_deal.company_name', 'like', $like)
                    ->orWhere('crm_deal_uf.uf_crm_1717755645', 'like', $like);
            });
        }

        // отбор по наличию КП
        $proposals = static::proposals();
        $taken = $proposals->keys()->all();

        if ($params['has_proposal'] === 'no' && !empty($taken)) {
            $builder->whereNotIn('crm_deal.id', $taken);
        }

        if ($params['has_proposal'] === 'yes') {
            $builder->whereIn('crm_deal.id', $taken ?: [0]);
        }

        // отбор по проекту: вкладки «Проекты» и «Архив проектов» (patch v24)
        if ($mode !== static::MODE_ALL) {
            $with_project = DealProjectService::dealIdsByMode($mode);
            $builder->whereIn('crm_deal.id', $with_project ?: [0]);
        }

        $rows = $builder->orderByDesc('crm_deal.date_create')->get();

        // проекты сделок — одной картой на запрос, без N+1
        $projects = DealProjectService::forDeals();

        return $rows->map(function ($row) use ($proposals, $projects) {
            $row->setAttribute('proposal', $proposals->get($row->id));
            $row->setAttribute('project', $projects->get((int) $row->id));
            $row->setAttribute('deal_url', static::url($row->id));
            $row->setAttribute('country', $row->country ?: static::COUNTRY_EMPTY);

            return $row;
        });
    }

    /**
     * Сколько строк в каждой вкладке (patch v25).
     *
     * Считаем то, что вкладка покажет по клику, — то есть с её собственными
     * значениями фильтра по умолчанию (у «Сделок» это сделки без привязанного
     * КП, у вкладок проектов — все). Текущий фильтр пользователя в счёт не
     * идёт: вкладки на него не ссылаются и открываются с чистым отбором.
     *
     * Одним лёгким запросом на все три числа: тянем только id сделок,
     * остальное считается по картам, которые и так собраны на запрос.
     *
     * @param Partner|null $partner Область партнёра — только его сделки
     * @param array $defaults Значения по умолчанию поверх DEFAULTS (карточка партнёра свои)
     * @return array ['all' => int, 'projects' => int, 'archive' => int]
     */
    public static function counts(?Partner $partner = null, array $defaults = []): array
    {
        $empty = [static::MODE_ALL => 0, static::MODE_PROJECTS => 0, static::MODE_ARCHIVE => 0];

        if ($partner) {
            $company_ids = static::partnerCompanyIds($partner);
            if (empty($company_ids)) return $empty;
        }

        $ids = CrmDeal::query()
            ->where('date_create', '>=', static::SINCE)
            ->when($partner, fn($builder) => $builder->whereIn('company_id', $company_ids ?? []))
            ->pluck('id')
            ->map(fn($id) => (int) $id);

        $taken = static::proposals()->keys()->map(fn($id) => (int) $id)->all();
        $projects = array_flip(DealProjectService::dealIdsByMode(static::MODE_PROJECTS));
        $archive = array_flip(DealProjectService::dealIdsByMode(static::MODE_ARCHIVE));

        $counts = [];

        foreach ([static::MODE_ALL, static::MODE_PROJECTS, static::MODE_ARCHIVE] as $mode) {
            $rows = match ($mode) {
                static::MODE_PROJECTS => $ids->filter(fn($id) => isset($projects[$id])),
                static::MODE_ARCHIVE => $ids->filter(fn($id) => isset($archive[$id])),
                default => $ids,
            };

            // отбор «есть КП» у вкладки такой же, каким она откроется
            $has_proposal = static::params($defaults, static::modeDefaults($mode))['has_proposal'];

            if ($has_proposal === 'no') $rows = $rows->reject(fn($id) => in_array($id, $taken, true));
            if ($has_proposal === 'yes') $rows = $rows->filter(fn($id) => in_array($id, $taken, true));

            $counts[$mode] = $rows->count();
        }

        return $counts;
    }

    /**
     * Значения для выпадающих списков фильтра.
     *
     * Формат списков — [['id' => значение, 'name' => подпись], …]: именно его
     * ждёт x-ui.select.multiple, когда значение и подпись различаются.
     *
     * @return array ['stages' => [...], 'managers' => [...], 'countries' => [...]]
     */
    public static function options(?Partner $partner = null): array
    {
        // менеджер хранится как «[83] Имя Фамилия»: в фильтр уходит значение
        // целиком, а показываем чистое имя
        $managers = ProposalDealService::managers()
            ->map(fn($item) => ['id' => $item, 'name' => trim(Str::afterLast($item, ']')) ?: $item])
            ->values()
            ->all();

        $stages = ProposalDealService::stages()
            ->map(fn($item) => ['id' => $item, 'name' => $item])
            ->values()
            ->all();

        return [
            'stages' => $stages,
            'managers' => $managers,
            'countries' => static::countries(),
            // заказчики берутся из самих сделок области: у партнёра — только его
            'customers' => static::customers($partner),
            // select ждёт список пар id/name (иначе компонент берёт первый символ подписи)
            'has_proposal_list' => collect(static::HAS_PROPOSAL)
                ->map(fn($label, $code) => ['id' => $code, 'name' => $label])
                ->values()
                ->all(),
        ];
    }

    /**
     * Заказчики сделок области (patch v25).
     *
     * В Битриксе конечный заказчик — текстовое поле сделки, а не справочник,
     * поэтому список собирается из самих сделок: на карточке партнёра это
     * заказчики его сделок, на реестре — все.
     *
     * @param Partner|null $partner Область партнёра
     * @return array [['id' => название, 'name' => название], …]
     */
    public static function customers(?Partner $partner = null): array
    {
        $field = static::UF_CUSTOMER;

        if ($partner) {
            $company_ids = static::partnerCompanyIds($partner);
            if (empty($company_ids)) return [];
        }

        return CrmDeal::query()
            ->join('crm_deal_uf', 'crm_deal.id', '=', 'crm_deal_uf.deal_id')
            ->where('crm_deal.date_create', '>=', static::SINCE)
            ->when($partner, fn($builder) => $builder->whereIn('crm_deal.company_id', $company_ids ?? []))
            ->whereNotNull('crm_deal_uf.' . $field)
            ->where('crm_deal_uf.' . $field, '!=', '')
            ->distinct()
            ->orderBy('crm_deal_uf.' . $field)
            ->pluck('crm_deal_uf.' . $field)
            ->map(fn($item) => ['id' => $item, 'name' => $item])
            ->values()
            ->all();
    }

    /**
     * Страны получения средств (поле компании в Битрикс24)
     *
     * @return array
     */
    public static function countries(): array
    {
        $field = CrmDealRepository::UF_COUNTRY;

        // у модели не задано соединение — она живёт связями от CrmCompany,
        // поэтому прямой запрос отправляем в базу Битрикса явно
        $list = CrmCompanyUf::on('bitrix')
            ->whereNotNull($field)
            ->where($field, '!=', '')
            ->distinct()
            ->orderBy($field)
            ->pluck($field)
            ->map(fn($item) => ['id' => $item, 'name' => $item])
            ->values()
            ->all();

        // сделки компаний без страны — отдельным пунктом списка
        $list[] = ['id' => static::COUNTRY_EMPTY, 'name' => static::COUNTRY_EMPTY];

        return $list;
    }

    /**
     * Карта «id сделки → последняя итерация КП»
     *
     * @return Collection
     */
    public static function proposals(): Collection
    {
        if (static::$proposals !== null) return static::$proposals;

        $links = ProposalCrmDeal::query()->get(['crm_deal_id', 'proposal_group']);
        if ($links->isEmpty()) return static::$proposals = collect();

        $proposals = Proposal::whereIn('group', $links->pluck('proposal_group')->unique()->all())
            ->latestIteration()
            ->get()
            ->keyBy('group');

        return static::$proposals = $links
            ->mapWithKeys(fn($link) => [$link->crm_deal_id => $proposals->get($link->proposal_group)])
            ->filter();
    }

    /**
     * Ссылка на сделку в Битриксе
     *
     * @param int|string $id
     * @return string
     */
    public static function url($id): string
    {
        return sprintf(static::DEAL_URL, $id);
    }

    /**
     * Компании Битрикса, сопоставленные партнёру.
     *
     * Сопоставление приезжает из patch v23; пока его нет — область партнёра
     * пустая, и вкладка честно скажет, что сопоставления не хватает.
     *
     * @param Partner $partner
     * @return array
     */
    public static function partnerCompanyIds(Partner $partner): array
    {
        return method_exists($partner, 'crmCompanyIds') ? (array) $partner->crmCompanyIds() : [];
    }

    /**
     * Значение фильтра-списка: только непустые строки
     *
     * @param mixed $value
     * @return array
     */
    protected static function listOf($value): array
    {
        return array_values(array_filter(
            array_map(fn($item) => is_scalar($item) ? trim((string) $item) : '', (array) $value),
            fn($item) => $item !== ''
        ));
    }
}
