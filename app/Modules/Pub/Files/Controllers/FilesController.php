<?php

namespace App\Modules\Pub\Files\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\EducationApplication\Models\EducationApplication;
use App\Modules\Pub\EducationApplication\Services\EducationApplicationService;
use App\Modules\Pub\Evaluation\Models\Evaluation;
use App\Modules\Pub\Evaluation\Services\EvaluationService;
use App\Modules\Pub\Files\Services\FileService;
use App\View\Components\Files\Dropzone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class FilesController extends Controller
{
    public $service;

    public function __construct()
    {
        $this->service = new FileService();
    }

    /**
     * Загрузить файл во временное хранилище
     *
     * @param Request $request
     * @return bool|int[]|string
     */
    public function upload_temporary(Request $request)
    {
        if ($this->service->check_preset($request)) {
            return $this->service->saveTemporary($request);
        } else {
            return ['error' => 1];
        }
    }

    /**
     * Загрузить файл во временное хранилище
     *
     * @param Request $request
     * @return bool|int[]|string
     */
    public function upload_trash(Request $request)
    {
        if ($this->service->check_preset($request, ['mode' => 'trash'])) {
            return ['result' => 'success', 'filename' => $this->service->saveTemporary($request, ['mode' => 'trash'])];
        } else {
            return ['result' => 'error'];
        }
    }

    /**
     * Удалить временный файл
     *
     * @param Request $request
     * @return string[]
     */
    public function delete_temporary(Request $request)
    {
        if ($this->service->deleteTemporary($request)) {
            return ['status' => 'success'];
        } else {
            return ['status' => 'error'];
        }
    }

    /**
     * Перерисовать блок с файлами
     *
     * @param Request $request
     * @return string
     */
    public function block_redraw(Request $request)
    {
        $files = [];
        switch ($request->mode) {
            case 'evaluation':
                $files = EvaluationService::getFilesByType(Evaluation::find($request->id));
                break;
        }
        $block = new Dropzone($request->mode, $request->id, $request->block, null, $files, $request->block_id);
        $files_html = View::make('components.files.files-list', ['files' => $block->files]);

        return $block->can_add . '|#|' . $files_html;
    }
}
