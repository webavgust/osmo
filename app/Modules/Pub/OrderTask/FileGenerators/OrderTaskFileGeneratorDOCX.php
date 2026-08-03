<?php

namespace App\Modules\Pub\OrderTask\FileGenerators;

use App\Facades\Tools;
use App\Modules\Pub\OrderTask\FileGenerators\Interfaces\OrderTaskFileGeneratorInterface;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use Illuminate\Support\Facades\Storage;

class OrderTaskFileGeneratorDOCX implements OrderTaskFileGeneratorInterface
{
    public function generate(OrderTask $order_task): string
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $fs = [
            'title' => (new \PhpOffice\PhpWord\Style\Font())->setBold(true)->setName('Calibri')->setSize(15),
            'bold' => (new \PhpOffice\PhpWord\Style\Font())->setBold(true)->setName('Calibri')->setSize(11),
            'regular' => (new \PhpOffice\PhpWord\Style\Font())->setName('Calibri')->setSize(11),
            'bold_tbl' => (new \PhpOffice\PhpWord\Style\Font())->setBold(true)->setName('Calibri')->setSize(10),
            'regular_tbl' => (new \PhpOffice\PhpWord\Style\Font())->setName('Calibri')->setSize(10),
            'title_tbl' => (new \PhpOffice\PhpWord\Style\Font())->setName('Calibri')->setSize(12)->setBold(true),
        ];



        $section = $phpWord->addSection((['marginLeft' => 200, 'marginRight' => 200, 'marginTop' => 600, 'marginBottom' => 600]));
        $sectionStyle = $section->getStyle();
        $sectionStyle->setOrientation($sectionStyle::ORIENTATION_LANDSCAPE);

        // Заголовок
        $section->addText('Приложение №' . $order_task->number?->number ?? '?', $fs['bold'], [ 'align' => 'right' ]);
        $section->addText('от ' . _date($order_task->created_at) . 'г.', $fs['bold'], [ 'align' => 'right' ]);

        if(!empty($order_task->order->id))
        {
            $section->addText('к Договору №' . $order_task->order->contract_id, $fs['bold'], [ 'align' => 'right']);
            $section->addText('от ' . _date($order_task->order->contract_conclusion, ['type' => 'r_full']), $fs['bold'], [ 'align' => 'right']);
        }

        $section->addText('Техническое задание на выполнение лабораторных исследований', $fs['title'], [ 'align' => 'center', 'marginTop' => 2000, 'margin' => 2000]);
        $section->addTextBreak(1);
        // Основная таблица

        $table = $section->addTable([
            'marginTop' => 2000,
            'borderColor' => '000000',
            'borderSize'  => 1,
            'cellPadding'  => 10,
            'unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT,
            'width' => 100 * 50,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
        ]);

        /*
         *  ОБЪЕКТ
         */
        $table->addRow(400);
        $table->addCell(3, ['valign' => 'center'])->addText("№\r\nп/п", $fs['bold'], ['align' => 'center']);
        $table->addCell(17, ['valign' => 'center'])->addText("Точный адрес и место точки отбора/измерения;<br/>Координаты метрические/географические;", $fs['bold'], ['align' => 'center']);
        $table->addCell(24, ['valign' => 'center'])->addText("Наименование точки<br/>отбора/измерений", $fs['bold'], ['align' => 'center']);
        $table->addCell(28, ['valign' => 'center'])->addText("Наименование измеряемого<br/>показателя", $fs['bold'], ['align' => 'center']);
        $table->addCell(7, ['valign' => 'center'])->addText("Стоимость<br/>измерения<br/>(руб.)", $fs['bold'], ['align' => 'center']);
        $table->addCell(15, ['valign' => 'center'])->addText("Примечание", $fs['bold'], ['align' => 'center']);


