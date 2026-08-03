<?php

namespace App\Modules\Pub\Project\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Pub\Project\Repositories\ProjectRepository;


class ProjectController extends Controller
{
    public function __construct(
        protected $repo = new ProjectRepository(),
    ) { }

    public function index()
    {
        return view('pub.projects.index', [
            'breadcrumbs' => $this->breadcrumb,
        ]);
    }
}
