<?php

namespace App\Modules\Pub\Neuroservice\Repositories;

use App\Modules\Pub\EducationTask\Models\EducationTask;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\OrderTaskAgreement\Models\OrderTaskAgreement;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Repositories\UserRepository;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NeuroserviceRepository
{
    public static function getAll()
    {
        return Neuroservice::with('neuroservice_group')->get();
    }


    public static function getCosts()
    {
        $ret = [
            'year' => [],
            'unlimited' => []
        ];
        $neuroservices = static::getAll();
        foreach($neuroservices as $neuro) {
            $ret['year'][$neuro->id] = $neuro->cost_year;
            $ret['unlimited'][$neuro->id] = $neuro->cost_unlimited;
        }

        return $ret;
    }

    public static function update(Neuroservice $neuroservice, array $data)
    {
        if(!isset($data['cb_registered']))
            $data['cb_registered'] = false;

        $neuroservice->update($data);
        $neuroservice->refresh();

        return $neuroservice;
    }


}
