<?php

namespace App\Modules\Pub\Analytics\Services;

use App\Modules\Pub\ContractSpecification\Services\SpecProposalService;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Models\PartnerGrade;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Скоринг партнёров.
 *
 * Патч v16: рейтинг пересобран. Раньше он считал КП и договоры штуками, из-за
 * чего десять предложений по 50 тысяч перевешивали одну спецификацию на
 * миллион. Теперь главное — деньги подписанных спецификаций, остальное
 * уточняет картину:
 *
 *   размер ожидаемых платежей        вес 35
 *   сумма подписанных спецификаций   вес 30
 *   конверсия решённых КП            вес 25
 *   доля просроченных платежей       вес 5 (чем меньше, тем выше балл)
 *   количество КП за год             вес 5
 *
 * Балл сглаживается по трём годам: текущий 75%, прошлый 20%, позапрошлый 5%.
 * Иначе партнёр, подписавший крупный договор в прошлом году, в этом выглядел
 * пустым — КП и спецификации остались в прошлом году, а деньги ещё идут.
 *
 * Ожидаемые платежи по той же причине считаются «от выбранного года и дальше»:
 * график договора 2024 года, растянутый до 2026-го, даёт вес и в 2025-м.
 *
 * Сроки считаются по дате спецификации (`date_create`), а не по дате
 * прикрепления КП: прикрепление говорит о том, когда данные завели в портал.
 *
 * Патч v20: выигранным считается не только КП со статусом «Выиграно» в базе,
 * но и любое КП, прикреплённое к спецификации (кроме проигранных и
 * отменённых). Прикрепление — это и есть факт продажи, а статус в базе мог
 * остаться старым (например, если КП заводили до патча v16 или статус не
 * обновился). КП без даты отправки относится к году своей спецификации,
 * иначе прикреплённые КП вываливались из отбора по году.
 *
 * Балл нормируется: у лучшего партнёра выборки всегда 100. Это шкала «кто
 * сильнее относительно остальных», а не абсолютная оценка — абсолютных
 * рублёвых порогов, одинаково честных для всех, не бывает.
 *
 * Суммы приводятся к рублям по текущему курсу: у партнёров спецификации и КП
 * в разных валютах, иначе сравнивать нечего.
 */
class PartnerScoringService
{
    /** Веса составляющих балла, в сумме 100 */
    public const WEIGHT_EXPECTED = 35;
    public const WEIGHT_SPECS = 30;
    public const WEIGHT_CONVERSION = 25;
    public const WEIGHT_OVERDUE = 5;
    public const WEIGHT_PROPOSALS = 5;

    /** Вклад года в итоговый балл: смещение назад → вес, в сумме 100 */
    public const YEAR_WEIGHTS = [0 => 75, 1 => 20, 2 => 5];

    /** Сколько лет назад считать место в рейтинге для графика */
    public const HISTORY_YEARS = 5;

    /** Кэш курсов на запрос */
    protected static array $rates = [];

    /** Кэш готовых выборок по годам: год → строки с баллом и местом */
    protected static array $cache = [];

    /** Кэш сырых баллов по годам (до сглаживания и нормировки) */
    protected static array $raw_cache = [];

    /**
     * Партнёры с показателями и баллом
     *
     * @param array $params ['year' => int|null, 'grade' => string|null, 'q' => string|null]
     * @return Collection
     */
    public static function rows(array $params = []): Collection
    {
        $rows = static::ranked($params['year'] ?? null);

        if (!empty($params['grade'])) {
            $rows = $rows->filter(fn($row) => $row['grade_key'] === $params['grade'])->values();
        }

        if (!empty($params['q'])) {
            $like = mb_strtolower(trim($params['q']));
            $rows = $rows->filter(fn($row) => str_contains(mb_strtolower((string) $row['partner']->name), $like))->values();
        }

        return $rows;
    }

