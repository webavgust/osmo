<?php

namespace App\Modules\Pub\Project\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Project\Models\Project;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use Illuminate\Http\Request;
use App\Modules\Pub\Project\Repositories\ProjectRepository;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;


class ProjectBoxController extends Controller
{
    public function add(Company $company)
    {
        $nextId = Project::max('id') + 1;
        $prefix = str_pad($nextId, 3, '0', STR_PAD_LEFT);

        $template = View::make('pub.project.boxes.add', [
            'title' => 'Создание проекта',
            'company' => $company,
            'prefix' => $prefix,
        ]);

        return $template;
    }

    public function edit(Project $project)
    {
        $template = View::make('pub.project.boxes.edit', [
            'title' => 'Редактирование проекта',
            'project' => $project,
        ]);

        return $template;
    }
}
