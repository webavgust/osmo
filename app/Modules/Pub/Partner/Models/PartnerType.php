<?php

namespace App\Modules\Pub\Partner\Models;


enum PartnerType: string
{
    case AGENT = 'agent';
    case DEALER = 'dealer';
    case DISTRIBUTOR = 'distributor';
    case VENDOR = 'vendor';

    public function data(): array
    {
        return match ($this) {
            PartnerType::AGENT => [
                'description' => 'Агент',
                'label' => 'Agent',
                'color' => ['button' => ['bg' => '#000', 'text' => '#FFF']],
                'sort' => __LINE__,
            ],
            PartnerType::DEALER => [
                'description' => 'Дилер',
                'label' => 'Dealer',
                'color' => ['button' => ['bg' => '#000', 'text' => '#FFF']],
                'sort' => __LINE__,
            ],
            PartnerType::DISTRIBUTOR => [
                'description' => 'Дистрибьютор',
                'label' => 'Distributor',
                'color' => ['button' => ['bg' => '#000', 'text' => '#FFF']],
                'sort' => __LINE__,
            ],
            PartnerType::VENDOR => [
                'description' => 'Вендор',
                'label' => 'Vendor',
                'color' => ['button' => ['bg' => '#000', 'text' => '#FFF']],
                'sort' => __LINE__,
            ],
        };
    }


}
