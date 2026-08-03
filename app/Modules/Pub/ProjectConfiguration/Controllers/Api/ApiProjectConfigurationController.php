<?php

namespace App\Modules\Pub\ProjectConfiguration\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Project\Models\Project;
use App\Modules\Pub\ProjectConfiguration\Models\ProjectConfiguration;
use App\Modules\Pub\ProjectConfiguration\Models\ProjectConfigurationPlatform;
use App\Modules\Pub\ProjectConfiguration\Repositories\ProjectConfigurationRepository;
use Illuminate\Http\Request;


class ApiProjectConfigurationController extends Controller
{
    public function __construct(
        protected $repo = new ProjectConfigurationRepository(),
    ) { }

    public function create(Project $project, Request $request)
    {
        $rules = [
            'data' => 'required|array',
            'data.platform' => 'required|string|in:' . collect(array_column(ProjectConfigurationPlatform::cases(), 'value'))->join(','),
            'data.duration' => 'required|int|min:0',
            'data.streams' => 'required|int|min:0',
        ];

        if($project->configurations->count() == 0) {
            $rules['data.comment'] = 'nullable|string';
        } else {
            $rules['data.comment'] = 'required|string';
        }

        $request->validate($rules);

        $this->repo->create($project, $request->input('data'));

        return ['result' => 'success'];
    }

    public function update(ProjectConfiguration $configuration, Request $request)
    {
        $rules = [
            'data' => 'required|array',
            'data.platform' => 'required|string|in:' . collect(array_column(ProjectConfigurationPlatform::cases(), 'value'))->join(','),
            'data.duration' => 'required|int|min:0',
            'data.streams' => 'required|int|min:0',
        ];

        if($configuration->project->configurations->count() == 0) {
            $rules['data.comment'] = 'nullable|string';
        } else {
            $rules['data.comment'] = 'required|string';
        }

        $request->validate($rules);

        $this->repo->update($configuration, $request->input('data'));

        return ['result' => 'success'];
    }
}
