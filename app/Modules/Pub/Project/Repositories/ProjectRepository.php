<?php

namespace App\Modules\Pub\Project\Repositories;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Project\Models\Project;

class ProjectRepository
{
    public function add(Company $company, string $name)
    {
        $project = Project::create([
            'name' => $name,
        ]);

        $project->company()->associate($company)->save();

        $project->update([
            'prefix' => str_pad($project->id, 3, '0', STR_PAD_LEFT)
        ]);

        return $project;
    }

    public function update(Project $project, string $name)
    {
        $project->update(['name' => $name]);

        return $project;
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }
}
