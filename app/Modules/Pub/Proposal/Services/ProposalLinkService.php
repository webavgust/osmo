<?php

namespace App\Modules\Pub\Proposal\Services;

use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecificationProposal;
use App\Modules\Pub\EntityLog\Services\EntityLogService;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalCrmDeal;
use App\Modules\Pub\Proposal\Models\ProposalLink;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Связка КП «главное / второстепенное» (patch v33).
 *
 * Два КП логически объединяются, не склеиваясь: главное участвует во всех
 * расчётах, второстепенное — только просмотр и история. Связка живёт на группах,
 * поэтому на вход можно подавать любую редакцию — работа идёт с последней.
 *
 * Правила: у главного нет главного, второстепенное не бывает главным для других
 * (цепочек нет); у главного может быть несколько второстепенных. Второстепенным
 * становится только КП без сделок Битрикс24, спецификаций и договоров — иначе
 * эти привязки выпали бы из расчётов.
 *
 * Ошибки для пользователя — \DomainException с русским текстом.
 * Журнал: изменения оборачиваются в EntityLogService::around() для последних
 * редакций всех затронутых КП — событие ложится в ленту каждого.
 */
class ProposalLinkService
{
    /**
     * Что мешает КП стать второстепенным: сделки Битрикс24, спецификации, договоры
     *
     * @param Proposal $proposal Любая редакция
     * @return array ['deals' => 'текст', 'specifications' => 'текст', 'contracts' => 'текст']; пусто — можно
     */
    public static function blockers(Proposal $proposal): array
    {
        return static::blockersMany([(string) $proposal->group])[(string) $proposal->group] ?? [];
    }

    /**
     * Причины blockers() сразу для нескольких групп — тремя-четырьмя запросами
     *
     * @param array $groups
     * @return array [group => ['deals' => …, 'specifications' => …, 'contracts' => …]]
     */
    protected static function blockersMany(array $groups): array
    {
        $groups = array_values(array_unique(array_filter($groups)));
        if (empty($groups)) return [];

        // сделки: привязки группы плюс главная сделка в proposals.crm_deal_id любой редакции
        $deals = [];
        ProposalCrmDeal::whereIn('proposal_group', $groups)->get(['proposal_group', 'crm_deal_id'])
            ->each(function ($row) use (&$deals) {
                $deals[$row->proposal_group][(int) $row->crm_deal_id] = true;
            });

        $rows = Proposal::whereIn('group', $groups)->get(['id', 'group', 'crm_deal_id']);
        foreach ($rows->whereNotNull('crm_deal_id') as $row) {
            $deals[$row->group][(int) $row->crm_deal_id] = true;
        }

        // спецификации: привязка живёт на группе
        $specs = ContractSpecificationProposal::whereIn('proposal_group', $groups)
            ->get(['proposal_group', 'contract_specification_id'])
            ->groupBy('proposal_group');

        // договоры: ссылаются на id редакции
        $group_of = $rows->pluck('group', 'id');
        $contracts = $group_of->isEmpty() ? collect() : Contract::whereIn('proposal_id', $group_of->keys()->all())
            ->get(['id', 'number', 'proposal_id'])
            ->groupBy(fn($contract) => $group_of->get($contract->proposal_id));

        $ret = [];
        foreach ($groups as $group) {
            $list = [];

            if (!empty($deals[$group])) {
                $ids = array_keys($deals[$group]);
                $list['deals'] = 'Сделки Битрикс24: ' . count($ids) . ' (' . static::listText(array_map(fn($id) => '#' . $id, $ids)) . ')';
            }

            if ($specs->has($group)) {
                $ids = $specs->get($group)->pluck('contract_specification_id')->unique()->values();
                $list['specifications'] = 'Спецификации: ' . $ids->count() . ' (' . static::listText($ids->map(fn($id) => '#' . $id)->all()) . ')';
            }

            if ($contracts->has($group)) {
                $items = $contracts->get($group)->unique('id');
                $list['contracts'] = 'Договоры: ' . $items->count() . ' (' . static::listText($items->map(
                    fn($contract) => trim((string) $contract->number) !== '' ? '№ ' . trim($contract->number) : '#' . $contract->id
                )->all()) . ')';
            }

            if (!empty($list)) $ret[$group] = $list;
        }

        return $ret;
    }

