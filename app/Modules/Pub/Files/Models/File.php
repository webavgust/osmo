<?php

namespace App\Modules\Pub\Files\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\EducationApplication\Models\EducationApplication;
use App\Modules\Pub\EducationTask\Models\EducationTask;
use App\Modules\Pub\Evaluation\Models\Evaluation;
use App\Modules\Pub\Files\Traits\HasFiles;
use App\Modules\Pub\Report\Models\Report;
use Illuminate\Support\Facades\Storage;

class File extends ModuleModel
{
    use HasFiles;

    public static $module_name = 'Файл';
    protected $fillable = [
        'file',
        'name',
        'target_block',
        'target_block_id',
        'disk'
    ];


    public const PRESETS = [
        'trash' => [
            'evaluation_import' => [
                'name' => 'Файл для импорта',
                'extensions' => ['docx', 'doc', 'pdf', 'xlsx', 'xls'],
                'filesize' => 14,
                'count' => 1
            ],
        ],
        Evaluation::class => [
            'blank' => [
                'name' => 'Бланк',
                'extensions' => ['docx', 'doc'],
                'available_mime' => [
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

//                    'application/vnd.ms-excel',
//                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                ],
                'filesize' => 15,
                'count' => 1
            ],
            'other' => [
                'name' => 'Документы',
                'extensions' => ['pdf', 'docx', 'rar', 'jpg'],
                'available_mime' => [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.rar',
                    'image/jpeg',

//                    'application/vnd.ms-excel',
//                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                ],
                'filesize' => 15,
                'count' => 0
            ],
        ],
    ];

    /**
     * Перегрузка событий
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        self::created(function (File $model) {
            $model->url = \Storage::disk($model->disk)->url($model->path);
            $model->save();
        });
    }

    /*** RELATIONS ***/

    public function target()
    {
        return $this->morphTo(__FUNCTION__, 'target_type', 'target_id');
    }


    /**
     * Получить параметр full_path с полным путём к файлу
     *
     * @return string
     */
    public function getFullPathAttribute()
    {
        return Storage::disk($this->disk)->path($this->path);
    }
}
