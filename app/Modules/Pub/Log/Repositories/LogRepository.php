<?php

namespace App\Modules\Pub\Log\Repositories;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Constant\Models\Constant;
use App\Modules\Pub\Log\Models\Log;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\ProposalVariantScenario\Models\ProposalVariantScenario;
use App\Modules\Pub\Scenario\Models\Scenario;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use App\Modules\Pub\Sector\Models\Sector;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use App\Modules\Pub\Proposal\Models\Proposal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LogRepository
{


    public static function getDays()
    {
        $ret = [];
        Log::all()->each(function($item) use (&$ret) {
            // Преобразуем дату в нужный формат
            $formattedDate = Carbon::parse($item->date)->format('Y-m-d');

            if (empty($ret[$formattedDate])) {
                $ret[$formattedDate] = 0;
            }
            $ret[$formattedDate]++;
        });

        return $ret;
    }


    public function create(array $data)
    {
        $company = Company::findOrFail($data['company']);
        $log = Log::create([
            'proposal_group' => $data['proposal_group'] ?? null,
            'text' => $data['text'],
            'date' => $data['date'],
        ]);

        $log->company()->associate($company)->save();
    }


    public static function getForDay(Carbon $date)
    {
        return Log::where('date', $date->format("Y-m-d"))->with('company')->orderBy('id', 'desc')->get();
    }
    public static function getAll()
    {
        return Log::with('company')->orderBy('date', 'desc')->orderBy('id', 'desc')->get()->groupBy('date');
    }
}
