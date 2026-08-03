<?php

namespace App\Modules\Bitrix\CrmDeal\Models;

use App\Modules\Pub\Payment\Models\PaymentStatus;
use Illuminate\Support\Str;
use function GuzzleHttp\default_ca_bundle;

enum CrmDealIssues
{
    case MONTH_NOT_MATCH_QUARTER;                   // Месяц и квартал не совпадают
    case NO_CUSTOMER;                               // Не указан конечный заказчик
    case NO_PARTNER;                                // Не указан партнёр
    case NO_MONTH_OR_QUARTER;                       // Не заполнен месяц сделки или квартал
    case SUM_LICENSE_SERVICES_NOT_MATCH_TOTAL;      // Стоимость лицензий и услуг не совпадает с общей (не валютные сделки)

    case LICENCE_OR_SERVICE_AMOUNT_DOESNT_FILL;     // Не указана стоимость лиценцзий и(или) услуг
    case AMOUNT_IS_NULL;                            // Не указана стоимость сделки
    case FOREIGN_LICENSE_OR_SERVICE_CONTAINS_SPACE; // Стоимость лицензий и услуг имеет кривые символы [НЕ РУБ!]


    public function data(): array
    {
        return match ($this) {
            self::MONTH_NOT_MATCH_QUARTER => [
                'label' => 'Месяц и квартал не совпадают',
                'validate' => function ($deal) {
                    $month = $deal->dealUf?->uf_crm_1736778153503 ?? null;
                    $quarter = $deal->dealUf?->uf_crm_1722255711522 ?? null;
                    if(empty($month) || empty($quarter) || (int)$month == 0 || (int)$quarter == 0) return true;

                    $quarter_int = (int)Str::afterLast($quarter, 'q');

                    $month_quarter = match((int)$month) {
                        1, 2, 3 => 1,
                        4, 5, 6 => 2,
                        7, 8, 9 => 3,
                        10, 11, 12 => 4,
                        default => -1
                    };

                    return $quarter_int === $month_quarter;
                }
            ],
            self::NO_CUSTOMER => [
                'label' => 'Не указан конечный заказчик',
                'validate' => function ($deal) {
                    if(in_array($deal->stage_name, [
                        'Lead',
                    ])) return true;

                    return !empty($deal->customer);
                }

            ],
            self::NO_PARTNER => [
                'label' => 'Не указан партнёр',
                'validate' => function ($deal) {
                    return !empty($deal->company_name);
                }
            ],
            self::NO_MONTH_OR_QUARTER => [
                'label' => 'Не заполнен месяц сделки или квартал',
                'validate' => function ($deal) {
                    if(in_array($deal->stage_name, [
                        'Lead',
                        'Research',
                        'Presentation',
                    ])) return true;

                    $month = $deal->dealUf?->uf_crm_1736778153503 ?? null;
                    $quarter = $deal->dealUf?->uf_crm_1722255711522 ?? null;


                    if(empty($month) || empty($quarter) || (int)$month == 0 || (int)$quarter == 0) return false;

                    return true;
                }
            ],
            self::LICENCE_OR_SERVICE_AMOUNT_DOESNT_FILL => [
                'label' => 'Не указана стоимость лиценцзий и(или) услуг',
                'validate' => function ($deal) {
                    if(in_array($deal->stage_name, [
                        'Lead',
                        'Research',
                        'Presentation',
                    ])) return true;

                    $costServices = $deal->dealUf?->uf_crm_1718977763677 ?? null;
                    $costSoftware = $deal->dealUf?->uf_crm_1718977752420 ?? null;

                    if($deal->opportunity == 0) return true;

                    return
                        (!empty($costServices) && $costServices !== '')
                        ||
                        (!empty($costSoftware) && $costSoftware !== '');
                }
            ],
            self::AMOUNT_IS_NULL => [
                'label' => 'Не указана стоимость сделки',
                'validate' => function ($deal) {
                    if(in_array($deal->stage_name, [
                        'Lead',
                        'Research',
                        'Presentation',
                        'Pilot project',
                        'Competition/tender',
                    ])) return true;

                    if($deal->opportunity > 0) return true;
                }
            ],
            self::SUM_LICENSE_SERVICES_NOT_MATCH_TOTAL => [
                'label' => 'Стоимость лицензий и услуг не совпадает с общей стоимостью',
                'validate' => function ($deal) {
                    if($deal->currency_id !== 'RUB') return true;
                    if($deal->opportunity == 0) return true;

                    $costServices = $deal->dealUf?->uf_crm_1718977763677 ?? null;
                    $costSoftware = $deal->dealUf?->uf_crm_1718977752420 ?? null;

                    if((float)$deal->opportunity === (float)$costServices + (float)$costSoftware) return true;

                    return false;
                }
            ],
            self::FOREIGN_LICENSE_OR_SERVICE_CONTAINS_SPACE => [
                'label' => 'Стоимость лицензий и услуг имеет кривые символы [НЕ РУБ!]',
                'validate' => function ($deal) {
                    if($deal->currency_id === 'RUB') return true;



                    $costServices = $deal->dealUf?->uf_crm_1718977763677 ?? null;
                    $costSoftware = $deal->dealUf?->uf_crm_1718977752420 ?? null;

                    $costServicesNumeric = preg_replace('/[^0-9]/', '', $costServices);
                    $costSoftwareNumeric = preg_replace('/[^0-9]/', '', $costSoftware);



                    if(!empty($costServices) && $costServices !== $costServicesNumeric) return false;
                    if(!empty($costSoftware) && $costSoftware !== $costSoftwareNumeric) return false;

                    return true;
                }
            ]
        };
    }

    static function getDecorated() {
        $ret = [];
        foreach(static::cases() as $case) {
            $ret[$case->value] = $case->data();
        }

        return $ret;
    }

    function validate(CrmDeal $deal) {
        return $this->data()['validate']($deal);
    }
}