    /**
     * Все партнёры года, с баллом и местом. Результат кэшируется на запрос:
     * график места по годам просит те же выборки повторно.
     *
     * @param int|null $year
     * @return Collection
     */
    public static function ranked(?int $year = null): Collection
    {
        $key = (string) ($year ?? 'all');
        if (isset(static::$cache[$key])) return static::$cache[$key];

        $rows = static::blend(static::raw($year), $year);
        $rows = static::normalize($rows)->sortByDesc('score')->values();

        $rows = $rows->map(function ($row, $i) {
            $row['place'] = $i + 1;
            return $row;
        });

        return static::$cache[$key] = $rows;
    }

    /**
     * Сырые баллы за один год, без сглаживания и нормировки
     *
     * @param int|null $year
     * @return Collection
     */
    public static function raw(?int $year = null): Collection
    {
        $key = (string) ($year ?? 'all');

        return static::$raw_cache[$key] ??= static::components(static::metrics($year));
    }

    /**
     * Сгладить балл по трём годам: текущий 75%, прошлый 20%, позапрошлый 5%.
     *
     * Показатели в строке остаются за выбранный год — меняется только балл.
     * Без года (вся история) сглаживать нечего.
     *
     * @param Collection $rows
     * @param int|null $year
     * @return Collection
     */
    public static function blend(Collection $rows, ?int $year = null): Collection
    {
        if (empty($year)) {
            return $rows->map(function ($row) {
                $row['score_years'] = [];
                $row['score_blended'] = $row['score_raw'];
                return $row;
            });
        }

        // прошлые годы: id партнёра → строка
        $past = [];
        foreach (static::YEAR_WEIGHTS as $offset => $weight) {
            if ($offset === 0) continue;
            $past[$offset] = static::raw($year - $offset)->keyBy(fn($row) => (int) $row['partner']->id);
        }

        return $rows->map(function ($row) use ($past, $year) {
            $id = (int) $row['partner']->id;
            $weight_now = static::YEAR_WEIGHTS[0];

            $years = [[
                'year' => $year,
                'weight' => $weight_now,
                'score' => $row['score_raw'],
                'current' => true,
            ]];

            $blended = $row['score_raw'] * $weight_now / 100;

            foreach ($past as $offset => $rows_past) {
                $weight = static::YEAR_WEIGHTS[$offset];
                $score = (float) ($rows_past->get($id)['score_raw'] ?? 0);
                $blended += $score * $weight / 100;

                $years[] = [
                    'year' => $year - $offset,
                    'weight' => $weight,
                    'score' => $score,
                    'current' => false,
                ];
            }

            $row['score_years'] = $years;
            $row['score_blended'] = $blended;

            return $row;
        });
    }

    /**
     * Сырые показатели по всем партнёрам за год
     *
     * @param int|null $year null — вся история
     * @return Collection
     */
    public static function metrics(?int $year = null): Collection
    {
        $proposals = static::proposals()->groupBy('partner_id');
        $specs = static::specifications()->groupBy('partner_id');
        $payments = static::payments()->groupBy('partner_id');
        $links = static::links()->groupBy('partner_id');

        $ids = collect()
            ->concat($proposals->keys())
            ->concat($specs->keys())
            ->unique()
            ->filter()
            ->values();

        if ($ids->isEmpty()) return collect();

        $partners = Partner::whereIn('id', $ids)->get()->keyBy('id');

        return $ids
            ->map(fn($id) => static::partner(
                partner: $partners->get($id),
                proposals: $proposals->get($id, collect()),
                specs: $specs->get($id, collect()),
                payments: $payments->get($id, collect()),
                links: $links->get($id, collect()),
                year: $year
            ))
            ->filter(fn($row) => !empty($row['partner']))
            ->values();
    }

