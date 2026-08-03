<?php

namespace App\Modules\Pub\Files\Services;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\EducationApplication\Models\EducationApplication;
use App\Modules\Pub\EducationApplication\Services\EducationApplicationService;
use App\Modules\Pub\EducationTask\Models\EducationTask;
use App\Modules\Pub\Evaluation\Models\Evaluation;
use App\Modules\Pub\Files\Models\File;
use App\Modules\Pub\Report\Models\Report;
use Barryvdh\DomPDF\PDF;
use Carbon\Carbon;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use \Dompdf\Dompdf;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Writer\HTML;
use PhpOffice\PhpWord\Writer\HTML\Memory as HtmlMemoryWriter;


class FileService
{
    /**
     * Сохраняет файл
     *
     * @param string $source
     * @return File|null
     */
    public static function store(string $source, $save_path = '', $disk = 'public', $block = null, $block_id = null, $name = null): ?File
    {
        if (!Storage::disk($disk)->exists($source)) return null;
        $infoPath = pathinfo($source);
        $carbon = new Carbon();

        if (empty($save_path))
            $save_path = "{$infoPath['extension']}/{$carbon->year}/{$carbon->month}/{$carbon->day}/" . $carbon->format('YmdHis') . "_{$out_name}.{$infoPath['extension']}";


        $source = Storage::disk($disk)->get($source);


        if (Storage::disk('massive')->put($save_path, $source)) {
            $file = new File();
            $file->disk = $disk;
            $file->filename = basename($save_path);
            $file->name = $name;
            $file->path = $save_path;
            $file->extension = $infoPath['extension'];
            $file->size = Storage::disk('massive')->fileSize($save_path);
            if (!empty($block)) $file->target_block = $block;
            if (!empty($block_id)) $file->target_block_id = $block_id;
            $file->save();

            return $file;
        }

        return null;
    }

    /**
     * Получить полный путь для файла
     *
     * @param $file
     * @return string
     */
    public static function getFullPath($file)
    {
        if (is_numeric($file))
            $file = File::findOrFail($file);

        return \Illuminate\Support\Facades\Storage::disk('public')->path($file->path);
    }


    /**
     * Конвертер PDF в JPG
     *
     * @param $filepath
     * @return string
     * @throws \Spatie\PdfToImage\Exceptions\PdfDoesNotExist
     *
     * TODO: Перенести это в специальный класс по преобразованию файлов
     */
    public static function convertPDFtoJPG($filepath)
    {
        $filename = md5($filepath) . '.jpg';
        $url = Storage::disk('temporary_flush')->url($filename);
        $path = Storage::disk('temporary_flush')->path($filename);
        if (!Storage::disk('temporary_flush')->exists($filename)) {
            $converter = new Pdf($filepath);
            $converter->setResolution(300);
            $converter->saveImage($path);
        }

        return $url;
    }

    /**
     * Копирование файла
     *
     * @param $file
     * @param $destination
     * @return mixed|null
     */
    public static function clone($file, $destination)
    {
        $source = Storage::disk($file->disk)->get($file->path);
        if (empty($source))
            return null;

        $destination_full = Storage::disk($file->disk)->path($destination);

        if (Storage::disk('massive')->put($destination, $source)) {
            $new_file = $file->replicate();
            $new_file->path = $destination;
            return $new_file;
        }

        return null;
    }

