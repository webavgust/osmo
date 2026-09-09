<?php

namespace App\View\Components\EducationTaskCourse;

use App\Modules\Pub\Document\Models\Document;
use App\Modules\Pub\Document\Repositories\DocumentRepository;
use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use function view;

class DocumentNew extends Component
{
    public $rows;
    public $uid;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->rows = DocumentRepository::getAll();
        $this->rows->map(function ($item) {
            $item->count = $item->blanks_free()->count();
            $item->name .= " ({$item->count})";
        });
        $this->uid = Str::random(16);
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_task_course.document_new', [
            'rows' => $this->rows,
            'uid' => $this->uid
        ]);
    }
}
