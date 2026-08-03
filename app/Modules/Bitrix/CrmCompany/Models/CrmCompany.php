<?php

namespace App\Modules\Bitrix\CrmCompany\Models;

use App\Models\ModuleModel;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Bitrix\CrmDeal\Models\CrmDealUf;

class CrmCompany extends ModuleModel
{
    public $connection = 'bitrix';
    public $table = 'crm_company';

    public function companyUf()
    {
        return $this->hasOne(CrmCompanyUf::class, 'company_id');
    }

    public function deals()
    {
        return $this->hasMany(CrmDeal::class, 'company_id', 'id');
    }
}
