<?php

namespace App\Modules\Pub\ContractSpecification\Models;

use App\Modules\Pub\Contract\Models\ContractType;

enum ContractSpecificationStatus: string
{
    case PROCESSING = 'processing';
    case CLOSED = 'closed';
    case CANCELED = 'canceled';

    public function data(): array
    {
        return match ($this) {
            static::PROCESSING => [
                'label' => 'В процессе',
                'color' => 'primary',
                'sort' => __LINE__,
            ],
            static::CLOSED => [
                'label' => 'Закрыта',
                'color' => 'secondary',
                'sort' => __LINE__,
            ],
            static::CANCELED => [
                'label' => 'Отменена',
                'color' => 'danger',
                'sort' => __LINE__,
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

    public static function getStatuses()
    {
        $arRet = [];
        foreach(static::cases() as $case) {
            $arRet[$case->value] = $case->data()['label'];
        }
        return $arRet;
    }
}
