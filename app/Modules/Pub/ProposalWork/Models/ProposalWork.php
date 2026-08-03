<?php

namespace App\Modules\Pub\ProposalWork\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Deal\Models\Deal;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\ProposalWorkScenario\Models\ProposalWorkScenario;
use App\Modules\Pub\Scenario\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ProposalWork extends ModuleModel
{
    protected $fillable = ['cb_process', 'description', 'notice', 'sort', 'group'];
    public $timestamps = false;

    /**
     * Дополняем слушатели событий
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($instance) {
            // почистим variants
        });
    }


    public function proposal()
    {
        return $this->belongsTo(Proposal::class)->orderBy('sort');
    }

    public function proposal_variant()
    {
        return $this->hasMany(ProposalVariant::class)->withPivot(['cost', 'count', 'discount', 'cost_total']);
    }

}
