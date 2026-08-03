<?php

namespace App\Modules\Pub\Hardware\Repository;

use App\Modules\Pub\Course\Models\Course;
use App\Modules\Pub\EducationApplication\Softwares\EducationApplicationListFilterSoftware;
use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use App\Modules\Pub\Hardware\Models\Hardware;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\Software\Models\Contractor;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Order\Softwares\OrderListFilterSoftware;
use App\Modules\Pub\Software\Services\SoftwareListFilterService;
use App\Modules\Pub\Sector\Models\Sector;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use App\Modules\Pub\Software\Models\Software;
use Illuminate\Support\Facades\DB;

class HardwareRepository
{
    public static function create(ProposalVariant $variant, array $data)
    {
        if($variant->proposal->variants->count() > 1 && !empty($data['cb_all']) && $data['cb_all']) {
            foreach($variant->proposal->variants as $variant) {
                $hardware = Hardware::make([
                    'name' => $data['name'],
                    'count' => $data['count'],
                    'params' => $data['params'],
                ]);

                $hardware->proposal_variant()->associate($variant)->save();
            }
        } else {
            $hardware = Hardware::make([
                'name' => $data['name'],
                'count' => $data['count'],
                'params' => $data['params'],
            ]);

            $hardware->proposal_variant()->associate($variant)->save();
        }

    }

    public static function update(Hardware $hardware, array $data)
    {
        $hardware->update([
            'name' => $data['name'],
            'count' => $data['count'],
            'params' => $data['params'],
        ]);
    }

    public static function delete(Hardware $hardware)
    {
        $hardware->delete();
    }

}
