<?php

namespace App\Modules\Pub\ContractSpecification\Services;

use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecificationProposal;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Привязка КП к спецификации рамочного договора.
 *
 * Цепочка в проекте: партнёр — рамочный договор — компания — спецификация.
 * Прикрепить к спецификации можно не любое КП, а только то, что этой
 * спецификации соответствует по двум признакам:
 *
 * 1) компания КП совпадает с компанией спецификации;
 * 2) в КП есть блок, ради которого заключён рамочный договор: договор по
 *    услугам берёт КП с работами, договор по ПО — КП с ПО.
 *
 * Прикрепление — событие продажи: КП из работы или заморозки автоматически
 * становится выигранным.
 */
class SpecProposalService
{
    /**
     * Тип рамочного договора → блок, который обязан быть в КП.
     *
     * Ключ — значение ContractType, relation — связь на варианте КП.
     * Типов без блока (Неизвестно) это не касается: для них годится любое КП.
     */
    public const TYPE_BLOCKS = [
        'services' => ['relation' => 'proposal_works', 'label' => 'Работы'],
        'license' => ['relation' => 'proposal_software', 'label' => 'ПО'],
        'platform' => ['relation' => 'proposal_platforms', 'label' => 'Платформа'],
    ];

    /** Статусы, из которых прикрепление переводит КП в «Выиграно» */
    public const WIN_FROM = [ProposalStatus::IN_WORK, ProposalStatus::FROZEN];

    /**
     * Требование к блоку для этой спецификации
     *
     * @param ContractSpecification $spec
     * @return array|null ['relation' => ..., 'label' => ...]
     */
    public static function block(ContractSpecification $spec): ?array
    {
        return static::TYPE_BLOCKS[(string) $spec->contract?->type] ?? null;
    }

    /**
     * КП, которые можно прикрепить к этой спецификации
     *
     * @param ContractSpecification $spec
     * @return Collection
     */
    public static function available(ContractSpecification $spec): Collection
    {
        if (empty($spec->company_id)) return collect();

        $block = static::block($spec);
        $busy = static::links($spec)->pluck('proposal_group')->all();

        return Proposal::query()
            ->latestIteration()
            ->where('company_id', $spec->company_id)
            ->whereNotIn('group', $busy)
            ->with([
                'partner', 'currency', 'variants.proposal_works',
                'variants.proposal_software', 'variants.proposal_platforms',
            ])
            ->orderByDesc('sended_at')
            ->get()
            ->filter(fn($proposal) => static::hasBlock($proposal, $block['relation'] ?? null))
            ->values();
    }

    /**
     * Прикреплённые КП (последние редакции)
     *
     * @param ContractSpecification $spec
     * @return Collection
     */
    public static function attached(ContractSpecification $spec): Collection
    {
        $groups = static::links($spec)->pluck('proposal_group');
        if ($groups->isEmpty()) return collect();

        return Proposal::query()
            ->latestIteration()
            ->whereIn('group', $groups)
            ->with(['partner', 'currency', 'variants'])
            ->orderByDesc('sended_at')
            ->get();
    }

    /**
     * Записи привязок спецификации
     *
     * @param ContractSpecification $spec
     * @return Collection
     */
    public static function links(ContractSpecification $spec): Collection
    {
        return ContractSpecificationProposal::where('contract_specification_id', $spec->id)->get();
    }

    /**
     * В КП есть непустой блок
     *
     * @param Proposal $proposal
     * @param string|null $relation связь на варианте; null — блок не важен
     * @return bool
     */
    public static function hasBlock(Proposal $proposal, ?string $relation): bool
    {
        if (empty($relation)) return true;

        foreach ($proposal->variants as $variant) {
            foreach ($variant->{$relation} ?? [] as $item) {
                if ((float) ($item->count ?? 0) > 0) return true;
            }
        }

        return false;
    }

