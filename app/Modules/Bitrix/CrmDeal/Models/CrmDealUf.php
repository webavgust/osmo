<?php

namespace App\Modules\Bitrix\CrmDeal\Models;

use App\Models\ModuleModel;
use App\Modules\Bitrix\CrmCompany\Models\CrmCompany;

class CrmDealUf extends ModuleModel
{
    public $connection = 'bitrix';
    protected $table = 'crm_deal_uf';
    protected $primaryKey = 'deal_id'; // если deal_id является первичным ключом

    public function deal()
    {
        return $this->belongsTo(CrmDeal::class);
    }

    public function company()
    {
        return $this->belongsTo(CrmCompany::class, 'uf_crm_1717755645', 'title');
    }
}
