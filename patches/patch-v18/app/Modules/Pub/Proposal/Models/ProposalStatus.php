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
 */
enum ProposalStatus: string
{
    case IN_WORK = 'in_work';
    case WON = 'won';
    case LOST = 'lost';
    case FROZEN = 'frozen';
    case CANCELED = 'canceled';

    public function data(): array
    {
        return match ($this) {
            ProposalStatus::IN_WORK => [
                'label' => 'В работе',
                'color' => 'secondary',
                'sort' => __LINE__,
                'icon' => 'fa-pen-ruler',
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
            ProposalStatus::FROZEN => [
                'label' => 'Заморожено',
                'color' => 'warning',
                'sort' => __LINE__,
                'icon' => 'fa-snowflake',
                'final' => false,
                'need_reason' => true,
            ],
            ProposalStatus::CANCELED => [
                'label' => 'Отменено',
                'color' => 'dark',
                'sort' => __LINE__,
                'icon' => 'fa-ban',
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