    /**
     * Показатели одного партнёра
     *
     * @param mixed $partner
     * @param Collection $proposals
     * @param Collection $specs
     * @param Collection $payments
     * @param Collection $links
     * @param int|null $year
     * @return array
     */
    public static function partner($partner, Collection $proposals, Collection $specs, Collection $payments, Collection $links, ?int $year = null): array
    {
        if (empty($partner)) return ['partner' => null];

        // ожидаемые платежи смотрят вперёд, поэтому нужна невырезанная выборка
        $payments_all = $payments;

        // отбор по году: КП — по дате отправки, спецификации — по своей дате
        // (если не заполнена, по дате рамочного договора)
        if ($year) {
            $proposals = $proposals->filter(fn($row) => $row->year_ref === $year);
            $specs = $specs->filter(fn($row) => static::specYear($row) === $year);
            $payments = $payments->filter(fn($row) => ($row->date_fact ?? $row->date_plan)?->year === $year);
            $links = $links->filter(fn($row) => static::linkDate($row)?->year === $year);
        }

        $won = $proposals->filter(fn($row) => (string) $row->status_effective === ProposalStatus::WON->value);
        $lost = $proposals->filter(fn($row) => (string) $row->status_effective === ProposalStatus::LOST->value);
        $decided = $won->count() + $lost->count();

        $amount_won = $won->sum(fn($row) => (float) $row->cost_total * static::rate($row->currency_slug));

        $specs_signed = $specs->filter(fn($row) => !empty($row->is_signed));
        $specs_active = $specs->filter(fn($row) => strtolower((string) $row->status) !== 'canceled');
        $specs_sum = $specs_signed->sum(fn($row) => (float) $row->amount * static::rate($row->currency_slug));

        $paid = $payments->where('state', 'paid');
        $overdue = $payments->where('state', 'overdue');
        $decided_payments = $paid->count() + $overdue->count();

        // ожидаемые платежи: неоплаченное, что ещё должно прийти. Считаются не за
        // год, а «от выбранного года и дальше»: график договора 2024-го, растянутый
        // до 2026-го, должен давать вес и в 2025-м. Просрочка сюда не входит —
        // у неё свой показатель
        $expected = $payments_all->filter(fn($row) => in_array($row->state, ['planned', 'unknown'])
            && (empty($year) || empty($row->date_plan) || $row->date_plan->year >= $year));

        // КП за последний год — отдельный показатель: он про активность сейчас,
        // а не про накопленную историю
        $recent = $year
            ? $proposals->count()
            : $proposals->filter(fn($row) => $row->sended_at && $row->sended_at->greaterThan(now()->subYear()))->count();

        // срок от последнего выставленного КП до даты спецификации.
        // Отрицательные значения не выбрасываем: КП часто выставлено позже даты
        // спецификации (старые записи, дозаказы), и прежний отбор по >= 0 обнулял
        // показатель целиком — выглядело как «нет прикреплённых КП» при том, что КП есть
        $days = $links
            ->map(fn($row) => static::linkDays($row))
            ->filter(fn($value) => $value !== null);

        return [
            'partner' => $partner,
            'grade' => PartnerGrade::tryFrom((string) $partner->grade)?->data(),
            'grade_key' => (string) $partner->grade,

            'proposals' => $proposals->count(),
            'won' => $won->count(),
            'lost' => $lost->count(),
            'in_work' => $proposals->count() - $decided,
            'conversion' => $decided > 0 ? $won->count() / $decided * 100 : null,
            'proposals_recent' => $recent,
            'amount_won' => $amount_won,

            'contracts' => $specs->pluck('contract_id')->unique()->count(),
            'specs' => $specs->count(),
            'specs_active' => $specs_active->count(),
            'specs_signed' => $specs_signed->count(),
            'specs_sum' => $specs_sum,

            'payments' => $payments->count(),
            'payments_paid' => $paid->count(),
            'payments_overdue' => $overdue->count(),
            'paid_sum' => $paid->sum(fn($row) => (float) $row->amount_fact * static::rate($row->currency_slug)),
            'overdue_sum' => $overdue->sum(fn($row) => (float) $row->amount_plan * static::rate($row->currency_slug)),
            'overdue_share' => $decided_payments > 0 ? $overdue->count() / $decided_payments * 100 : null,

            'expected' => $expected->count(),
            'expected_sum' => $expected->sum(fn($row) => (float) $row->amount_plan * static::rate($row->currency_slug)),

            'days_to_spec' => $days->isNotEmpty() ? (int) round($days->avg()) : null,
            'days_known' => $days->count(),
            'links' => $links->count(),
        ];
    }

