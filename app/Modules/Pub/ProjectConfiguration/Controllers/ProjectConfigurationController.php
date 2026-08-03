<?php

namespace App\Modules\Pub\ProjectConfiguration\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Pub\ProjectConfiguration\Repositories\ProjectConfigurationRepository;


class ProjectConfigurationController extends Controller
{
    public function __construct(
        protected $repo = new ProjectConfigurationRepository(),
    ) { }

    public function index()
    {
        return view('pub.projectconfigurations.index', [
            'breadcrumbs' => $this->breadcrumb,
        ]);
    }
}
