<?php

namespace App\Modules\Pub\Organization\Model;

use App\Models\ModuleModel;
use App\Models\traits\HasDetailPage;
use App\Modules\Pub\DocumentNumber\Models\DocumentNumber;
use App\Modules\Pub\Evaluation\Models\Evaluation;
use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\OrderTask\Models\PlanVisit;
use App\Modules\Pub\OrderTask\Models\Salary;
use App\Modules\Pub\OrderTask\Models\Sampler;
use App\Modules\Pub\OrderTaskAgreement\Models\OrderTaskAgreement;
use App\Modules\Pub\OrderTaskObject\Models\OrderTaskObject;
use App\Modules\Pub\Reminder\Traits\HasReminder;
use App\Modules\Pub\Service\Models\Service;
use App\Modules\Pub\SubContract\Models\SubContract;
use App\Modules\Pub\User\Models\User;
use App\Traits\Eloquent\Model\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Support\Facades\DB;

class Organization extends ModuleModel
{
    public $timestamps = false;
    protected $fillable = ['name', 'slug'];

    public const OSMOVIEW = 1;
    public const NEUROLIS = 2;
}