    /**
     * Составляющие балла за один год: каждая приведена к 0–100 относительно
     * лучшего результата выборки, `score_raw` — их взвешенная сумма.
     *
     * @param Collection $rows
     * @return Collection
     */
    public static function components(Collection $rows): Collection
    {
        $best_expected = (float) $rows->max('expected_sum');
        $best_specs = (float) $rows->max('specs_sum');
        $best_recent = (float) $rows->max('proposals_recent');

        return $rows->map(function ($row) use ($best_expected, $best_specs, $best_recent) {
            $expected = $best_expected > 0 ? $row['expected_sum'] / $best_expected * 100 : 0;
            $specs = $best_specs > 0 ? $row['specs_sum'] / $best_specs * 100 : 0;
            $conversion = $row['conversion'] ?? 0;

            // партнёру без платежей просрочку не ставим — считаем нейтрально
            $overdue = 100 - ($row['overdue_share'] ?? 50);
            $recent = $best_recent > 0 ? $row['proposals_recent'] / $best_recent * 100 : 0;

            $row['parts'] = [
                'expected' => ['label' => 'Размер ожидаемых платежей', 'weight' => static::WEIGHT_EXPECTED, 'value' => $expected],
                'specs' => ['label' => 'Сумма подписанных спецификаций', 'weight' => static::WEIGHT_SPECS, 'value' => $specs],
                'conversion' => ['label' => 'Конверсия решённых КП', 'weight' => static::WEIGHT_CONVERSION, 'value' => $conversion],
                'overdue' => ['label' => 'Платежи без просрочки', 'weight' => static::WEIGHT_OVERDUE, 'value' => $overdue],
                'proposals' => ['label' => 'Количество КП за год', 'weight' => static::WEIGHT_PROPOSALS, 'value' => $recent],
            ];

            $raw = 0.0;
            foreach ($row['parts'] as $code => $part) {
                $points = $part['weight'] * $part['value'] / 100;
                $row['parts'][$code]['points'] = $points;
                $raw += $points;
            }

            $row['score_raw'] = $raw;

            return $row;
        });
    }

    /**
     * Нормировка на лидера выборки: у лучшего партнёра 100
     *
     * @param Collection $rows
     * @return Collection
     */
    public static function normalize(Collection $rows): Collection
    {
        $best = (float) $rows->max('score_blended');

        return $rows->map(function ($row) use ($best) {
            $row['score'] = $best > 0 ? (int) round($row['score_blended'] / $best * 100) : 0;
            $row['rank'] = static::rank($row['score']);

            foreach ($row['parts'] as $code => $part) {
                $row['parts'][$code]['share'] = $row['score_raw'] > 0
                    ? $part['points'] / $row['score_raw'] * 100
                    : 0;
            }

            return $row;
        });
    }

    /**
     * Буква, цвет и подпись по баллу
     *
     * @param float $score
     * @return array
     */
    public static function rank(float $score): array
    {
        return match (true) {
            $score >= 80 => ['letter' => 'A', 'color' => 'success', 'label' => 'Опора'],
            $score >= 65 => ['letter' => 'B', 'color' => 'primary', 'label' => 'Надёжный'],
            $score >= 50 => ['letter' => 'C', 'color' => 'info', 'label' => 'Рабочий'],
            $score >= 35 => ['letter' => 'D', 'color' => 'warning', 'label' => 'Слабый'],
            default => ['letter' => 'E', 'color' => 'danger', 'label' => 'Требует внимания'],
        };
    }

