<?php

namespace App\Modules\Bitrix\CrmCompany\Models;

use App\Models\ModuleModel;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Bitrix\CrmDeal\Models\CrmDealUf;

class CrmCompanyUf extends ModuleModel
{
    protected $table = 'crm_company_uf';
    protected $primaryKey = 'company_id'; // если deal_id является первичным ключом

    public function company()
    {
        return $this->belongsTo(CrmCompany::class, 'id');
    }
}

