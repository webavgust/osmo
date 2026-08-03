<?php

namespace App\Modules\Pub\Files\Repositories;

use App\Modules\Pub\Files\Models\File;
use Illuminate\Support\Facades\Storage;

class FileRepository
{
    /**
     * Получить файлы из хранилища
     *
     * @param $path
     * @param $disk
     * @param $target_block
     * @param $target_block_id
     * @return \App\Models\ModuleModel|File|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getFromDisk($path, $disk = null, $target_block = null, $target_block_id = null)
    {
        return File::where('path', $path)
            ->where('disk', $disk)
            ->where('target_block', $target_block)
            ->where('target_block_id', $target_block_id)
            ->first();
    }

    /**
     * Создать файлы в хранилище
     *
     * @param $path
     * @param $disk
     * @param $target_block
     * @param $target_block_id
     * @return \App\Models\ModuleModel|File|false|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object
     * @throws \League\Flysystem\FilesystemException
     */
    public function createFromDisk($path, $disk = null, $target_block = null, $target_block_id = null)
    {
        if (empty($disk))
            $disk = 'massive';

        if (!Storage::disk($disk)->exists($path))
            return false;

        $file = $this->getFromDisk($path, $disk, $target_block, $target_block_id);
        if (!empty($file))
            return $file;

        $file = pathinfo(Storage::disk($disk)->path($path));
        $db_file = new File();
        $db_file->disk = $disk;
        $db_file->filename = basename($path);
        $db_file->path = $path;
        $db_file->extension = $file['extension'];
        $db_file->size = Storage::disk($disk)->fileSize($path);
        $db_file->target_block = $target_block;
        $db_file->target_block_id = $target_block_id;
        $db_file->save();

        return $db_file;
    }
}
