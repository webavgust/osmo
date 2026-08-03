<?php

namespace App\Modules\Pub\ProjectConfiguration\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Project\Models\Project;
use App\Modules\Pub\ProjectConfiguration\Models\ProjectConfiguration;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use Illuminate\Http\Request;
use App\Modules\Pub\Project\Repositories\ProjectRepository;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;


class ProjectConfigurationBoxController extends Controller
{
    public function add(Project $project)
    {
        $num = $project->configurations->count() + 1;
        $template = View::make('pub.project_configuration.boxes.add', [
            'title' => 'Создание конфигурации',
            'project' => $project,
            'num' => $num,
        ]);

        return $template;
    }
    public function edit(ProjectConfiguration $configuration)
    {
        $num = $configuration->sort;
        $template = View::make('pub.project_configuration.boxes.edit', [
            'title' => 'Редактирование конфигурации',
            'configuration' => $configuration,
            'num' => $num,
        ]);

        return $template;
    }
}
