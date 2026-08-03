<?php

namespace App\Modules\Pub\Currency\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends ModuleModel
{
    public $timestamps = false;
    protected $fillable = ['slug', 'name', 'symbol'];

    const CURRENCY_DEFAULT = 'RUB';

    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'currency_slug', 'slug');
    }

}
