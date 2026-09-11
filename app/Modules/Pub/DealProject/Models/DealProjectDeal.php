<?php

namespace App\Modules\Pub\DealProject\Models;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Привязка сделки Битрикса к проекту (patch v24).
 *
 * UNIQUE по crm_deal_id в таблице и есть правило «у сделки один проект».
 */
class DealProjectDeal extends Model
{
    protected $table = 'deal_project_deals';
    public $timestamps = false;

    protected $fillable = ['deal_project_id', 'crm_deal_id', 'attached_at', 'attached_by'];
    protected $casts = ['attached_at' => 'datetime'];

    public function deal_project()
    {
        return $this->belongsTo(DealProject::class, 'deal_project_id');
    }

    /** Сделка Битрикса (другое соединение — связь по id) */
    public function crm_deal()
    {
        return $this->belongsTo(CrmDeal::class, 'crm_deal_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'attached_by');
    }
}