    /**
     * Легенда: что означают буквы и баллы
     *
     * @return array
     */
    public static function grades(): array
    {
        return [
            ['letter' => 'A', 'color' => 'success', 'label' => 'Опора', 'range' => '80–100',
                'hint' => 'Основной канал: крупные ожидаемые платежи и подписанные спецификации'],
            ['letter' => 'B', 'color' => 'primary', 'label' => 'Надёжный', 'range' => '65–79',
                'hint' => 'Стабильно доводит сделки до подписания, объём ниже лидера'],
            ['letter' => 'C', 'color' => 'info', 'label' => 'Рабочий', 'range' => '50–64',
                'hint' => 'Сделки есть, но объём или конверсия заметно отстают'],
            ['letter' => 'D', 'color' => 'warning', 'label' => 'Слабый', 'range' => '35–49',
                'hint' => 'Много КП, мало подписанного — стоит разобраться, где встаёт'],
            ['letter' => 'E', 'color' => 'danger', 'label' => 'Требует внимания', 'range' => '0–34',
                'hint' => 'Подписанных спецификаций почти нет либо копится просрочка'],
        ];
    }

    /**
     * Место партнёра в рейтинге по годам — для графика в подсказке.
     * Текущий год считается на сегодня, поэтому у него данные неполные.
     *
     * @param int $partner_id
     * @param array $years
     * @return array [['year' => int, 'place' => int|null, 'total' => int, 'score' => int|null]]
     */
    public static function history(int $partner_id, array $years = []): array
    {
        $years = !empty($years)
            ? $years
            : range((int) now()->year - static::HISTORY_YEARS + 1, (int) now()->year);

        $ret = [];
        foreach ($years as $year) {
            $rows = static::ranked((int) $year);
            $row = $rows->first(fn($row) => (int) $row['partner']->id === $partner_id);

            $ret[] = [
                'year' => (int) $year,
                'place' => $row['place'] ?? null,
                'score' => $row['score'] ?? null,
                'total' => $rows->count(),
                'current' => (int) $year === (int) now()->year,
            ];
        }

        return $ret;
    }

    /**
     * КП: последняя редакция каждого, с суммой последнего варианта.
     * Варианты не грузим целиком — нужна только сумма.
     *
     * @return Collection
     */
    public static function proposals(): Collection
    {
        static $rows = null;
        if ($rows !== null) return $rows;

        $attached = static::attachedGroups();
        $dates = static::linkDates();
        $final = [ProposalStatus::WON->value, ProposalStatus::LOST->value, ProposalStatus::CANCELED->value];

        return $rows = collect(DB::table('proposals as p')
            ->leftJoin('companies as cm', 'cm.id', '=', 'p.company_id')
            ->whereNotNull('p.partner_id')
            ->whereIn('p.id', fn($query) => $query->selectRaw('MAX(id)')->from('proposals')->groupBy('group'))
            ->select([
                'p.id', 'p.group', 'p.iteration', 'p.number', 'p.name', 'p.status',
                'p.sended_at', 'p.currency_slug', 'p.partner_id', 'p.company_id',
                'cm.name as company_name',
                DB::raw('(SELECT v.cost_total FROM proposal_variants v WHERE v.proposal_id = p.id ORDER BY v.id DESC LIMIT 1) as cost_total'),
            ])
            ->get())
            ->map(function ($row) use ($attached, $dates, $final) {
                $row->sended_at = $row->sended_at ? Carbon::parse($row->sended_at) : null;
                $row->spec_date = $dates[(string) $row->group] ?? null;

                // КП прикреплено к спецификации — значит продано
                $row->is_attached = isset($attached[(string) $row->group]);
                $row->status_effective = $row->is_attached && !in_array((string) $row->status, $final, true)
                    ? ProposalStatus::WON->value
                    : (string) $row->status;

                // год, к которому относим КП: дата отправки, иначе дата спецификации
                $row->year_ref = $row->sended_at?->year ?? $row->spec_date?->year;

                return $row;
            });
    }

