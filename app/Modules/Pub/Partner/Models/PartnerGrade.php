<?php

namespace App\Modules\Pub\Partner\Models;

use App\Modules\Pub\Personal\Buyer\Models\BuyerType;

enum PartnerGrade: string
{
    case AGENT = 'agent';
    case VENDOR = 'vendor';
    case BRONZE = 'bronze';
    case SILVER = 'silver';
    case GOLD = 'gold';
    case PLATINUM = 'platinum';

    public function data(): array
    {
        return match ($this) {
            PartnerGrade::AGENT => [
                'description' => 'Агент',
                'label' => 'Agent',
                'color' => ['button' => ['bg' => '#000', 'text' => '#FFF'], 'medal' => '#000'],
                'sort' => __LINE__,
            ],
            PartnerGrade::VENDOR => [
                'description' => 'Вендор',
                'label' => 'Vendor',
                'color' => ['button' => ['bg' => '#000', 'text' => '#FFF'], 'medal' => '#000'],
                'sort' => __LINE__,
            ],
            PartnerGrade::BRONZE => [
                'description' => 'без обучения, без сделок размером 1млн+',
                'label' => 'Bronze',
                'color' => ['button' => ['bg' => '#a95619', 'text' => '#FFF'], 'medal' => '#a95619'],
                'sort' => __LINE__,
            ],
            PartnerGrade::SILVER => [
                'description' => 'без обучения, активны по сделкам с размером более 1млн+',
                'label' => 'Silver',
                'color' => ['button' => ['bg' => '#bebebe', 'text' => '#FFF'], 'medal' => '#bebebe'],
                'sort' => __LINE__,
            ],
            PartnerGrade::GOLD => [
                'description' => 'сертифицированы, без сделок размером 1млн+',
                'label' => 'Gold',
                'color' => ['button' => ['bg' => '#ffb604', 'text' => '#FFF'], 'medal' => '#ffb604'],
                'sort' => __LINE__,
            ],
            PartnerGrade::PLATINUM => [
                'description' => 'сертифицированы, активны по сделкам с размером более 1млн+',
                'label' => 'Platinum',
                'color' => ['button' => ['bg' => '#dc0404', 'text' => '#FFF'], 'medal' => '#dc0404'],
                'sort' => __LINE__,
            ],
        };
    }


}
