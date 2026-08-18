<?php

namespace App\Modules\Pub\Report\Services;

use App\Modules\Pub\LicenseKey\Models\LicenseKey;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ChinaReportService
{



    public static function getData__China1(array $keysID = null)
    {

        // отберём нужные договоры
        if(empty($keysID)) {
            $keys = LicenseKey::where('active_to', '>', now())->get();
        } else {
            $keys = LicenseKey::where('active_to', '>', now())->whereIn('id', $keysID)->get();
        }

        $i = 0;
        $arAmount = [];

        foreach ($keys->sortBy('name') as $key) {

            $json = json_decode($key->comment, 1);
            if(is_array($json)) {
                $json_keys = array_keys($json);
                $json_values = array_values($json);
            }

            for($jj = 0; $jj < (is_array($json) ? max(1, count($json)) : 1); $jj++) {
                if(empty($arAmount[$key->id])) {
                    if(!empty($key->specification?->contract?->proposal)) {
                        $proposal = ProposalRepository::getLast($key->specification?->contract?->proposal);
                        $arAmount[$key->id] = ['sum' => $proposal->variants->min('cost_total'), 'currency' => $proposal->currency_slug];
                    } else {
                        $arAmount[$key->id] = null;
                    }
                }

                $grid[$i] = []; // строка
                $grid[$i][0] = ['rowspan' => 1, 'cell' => $key->company->name, 'id' => $key->company->id, 'system' => $key->id];
                $grid[$i][1] = ['rowspan' => 1, 'cell' => $key->company?->country?->name_en ?? '', 'system' => $key->id];
                $grid[$i][2] = ['rowspan' => 1, 'cell' => $key->company->partner->name, 'id' => $key->company->partner->id, 'system' => $key->id];

                // определим КП


                $grid[$i][3] = ['rowspan' => 1,
                    'cell' => $arAmount[$key->id]['sum'] ?? 0,
                    'class' => [
                        'unactive' => !$key->active,
                        'expired' => $key->active && $key->active_to->lessThan(now()),
                        'warning' => $key->active && $key->active_to->greaterThan(now()) && $key->active_to->subMonths(3)->lessThan(now()),
                    ],
                    'currency' => $arAmount[$key->id]['currency'] ?? null,
                    'system' => $key->id
                ];
                $grid[$i][4] = ['rowspan' => 1, 'cell' => $key->active_from->format("d.m.Y"),
                    'class' => [
                        'unactive' => !$key->active,
                        'expired' => $key->active && $key->active_to->lessThan(now()),
                        'warning' => $key->active && $key->active_to->greaterThan(now()) && $key->active_to->subMonths(3)->lessThan(now()),
                    ], 'system' => $key->id];
                $grid[$i][5] = ['rowspan' => 1, 'cell' => $key->active_to->format("d.m.Y"),
                    'class' => [
                        'unactive' => !$key->active,
                        'expired' => $key->active && $key->active_to->lessThan(now()),
                        'warning' => $key->active && $key->active_to->greaterThan(now()) && $key->active_to->subMonths(3)->lessThan(now()),
                    ], 'system' => $key->id];
                $grid[$i][6] = ['rowspan' => 1, 'cell' => '??',
                    'class' => [
                        'unactive' => !$key->active,
                        'expired' => $key->active && $key->active_to->lessThan(now()),
                        'warning' => $key->active && $key->active_to->greaterThan(now()) && $key->active_to->subMonths(3)->lessThan(now()),
                    ], 'system' => $key->id];
                $grid[$i][7] = ['rowspan' => 1, 'cell' => $key->count,
                    'class' => [
                        'unactive' => !$key->active,
                        'expired' => $key->active && $key->active_to->lessThan(now()),
                        'warning' => $key->active && $key->active_to->greaterThan(now()) && $key->active_to->subMonths(3)->lessThan(now()),
                    ], 'system' => $key->id];

                if(is_array($json)) {
                    $grid[$i][8] = ['rowspan' => 1, 'cell' => $json_keys[$jj] ?? '',
                        'class' => [
                            'unactive' => !$key->active,
                            'expired' => $key->active && $key->active_to->lessThan(now()),
                            'warning' => $key->active && $key->active_to->greaterThan(now()) && $key->active_to->subMonths(3)->lessThan(now()),
                            'text-wrap',
                            'fs-8',
                        ], 'system' => $key->id . '-' . $jj];
                    $grid[$i][9] = ['rowspan' => 1, 'cell' => $json_values[$jj] ?? '',
                        'class' => [
                            'unactive' => !$key->active,
                            'expired' => $key->active && $key->active_to->lessThan(now()),
                            'warning' => $key->active && $key->active_to->greaterThan(now()) && $key->active_to->subMonths(3)->lessThan(now()),
                            'text-wrap',
                            'fs-8',
                        ], 'system' => $key->id . '-' . $jj];
                } else {
                    $grid[$i][8] = ['rowspan' => 1, 'cell' => '', 'system' => $key->id . '-' . $jj];
                    $grid[$i][9] = ['rowspan' => 1, 'cell' => '', 'system' => $key->id . '-' . $jj];
                }

                $i++;
            }
        }

        // Sort the grid by 'cell' values of the first 9 columns
        usort($grid, function($a, $b) {
            for ($i = 0; $i <= 5; $i++) {
                if($i == 3) continue;
                $cellA = $a[$i]['cell'] ?? '';
                $cellB = $b[$i]['cell'] ?? '';
                if ($cellA < $cellB) return -1;
                if ($cellA > $cellB) return 1;
            }
            return 0; // If all first 9 cells are equal
        });

        // склеим колонки
        // Merge rows based on consecutive identical 'system' values
        if(1)
            for ($col = 0; $col <= 7; $col++) {
                $count = 1; // Count of consecutive rows
                for ($row = 1; $row < count($grid); $row++) {
                    if (!empty($grid[$row - 1][$col]['system']) && $grid[$row][$col]['system'] === $grid[$row - 1][$col]['system']) {
                        $count++;
                    } else {
                        if ($count > 1) {
                            // Set rowspan for the first occurrence
                            $grid[$row - $count][$col]['rowspan'] = $count;
                            // Set other occurrences to null
                            for ($j = 1; $j < $count; $j++) {
                                $grid[$row - $j][$col] = null;
                            }
                        }
                        $count = 1; // Reset count for new value
                    }
                }
                // Check for the last group
                if ($count > 1) {
                    $grid[$row - $count][$col]['rowspan'] = $count;
                    for ($j = 1; $j < $count; $j++) {
                        $grid[$row - $j][$col] = null;
                    }
                }
            }

        return $grid;
    }

    public static function generate_first(array $form)
    {
        $keysID = $form['key'];


        // styles
        $style__line_outline = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        $style__line_right = [
            'borders' => [
                'right' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        $style__cell_padding = [
            'alignment' => [
                'indent' => 0, // Отступ в "единицах" (примерно 1 мм)
            ],
        ];
        $style__cell_highlight = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                ]
            ]
        ];


        try {
            $data = static::getData__China1(keysID: $keysID);

            $template = $_SERVER["DOCUMENT_ROOT"] . '/../resources/stubs/excel/china_report_1.xlsx';

            // Проверка существования файла шаблона
            if (!file_exists($template)) {
                throw new Exception("Template file not found");
            }

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
            $spreadsheet = $reader->load($template);
            $sheet = $spreadsheet->getSheet(0);
            $sheet->setTitle("Запрос на расчёт поставки");

            $startRow = 3;
            $iteration = 0;
            $additionalPaddingPt = 2.25 * 2;
            $block_i = 0;

            foreach ($data as $i => $row) {
                foreach(['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'] as $cell_i => $letter) {
                    if (!empty($row[$cell_i]['cell'])) {

                        // обработаем сумму
                        if($cell_i == 3) {
//                        $grid[$i][3] = ['rowspan' => 1,
//                            'cell' => $arAmount[$key->id]['sum'] ?? 0,
//                            'class' => [
//                                'unactive' => !$key->active,
//                                'expired' => $key->active && $key->active_to->lessThan(now()),
//                                'warning' => $key->active && $key->active_to->greaterThan(now()) && $key->active_to->subMonths(3)->lessThan(now()),
//                            ],
//                            'currency' => $arAmount[$key->id]['currency'] ?? null,
//                            'system' => $key->id
//                        ];
//
//
                            $amount_rub = $row[$cell_i]['currency'] == 'RUB' ? $row[$cell_i]['cell'] : $row[$cell_i]['cell'] * $form['rates'][$row[$cell_i]['currency']];

                            $amount_target = $amount_rub / $form['rates'][$form['currency']];
                            $row[$cell_i]['cell'] = round($amount_target);
                        }


                        $sheet->setCellValue($letter . ($startRow + $iteration), $row[$cell_i]['cell']);
                    }



                    if (!empty($row[$cell_i]['rowspan']) && $row[$cell_i]['rowspan'] > 1) {
                        $startCell = $letter . ($startRow + $iteration);
                        $endCell = $letter . ($startRow + $iteration + $row[$cell_i]['rowspan'] - 1);
                        $sheet->mergeCells($startCell . ':' . $endCell);
                    }

                    if($cell_i <= 7) {
                        $sheet->getStyle($letter . ($startRow + $iteration))->applyFromArray($style__line_right);
                    }
                    $sheet->getStyle($letter . ($startRow + $iteration))->applyFromArray($style__cell_padding);
                }

                if(!empty($row[0])) {
                    $block_i++;
                    $rows = max(1, (!empty($row[0]['rowspan'])) ? $row[0]['rowspan'] : 0);

                    // нарисовать толстую границу по краям $diap
                    $diap = "B" . ($startRow + $iteration) . ":K" . ($startRow + $iteration + $rows - 1);
                    $sheet->getStyle($diap)->applyFromArray($style__line_outline);
                    if($block_i % 2 == 0 )
                        $sheet->getStyle($diap)->applyFromArray($style__cell_highlight);
                }


                // Получаем текущую высоту (с учетом автоподбора)
                $rowDimension = $sheet->getRowDimension($startRow + $iteration);
                $currentHeight = $rowDimension->getRowHeight();

                if ($currentHeight == -1) {
                    // Форсируем расчет автовысоты
                    $rowDimension->setRowHeight(-1);
                    $currentHeight = max(25, $rowDimension->getRowHeight()); // Не меньше 15pt
                }

                // Добавляем отступы (3px сверху + 3px снизу = 4.5pt)
                $rowDimension->setRowHeight($currentHeight + 4.5);

                $iteration++;
            }

            // Очищаем буфер вывода
            if (ob_get_length()) {
                ob_end_clean();
            }

            // Устанавливаем заголовки
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="china_report_' . date('Y-m-d H:i:s') . '.xlsx"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            // Логирование ошибки
            error_log("Excel generation error: " . $e->getMessage());

            // Отправка HTTP-кода ошибки
            http_response_code(500);
            die("Error generating Excel file: " . $e->getMessage());
        }
    }

}
