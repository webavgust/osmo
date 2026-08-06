<?php

namespace App\Modules\Pub\DealCard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\DealCard\Services\DealChainService;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Support\Facades\View;

class DealCardController extends Controller
{
    /**
     * Сводная информация по сделке
     *
     * @param Proposal $proposal
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Proposal $proposal)
    {
        $data = DealChainService::build($proposal);

        return View::make('pub.deal_card.index', array_merge($data, [
            'title' => 'Сводная информация: ' . ($data['proposal']->name ?? ''),
            'bottleneck' => DealChainService::bottleneck($data['steps']),
        ]));
    }
}
