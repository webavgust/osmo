<?php

namespace App\Modules\Pub\Log\Services;

use App\Modules\Pub\Log\Repositories\LogRepository;
use App\Modules\Pub\Partner\Models\PartnerGrade;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;

class LogService
{

    public function __construct()
    {
        $this->repo = new LogRepository();
    }

}

