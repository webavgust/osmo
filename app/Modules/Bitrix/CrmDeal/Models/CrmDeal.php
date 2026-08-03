<?php

namespace App\Modules\Bitrix\CrmDeal\Models;

use App\Models\ModuleModel;
use App\Modules\Bitrix\CrmCompany\Models\CrmCompany;
use Illuminate\Support\Str;

class CrmDeal extends ModuleModel
{
    public $connection = 'bitrix';
    public $table = 'crm_deal';

    public function crm_company()
    {
        return $this->belongsTo(CrmCompany::class, 'company_id', 'id');
    }

    public function dealUf()
    {
        return $this->hasOne(CrmDealUf::class, 'deal_id');
    }

    public function customer()
    {
        return $this->hasOneThrough(
            CrmCompany::class,      // Конечная модель
            CrmDealUf::class,       // Промежуточная модель
            'deal_id',              // Внешний ключ в промежуточной таблице (crm_deal_uf)
            'title',                // Внешний ключ в конечной таблице (crm_company)
            'id',                   // Локальный ключ (crm_deal)
            'uf_crm_1717755645'     // Ключ в промежуточной таблице для связи с конечной
        );
    }

    public function getManagerAttribute()
    {
        return trim(Str::afterLast($this->assigned_by, ']'));
    }
}