    /**
     * Проверить настройки для загрузки файла
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    public function check_preset(\Illuminate\Http\Request $request, $arParams = [])
    {
        if (!empty($arParams['mode']) && $arParams['mode'] == 'trash') {
            $preset = File::PRESETS['trash'][$request->input('block')] ?? [];
            if (empty($preset))
                return false;


        } else {
            switch ($request->input('mode')) {
                case 'evaluation':
                    $preset = File::PRESETS[Evaluation::class][$request->input('block')] ?? [];

                    break;
            }

            if (empty($preset))
                return false;

            // проверка на кол-во файлов в блоке
            if ($preset['count'] > 0 && $this->temporaryDBCount($request->input('mode'), $request->input('id'), $request->input('block'), $request->input('block_id')) >= $preset['count'])
                return false;
        }

        $file = $request->file('file');
        if ($file->getError()) {
            return false;
        }

        // проверка на расширение
        if (!in_array($request->file('file')->extension(), $preset['extensions']))
            return false;

        // проверка на размер
        if ($file->getSize() > $preset['filesize'] * 1024 * 1024)
            return false;


        return true;
    }

    /**
     * Перенести файлы из временного хранилища в постоянное
     *
     * @param \Illuminate\Http\Request $request
     * @return bool|string
     */
    public function saveTemporary(\Illuminate\Http\Request $request, $arParams = [])
    {
        $block = $request->input('block');
        $mode = $request->input('mode');
        $block_id = $request->input('block_id');

        if (!empty($arParams['mode']) && $arParams['mode'] == 'trash') {
            $mode = 'trash';
            $preset = File::PRESETS[$mode][$block] ?? [];
            $block_id = time();
        } else {
            switch ($request->input('mode')) {
                case 'evaluation':
                    $preset = File::PRESETS[Evaluation::class][$block] ?? [];
                    break;
                default:
                    return false;
            }
        }

        if (empty($preset))
            return false;


        $file = $request->file('file');
        $path = $mode . '/' . $block_id . '/' . $block;
        $uploaded = Storage::disk('temporary_flush')->put($path, $file);
        if (!empty($uploaded))
            $this->temporaryDBStore($mode, $request->input('id'), $block, $uploaded, $file, $block_id);

        return $uploaded;
    }

    /**
     * Сохранить мусорный файл
     *
     * @param \Illuminate\Http\Request $request
     * @return bool|string
     */
    public function saveTrash(\Illuminate\Http\Request $request)
    {
        switch ($request->input('mode')) {
            case 'evaluation':
                $preset = File::PRESETS[Evaluation::class][$request->input('block')] ?? [];
                if (empty($preset))
                    return false;
                break;
            default:
                return false;
        }

        $file = $request->file('file');
        $path = $request->input('mode') . '/' . $request->input('id') . '/' . $request->input('block');
        $uploaded = Storage::disk('temporary_flush')->put($path, $file);
        if (!empty($uploaded))
            $this->temporaryDBStore($request->input('mode'), $request->input('id'), $request->input('block'), $uploaded, $file, $request->input('block_id'));

        return $uploaded;
    }