        foreach($order_task->objects as $object_i => $object)
        {
            $table->addRow(400);
            $table->addCell(null, ['gridSpan' => 6, 'valign' => 'center'])->addText(($object_i + 1) . ". {$object->name}", $fs['title_tbl'], [ 'align' => 'center', 'space' => ['before' => 100, 'after' => 100]]);

            foreach($object->addresses as $a_i => $address)
            {
                foreach($address->points as $a_p => $point)
                {
                    foreach($point->measures as $a_m => $measure)
                    {
                        $table->addRow();

                        // первая колонка
                        if($a_m == 0 && $a_p == 0) {
                            $table->addCell(5, ['vMerge' => 'restart'])->addText($a_i + 1, $fs['regular_tbl'], ['align' => 'center', 'space' => ['before' => 40, 'after' => 40], 'indentation' => ['left' => 70, 'right' =>70]]);
                            $table->addCell(15, ['vMerge' => 'restart'])->addText($address->address, $fs['regular_tbl'], ['space' => ['before' => 40, 'after' => 40], 'indentation' => ['left' => 70, 'right' =>70]]);
                        } else {
                            $table->addCell(null, ['vMerge' => 'continue']);
                            $table->addCell(null, ['vMerge' => 'continue']);
                        }

                        // вторая колонка
                        if($a_m == 0) {
                            $table->addCell(20, ['vMerge' => 'restart'])->addText($point->name, $fs['regular_tbl'], ['space' => ['before' => 40, 'after' => 40], 'indentation' => ['left' => 70, 'right' =>70]]);
                        } else {
                            $table->addCell(null, ['vMerge' => 'continue']);
                        }

                        $table->addCell(20)->addText($measure->measure->name, $fs['regular_tbl'], ['space' => ['before' => 40, 'after' => 40], 'indentation' => ['left' => 70, 'right' =>70]]);
                        $table->addCell(20)->addText(Tools::cost_normalize($measure->cost_total), $fs['regular_tbl'], ['align' => 'center', 'space' => ['before' => 40, 'after' => 40], 'indentation' => ['left' => 70, 'right' =>70]]);

                        // последняя колонка
                        if($a_m == 0) {
                            $table->addCell(20, ['vMerge' => 'restart'])->addText($measure->comment, $fs['regular_tbl'], ['align' => 'center', 'space' => ['before' => 40, 'after' => 40], 'indentation' => ['left' => 70, 'right' =>70]]);
                        } else {
                            $table->addCell(null, ['vMerge' => 'continue']);
                        }
                    }
                }
            }



            foreach($object->services as $s_i => $service)
            {
                $table->addRow();
                $table->addCell(5, ['vMerge' => 'restart'])->addText(count($object->addresses) + $s_i + 1, $fs['regular_tbl'], ['align' => 'center', 'space' => ['before' => 40, 'after' => 40], 'indentation' => ['left' => 70, 'right' =>70]]);
                $table->addCell(15, ['vMerge' => 'restart'])->addText($service->name, $fs['regular_tbl'], ['space' => ['before' => 40, 'after' => 40], 'indentation' => ['left' => 70, 'right' =>70]]);
                $table->addCell();
                $table->addCell();
                $table->addCell(20)->addText(Tools::cost_normalize($service->pivot->cost_total), $fs['regular_tbl'], ['align' => 'center', 'space' => ['before' => 40, 'after' => 40], 'indentation' => ['left' => 70, 'right' =>70]]);
                $table->addCell();
            }
        }

        $table->addRow();
        $table->addCell(null, ['gridSpan' => 4])->addText('Итоговая стоимость работ (руб.)', $fs['bold_tbl'], ['space' => ['before' => 40, 'after' => 40], 'indentation' => ['left' => 70, 'right' =>70]]);
        $cell = $table->addCell(null, ['gridSpan' => 2]);
        $cell->addText( Tools::cost_full_string($order_task->cost_total), $fs['bold_tbl'], ['align' => 'right', 'space' => ['before' => 40, 'after' => 40], 'indentation' => ['left' => 70, 'right' =>70]]);
        $cell->addText( 'НДС не облагается согласно п. 1 ст. 346.12 гл. 26.2 Налогового Кодекса Российской Федерации.', $fs['bold_tbl'], ['align' => 'right', 'space' => ['before' => 40, 'after' => 40], 'indentation' => ['left' => 70, 'right' =>70]]);

        $table->addRow();
        $cell = $table->addCell(null, ['gridSpan' => 6]);
        $cell->addText('Официальные контактные данные заказчика:', $fs['bold_tbl'], ['space' => ['after' => 200]]);
        $cell->addText('Юридический адрес: ...' , $fs['regular_tbl']);
        $cell->addText('Почтовый адрес: ...' , $fs['regular_tbl']);
        $cell->addText('Электронная почта: ...' , $fs['regular_tbl']);
        $cell->addText('Телефон: ...' , $fs['regular_tbl']);
        ;

        $table->addRow();
        $cell = $table->addCell(null, ['gridSpan' => 6]);
        $cell->addText('Уполномоченный представитель заказчика: ', $fs['bold_tbl']);
        $cell->addText('...' , $fs['regular_tbl']);
        ;

