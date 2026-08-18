<?php

namespace App\Modules\Pub\ContractSpecification\Models;

use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Привязка КП к спецификации.
 *
 * Ключ привязки — группа КП, а не id редакции: прикрепляется предложение
 * целиком. Дата прикрепления — момент, когда КП стало договором; по ней
 * считается показатель «КП → договор» в скоринге партнёров.
 */
class ContractSpecificationProposal extends Model
{
    protected $table = 'contract_specification_proposals';
    public $timestamps = false;

    protected $fillable = ['contract_specification_id', 'proposal_group', 'attached_at', 'attached_by'];
    protected $casts = ['attached_at' => 'datetime'];

    public function specification()
    {
        return $this->belongsTo(ContractSpecification::class, 'contract_specification_id');
    }

    /** Последняя редакция привязанного КП */
    public function proposal()
    {
        return $this->belongsTo(Proposal::class, 'proposal_group', 'group');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'attached_by');
    }
}
