<?php

namespace App\Modules\Pub\Contract\Models;

enum ContractType: string
{
    case SERVICES = 'services';
    case PLATFORM = 'platform';
    case LICENSE = 'license';
    case DEVELOPMENT = 'development';
    case PARTNERSHIP = 'partnership';
    case UNKNOWN = 'unknown';

    public function data(): array
    {
        return match ($this) {
            ContractType::SERVICES => [
                'prefix' => 'S',
                'label' => 'Услуги',
                'color' => 'warning',
                'sort' => __LINE__,
                'icon' => 'fa-person-digging',
            ],
            ContractType::PLATFORM => [
                'prefix' => 'P',
                'label' => 'Платформа',
                'color' => 'primary',
                'sort' => __LINE__,
                'icon' => 'fa-desktop',
                'hidden' => true,
            ],
            ContractType::LICENSE => [
                'prefix' => 'L',
                'label' => 'ПО',
                'color' => 'danger',
                'sort' => __LINE__,
                'icon' => 'fa-brain-circuit',
            ],
            // patch v46: тип из таблицы договоров, пока не выбирается в формах
            ContractType::DEVELOPMENT => [
                'prefix' => 'D',
                'label' => 'Разработка',
                'color' => 'info',
                'sort' => __LINE__,
                'icon' => 'fa-code',
                'hidden' => true,
            ],
            ContractType::PARTNERSHIP => [
                'prefix' => 'PA',
                'label' => 'Партнёрское соглашение',
                'color' => 'success',
                'sort' => __LINE__,
                'icon' => 'fa-handshake',
            ],
            ContractType::UNKNOWN => [
                'prefix' => 'U',
                'label' => 'Неизвестно',
                'color' => 'secondary',
                'sort' => __LINE__,
                'icon' => 'fa-question',
                'hidden' => true,
            ],
        };
    }

    static function getDecorated() {
        $ret = [];
        foreach(static::cases() as $case) {
            $data = $case->data();
            $ret[$case->value] = $data;
        }

        return $ret;
    }
    static function getActualDecorated() {
        $ret = [];
        foreach(static::cases() as $case) {
            $data = $case->data();
            if($data['hidden'] ?? false) continue;
            $ret[$case->value] = $data;
        }

        return $ret;
    }

    static function getPrefixes(): array
    {
        $ret = [];
        foreach (static::cases() as $case) {
            $ret[$case->value] = $case->data()['prefix'];
        }
        return $ret;
    }

}