        $table->addRow();
        $cell = $table->addCell(null, ['gridSpan' => 6]);
        $cell->addText('Порядок и сроки (календарный план) выполнения работ*', $fs['bold_tbl'], ['align' => 'center', 'space' => ['before' => 80, 'after' => 80]]);

        $table->addRow();
        $table->addCell(null, ['gridSpan' => 4])->addText('Вид работ', $fs['bold_tbl'], ['align' => 'center', 'space' => ['before' => 80, 'after' => 200]]);
        $table->addCell(null, ['gridSpan' => 2])->addText('Срок выполнения (р/дней)', $fs['bold_tbl'], ['align' => 'center', 'space' => ['before' => 80, 'after' => 200]]);

        $table->addRow();
        $table->addCell(null)->addText('1', $fs['regular_tbl'], ['align' => 'center', 'space' => ['before' => 80, 'after' => 200]]);
        $table->addCell(null, ['gridSpan' => 3])->addText('Проведение отбора/приема проб', $fs['regular_tbl'], ['space' => ['before' => 80, 'after' => 200], 'indentation' => ['left' => 70, 'right' =>70]]);
        $table->addCell(null, ['gridSpan' => 2, 'valign' => 'center', 'vMerge' => 'restart'])->addText((!empty($order_task->order) ? $order_task->order->periods()->orderBy('id', 'desc')->first()->days ?? '?' : '?'), $fs['regular_tbl'], ['align' => 'center', 'space' => ['before' => 80, 'after' => 200]]);
        $table->addRow();
        $table->addCell(null)->addText('2', $fs['regular_tbl'], ['align' => 'center', 'space' => ['before' => 80, 'after' => 200]]);
        $table->addCell(null, ['gridSpan' => 3])->addText('Проведение анализа', $fs['regular_tbl'], ['space' => ['before' => 80, 'after' => 200], 'indentation' => ['left' => 70, 'right' =>70]]);
        $table->addCell(null, ['gridSpan' => 2, 'vMerge' => 'continue']);
        $table->addRow();
        $table->addCell(null)->addText('3', $fs['regular_tbl'], ['align' => 'center', 'space' => ['before' => 80, 'after' => 200]]);
        $table->addCell(null, ['gridSpan' => 3])->addText('Оформление протокола анализа', $fs['regular_tbl'], ['space' => ['before' => 80, 'after' => 200], 'indentation' => ['left' => 70, 'right' =>70]]);
        $table->addCell(null, ['gridSpan' => 2, 'vMerge' => 'continue']);
        $table->addRow();
        $cell = $table->addCell(null, ['gridSpan' => 6]);
        $cell->addText('Результаты работ:', $fs['bold_tbl'], ['align' => 'center', 'space' => ['before' => 80, 'after' => 200]]);
        $cell->addText('1. Акт (-ы) отбора/приема проб;', $fs['regular_tbl']);
        $cell->addText('2. Протоколы лабораторных анализов.', $fs['regular_tbl']);

        $section->addText('* - все работы по договору выполняются последовательно, в соответствии с календарным планом.');
        $section->addTextBreak(2);

        $table = $section->addTable([
            'marginTop' => 2000,
            'borderSize'  => 0,
            'borderColor' => 'ffffff',
            'cellPadding'  => 10,
            'unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT,
            'width' => 100 * 50,
            'alignment'   => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
            'layout'      => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
        ]);
        $table->addRow();
        $cell = $table->addCell(50);
        $cell->addText('Директор', $fs['bold']);
        $cell->addText(!empty($order_task->order) ? $order_task->order->customer_name : '', $fs['bold']);
        $cell->addTextBreak(1);
        $cell->addText('____________________ ...', $fs['regular']);
        $cell->addText('м.п.', $fs['regular_tbl']);

        $cell = $table->addCell(50);
        $cell->addText('Генеральный директор', $fs['bold']);
        $cell->addText('ООО ХАЛ «РПН-Сфера»', $fs['bold']);
        $cell->addTextBreak(1);
        $cell->addText('____________________ Ю.А. Кортунов', $fs['regular']);
        $cell->addText('м.п.', $fs['regular_tbl']);

//        header("Content-Description: File Transfer");
//        header('Content-Disposition: attachment; filename="test.docx"');
//        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
//        header('Content-Transfer-Encoding: binary');
//        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
//        header('Expires: 0');

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $filename = 'temp/agree.docx';

        $res = $objWriter->save(Storage::disk('massive')->path($filename));

        return $filename;
    }
}
