<?php

namespace App\Modules\Pub\ProjectConfiguration\Models;

use App\Modules\Pub\Contract\Models\ContractType;

enum ProjectConfigurationPlatform:string
{
    case PLATFORM_BASE = "platform_base";
    case PLATFORM_SCENARIOS = "platform_scenarios";

    public function data(): array
    {
        return match ($this) {
            self::PLATFORM_BASE => [
                'id' => 1,
                'label' => 'Чистая платформа',
            ],
            self::PLATFORM_SCENARIOS => [
                'id' => 2,
                'label' => 'Платформа с сервисами / сервисы',
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
