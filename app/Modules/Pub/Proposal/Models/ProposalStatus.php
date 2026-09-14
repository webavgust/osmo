<?php

namespace App\Modules\Pub\Proposal\Models;

/**
 * Статус коммерческого предложения.
 *
 * Патч v16: убраны «Отправлено» и «На согласовании». Оба были промежуточными
 * и ни на что не влияли: для конверсии, скоринга и отбора важно одно — КП ещё
 * в работе или уже решено. Существующие записи переведены в «В работе»
 * миграцией patch_v16_status.sql.
 *
 * «Выиграно» теперь ставится и автоматически — при прикреплении КП к
 * спецификации рамочного договора (см. SpecProposalService).
 *
 * Патч v27: остались три статуса — «В работе», «Выиграно», «Проиграно».
 * «Заморожено» и «Отменено» стали причинами проигрыша (ProposalLostReason);
 * записи переведены миграцией patch_v27_proposal_status.sql.
 */
enum ProposalStatus: string
{
    case IN_WORK = 'in_work';
    case WON = 'won';
    case LOST = 'lost';

    public function data(): array
    {
        return match ($this) {
            ProposalStatus::IN_WORK => [
                'label' => 'В работе',
                'color' => 'secondary',
                'sort' => __LINE__,
                'icon' => 'fa-play',
                'final' => false,
            ],
            ProposalStatus::WON => [
                'label' => 'Выиграно',
                'color' => 'success',
                'sort' => __LINE__,
                'icon' => 'fa-trophy',
                'final' => true,
                'success' => true,
            ],
            ProposalStatus::LOST => [
                'label' => 'Проиграно',
                'color' => 'danger',
                'sort' => __LINE__,
                'icon' => 'fa-thumbs-down',
                'final' => true,
                'need_reason' => true,
            ],
        };
    }

    /** Статус закрывает работу по КП */
    public function isFinal(): bool
    {
        return $this->data()['final'] ?? false;
    }

    /** Статус требует указания причины */
    public function needReason(): bool
    {
        return $this->data()['need_reason'] ?? false;
    }

    /** Успешное завершение (для конверсии) */
    public function isSuccess(): bool
    {
        return $this->data()['success'] ?? false;
    }

    static function getDecorated(): array
    {
        $ret = [];
        foreach (static::cases() as $case) {
            $ret[$case->value] = $case->data();
        }

        return $ret;
    }

    /** Статусы, участвующие в расчёте конверсии (завершённые) */
    static function getFinal(): array
    {
        return array_filter(static::cases(), fn($case) => $case->isFinal());
    }
}
