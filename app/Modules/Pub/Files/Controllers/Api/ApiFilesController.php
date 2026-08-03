<?php

namespace App\Modules\Pub\Files\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Pub\EducationApplication\Models\EducationApplication;
use App\Modules\Pub\EducationApplication\Services\EducationApplicationService;
use App\Modules\Pub\Evaluation\Models\Evaluation;
use App\Modules\Pub\Evaluation\Services\EvaluationService;
use App\Modules\Pub\Files\Services\FileService;
use App\View\Components\Files\Dropzone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ApiFilesController extends Controller
{
    public $service;

    public function __construct()
    {
        $this->service = new FileService();
    }

    public function generatePdfFromFile(Request $request)
    {
        sleep(1);
        $request->validate([
            'path' => 'required|string',
            'disk' => 'nullable|string'
        ]);

        return $this->service->generatePdfFromFile($request->input('path'), $request->input('disk'));
    }
}