    /**
     * Прикрепить КП к спецификации.
     * Заодно переводит КП в «Выиграно», если оно было в работе или заморожено.
     *
     * @param ContractSpecification $spec
     * @param Proposal $proposal
     * @return bool сменился ли статус КП на «Выиграно»
     */
    public static function attach(ContractSpecification $spec, Proposal $proposal): bool
    {
        ContractSpecificationProposal::firstOrCreate([
            'contract_specification_id' => $spec->id,
            'proposal_group' => $proposal->group,
        ], [
            'attached_at' => now(),
            'attached_by' => auth()->id(),
        ]);

        return static::win($proposal);
    }

    /**
     * Открепить КП. Статус при этом не откатываем: сделка могла состояться
     * и без этой спецификации, а угадывать прошлый статус — хуже, чем оставить.
     *
     * @param ContractSpecification $spec
     * @param Proposal $proposal
     * @return void
     */
    public static function detach(ContractSpecification $spec, Proposal $proposal): void
    {
        ContractSpecificationProposal::where('contract_specification_id', $spec->id)
            ->where('proposal_group', $proposal->group)
            ->delete();
    }

    /**
     * Перевести КП в «Выиграно».
     *
     * Статус — свойство предложения, а не редакции, поэтому меняется по всей
     * группе: иначе список (он читает последнюю редакцию) и история разойдутся.
     *
     * @param Proposal $proposal
     * @return bool сменился ли статус
     */
    public static function win(Proposal $proposal): bool
    {
        $from = array_map(fn($case) => $case->value, static::WIN_FROM);

        $changed = Proposal::where('group', $proposal->group)
            ->whereIn('status', $from)
            ->update([
                'status' => ProposalStatus::WON->value,
                'status_reason' => null,
                'status_changed_at' => now(),
                'status_changed_by' => auth()->id(),
            ]);

        return $changed > 0;
    }

    /**
     * Спецификации, к которым прикреплено это КП
     *
     * @param Proposal $proposal
     * @return Collection
     */
    public static function specifications(Proposal $proposal): Collection
    {
        return ContractSpecificationProposal::where('proposal_group', $proposal->group)
            ->with(['specification.contract', 'specification.company'])
            ->get()
            ->map(fn($link) => $link->specification)
            ->filter()
            ->values();
    }

    /**
     * Срок от последнего выставленного КП до прикрепления к спецификации.
     *
     * Считается по дате отправки последней редакции: именно она — «последнее
     * выставленное КП». Если привязка старая и даты прикрепления нет, берём
     * дату рамочного договора.
     *
     * @param ContractSpecificationProposal $link
     * @param Proposal|null $proposal последняя редакция
     * @return int|null дней
     */
    public static function daysToSpec(ContractSpecificationProposal $link, ?Proposal $proposal = null): ?int
    {
        $proposal = $proposal ?? $link->proposal;
        if (empty($proposal?->sended_at)) return null;

        $to = $link->attached_at ?? $link->specification?->contract?->date;
        if (empty($to)) return null;

        return (int) Carbon::parse($proposal->sended_at)->diffInDays(Carbon::parse($to), false);
    }

    /**
     * Дни в человеческом виде: 43 → «1 мес, 13 д», 400 → «1 г, 1 мес, 5 д»
     *
     * Месяц считаем в 30 дней, год в 365 — это показатель срока, а не календарь.
     *
     * @param int|null $days
     * @return string|null
     */
    public static function humanPeriod(?int $days): ?string
    {
        if ($days === null) return null;

        $sign = $days < 0 ? '−' : '';
        $days = abs($days);

        if ($days < 31) return $sign . $days . ' д';

        $years = intdiv($days, 365);
        $rest = $days % 365;
        $months = intdiv($rest, 30);
        $tail = $rest % 30;

        $parts = [];
        if ($years > 0) $parts[] = $years . ' г';
        if ($months > 0) $parts[] = $months . ' мес';
        if ($tail > 0) $parts[] = $tail . ' д';

        return $sign . implode(', ', $parts);
    }
}
