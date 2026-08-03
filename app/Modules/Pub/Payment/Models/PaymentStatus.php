<?php

namespace App\Modules\Pub\Payment\Models;

enum PaymentStatus: string
{
    case WAITING = 'waiting';
    case SUCCESS = 'success';
    case DELAYED = 'delayed';
    case EXPIRED = 'expired';

    public function data(): array
    {
        return match ($this) {
            PaymentStatus::WAITING => [
                'label' => 'Ожидаем платёж',
                'color' => 'secondary',
                'sort' => __LINE__,
                'icon' => 'fa-hourglass-start',
            ],
            PaymentStatus::SUCCESS => [
                'label' => 'Оплачено',
                'color' => 'success',
                'sort' => __LINE__,
                'icon' => 'fa-circle-check',
            ],
            PaymentStatus::DELAYED => [
                'label' => 'Пошла просрочка',
                'color' => 'warning',
                'sort' => __LINE__,
                'icon' => 'fa-hourglass-start',
            ],
            PaymentStatus::EXPIRED => [
                'label' => 'Оплачено с просрочкой',
                'color' => 'danger',
                'sort' => __LINE__,
                'icon' => 'fa-circle-exclamation',
            ],
        };
    }

    static function getDecorated() {
        $ret = [];
        foreach(static::cases() as $case) {
            $ret[$case->value] = $case->data();
        }

        return $ret;
    }
}
