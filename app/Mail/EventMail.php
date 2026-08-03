<?php

namespace App\Mail;

use App\Modules\Pub\EducationTask\Models\EducationTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class EventMail extends Mailable
{
    use Queueable, SerializesModels;
    public $title;
    public $message;
    public $email;
    public $files;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($arParams)
    {
        $this->title = $arParams['title'] ?? '';
        $this->message = $arParams['message'] ?? '';
        $this->email = $arParams['email'] ?? '';
        $this->files = $arParams['files'] ?? [];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if(env('APP_ENV') == 'development')
            $this->email = 'avg.den@yandex.ru';

        $instance = $this->subject($this->title)->view('templates.email.notify')->with(['text' => $this->message, 'email' => $this->email]);
        if(!empty($this->files)) {
            foreach($this->files as $file) {
                $file_model = Storage::disk($file->disk)->path($file->path);
                $instance->attach(
                    Storage::disk($file->disk)->path($file->path),
                    [
                        'as' => $file->filename,
                        'mime' => Storage::disk($file->disk)->mimeType($file->path)
                    ]
                );
            }
        }
        return $instance;
    }
}
