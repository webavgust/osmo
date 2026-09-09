<?php

namespace App\View\Components\EducationApplication\Clients;


use App\Modules\Pub\Client\Models\Client;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class TableTr extends Component
{
    public $uid;
    public $clientId;
    public $courses;
    public $data;
    public $app;

    public function __construct($courses, $clientId = null, $data = [], $app = null, $task = false)
    {
        $this->courses = $courses;
        $this->uid = Str::random(8);
        $this->clientId = $clientId;
        $this->data = $data;
        $this->app = $app;
    }


    public function render()
    {
        return view('components.education_application.clients.table_tr', [
            'uid' => $this->uid,
            'clientId' => $this->clientId,
            'courses' => $this->courses,
            'data' => $this->data,
            'app' => $this->app,
        ]);
    }
}