    /**
     * Связать КП: $secondary становится второстепенным к $main.
     *
     * Если у будущего второстепенного есть свои второстепенные — они переезжают
     * к $main в той же транзакции (цепочек нет).
     *
     * @param Proposal $main Будущее главное (любая редакция)
     * @param Proposal $secondary Будущее второстепенное (любая редакция)
     * @param string|null $comment Комментарий к связке
     * @param User|null $user Кто связал; null — текущий пользователь
     * @return ProposalLink
     * @throws \DomainException нарушено правило связки — текст для пользователя
     */
    public static function link(Proposal $main, Proposal $secondary, ?string $comment = null, ?User $user = null): ProposalLink
    {
        $given = [$main, $secondary];
        $main = static::last($main) ?? $main;
        $secondary = static::last($secondary) ?? $secondary;

        if ((string) $main->group === (string) $secondary->group) {
            throw new \DomainException('Нельзя связать КП с самим собой');
        }

        // главное само второстепенное — цепочек не бывает
        $main_link = ProposalLink::where('secondary_group', $main->group)->first();
        if ($main_link) {
            if ((string) $main_link->main_group === (string) $secondary->group) {
                throw new \DomainException(static::title($main) . ' уже второстепенное к ' . static::ref($secondary)
                    . '. Чтобы поменять роли, сделайте ' . static::ref($main) . ' главным.');
            }

            $top = static::last($main_link->main_group);
            throw new \DomainException(static::title($main) . ' — второстепенное к ' . static::ref($top) . ', свяжите с ' . static::ref($top));
        }

        // второстепенное уже при главном
        $own = ProposalLink::where('secondary_group', $secondary->group)->first();
        if ($own) {
            if ((string) $own->main_group === (string) $main->group) {
                throw new \DomainException(static::title($secondary) . ' уже второстепенное к ' . static::ref($main));
            }

            throw new \DomainException(static::title($secondary) . ' уже второстепенное к ' . static::ref(static::last($own->main_group))
                . '. Сначала разъедините их.');
        }

        $blockers = static::blockers($secondary);
        if (!empty($blockers)) {
            throw new \DomainException(static::title($secondary) . ' не может быть второстепенным: ' . implode('; ', $blockers)
                . '. Перенесите привязки на ' . static::ref($main) . ' и свяжите заново.');
        }

        // свои второстепенные будущего второстепенного — переезжают к главному
        $moved = ProposalLink::where('main_group', $secondary->group)->pluck('secondary_group')->all();

        try {
            return static::logged(array_merge([$main, $secondary], $moved), fn() => DB::transaction(function () use ($main, $secondary, $moved, $comment, $user) {
                if (!empty($moved)) {
                    ProposalLink::where('main_group', $secondary->group)->update(['main_group' => $main->group]);
                }

                return ProposalLink::create([
                    'main_group' => $main->group,
                    'secondary_group' => $secondary->group,
                    'comment' => static::comment($comment),
                    'linked_by' => $user?->id ?? auth()->id(),
                    'linked_at' => now(),
                ]);
            }));
        } finally {
            static::forget(...$given);
        }
    }

    /**
     * Разъединить: КП перестаёт быть второстепенным и снова участвует в расчётах
     *
     * @param Proposal $secondary Второстепенное (любая редакция)
     * @param User|null $user Кто разъединил; автора события журнал берёт из auth() — параметр для единообразия вызовов
     * @return void
     * @throws \DomainException КП не второстепенное
     */
    public static function unlink(Proposal $secondary, ?User $user = null): void
    {
        $link = ProposalLink::where('secondary_group', $secondary->group)->first();
        if (empty($link)) {
            throw new \DomainException(static::title(static::last($secondary) ?? $secondary) . ' не связано с главным КП');
        }

        try {
            // удаление модельное: связку видит журнал главного через logParent()
            static::logged([$link->main_group, $link->secondary_group], fn() => DB::transaction(fn() => $link->delete()));
        } finally {
            static::forget($secondary);
        }
    }