    /**
     * Группы КП, прикреплённых хоть к одной спецификации.
     * Отдельным лёгким запросом: links() сам опирается на proposals().
     *
     * @return array группа → true
     */
    public static function attachedGroups(): array
    {
        static $groups = null;
        if ($groups !== null) return $groups;

        $keys = DB::table('contract_specification_proposals')
            ->pluck('proposal_group')
            ->filter()
            ->map(fn($value) => (string) $value)
            ->unique()
            ->all();

        return $groups = array_fill_keys($keys, true);
    }

    /**
     * Дата спецификации по группе КП: самая ранняя из прикреплённых
     * (у спецификации нет даты — берём дату рамочного договора)
     *
     * @return array группа → Carbon
     */
    public static function linkDates(): array
    {
        static $dates = null;
        if ($dates !== null) return $dates;

        $dates = [];
        $rows = DB::table('contract_specification_proposals as l')
            ->join('contract_specifications as s', 's.id', '=', 'l.contract_specification_id')
            ->join('contracts as c', 'c.id', '=', 's.contract_id')
            ->select(['l.proposal_group', 's.date_create', 'c.date as contract_date'])
            ->get();

        foreach ($rows as $row) {
            $raw = $row->date_create ?? $row->contract_date;
            if (empty($raw)) continue;

            $date = Carbon::parse($raw);
            $key = (string) $row->proposal_group;

            if (!isset($dates[$key]) || $date->lessThan($dates[$key])) $dates[$key] = $date;
        }

        return $dates;
    }

    /**
     * Спецификации партнёров со своей датой и числом прикреплённых КП
     *
     * @return Collection
     */
    public static function specifications(): Collection
    {
        static $rows = null;
        if ($rows !== null) return $rows;

        return $rows = collect(DB::table('contract_specifications as s')
            ->join('contracts as c', 'c.id', '=', 's.contract_id')
            ->leftJoin('contract_specification_proposals as l', 'l.contract_specification_id', '=', 's.id')
            ->whereNotNull('c.partner_id')
            ->select([
                's.id', 's.name', 's.date_create', 's.amount', 's.is_signed', 's.status',
                's.currency_slug', 's.company_id',
                'c.id as contract_id', 'c.number as contract_number', 'c.type as contract_type',
                'c.date as contract_date', 'c.cb_signed as contract_signed', 'c.partner_id',
                DB::raw('COUNT(l.id) as proposals_count'),
            ])
            ->groupBy([
                's.id', 's.name', 's.date_create', 's.amount', 's.is_signed', 's.status',
                's.currency_slug', 's.company_id',
                'c.id', 'c.number', 'c.type', 'c.date', 'c.cb_signed', 'c.partner_id',
            ])
            ->get())
            ->map(function ($row) {
                $row->contract_date = $row->contract_date ? Carbon::parse($row->contract_date) : null;
                $row->date_create = $row->date_create ? Carbon::parse($row->date_create) : null;
                $row->spec_date = $row->date_create ?? $row->contract_date;
                return $row;
            });
    }

    /**
     * Платежи по спецификациям партнёров.
     * Отменённые спецификации просрочки не дают.
     *
     * @return Collection
     */
    public static function payments(): Collection
    {
        static $rows = null;
        if ($rows !== null) return $rows;

        return $rows = collect(DB::table('payments as p')
            ->join('contract_specifications as s', 's.id', '=', 'p.contract_specification_id')
            ->join('contracts as c', 'c.id', '=', 's.contract_id')
            ->whereNotNull('c.partner_id')
            ->select([
                'p.id', 'p.date_plan', 'p.date_fact', 'p.amount_plan', 'p.amount_fact', 'p.is_unknown',
                's.id as spec_id', 's.name as spec_name', 's.status as spec_status', 's.currency_slug',
                'c.partner_id', 'c.number as contract_number',
            ])
            ->get())
            ->map(function ($row) {
                $row->date_plan = $row->date_plan ? Carbon::parse($row->date_plan) : null;
                $row->date_fact = $row->date_fact ? Carbon::parse($row->date_fact) : null;

                $row->state = match (true) {
                    !empty($row->date_fact) => 'paid',
                    strtolower((string) $row->spec_status) === 'canceled' => 'canceled',
                    !empty($row->is_unknown) || empty($row->date_plan) => 'unknown',
                    $row->date_plan->isPast() => 'overdue',
                    default => 'planned',
                };

                return $row;
            });
    }

