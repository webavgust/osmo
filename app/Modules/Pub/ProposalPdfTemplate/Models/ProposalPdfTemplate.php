<?php

namespace App\Modules\Pub\ProposalPdfTemplate\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Traits\Eloquent\Model\HasCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalPdfTemplate extends ModuleModel
{
    use HasCreator;

    protected $fillable = ['html', 'name'];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

}