    /**
     * Сделать второстепенное главным: бывшее главное M и остальные его
     * второстепенные переходят под $secondary (S).
     *
     * M может стать второстепенным, только если у него нет сделок, спецификаций
     * и договоров — иначе они выпали бы из расчётов.
     *
     * @param Proposal $secondary Второстепенное, которое станет главным (любая редакция)
     * @param User|null $user Кто поменял роли; null — текущий пользователь
     * @return void
     * @throws \DomainException
     */
    public static function makeMain(Proposal $secondary, ?User $user = null): void
    {
        $given = $secondary;
        $secondary = static::last($secondary) ?? $secondary;

        $link = ProposalLink::where('secondary_group', $secondary->group)->first();
        if (empty($link)) {
            throw new \DomainException(static::title($secondary) . ' не второстепенное — оно уже участвует в расчётах');
        }

        $main = static::last($link->main_group);
        if (empty($main)) {
            throw new \DomainException('Главное КП для ' . static::ref($secondary) . ' не найдено. Разъедините связку.');
        }

        $blockers = static::blockers($main);
        if (!empty($blockers)) {
            throw new \DomainException(static::title($main) . ' не может стать второстепенным: ' . implode('; ', $blockers)
                . '. Разъедините КП, перенесите привязки на ' . static::ref($secondary) . ' обычными попапами и свяжите заново.');
        }

        $others = ProposalLink::where('main_group', $main->group)->where('id', '!=', $link->id)->pluck('secondary_group')->all();

        try {
            static::logged(array_merge([$main, $secondary], $others), fn() => DB::transaction(function () use ($link, $main, $secondary, $others, $user) {
                $link->delete();

                if (!empty($others)) {
                    ProposalLink::where('main_group', $main->group)->update(['main_group' => $secondary->group]);
                }

                ProposalLink::create([
                    'main_group' => $secondary->group,
                    'secondary_group' => $main->group,
                    'comment' => $link->comment,
                    'linked_by' => $user?->id ?? auth()->id(),
                    'linked_at' => now(),
                ]);
            }));
        } finally {
            static::forget($given);
        }
    }

    /**
     * Перечень через запятую, длинный — с хвостом «и ещё N»
     *
     * @param array $items
     * @param int $limit
     * @return string
     */
    protected static function listText(array $items, int $limit = 5): string
    {
        $text = implode(', ', array_slice($items, 0, $limit));

        return count($items) > $limit ? $text . ' и ещё ' . (count($items) - $limit) : $text;
    }

    /**
     * Главное КП для второстепенного (последняя редакция)
     *
     * @param Proposal $proposal Любая редакция
     * @return Proposal|null null — КП не второстепенное
     */
    public static function mainOf(Proposal $proposal): ?Proposal
    {
        $link = $proposal->main_link;
        if (empty($link)) return null;

        return $link->relationLoaded('main') ? $link->main : static::last($link->main_group);
    }

    /**
     * Второстепенные КП главного (последние редакции, в порядке связывания)
     *
     * @param Proposal $proposal Любая редакция
     * @return Collection
     */
    public static function secondariesOf(Proposal $proposal): Collection
    {
        $groups = ProposalLink::where('main_group', $proposal->group)->orderBy('id')->pluck('secondary_group');
        if ($groups->isEmpty()) return collect();

        $rows = Proposal::whereIn('group', $groups->all())->latestIteration()->get()->keyBy('group');

        return $groups->map(fn($group) => $rows->get($group))->filter()->values();
    }

    /**
     * Группы КП из тех же связок, что и переданные: главное и все его второстепенные.
     * Нужны поиску списка КП — по номеру главного находятся и второстепенные, и наоборот
     *
     * @param iterable $groups Группы найденных КП
     * @return array Группы связанных КП (без переданных); пусто — связок нет
     */
    public static function clusterGroups(iterable $groups): array
    {
        $groups = collect($groups)->filter()->map(fn($group) => (string) $group)->unique()->values();
        if ($groups->isEmpty()) return [];

        // главные связок: сами найденные и главные найденных второстепенных
        $mains = ProposalLink::whereIn('main_group', $groups)->orWhereIn('secondary_group', $groups)
            ->pluck('main_group')->unique()->values();
        if ($mains->isEmpty()) return [];

        $cluster = ProposalLink::whereIn('main_group', $mains)->pluck('secondary_group')->merge($mains);

        return $cluster->unique()->diff($groups)->values()->all();
    }

