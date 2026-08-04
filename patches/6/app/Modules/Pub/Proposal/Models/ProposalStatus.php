<?php

namespace App\Modules\Pub\Proposal\Models;

/**
 * Статус коммерческого предложения.
 *
 * Без статуса невозможно посчитать конверсию: до этой правки в proposals
 * не было ни одного поля, отличающего выигранное КП от проигранного.
 */
enum ProposalStatus: string
{
    case IN_WORK = 'in_work';
    case SENT = 'sent';
    case NEGOTIATION = 'negotiation';
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
            ProposalStatus::SENT => [
                'label' => 'Отправлено',
                'color' => 'info',
                'sort' => __LINE__,
                'icon' => 'fa-paper-plane',
                'final' => false,
            ],
            ProposalStatus::NEGOTIATION => [
                'label' => 'На согласовании',
                'color' => 'primary',
                'sort' => __LINE__,
                'icon' => 'fa-comments',
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
