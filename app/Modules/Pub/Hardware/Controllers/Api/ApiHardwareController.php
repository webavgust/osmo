<?php

namespace App\Modules\Pub\Hardware\Controllers\Api;

use App\Modules\Pub\Hardware\Models\Hardware;
use App\Modules\Pub\Hardware\Repository\HardwareRepository;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\Software\Models\Software;
use App\Modules\Pub\Software\Repositories\SoftwareRepository;
use App\Modules\Pub\Software\Requests\ListFilterRequest;
use App\Modules\Pub\Software\Services\SoftwareListFilterService;
use App\Modules\Pub\Software\Services\SoftwareService;
use App\View\Components\Log\Story;
use App\View\Components\Proposal\HardwareTable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class ApiHardwareController
{
    public $repo;
    public function __construct()
    {
        $this->repo = new SoftwareRepository();
        $this->service = new SoftwareService();
    }


    public function store(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:proposal_variants,id',
            'name' => 'required|string',
            'count' => 'nullable|string',
            'params' => 'nullable|string',
            'cb_all' => 'nullable|boolean',
        ]);

        $variant = ProposalVariant::find($request->input('id'));

        $data = $request->all();

        HardwareRepository::create(variant: $variant, data: $data);

        // response
        $cb_all = $variant->proposal->variants->count() > 1 && !empty($data['cb_all']) && $data['cb_all'];
        if($cb_all) {
            $html = [];
            foreach($variant->proposal->variants as $variant) {
                $component = new HardwareTable(variant: $variant);

                $html[$variant->id] = $component->render()->render();
            }
        } else {
            $component = new HardwareTable(variant: $variant);
            $html = $component->render()->render();
        }



        return ['result' => 'success', 'html' => $html];
    }


    public function update(Hardware $hardware, Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'count' => 'nullable|string',
            'params' => 'nullable|string',
        ]);

        HardwareRepository::update($hardware, $request->all());

        $hardware->refresh();

        $component = new HardwareTable(variant: $hardware->proposal_variant);
        $html = $component->render()->render();


        return ['result' => 'success', 'html' => $html];
    }

    public function delete(Request $request, Proposal $proposal, int $iteration)
    {
        $request->validate([
            'id' => 'required|exists:hardware,id',
            'variant' => 'required|exists:proposal_variants,id',
        ]);

        $proposal = ProposalRepository::getOnce($proposal->group, $iteration);
        if(empty($proposal)) abort(404);

        $variant = $proposal->variants()->find($request->input('variant'));
        if(!$variant)
            throw(new AuthenticationException());

        $hardware = $variant->hardware()->find($request->input('id'));
        if(!$hardware)
             throw(new AuthenticationException());

        HardwareRepository::delete(hardware: $hardware);

        return ['result' => 'success'];
    }


}
