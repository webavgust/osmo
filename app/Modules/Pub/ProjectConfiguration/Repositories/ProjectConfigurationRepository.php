<?php

namespace App\Modules\Pub\ProjectConfiguration\Repositories;

use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\Project\Models\Project;
use App\Modules\Pub\ProjectConfiguration\Models\ProjectConfiguration;
use App\Modules\Pub\ProjectConfiguration\Models\ProjectConfigurationPlatform;

class ProjectConfigurationRepository
{
    public function create(Project $project, array $data)
    {
        $sort = $project->configurations->max('sort') + 1;
        $platform = ProjectConfigurationPlatform::from($data['platform']);

        $number = "{$project->prefix}.{$platform->data()['id']}.{$data['duration']}.{$data['streams']}.{$sort}";

        ProjectConfiguration::make([
            'number' => $number,
            'platform' => $data['platform'],
            'duration' => $data['duration'],
            'streams' => $data['streams'],
            'comment' => $data['comment'],
            'sort' => $sort
        ])
        ->project()->associate($project)
        ->save();
    }

    public function update(ProjectConfiguration $configuration, array $data)
    {
        $platform = ProjectConfigurationPlatform::from($data['platform']);

        $number = "{$configuration->project->prefix}.{$platform->data()['id']}.{$data['duration']}.{$data['streams']}.{$configuration->sort}";

        $configuration->update([
            'number' => $number,
            'platform' => $data['platform'],
            'duration' => $data['duration'],
            'streams' => $data['streams'],
            'comment' => $data['comment'],
        ]);
    }

    public static function getAvailable(ContractSpecification $spec)
    {
        return $spec
            ->company
            ->configurations()
            ->where(function($query) use ($spec) {})
            ->whereDoesntHave('contract_specification')
            ->get();
    }
}
