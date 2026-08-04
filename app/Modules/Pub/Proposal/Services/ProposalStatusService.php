<?php

namespace App\Modules\Pub\Proposal\Services;

use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalLostReason;
use App\Modules\Pub\Proposal\Models\ProposalStatus;

/**
 * Управление статусом КП.
 *
 * Статус принадлежит группе (КП целиком), поэтому пишется во все итерации.
 */
class ProposalStatusService
{
    /**
     * Сменить статус у всей группы КП
     *
     * @param Proposal $proposal Любая итерация КП
     * @param ProposalStatus $status Новый статус
     * @param ProposalLostReason|null $reason Причина (обязательна для проигрыша/заморозки/отмены)
     * @param string|null $comment Комментарий менеджера
     * @return Proposal Обновлённая итерация
     */
    public static function set(
        Proposal $proposal,
        ProposalStatus $status,
        ProposalLostReason $reason = null,
        string $comment = null
    ): Proposal {
        if ($status->needReason() && empty($reason)) {
            throw new \InvalidArgumentException(
                'Для статуса «' . $status->data()['label'] . '» нужно указать причину'
            );
        }

        // причина имеет смысл только для «неуспешных» статусов
        if (!$status->needReason()) {
            $reason = null;
        }

        Proposal::where('group', $proposal->group)->update([
            'status' => $status->value,
            'status_reason' => $reason?->value,
            'status_comment' => $comment,
            'status_changed_at' => now(),
            'status_changed_by' => auth()->id(),
        ]);

        return $proposal->refresh();
    }

    /**
     * Разложить КП по статусам: ['won' => 12, 'lost' => 3, ...]
     *
     * @param \Illuminate\Support\Collection|null $proposals Если null — считаем по всем главным итерациям
     * @return array
     */
    public static function counters($proposals = null): array
    {
        $ret = [];
        foreach (ProposalStatus::cases() as $case) {
            $ret[$case->value] = 0;
        }

        $rows = $proposals ?? ProposalStatusService::latestIterations();

        foreach ($rows as $row) {
            $key = $row->status ?? ProposalStatus::IN_WORK->value;
            if (!array_key_exists($key, $ret)) continue;
            $ret[$key]++;
        }

        return $ret;
    }

    /**
     * Конверсия: доля выигранных среди завершённых КП
     *
     * @param \Illuminate\Support\Collection|null $proposals
     * @return float Процент, 0..100
     */
    public static function conversion($proposals = null): float
    {
        $rows = $proposals ?? ProposalStatusService::latestIterations();

        $final = $rows->filter(
            fn($row) => ($status = ProposalStatus::tryFrom($row->status ?? '')) && $status->isFinal()
        );

        if ($final->isEmpty()) return 0;

        $won = $final->filter(fn($row) => $row->status === ProposalStatus::WON->value);

        return round($won->count() / $final->count() * 100, 1);
    }

    /**
     * Последние итерации всех КП — по одной строке на группу
     *
     * @return \Illuminate\Support\Collection
     */
    public static function latestIterations()
    {
        return Proposal::query()
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')->from('proposals')->groupBy('group');
            })
            ->get();
    }
}
