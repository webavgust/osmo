<?php

namespace App\Modules\Pub\ProposalTools\Services;

use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Клонирование КП.
 *
 * Копия — это новое КП (новая group, итерация 1), а не новая итерация:
 * так делают, когда тот же расчёт уходит другой компании или партнёру.
 *
 * Копируем ровно то, что копирует пересчёт валюты (ProposalRepository::convert),
 * плюс привязки нейросервисов и дополнительные платежи. Не переносим:
 * статус, привязки к сделкам Битрикса, ссылку на родительскую итерацию,
 * договоры, спецификации и платежи — они относятся к исходной сделке.
 */
class ProposalCloneService
{
    /**
     * Склонировать КП
     *
     * @param Proposal $proposal Итерация-источник
     * @param array $params [
     *     'name' => string,
     *     'number' => string,
     *     'sended_at' => string|null (Y-m-d),
     *     'company_id' => int|null,
     *     'partner_id' => int|null,
     *     'manager_id' => int|null,
     * ]
     * @return Proposal Новое КП
     */
    public static function clone(Proposal $proposal, array $params = []): Proposal
    {
        $proposal->load([
            'software', 'works',
            'variants.proposal_platforms',
            'variants.proposal_scenarios.neuroservices',
            'variants.proposal_works',
            'variants.proposal_software',
        ]);

        DB::beginTransaction();

        try {
            $number = trim((string) ($params['number'] ?? '')) ?: ($proposal->number . '-К');
            preg_match('/\d+$/', $number, $matches);

            $new = $proposal->replicate();
            $new->fill([
                'group' => (string) Str::uuid(),
                'iteration' => 1,
                'name' => trim((string) ($params['name'] ?? '')) ?: ($proposal->name . ' (копия)'),
                'number' => $number,
                'number_int' => $matches[0] ?? 0,
                'sended_at' => $params['sended_at'] ?? now()->format('Y-m-d'),
                'status' => ProposalStatus::IN_WORK->value,
                'status_reason' => null,
                'status_comment' => null,
            ]);

            // поля, которых у копии быть не должно
            foreach ([
                'status_changed_at', 'status_changed_by',
                'crm_deal_id', 'crm_deal_linked_at', 'crm_deal_linked_by',
                'proposal_id',
            ] as $field) {
                if (array_key_exists($field, $new->getAttributes())) $new->{$field} = null;
            }

            foreach (['company_id', 'partner_id', 'manager_id'] as $field) {
                if (!empty($params[$field])) $new->{$field} = (int) $params[$field];
            }

            $new->save();

            // ПО
            $transferSoftware = [];
            foreach ($proposal->software as $software) {
                $copy = $software->replicate();
                $new->software()->save($copy);
                $transferSoftware[$software->id] = $copy;
            }

            // РАБОТЫ
            $transferWork = [];
            foreach ($proposal->works as $work) {
                $copy = $work->replicate();
                $new->works()->save($copy);
                $transferWork[$work->id] = $copy;
            }

            // ВАРИАНТЫ
            foreach ($proposal->variants as $variant) {
                $new_variant = $variant->replicate();
                $new->variants()->save($new_variant);

                // ПЛАТФОРМА
                foreach ($variant->proposal_platforms as $platform) {
                    $new_variant->proposal_platforms()->save($platform->replicate());
                }

                // СЦЕНАРИИ (вместе с привязкой нейросервисов)
                foreach ($variant->proposal_scenarios as $scenario) {
                    $new_scenario = $scenario->replicate();
                    $new_variant->proposal_scenarios()->save($new_scenario);

                    foreach ($scenario->neuroservices as $neuroservice) {
                        $new_scenario->neuroservices()->attach($neuroservice->id, [
                            'cost' => $neuroservice->pivot->cost ?? null,
                        ]);
                    }
                }

                // РАБОТЫ ВАРИАНТА
                foreach ($variant->proposal_works as $work) {
                    $copy = $work->replicate();
                    if (!empty($transferWork[$work->proposal_work_id])) {
                        $copy->proposal_work()->associate($transferWork[$work->proposal_work_id]);
                    }
                    $new_variant->proposal_works()->save($copy);
                }

                // ПО ВАРИАНТА
                foreach ($variant->proposal_software as $software) {
                    $copy = $software->replicate();
                    if (!empty($transferSoftware[$software->proposal_software_id])) {
                        $copy->proposal_software()->associate($transferSoftware[$software->proposal_software_id]);
                    }
                    $new_variant->proposal_software()->save($copy);
                }

                // ВЫЧИСЛИТЕЛЬНЫЕ РЕСУРСЫ
                if (method_exists($variant, 'hardware')) {
                    foreach ($variant->hardware as $hardware) {
                        $new_variant->hardware()->save($hardware->replicate());
                    }
                }

                // ДОПОЛНИТЕЛЬНЫЕ ПЛАТЕЖИ
                if (method_exists($variant, 'extra_pays')) {
                    foreach ($variant->extra_pays as $pay) {
                        $new_variant->extra_pays()->save($pay->replicate());
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $new->refresh();
    }
}