    /**
     * Сохранить файл во временное хранилище
     *
     * @param $mode
     * @param $id
     * @param $block
     * @param $path
     * @param $file
     * @param $block_id
     * @return void
     */
    private function temporaryDBStore($mode, $id, $block, $path, $file, $block_id = null)
    {
        DB::table('files_temporary')->insert([
            'mode' => $mode,
            'target_id' => $id,
            'block' => $block,
            'block_id' => $block_id,
            'path' => $path,
            'realname' => $file->getClientOriginalName(),
            'extension' => $file->extension(),
            'filesize' => $file->getSize(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Получить кол-во файлов во временном хранилище
     *
     * @param $mode
     * @param $id
     * @param $block
     * @param $block_id
     * @return int
     */
    private function temporaryDBCount($mode, $id, $block, $block_id = null)
    {
        return DB::table('files_temporary')->where([
            'mode' => $mode,
            'target_id' => $id,
            'block' => $block,
            'block_id' => $block_id
        ])->count();
    }

    /**
     * Получить файлы из временного хранилища
     *
     * @param $mode
     * @param $id
     * @param $block
     * @param $block_id
     * @return \Illuminate\Support\Collection
     */
    public static function temporaryDBFiles($mode, $id, $block = null, $block_id = null)
    {

        $builder = DB::table('files_temporary')->where([
            'mode' => $mode,
            'target_id' => $id
        ]);
        if (!empty($block))
            $builder->where('block', $block);

        if (!empty($block_id))
            $builder->where('block_id', $block_id);

        return $builder->get();
    }

    /**
     * Удалить временный файл
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    public function deleteTemporary(\Illuminate\Http\Request $request)
    {
        switch ($request->mode) {
            case 'evaluation':
                $row = Evaluation::find($request->id);
                if (empty($row)) abort(404);
                break;
        }

        if ($request->kind == 'exists') {
            $file = $row->files()->where('id', $request->file_id)->first();
            Storage::disk($file->disk)->delete($file->path);
            $file->delete();
            return true;
        } else {
            $file = DB::table('files_temporary')->where([
                'id' => $request->file_id,
                'mode' => $request->mode,
                'target_id' => $request->id,
                'block' => $request->block,
                'block_id' => $request->block_id,
            ])->first();

            if (empty($file))
                return false;

            Storage::disk('temporary_flush')->delete($file->path);
            DB::table('files_temporary')->delete($request->file_id);

            return true;
        }
    }

    /**
     * Скопировать из временного хранилища
     *
     * @param $files
     * @param $model
     * @return bool
     */
    public function copyFromTemporary($files, $model)
    {
        foreach ($files as $file) {
            $path = Storage::disk('temporary_flush')->path($file->path);
            $path_relative = Str::beforeLast(Str::replace(Storage::disk('temporary_flush')->getConfig()['root'], '', $path), '/');

            $path_save_relative = $path_relative . '/' . $file->id . '_' . $file->realname;
            $path_save = Storage::disk('massive')->path($path_save_relative);
            $source = Storage::disk('temporary_flush')->get($file->path);

            if (Storage::disk('massive')->put($path_save_relative, $source)) {
                $db_file = new File();
                $db_file->disk = 'massive';
                $db_file->filename = basename($path_save);
                $db_file->path = $path_save_relative;
                $db_file->extension = $file->extension;
                $db_file->size = $file->filesize;
                $db_file->target_block = $file->block;
                $db_file->target_block_id = $file->block_id;


                $model->files()->save($db_file);
                Storage::disk('temporary_flush')->delete($file->path);
                $a = DB::table('files_temporary')->delete($file->id);
            } else {
                dd("COPY ERROR");
            }
        }

        return true;
    }


    public static function generatePdfFromFile(string $path, string $disk = null)
    {
        if(empty($disk))
            $disk = 'temporary_flush';

        if (!Storage::disk($disk)->exists($path))
            abort(404);

        $extension = Str::afterLast($path, '.');
        switch ($extension) {
            case "pdf":
                $path = Str::replace(env('APP_URL'), '', Storage::disk($disk)->url($path));
                break;
            case "xls":
            case "xlsx":
                $path_source = Storage::disk($disk)->path($path);
                $spreadsheet = IOFactory::load($path_source);

                $html = '<table>';
                foreach ($spreadsheet->getActiveSheet()->toArray() as $row) {
                    $html .= '<tr><td>' . implode('</td><td>', $row) . '</td></tr>';
                }
                $html .= '</table>';

                $filename = Str::uuid() . '.pdf';
                $path_to_save = Storage::disk('temporary_flush')->path($filename);

                // Создаем объект mPDF и сохраняем результат в файл PDF
                $mpdf = new Mpdf();
                $mpdf->WriteHTML($html);
                $mpdf->Output($path_to_save, 'F');

                $path = Str::replace(env('APP_URL'), '', Storage::disk('temporary_flush')->url($filename));
                break;
            case "docx":
                $path_source = Storage::disk($disk)->path($path);
                $filename = Str::uuid() . '.html';
                $path_to_temp = Storage::disk('temporary_flush')->path($filename);

                // загрузим файл
                $phpWord = \PhpOffice\PhpWord\IOFactory::createReader('Word2007')->load($path_source);
                $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
                $objWriter->save($path_to_temp);
                $html = Storage::disk('temporary_flush')->get($filename);

                $filename = Str::uuid() . '.pdf';
                $path_to_save = Storage::disk('temporary_flush')->path($filename);

                // Создаем объект mPDF и сохраняем результат в файл PDF
                $mpdf = new Mpdf();
                $mpdf->WriteHTML($html);
                $mpdf->Output($path_to_save, 'F');

                $path = Str::replace(env('APP_URL'), '', Storage::disk('temporary_flush')->url($filename));

                break;
            case "doc":
                $path_source = Storage::disk($disk)->url($path);
                $filename = Str::uuid() . '.html';
                $path_to_temp = Storage::disk('temporary_flush')->path($filename);

                // загрузим файл
                $phpWord = \PhpOffice\PhpWord\IOFactory::createReader('MsDoc')->load($path_source);
                $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
                $objWriter->save($path_to_temp);
                $html = Storage::disk('temporary_flush')->get($filename);


                // Создаем объект mPDF и сохраняем результат в файл PDF
                $filename = Str::uuid() . '.pdf';
                $path_to_save = Storage::disk('temporary_flush')->path($filename);

                $mpdf = new Mpdf();
                $mpdf->WriteHTML($html);
                $mpdf->Output($path_to_save, 'F');

                $path = Str::replace(env('APP_URL'), '', Storage::disk('temporary_flush')->url($filename));

                break;
            default:
                abort(404);
        }

        return json_encode(['status' => 'success', 'path' => $path]);
    }
}
