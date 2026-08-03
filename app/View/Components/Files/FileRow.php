<?php

namespace App\View\Components\Files;


use Illuminate\Support\Facades\Storage;
use Illuminate\View\Component;

class FileRow extends Component
{

    public $file;
    public $icon = 'fa-duotone fa-file';
    public $color = 'secondary';
    public $link;
    public $size;
    public $type = 'new';

    public function __construct($file)
    {

        switch ($file->extension) {
            case 'pdf':
                $this->icon = 'fa-duotone fa-file-pdf';
                $this->color = 'danger';
                break;
            case "doc":
            case "docx":
                $this->icon = 'fa-solid fa-file-word';
                $this->color = 'text-info';
                break;
        }
        $this->file = $file;

        if (!empty($file->disk)) {
            $this->type = 'old';
            $this->size = round($file->size / (1024 * 1024), 2);
        } else {
            $this->size = round($file->filesize / (1024 * 1024), 2);
        }
    }


    public function render()
    {
        return view('components.files.file_row');
    }
}