    /**
     * КП для связывания: последние редакции, кроме своей группы.
     *
     * Поиск — по номеру, названию и компании (каждое слово хотя бы в одном поле).
     * Сначала КП той же компании, дальше по дате КП от новых. К строке приложены:
     * is_secondary / is_main (в toArray тоже) и blockers — что мешает стать второстепенным.
     *
     * @param Proposal $proposal КП, для которого ищем пару
     * @param string $q Строка поиска
     * @param int $limit
     * @return Collection
     */
    public static function candidates(Proposal $proposal, string $q = '', int $limit = 20): Collection
    {
        $q = trim($q);

        $builder = Proposal::query()
            ->latestIteration()
            ->where('proposals.group', '!=', (string) $proposal->group)
            ->with(['company', 'partner', 'variants', 'main_link.main', 'secondary_links']);

        foreach (preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $like = '%' . $word . '%';

            $builder->where(function ($builder) use ($like) {
                $builder->where('proposals.number', 'like', $like)
                    ->orWhere('proposals.name', 'like', $like)
                    ->orWhere('proposals.name_alt', 'like', $like)
                    ->orWhereHas('company', fn($builder) => $builder->where('name', 'like', $like));
            });
        }

        // сначала КП той же компании (компании у КП может не быть)
        if (!empty($proposal->company_id)) {
            $builder->orderByRaw('CASE WHEN proposals.company_id = ? THEN 0 ELSE 1 END', [(int) $proposal->company_id]);
        }

        $rows = $builder->orderByDesc('proposals.sended_at')->orderByDesc('proposals.id')->limit(max(1, $limit))->get();
        if ($rows->isEmpty()) return collect();

        $blockers = static::blockersMany($rows->pluck('group')->all());

        return $rows->map(function (Proposal $row) use ($blockers) {
            $row->blockers = $blockers[$row->group] ?? [];
            $row->append(['is_secondary', 'is_main']);

            return $row;
        })->values();
    }

    /**
     * Запрет правки второстепенного КП: 403 с понятным текстом
     *
     * @param Proposal $proposal
     * @return void
     * @throws HttpException
     */
    public static function assertEditable(Proposal $proposal): void
    {
        $message = static::readonlyMessage($proposal);

        if ($message !== null) {
            throw new HttpException(403, $message);
        }
    }

    /**
     * Текст «только просмотр» для второстепенного КП; null — КП можно править
     *
     * @param Proposal $proposal
     * @return string|null
     */
    public static function readonlyMessage(Proposal $proposal): ?string
    {
        if (!$proposal->is_secondary) return null;

        return static::title($proposal) . ' — второстепенное к ' . static::ref(static::mainOf($proposal))
            . ': только просмотр. Правьте главное КП.';
    }

    /**
     * Последняя редакция КП группы
     *
     * @param Proposal|string|null $proposal КП или его group
     * @return Proposal|null
     */
    public static function last($proposal): ?Proposal
    {
        $group = $proposal instanceof Proposal ? (string) $proposal->group : (string) $proposal;

        return ProposalLink::lastOf($group);
    }

    /**
     * Подпись КП для текстов: «КП № AA793», без номера — «КП «Название»»
     *
     * @param Proposal|null $proposal
     * @return string
     */
    protected static function title(?Proposal $proposal): string
    {
        $ref = ProposalLink::refOf($proposal);

        return $ref !== '' ? 'КП ' . $ref : 'КП';
    }

    /**
     * Ссылка на КП для текстов: «№ AA793»; КП нет — «(КП не найдено)»
     *
     * @param Proposal|null $proposal
     * @return string
     */
    protected static function ref(?Proposal $proposal): string
    {
        $ref = ProposalLink::refOf($proposal);

        return $ref !== '' ? $ref : '(КП не найдено)';
    }

    /**
     * Комментарий к связке: пустой — null, длинный обрезается под колонку
     *
     * @param string|null $comment
     * @return string|null
     */
    protected static function comment(?string $comment): ?string
    {
        $comment = trim((string) $comment);

        return $comment === '' ? null : mb_substr($comment, 0, 500);
    }

    /**
     * Выполнить правку связок с журналом по всем затронутым КП:
     * EntityLogService::around() вокруг последней редакции каждого
     * (baseline до правки, пометка «грязным» после) — событие ляжет в ленту каждого
     *
     * @param array $proposals Proposal или group
     * @param callable $fn
     * @return mixed результат $fn
     */
    protected static function logged(array $proposals, callable $fn): mixed
    {
        $roots = [];
        foreach ($proposals as $proposal) {
            $root = static::last($proposal);
            if ($root) $roots[$root->getKey()] = $root;
        }

        foreach ($roots as $root) {
            $inner = $fn;
            $fn = fn() => EntityLogService::around($root, $inner);
        }

        try {
            return $fn();
        } finally {
            ProposalLink::flush();
        }
    }

    /**
     * Сбросить загруженные связки у переданных моделей: после правки
     * is_secondary / is_main должны читаться заново
     *
     * @param Proposal ...$proposals
     * @return void
     */
    protected static function forget(Proposal ...$proposals): void
    {
        foreach ($proposals as $proposal) {
            $proposal->unsetRelation('main_link');
            $proposal->unsetRelation('main_links');
            $proposal->unsetRelation('secondary_links');
        }
    }
}
