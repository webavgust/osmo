<?php

namespace App\Modules\Pub\Files\Traits;

use App\Modules\Pub\Files\Models\File;

trait HasFiles
{
    /*** RELATIONS ***/
    public function files()
    {
        return $this->morphMany(File::class, 'target');
    }

    /**
     * Получить файлы по типу
     *
     * @return array
     */
    public function getFilesByType()
    {
        $ret = [];
        foreach ($this->files as $file) {
            if (!empty($file->target_block_id)) {
                $ret[$file->target_block][$file->target_block_id][] = $file;
            } else {
                $ret[$file->target_block][] = $file;
            }
        }

        return $ret;
    }
}
