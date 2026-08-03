<?php

namespace App\View\Components\Ui\Icon;


use Illuminate\View\Component;

class Files extends Component
{
    public $icon;
    public $color;
    public $rgb;

    public function __construct($ext)
    {
        switch ($ext) {
            case "jpg":
            case "jpeg":
                $this->icon = 'fa-solid fa-file-image';
                $this->color = 'text-info';
                break;
            case "pdf":
                $this->icon = 'fa-solid fa-file-pdf';
                $this->color = 'text-danger';
                break;
            case "doc":
            case "docx":
                $this->icon = 'fa-solid fa-file-word';
                $this->color = 'text-info';
                break;
            default:
                $this->icon = 'fa-solid fa-file';
                $this->rgb = '#000000';
        }
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.ui.icon.files');
    }
}
