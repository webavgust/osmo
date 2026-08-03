<?php

namespace App\Modules\Pub\Project\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecification\Repository\ContractSpecificationRepository;
use App\Modules\Pub\Project\Models\Project;
use App\Modules\Pub\Project\Repositories\ProjectRepository;
use Illuminate\Http\Request;


class ApiProjectController extends Controller
{
    public function __construct(
        protected $repo = new ProjectRepository(),
    ) { }

    public function add(Company $company, Request $request)
    {
        $request->validate(['name' => 'required|string']);

        $this->repo->add(company: $company, name: $request->name);

        return ['result' => 'success'];
    }

    public function update(Project $project, Request $request)
    {
        $request->validate(['name' => 'required|string']);

        $this->repo->update(project: $project, name: $request->name);

        return ['result' => 'success'];
    }

    public function delete(Project $project)
    {
        if(!$project->canDelete()) abort(404);

        $this->repo->delete(project: $project);

        return ['result' => 'success'];
    }
}
