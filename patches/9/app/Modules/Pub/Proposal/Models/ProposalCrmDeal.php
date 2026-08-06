<?php

namespace App\Modules\Pub\Proposal\Models;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Привязка КП к сделке Битрикса.
 *
 * У одного КП может быть несколько сделок. Одна из них — главная:
 * её id дублируется в proposals.crm_deal_id для старого кода и фильтров.
 */
class ProposalCrmDeal extends Model
{
    protected $table = 'proposal_crm_deals';
    public $timestamps = false;

    protected $fillable = ['proposal_group', 'crm_deal_id', 'is_main', 'comment', 'linked_at', 'linked_by'];
    protected $casts = ['is_main' => 'boolean', 'linked_at' => 'datetime'];

    /**
     * Сделка Битрикса (другое соединение — связь по id вручную)
     */
    public function crm_deal()
    {
        return $this->belongsTo(CrmDeal::class, 'crm_deal_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'linked_by');
    }

    /**
     * Привязки КП
     *
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function forGroup(string $group)
    {
        return static::where('proposal_group', $group)
            ->orderByDesc('is_main')
            ->orderBy('id');
    }
}
