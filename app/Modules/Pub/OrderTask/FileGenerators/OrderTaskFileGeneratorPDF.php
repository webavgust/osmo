<?php

namespace App\Modules\Pub\OrderTask\FileGenerators;

use App\Facades\Tools;
use App\Modules\Pub\OrderTask\FileGenerators\Interfaces\OrderTaskFileGeneratorInterface;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use Illuminate\Support\Facades\Storage;
use PDF;

class OrderTaskFileGeneratorPDF implements OrderTaskFileGeneratorInterface
{
    public function generate(OrderTask $order_task): string
    {
//        $pdf = PDF::loadView('templates.pdf.task_order', ['order_task' => $order_task]);

        $filename = 'temp/agree.pdf';
        $pdf = PDF::loadView('templates.pdf.task_order', ['order_task' => $order_task], [],
        [
            'format' => 'A4-L',
            'orientation' => 'L'
        ]);
//        $pdf->save(storage_path('app/public/' . $filename));
        $pdf->save(Storage::disk('massive')->path($filename));
        return $filename;
    }
}
