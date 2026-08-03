<?php

namespace App\View\Components\Client;


use App\Modules\Pub\Client\Models\Client;
use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use Illuminate\View\Component;

class Avatar extends Component
{
    public $client;
    public $course;
    public $type;
    public $size;
    public $style = null;
    public $photo = null;
    public $number = null;

    public function __construct(Client $client, EducationTaskCourse $course = null, $size = null, $upload = null)
    {
        $this->client = $client;
        $this->size = $size;

        $css = [];
        if (!empty($size)) {
            $css[] = "width: {$size}px";
            $css[] = "height: {$size}px";
        }

        if (!empty($course)) {
            if (!empty($course->clients->keyBy('id')[$client->id]->pivot['avatar'])) {
                $this->course = $course;
                $json = json_decode($course->clients->keyBy('id')[$client->id]->pivot['avatar']);
                $this->photo = \Storage::disk('massive')->url($json->avatar);
            }
        }


        $this->style = collect($css)->implode("; ");
        if (!empty($upload)) {
            $this->type = 'upload';
        } else {
            $this->type = 'flat';
        }
    }


    public function render()
    {
        return view('components.client.avatar', [
            'client' => $this->client,
            'course' => $this->course,
            'type' => $this->type,
            'size' => $this->size,
            'style' => $this->style,
            'photo' => $this->photo,
            'number' => $this->number,
        ]);
    }
}
