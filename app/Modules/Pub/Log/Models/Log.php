<?php

namespace App\Modules\Pub\Log\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends ModuleModel
{
    protected $fillable = ['proposal_group', 'date', 'text'];
    protected $casts = ['date' => 'date'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function proposal()
    {
        return $this->belongsTo(Proposal::class, 'proposal_group', 'group')->orderBy('iteration', 'desc');
    }
}