    /**
     * Привязки КП к спецификациям: дата отправки КП и дата спецификации
     *
     * @return Collection
     */
    public static function links(): Collection
    {
        static $rows = null;
        if ($rows !== null) return $rows;

        $sended = static::proposals()->keyBy('group');

        return $rows = collect(DB::table('contract_specification_proposals as l')
            ->join('contract_specifications as s', 's.id', '=', 'l.contract_specification_id')
            ->join('contracts as c', 'c.id', '=', 's.contract_id')
            ->select([
                'l.id', 'l.proposal_group',
                's.id as spec_id', 's.name as spec_name', 's.date_create',
                'c.partner_id', 'c.date as contract_date', 'c.number as contract_number',
            ])
            ->get())
            ->map(function ($row) use ($sended) {
                $row->contract_date = $row->contract_date ? Carbon::parse($row->contract_date) : null;
                $row->date_create = $row->date_create ? Carbon::parse($row->date_create) : null;
                $row->spec_date = $row->date_create ?? $row->contract_date;
                $row->sended_at = $sended->get($row->proposal_group)?->sended_at;
                $row->number = $sended->get($row->proposal_group)?->number;

                return $row;
            });
    }

    /**
     * Год спецификации: по своей дате, иначе по дате договора
     *
     * @param mixed $spec
     * @return int|null
     */
    public static function specYear($spec): ?int
    {
        return ($spec->spec_date ?? $spec->contract_date)?->year;
    }

    /**
     * Дата, к которой относим привязку — дата спецификации
     *
     * @param mixed $link
     * @return Carbon|null
     */
    public static function linkDate($link): ?Carbon
    {
        return $link->spec_date ?? $link->contract_date;
    }

    /**
     * Дней от последнего выставленного КП до даты спецификации
     *
     * @param mixed $link
     * @return int|null
     */
    public static function linkDays($link): ?int
    {
        $to = static::linkDate($link);
        if (empty($link->sended_at) || empty($to)) return null;

        return (int) $link->sended_at->diffInDays($to, false);
    }

    /**
     * Срок в человеческом виде: 43 → «1 мес, 13 д»
     *
     * @param int|null $days
     * @return string|null
     */
    public static function humanPeriod(?int $days): ?string
    {
        return SpecProposalService::humanPeriod($days);
    }

    /**
     * Курс валюты к рублю на сегодня, с кэшем
     *
     * @param string|null $slug
     * @return float
     */
    public static function rate(?string $slug): float
    {
        $slug = CurrencyService::slug($slug);

        return static::$rates[$slug] ??= (float) (CurrencyService::getConvertRateForDate(now(), $slug, null) ?? 1);
    }

    /**
     * Годы, за которые есть данные
     *
     * @return array
     */
    public static function years(): array
    {
        return DiscountAnalysisService::years();
    }

    /**
     * Итоги по выборке
     *
     * @param Collection $rows
     * @return array
     */
    public static function totals(Collection $rows): array
    {
        return [
            'count' => $rows->count(),
            'proposals' => (int) $rows->sum('proposals'),
            'won' => (int) $rows->sum('won'),
            'specs_signed' => (int) $rows->sum('specs_signed'),
            'specs_sum' => (float) $rows->sum('specs_sum'),
            'amount_won' => (float) $rows->sum('amount_won'),
            'overdue' => (int) $rows->sum('payments_overdue'),
            'expected_sum' => (float) $rows->sum('expected_sum'),
        ];
    }
}
