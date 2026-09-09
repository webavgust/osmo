<?php

namespace App\View\Components\EducationTaskCourse\Detail;

use App\Modules\Pub\Client\Models\Client;
use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use Illuminate\View\Component;
use function view;

class ClientRow extends Component
{
    public $course;
    public $client;
    public $source;
    public $avatar;
    public $number;


    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(EducationTaskCourse $course, Client $client)
    {
        $course_client = $course->clients->keyBy('id')[$client->id];

        $this->course = $course;
        $this->client = $client;
        $this->source = $course->education_task->files()->where('target_block', 'photo')->where('target_block_id', $client->id)->first() ?? null;
        $this->avatar = $course_client->pivot['avatar'] ?? null;
        $this->number = $course->clients->keyBy('id')[$client->id]->pivot['number'] ?? null;
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.education_task_course.detail.client_row', [
            'course' => $this->course,
            'client' => $this->client,
            'source' => $this->source,
            'avatar' => $this->avatar,
            'number' => $this->number,
        ]);
    }
}
