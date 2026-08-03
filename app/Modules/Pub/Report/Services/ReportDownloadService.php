<?php

namespace App\Modules\Pub\Report\Services;

use App\Modules\Bitrix\Dashboard\Services\DashboardDataService;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\View\Components\Bitrix\Dashboard\TblCountryStatusMonth;
use App\View\Components\Bitrix\Dashboard\TblCountryStatusQuarter;
use App\View\Components\Bitrix\Dashboard\TblIndustryName;
use App\View\Components\Bitrix\Dashboard\TblManagerStatusQuarter;
use App\View\Components\Bitrix\Dashboard\TblStatusCountryMonth;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportDownloadService
{
    static $styles = [
        'style__line_outline' => [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ],
        'style__line_right' => [
            'borders' => [
                'right' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ],
        'style__cell_padding' => [
            'alignment' => [
                'indent' => 0, // Отступ в "единицах" (примерно 1 мм)
            ],
        ],
        'style__cell_highlight' => [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                ]
            ]
        ],
        'style__total' => [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
            ],
            'font' => [
                'bold' => true,
                'size' => 13,
            ],
        ],
    ];

    public static function specs(string $mode = null)
    {
        $data = ReportSpecService::specs();

        switch ($mode) {
            case "pdf":
                $view = view('pub.report.specs', [
                    'companies' => CompanyRepository::getAll(),
                    'data' => $data,
                    'ignore_layout' => true, // если используете в шаблоне
                ]);
                $sections = $view->renderSections();          // получаем все секции
                $html = $sections['content'] ?? $view->render(); // берем 'content' или весь html как fallback
                $html .= $sections['styles'] ?? '';

                $htmlFinal = view('layouts.layout_pdf', [
                    'html' => $html
                ])->render();

                $tmp = storage_path('temp');
                $snappy = app('snappy.pdf'); // Knp\Snappy\Pdf
                $snappy->setBinary(config('snappy.pdf.binary'));
                $snappy->setTemporaryFolder($tmp);
                $snappy->setOption('enable-local-file-access', true);
                $snappy->setOption('orientation', 'Landscape');
                $snappy->setOption('margin-top', '10mm');
                $snappy->setOption('margin-right', '10mm');
                $snappy->setOption('margin-bottom', '10mm');
                $snappy->setOption('margin-left', '10mm');
                $snappy->setOption('encoding', 'utf-8');

                $out = $snappy->getOutputFromHtml($htmlFinal);

                $filename = 'temp/report-' . date('Ymd-His') . '.pdf';
                Storage::put($filename, $out);

                return $filename;
            default:

                try {

                    $template = $_SERVER["DOCUMENT_ROOT"] . '/../resources/stubs/excel/sales.xlsx';

                    // Проверка существования файла шаблона
                    if (!file_exists($template)) {
                        throw new Exception("Template file not found");
                    }

                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
                    $spreadsheet = $reader->load($template);
                    $sheet = $spreadsheet->getSheet(0);
                    $sheet->setTitle("Конфигурации. Сводная");

                    $startRow = 3;
                    $iteration = 0;
                    $block_i = 0;

                    foreach ($data as $i => $row) {
                        foreach (['B', 'C', 'D', 'E', 'F', 'G'] as $cell_i => $letter) {
                            if (!empty($row[$cell_i]['cell'])) {
                                $sheet->setCellValue($letter . ($startRow + $iteration), $row[$cell_i]['cell']);
                            }

                            if (!empty($row[$cell_i]['rowspan']) && $row[$cell_i]['rowspan'] > 1) {
                                $startCell = $letter . ($startRow + $iteration);
                                $endCell = $letter . ($startRow + $iteration + $row[$cell_i]['rowspan'] - 1);
                                $sheet->mergeCells($startCell . ':' . $endCell);
                            }

                            $sheet->getStyle($letter . ($startRow + $iteration))->applyFromArray(static::$styles['style__cell_padding']);
                        }

                        if (!empty($row[0])) {
                            $block_i++;
                            $rows = max(1, (!empty($row[0]['rowspan'])) ? $row[0]['rowspan'] : 0);

                            // нарисовать толстую границу по краям $diap
                            $diap = "B" . ($startRow + $iteration) . ":G" . ($startRow + $iteration + $rows - 1);
                            $sheet->getStyle($diap)->applyFromArray(static::$styles['style__line_outline']);
                            if ($block_i % 2 == 0)
                                $sheet->getStyle($diap)->applyFromArray(static::$styles['style__cell_highlight']);
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

                    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
                    $filename = 'temp/report-' . date('Ymd-His') . '.xlsx';
                    $writer->save(Storage::path($filename));

                    return $filename;

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


    public static function payments(string $mode = null)
    {
        switch ($mode) {
            case "pdf":
                $filter_service = new ReportPaymentFilterService();
                $filter = $filter_service->getFilter();

                $currency_slug = Cache::get('dashboard_currency') ?? "RUB";
                $currency = CurrencyRepository::get($currency_slug);

                $data = ReportService::getPaymentSummary($currency, $filter);

                $view = view('pub.report.payment', [
                    'companies' => CompanyRepository::getAll(),
                    'users' => UserRepository::getAllWithTrashed(),
                    'filter' => $filter,
                    'data' => $data,
                    'currency' => $currency,
                ]);
                $sections = $view->renderSections();          // получаем все секции
                $html = $sections['content'] ?? $view->render(); // берем 'content' или весь html как fallback
                $html .= $sections['styles'] ?? '';


                $htmlFinal = view('layouts.layout_pdf', [
                    'html' => $html
                ])->render();

                $tmp = storage_path('temp');
                $snappy = app('snappy.pdf'); // Knp\Snappy\Pdf
                $snappy->setBinary(config('snappy.pdf.binary'));
                $snappy->setTemporaryFolder($tmp);
                $snappy->setOption('enable-local-file-access', true);
                $snappy->setOption('page-size', 'A3'); // размер листа A3
                $snappy->setOption('orientation', 'Landscape');
                $snappy->setOption('margin-top', '10mm');
                $snappy->setOption('margin-right', '10mm');
                $snappy->setOption('margin-bottom', '10mm');
                $snappy->setOption('margin-left', '10mm');
                $snappy->setOption('encoding', 'utf-8');

                $out = $snappy->getOutputFromHtml($htmlFinal);

                $filename = 'temp/report-' . date('Ymd-His') . '.pdf';
                Storage::put($filename, $out);

                return $filename;

                break;
            default:
                try {
                    $filter_service = new ReportPaymentFilterService();
                    $filter = $filter_service->getFilter();


                    $currency_slug = Cache::get('dashboard_currency') ?? "RUB";
                    $currency = CurrencyRepository::get($currency_slug);

                    $data = ReportService::getPaymentSummary($currency, $filter);

                    $template = $_SERVER["DOCUMENT_ROOT"] . '/../resources/stubs/excel/payments.xlsx';

                    // Проверка существования файла шаблона
                    if (!file_exists($template)) {
                        throw new Exception("Template file not found");
                    }

                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
                    $spreadsheet = $reader->load($template);
                    $sheet = $spreadsheet->getSheet(0);
                    $sheet->setTitle("Конфигурации. Сводная");

                    $startRow = 4;
                    $iteration = 0;
                    $block_i = 0;

                    $future = $past = $plan = [1 => 0, 2 => 0];

                    foreach ($data as $i => $row) {
                        foreach ([
                                     'B' => 0,
                                     'C' => 1,
                                     'D' => 2,
                                     'E' => 3,
                                     'F' => 12,
                                     'G' => 4,
                                     'H' => 13,
                                     'I' => 7,
                                     'J' => 8,
                                     'K' => 9,
                                     'L' => 10,
                                     'M' => 11,
                                     'N' => 6,
                                 ] as $letter => $cell_i) {
                            if (!empty($row[$cell_i]['cell'])) {

                                // суммы
                                switch ($cell_i) {
                                    case 6:
                                        $past[$row[6]['org']->id] += $row[6]['cell'];
                                        break;
                                    case 7:
                                        $future[$row[7]['org']->id] += $row[7]['cell'];
                                        break;
                                    case 9:
                                        $plan[$row[9]['org']->id] += $row[9]['cell'];
                                        break;
                                }

                                switch ($letter) {
                                    case 'C':
                                        $sheet->setCellValue($letter . ($startRow + $iteration), ContractType::from($row[$cell_i]['cell'])->data()['label']);
                                        break;
                                    case 'F':
                                        if ($row[$cell_i]['cell'])
                                            $sheet->setCellValue($letter . ($startRow + $iteration), 'Да');
                                        break;
                                    case 'J':
                                    case 'L':
                                        $sheet->setCellValue($letter . ($startRow + $iteration), _date($row[$cell_i]['cell'], ['format' => 'Y-m-d']));
                                        break;
                                    default:
                                        $sheet->setCellValue($letter . ($startRow + $iteration), $row[$cell_i]['cell']);
                                }
                            }

                            if (!empty($row[$cell_i]['rowspan']) && $row[$cell_i]['rowspan'] > 1) {
                                $startCell = $letter . ($startRow + $iteration);
                                $endCell = $letter . ($startRow + $iteration + $row[$cell_i]['rowspan'] - 1);
                                $sheet->mergeCells($startCell . ':' . $endCell);
                            }

                            $sheet->getStyle($letter . ($startRow + $iteration))->applyFromArray(static::$styles['style__cell_padding']);
                        }

                        if (!empty($row[0])) {
                            $block_i++;
                            $rows = max(1, (!empty($row[0]['rowspan'])) ? $row[0]['rowspan'] : 0);

                            // нарисовать толстую границу по краям $diap
                            $diap = "B" . ($startRow + $iteration) . ":N" . ($startRow + $iteration + $rows - 1);
                            $sheet->getStyle($diap)->applyFromArray(static::$styles['style__line_outline']);
                            if ($block_i % 2 == 0)
                                $sheet->getStyle($diap)->applyFromArray(static::$styles['style__cell_highlight']);
                        }


                        // Получаем текущую высоту (с учетом автоподбора)
                        $rowDimension = $sheet->getRowDimension($startRow + $iteration);
                        $currentHeight = $rowDimension->getRowHeight();

                        if ($currentHeight == -1) {
                            // Форсируем расчет автовысоты
                            $rowDimension->setRowHeight(-1);
                            $currentHeight = max(30, $rowDimension->getRowHeight()); // Не меньше 15pt
                        }

                        // Добавляем отступы (3px сверху + 3px снизу = 4.5pt)
                        $rowDimension->setRowHeight($currentHeight + 4.5);

                        $iteration++;
                    }

                    $p = $startRow + $iteration;

                    // итого
                    $diap = "A{$p}:H{$p}";
                    $sheet->mergeCells($diap);
                    $sheet->setCellValue("A{$p}", 'OSMOVIEW');
                    $sheet->setCellValue("I{$p}", $future[1] ?? 0);
                    $sheet->setCellValue("K{$p}", $plan[1] ?? 0);
                    $sheet->setCellValue("N{$p}", $past[1] ?? 0);

                    $rowDimension = $sheet->getRowDimension($p);
                    $rowDimension->setRowHeight(30);
                    $sheet->getStyle("A{$p}:K{$p}")->applyFromArray(static::$styles['style__total']);
                    $sheet->getStyle("N{$p}")->applyFromArray(static::$styles['style__total']);

                    // итого 2
                    $iteration++;
                    $p = $startRow + $iteration;
                    $diap = "A{$p}:H{$p}";
                    $sheet->mergeCells($diap);
                    $sheet->setCellValue("A{$p}", 'NEUROLIS');
                    $sheet->setCellValue("I{$p}", $future[2] ?? 0);
                    $sheet->setCellValue("K{$p}", $plan[2] ?? 0);
                    $sheet->setCellValue("N{$p}", $past[2] ?? 0);

                    $rowDimension = $sheet->getRowDimension($p);
                    $rowDimension->setRowHeight(30);
                    $sheet->getStyle("A{$p}:K{$p}")->applyFromArray(static::$styles['style__total']);
                    $sheet->getStyle("N{$p}")->applyFromArray(static::$styles['style__total']);


                    $sheet->removeRow(4);
                    $sheet->removeRow(3);


                    // Очищаем буфер вывода
                    if (ob_get_length()) {
                        ob_end_clean();
                    }

                    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
                    $filename = 'temp/report-' . date('Ymd-His') . '.xlsx';
                    $writer->save(Storage::path($filename));

                    return $filename;

                } catch (Exception $e) {
                    // Логирование ошибки
                    error_log("Excel generation error: " . $e->getMessage());

                    // Отправка HTTP-кода ошибки
                    http_response_code(500);
                    die("Error generating Excel file: " . $e->getMessage());
                }

        }
    }

    public static function tbl_industry_name(string $mode = null)
    {
        switch ($mode) {
            case "pdf":
                $view = (new TblIndustryName())->render();
                $sections = $view->renderSections();          // получаем все секции
                $html = $sections['content'] ?? $view->render(); // берем 'content' или весь html как fallback
                $html .= $sections['styles'] ?? '';

                $htmlFinal = view('layouts.layout_pdf', [
                    'html' => $html
                ])->render();

                $tmp = storage_path('temp');
                $snappy = app('snappy.pdf'); // Knp\Snappy\Pdf
                $snappy->setBinary(config('snappy.pdf.binary'));
                $snappy->setTemporaryFolder($tmp);
                $snappy->setOption('enable-local-file-access', true);
                $snappy->setOption('page-size', 'A3'); // размер листа A3
                $snappy->setOption('orientation', 'Landscape');
                $snappy->setOption('margin-top', '10mm');
                $snappy->setOption('margin-right', '10mm');
                $snappy->setOption('margin-bottom', '10mm');
                $snappy->setOption('margin-left', '10mm');
                $snappy->setOption('encoding', 'utf-8');

                $out = $snappy->getOutputFromHtml($htmlFinal);

                $filename = 'temp/report-' . date('Ymd-His') . '.pdf';
                Storage::put($filename, $out);

                return $filename;

                break;
            default:
                $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];
                try {
                    $service = new DashboardDataService();
                    $data = $service->industry_name();

                    $template = $_SERVER["DOCUMENT_ROOT"] . '/../resources/stubs/excel/tbl_one_column.xlsx';

                    // Проверка существования файла шаблона
                    if (!file_exists($template)) {
                        throw new Exception("Template file not found");
                    }

                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
                    $spreadsheet = $reader->load($template);
                    $sheet = $spreadsheet->getSheet(0);
                    $sheet->setTitle("Cферы деятельности");

                    $rowStart = 2;
                    $columnStart = 2;
                    $iteration = 0;
                    $block_i = 0;
                    $column_total = collect();


                    // Отрисовываем заголовок
                    foreach ($data['columns'] as $column_i => $column) {
                        $letter = $letters[$columnStart + $column_i];
                        $sheet->setCellValue($letter . ($rowStart + $iteration), Str::replace(' ', "\r\n", $column));
                        $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            ],
                            'font' => [
                                'bold' => true,
                                'size' => 10,
                            ],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => [
                                    'argb' => 'FFDAE9F8', // Формат ARGB (Alpha + RGB)
                                ]
                            ]
                        ])->getAlignment()->setWrapText(true);

                    }
                    // Колонка итого
                    $letter = $letters[$columnStart + $column_i + 1];
                    $sheet->setCellValue($letter . ($rowStart + $iteration), "Итого");
                    $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                        ],
                        'font' => [
                            'bold' => true,
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                            ]
                        ]
                    ]);

                    foreach ($data['rows'] as $row) {
                        // если нет данных, пропускаем
                        if ($data['matrix'][$row]->isEmpty()) continue;

                        // смещаем строку
                        $iteration++;

                        // сумма по строке
                        $row_total = 0;

                        // выведем название индустрии
                        $sheet->setCellValue("B" . ($rowStart + $iteration), $row);
                        $sheet->getStyle("B" . ($rowStart + $iteration))->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                            ],
                            'font' => [
                                'bold' => true,
                                'size' => 11,
                            ],
                        ]);

                        // выведем колонки
                        foreach ($data['columns'] as $column_i => $column) {
                            // определим букву
                            $letter = $letters[$columnStart + $column_i];

                            // если в ячейке есть значение, то выведем её
                            if (!empty($data['matrix'][$row][$column])) {
                                $row_total += $data['matrix'][$row][$column]['amount'];
                                if (empty($column_total[$column])) $column_total[$column] = 0;

                                // суммируем
                                $column_total[$column] += $data['matrix'][$row][$column]['amount'];

                                // выводим значение
                                $sheet->setCellValue($letter . ($rowStart + $iteration), round($data['matrix'][$row][$column]['amount']));
                                $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                                    'alignment' => [
                                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                                    ],
                                    'font' => [
                                        'bold' => false,
                                        'size' => 10,
                                    ],
                                ])->getAlignment()->setWrapText(true);;
                            }
                        }

                        // выводим итого в строке
                        $letter = $letters[$columnStart + $column_i + 1];
                        $sheet->setCellValue($letter . ($rowStart + $iteration), round($row_total));
                        $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                            ],
                            'font' => [
                                'bold' => true,
                                'size' => 12,
                            ],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => [
                                    'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                                ]
                            ]
                        ]);
                    }

                    // выведем итого
                    $iteration++;


                    foreach ($data['columns'] as $column_i => $column) {
                        // определим букву
                        $letter = $letters[$columnStart + $column_i];

                        // выводим значение
                        $sheet->setCellValue($letter . ($rowStart + $iteration), round($column_total[$column]));
                        $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                            ],
                            'font' => [
                                'bold' => true,
                                'size' => 12,
                            ],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => [
                                    'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                                ]
                            ]
                        ])->getAlignment()->setWrapText(true);
                    }

                    // выведем общую сумму
                    $letter = $letters[$columnStart + $column_i + 1];
                    $sheet->setCellValue($letter . ($rowStart + $iteration), round($column_total->sum()));
                    $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                        ],
                        'font' => [
                            'bold' => true,
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => 'FFE0E0E0', // Формат ARGB (Alpha + RGB)
                            ]
                        ]
                    ])->getAlignment()->setWrapText(true);


                    // выставим формат
                    $sheet->getStyle("C3:" . $letter . ($rowStart + $iteration))
                        ->getNumberFormat()
                        ->setFormatCode('### ### ### ##0');

                    // Очищаем буфер вывода
                    if (ob_get_length()) {
                        ob_end_clean();
                    }

                    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
                    $filename = 'temp/tbl-industry-name-' . date('Ymd-His') . '.xlsx';
                    $writer->save(Storage::path($filename));

                    return $filename;

                } catch (Exception $e) {
                    // Логирование ошибки
                    error_log("Excel generation error: " . $e->getMessage());

                    // Отправка HTTP-кода ошибки
                    http_response_code(500);
                    die("Error generating Excel file: " . $e->getMessage());
                }

        }
    }

    public static function tbl_country_status__quarter(string $mode = null)
    {
        switch ($mode) {
            case "pdf":
                $view = (new TblCountryStatusQuarter())->render();
                $sections = $view->renderSections();          // получаем все секции
                $html = $sections['content'] ?? $view->render(); // берем 'content' или весь html как fallback
                $html .= $sections['styles'] ?? '';

                $htmlFinal = view('layouts.layout_pdf', [
                    'html' => $html
                ])->render();

                $tmp = storage_path('temp');
                $snappy = app('snappy.pdf'); // Knp\Snappy\Pdf
                $snappy->setBinary(config('snappy.pdf.binary'));
                $snappy->setTemporaryFolder($tmp);
                $snappy->setOption('enable-local-file-access', true);
                $snappy->setOption('page-size', 'A3'); // размер листа A3
                $snappy->setOption('orientation', 'Landscape');
                $snappy->setOption('margin-top', '10mm');
                $snappy->setOption('margin-right', '10mm');
                $snappy->setOption('margin-bottom', '10mm');
                $snappy->setOption('margin-left', '10mm');
                $snappy->setOption('encoding', 'utf-8');

                $out = $snappy->getOutputFromHtml($htmlFinal);

                $filename = 'temp/tbl_country_status__quarter-' . date('Ymd-His') . '.pdf';
                Storage::put($filename, $out);

                return $filename;

                break;
            default:
                $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];
                try {
                    $service = new DashboardDataService();
                    $data = $service->country_status_quarter();

                    $template = $_SERVER["DOCUMENT_ROOT"] . '/../resources/stubs/excel/tbl_two_columns.xlsx';

                    // Проверка существования файла шаблона
                    if (!file_exists($template)) {
                        throw new Exception("Template file not found");
                    }

                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
                    $spreadsheet = $reader->load($template);
                    $sheet = $spreadsheet->getSheet(0);
                    $sheet->setTitle("Страны и статусы поквартально");

                    $rowStart = 2;
                    $columnStart = 3;
                    $iteration = 0;
                    $block_i = 0;
                    $column_total = collect();


                    // Отрисовываем заголовок
                    $sheet->setCellValue("B" . ($rowStart + $iteration), Str::replace(' ', "\r\n", "Страна"));
                    $sheet->setCellValue("C" . ($rowStart + $iteration), Str::replace(' ', "\r\n", "Статус"));

                    foreach ($data['columns'] as $column_i => $column) {
                        $letter = $letters[$columnStart + $column_i];
                        $sheet->setCellValue($letter . ($rowStart + $iteration), Str::replace(' ', "\r\n", $column));
                    }

                    // Колонка итого
                    $letter = $letters[$columnStart + $column_i + 1];
                    $sheet->setCellValue($letter . ($rowStart + $iteration), "Итого");
                    $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                        ],
                        'font' => [
                            'bold' => true,
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                            ]
                        ]
                    ]);

                    $sheet->getStyle("B2:" . $letters[$columnStart + $column_i] . "2")->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        ],
                        'font' => [
                            'bold' => true,
                            'size' => 10,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => 'FFDAE9F8', // Формат ARGB (Alpha + RGB)
                            ]
                        ]
                    ])->getAlignment()->setWrapText(true);


                    // отрисовываем матрицу
                    foreach($data['matrix'] as $country => $line1) {
                        $subtotal = collect();
                        foreach($data['columns'] as $column)
                            $subtotal[$column] = 0;

                        $p = 0;
                        foreach($line1 as $status => $line2) {
                            $row_total = 0;
                            $iteration++;
                            $p++;

                            // если первая строка в блоке, то склеим и выведем
                            if($p == 1) {
                                $rowspan = count($line1) - 1;

                                if($rowspan >= 1) {
                                    $startCell = "B" . ($rowStart + $iteration);
                                    $endCell = "B" . ($rowStart + $iteration + $rowspan);
                                   $sheet->mergeCells($startCell . ':' . $endCell);
                                }
                                $sheet->setCellValue("B" . ($rowStart + $iteration), $country);

                                // нарисуем линию
                                $sheet->getStyle("B" . ($rowStart + $iteration + $rowspan) . ":" . $letters[count($data['columns']) + 3]. ($rowStart + $iteration + $rowspan))->applyFromArray([
                                    'borders' => [
                                        'bottom' => [
                                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                            'color' => ['argb' => 'FF000000'], // Черный цвет (можно изменить)
                                        ],
                                    ],
                                ]);
                            }

                            $sheet->setCellValue("C" . ($rowStart + $iteration), $status);







                            // выводим колонки
                            foreach($data['columns'] as $column_i => $column) {
                                // определим букву
                                $letter = $letters[$columnStart + $column_i];
                                if(empty($line2[$column])) continue;

                                // инициализируем переменные
                                $row_total += $line2[$column]['amount'];
                                if(empty($column_total[$column])) $column_total[$column] = 0;
                                $column_total[$column] += $line2[$column]['amount'];
                                $subtotal[$column] += $line2[$column]['amount'];

                                // выводим значение
                                $sheet->setCellValue($letter . ($rowStart + $iteration), round($line2[$column]['amount']));
                            }



                            // выводим итого в строке
                            $letter = $letters[$columnStart + $column_i + 1];
                            $sheet->setCellValue($letter . ($rowStart + $iteration), round($row_total));
                            $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                                'alignment' => [
                                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                                ],
                                'font' => [
                                    'bold' => true,
                                    'size' => 12,
                                ],
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => [
                                        'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                                    ]
                                ]
                            ]);

                        }

                    }

                    // выведем общую сумму
                    $iteration++;

                    foreach ($data['columns'] as $column_i => $column) {
                        // определим букву
                        $letter = $letters[$columnStart + $column_i];

                        // выводим значение
                        $sheet->setCellValue($letter . ($rowStart + $iteration), round($column_total[$column] ?? 0));
                        $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                            ],
                            'font' => [
                                'bold' => true,
                                'size' => 12,
                            ],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => [
                                    'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                                ]
                            ]
                        ])->getAlignment()->setWrapText(true);
                    }

                    $letter = $letters[$columnStart + $column_i + 1];
                    $sheet->setCellValue($letter . ($rowStart + $iteration), round($column_total->sum()));
                    $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                        ],
                        'font' => [
                            'bold' => true,
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => 'FFE0E0E0', // Формат ARGB (Alpha + RGB)
                            ]
                        ]
                    ])->getAlignment()->setWrapText(true);


                    // выставим формат
                    $sheet->getStyle("C3:" . $letter . ($rowStart + $iteration))
                        ->getNumberFormat()
                        ->setFormatCode('### ### ### ##0');


                    // Очищаем буфер вывода
                    if (ob_get_length()) {
                        ob_end_clean();
                    }

                    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
                    $filename = 'temp/tbl-country-status-quarter-' . date('Ymd-His') . '.xlsx';
                    $writer->save(Storage::path($filename));

                    return $filename;

                } catch (Exception $e) {
                    // Логирование ошибки
                    error_log("Excel generation error: " . $e->getMessage());

                    // Отправка HTTP-кода ошибки
                    http_response_code(500);
                    die("Error generating Excel file: " . $e->getMessage());
                }

        }
    }
    public static function tbl_manager_status__quarter(string $mode = null)
    {
        switch ($mode) {
            case "pdf":
                $view = (new TblManagerStatusQuarter())->render();
                $sections = $view->renderSections();          // получаем все секции
                $html = $sections['content'] ?? $view->render(); // берем 'content' или весь html как fallback
                $html .= $sections['styles'] ?? '';

                $htmlFinal = view('layouts.layout_pdf', [
                    'html' => $html
                ])->render();

                $tmp = storage_path('temp');
                $snappy = app('snappy.pdf'); // Knp\Snappy\Pdf
                $snappy->setBinary(config('snappy.pdf.binary'));
                $snappy->setTemporaryFolder($tmp);
                $snappy->setOption('enable-local-file-access', true);
                $snappy->setOption('page-size', 'A3'); // размер листа A3
                $snappy->setOption('orientation', 'Landscape');
                $snappy->setOption('margin-top', '10mm');
                $snappy->setOption('margin-right', '10mm');
                $snappy->setOption('margin-bottom', '10mm');
                $snappy->setOption('margin-left', '10mm');
                $snappy->setOption('encoding', 'utf-8');

                $out = $snappy->getOutputFromHtml($htmlFinal);

                $filename = 'temp/tbl_manager_status__quarter-' . date('Ymd-His') . '.pdf';
                Storage::put($filename, $out);

                return $filename;

                break;
            default:
                $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];
                try {
                    $service = new DashboardDataService();
                    $data = $service->manager_status_quarter();

                    $template = $_SERVER["DOCUMENT_ROOT"] . '/../resources/stubs/excel/tbl_two_columns.xlsx';

                    // Проверка существования файла шаблона
                    if (!file_exists($template)) {
                        throw new Exception("Template file not found");
                    }

                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
                    $spreadsheet = $reader->load($template);
                    $sheet = $spreadsheet->getSheet(0);
                    $sheet->setTitle("Отчёт");

                    $rowStart = 2;
                    $columnStart = 3;
                    $iteration = 0;
                    $block_i = 0;
                    $column_total = collect();


                    // Отрисовываем заголовок
                    $sheet->setCellValue("B" . ($rowStart + $iteration), Str::replace(' ', "\r\n", "Ответственный менеджер"));
                    $sheet->setCellValue("C" . ($rowStart + $iteration), Str::replace(' ', "\r\n", "Статус"));

                    foreach ($data['columns'] as $column_i => $column) {
                        $letter = $letters[$columnStart + $column_i];
                        $sheet->setCellValue($letter . ($rowStart + $iteration), Str::replace(' ', "\r\n", $column));
                    }

                    // Колонка итого
                    $letter = $letters[$columnStart + $column_i + 1];
                    $sheet->setCellValue($letter . ($rowStart + $iteration), "Итого");
                    $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                        ],
                        'font' => [
                            'bold' => true,
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                            ]
                        ]
                    ]);

                    $sheet->getStyle("B2:" . $letters[$columnStart + $column_i] . "2")->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        ],
                        'font' => [
                            'bold' => true,
                            'size' => 10,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => 'FFDAE9F8', // Формат ARGB (Alpha + RGB)
                            ]
                        ]
                    ])->getAlignment()->setWrapText(true);


                    // отрисовываем матрицу
                    foreach($data['matrix'] as $country => $line1) {
                        $subtotal = collect();
                        foreach($data['columns'] as $column)
                            $subtotal[$column] = 0;

                        $p = 0;
                        foreach($line1 as $status => $line2) {
                            $row_total = 0;
                            $iteration++;
                            $p++;

                            // если первая строка в блоке, то склеим и выведем
                            if($p == 1) {
                                $rowspan = count($line1) - 1;

                                if($rowspan >= 1) {
                                    $startCell = "B" . ($rowStart + $iteration);
                                    $endCell = "B" . ($rowStart + $iteration + $rowspan);
                                   $sheet->mergeCells($startCell . ':' . $endCell);
                                }
                                $sheet->setCellValue("B" . ($rowStart + $iteration), $country);

                                // нарисуем линию
                                $sheet->getStyle("B" . ($rowStart + $iteration + $rowspan) . ":" . $letters[count($data['columns']) + 3]. ($rowStart + $iteration + $rowspan))->applyFromArray([
                                    'borders' => [
                                        'bottom' => [
                                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                            'color' => ['argb' => 'FF000000'], // Черный цвет (можно изменить)
                                        ],
                                    ],
                                ]);
                            }

                            $sheet->setCellValue("C" . ($rowStart + $iteration), $status);







                            // выводим колонки
                            foreach($data['columns'] as $column_i => $column) {
                                // определим букву
                                $letter = $letters[$columnStart + $column_i];
                                if(empty($line2[$column])) continue;

                                // инициализируем переменные
                                $row_total += $line2[$column]['amount'];
                                if(empty($column_total[$column])) $column_total[$column] = 0;
                                $column_total[$column] += $line2[$column]['amount'];
                                $subtotal[$column] += $line2[$column]['amount'];

                                // выводим значение
                                $sheet->setCellValue($letter . ($rowStart + $iteration), round($line2[$column]['amount']));
                            }



                            // выводим итого в строке
                            $letter = $letters[$columnStart + $column_i + 1];
                            $sheet->setCellValue($letter . ($rowStart + $iteration), round($row_total));
                            $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                                'alignment' => [
                                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                                ],
                                'font' => [
                                    'bold' => true,
                                    'size' => 12,
                                ],
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => [
                                        'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                                    ]
                                ]
                            ]);

                        }

                    }

                    // выведем общую сумму
                    $iteration++;

                    foreach ($data['columns'] as $column_i => $column) {
                        // определим букву
                        $letter = $letters[$columnStart + $column_i];

                        // выводим значение
                        $sheet->setCellValue($letter . ($rowStart + $iteration), round($column_total[$column]));
                        $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                            ],
                            'font' => [
                                'bold' => true,
                                'size' => 12,
                            ],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => [
                                    'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                                ]
                            ]
                        ])->getAlignment()->setWrapText(true);
                    }

                    $letter = $letters[$columnStart + $column_i + 1];
                    $sheet->setCellValue($letter . ($rowStart + $iteration), round($column_total->sum()));
                    $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                        ],
                        'font' => [
                            'bold' => true,
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => 'FFE0E0E0', // Формат ARGB (Alpha + RGB)
                            ]
                        ]
                    ])->getAlignment()->setWrapText(true);


                    // выставим формат
                    $sheet->getStyle("C3:" . $letter . ($rowStart + $iteration))
                        ->getNumberFormat()
                        ->setFormatCode('### ### ### ##0');


                    // Очищаем буфер вывода
                    if (ob_get_length()) {
                        ob_end_clean();
                    }

                    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
                    $filename = 'temp/tbl-manager-status-quarter-' . date('Ymd-His') . '.xlsx';
                    $writer->save(Storage::path($filename));

                    return $filename;

                } catch (Exception $e) {
                    // Логирование ошибки
                    error_log("Excel generation error: " . $e->getMessage());

                    // Отправка HTTP-кода ошибки
                    http_response_code(500);
                    die("Error generating Excel file: " . $e->getMessage());
                }

        }


    }
    public static function tbl_country_status__month(string $mode = null)
    {
        switch ($mode) {
            case "pdf":
                $view = (new TblCountryStatusMonth())->render();
                $sections = $view->renderSections();          // получаем все секции
                $html = $sections['content'] ?? $view->render(); // берем 'content' или весь html как fallback
                $html .= $sections['styles'] ?? '';

                $htmlFinal = view('layouts.layout_pdf', [
                    'html' => $html
                ])->render();

                $tmp = storage_path('temp');
                $snappy = app('snappy.pdf'); // Knp\Snappy\Pdf
                $snappy->setBinary(config('snappy.pdf.binary'));
                $snappy->setTemporaryFolder($tmp);
                $snappy->setOption('enable-local-file-access', true);
                $snappy->setOption('page-size', 'A3'); // размер листа A3
                $snappy->setOption('orientation', 'Landscape');
                $snappy->setOption('margin-top', '10mm');
                $snappy->setOption('margin-right', '10mm');
                $snappy->setOption('margin-bottom', '10mm');
                $snappy->setOption('margin-left', '10mm');
                $snappy->setOption('encoding', 'utf-8');

                $out = $snappy->getOutputFromHtml($htmlFinal);

                $filename = 'temp/tbl_country_status__quarter-' . date('Ymd-His') . '.pdf';
                Storage::put($filename, $out);

                return $filename;

                break;
            default:
                $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];
                try {
                    $service = new DashboardDataService();
                    $data = $service->country_status_month();

                    $template = $_SERVER["DOCUMENT_ROOT"] . '/../resources/stubs/excel/tbl_two_columns.xlsx';

                    // Проверка существования файла шаблона
                    if (!file_exists($template)) {
                        throw new Exception("Template file not found");
                    }

                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
                    $spreadsheet = $reader->load($template);
                    $sheet = $spreadsheet->getSheet(0);
                    $sheet->setTitle("Страны и статусы помесячно");

                    $rowStart = 2;
                    $columnStart = 3;
                    $iteration = 0;
                    $block_i = 0;
                    $column_total = collect();


                    // Отрисовываем заголовок
                    $sheet->setCellValue("B" . ($rowStart + $iteration), Str::replace(' ', "\r\n", "Страна"));
                    $sheet->setCellValue("C" . ($rowStart + $iteration), Str::replace(' ', "\r\n", "Статус"));

                    foreach ($data['columns'] as $column_i => $column) {
                        $letter = $letters[$columnStart + $column_i];
                        $sheet->setCellValue($letter . ($rowStart + $iteration), Str::replace(' ', "\r\n", $column));
                    }

                    // Колонка итого
                    $letter = $letters[$columnStart + $column_i + 1];
                    $sheet->setCellValue($letter . ($rowStart + $iteration), "Итого");
                    $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                        ],
                        'font' => [
                            'bold' => true,
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                            ]
                        ]
                    ]);

                    $sheet->getStyle("B2:" . $letters[$columnStart + $column_i] . "2")->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        ],
                        'font' => [
                            'bold' => true,
                            'size' => 10,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => 'FFDAE9F8', // Формат ARGB (Alpha + RGB)
                            ]
                        ]
                    ])->getAlignment()->setWrapText(true);


                    // отрисовываем матрицу
                    foreach($data['matrix'] as $country => $line1) {
                        $subtotal = collect();
                        foreach($data['columns'] as $column)
                            $subtotal[$column] = 0;

                        $p = 0;
                        foreach($line1 as $status => $line2) {
                            $row_total = 0;
                            $iteration++;
                            $p++;

                            // если первая строка в блоке, то склеим и выведем
                            if($p == 1) {
                                $rowspan = count($line1) - 1;

                                if($rowspan >= 1) {
                                    $startCell = "B" . ($rowStart + $iteration);
                                    $endCell = "B" . ($rowStart + $iteration + $rowspan);
                                    $sheet->mergeCells($startCell . ':' . $endCell);
                                }
                                $sheet->setCellValue("B" . ($rowStart + $iteration), $country);

                                // нарисуем линию
                                $sheet->getStyle("B" . ($rowStart + $iteration + $rowspan) . ":" . $letters[count($data['columns']) + 3]. ($rowStart + $iteration + $rowspan))->applyFromArray([
                                    'borders' => [
                                        'bottom' => [
                                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                            'color' => ['argb' => 'FF000000'], // Черный цвет (можно изменить)
                                        ],
                                    ],
                                ]);
                            }

                            $sheet->setCellValue("C" . ($rowStart + $iteration), $status);


                            // выводим колонки
                            foreach($data['columns'] as $column_i => $column) {
                                // определим букву
                                $letter = $letters[$columnStart + $column_i];
                                if(empty($line2[$column])) continue;

                                // инициализируем переменные
                                $row_total += $line2[$column]['amount'];
                                if(empty($column_total[$column])) $column_total[$column] = 0;
                                $column_total[$column] += $line2[$column]['amount'];
                                $subtotal[$column] += $line2[$column]['amount'];

                                // выводим значение
                                $sheet->setCellValue($letter . ($rowStart + $iteration), round($line2[$column]['amount']));
                            }



                            // выводим итого в строке
                            $letter = $letters[$columnStart + $column_i + 1];
                            $sheet->setCellValue($letter . ($rowStart + $iteration), round($row_total));
                            $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                                'alignment' => [
                                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                                ],
                                'font' => [
                                    'bold' => true,
                                    'size' => 12,
                                ],
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => [
                                        'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                                    ]
                                ]
                            ]);

                        }

                    }

                    // выведем общую сумму
                    $iteration++;

                    foreach ($data['columns'] as $column_i => $column) {
                        // определим букву
                        $letter = $letters[$columnStart + $column_i];

                        // выводим значение
                        if(!empty($column_total[$column]))
                            $sheet->setCellValue($letter . ($rowStart + $iteration), round($column_total[$column]));
                        $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                            ],
                            'font' => [
                                'bold' => true,
                                'size' => 12,
                            ],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => [
                                    'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                                ]
                            ]
                        ])->getAlignment()->setWrapText(true);

                        // отрисуем полоски
                        if((int)$column % 3 == 0) {
                            $sheet->getStyle("{$letter}2:{$letter}" . ($rowStart + $iteration))->applyFromArray([
                                'borders' => [
                                    'right' => [
                                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                        'color' => ['argb' => 'FF000000'], // Черный цвет (можно изменить)
                                    ],
                                ],
                            ]);
                        }


                    }

                    $letter = $letters[$columnStart + $column_i + 1];
                    $sheet->setCellValue($letter . ($rowStart + $iteration), round($column_total->sum()));
                    $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                        ],
                        'font' => [
                            'bold' => true,
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => 'FFE0E0E0', // Формат ARGB (Alpha + RGB)
                            ]
                        ]
                    ])->getAlignment()->setWrapText(true);


                    // выставим формат
                    $sheet->getStyle("C3:" . $letter . ($rowStart + $iteration))
                        ->getNumberFormat()
                        ->setFormatCode('### ### ### ##0');


                    // Очищаем буфер вывода
                    if (ob_get_length()) {
                        ob_end_clean();
                    }

                    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
                    $filename = 'temp/tbl-country-status-month-' . date('Ymd-His') . '.xlsx';
                    $writer->save(Storage::path($filename));

                    return $filename;

                } catch (Exception $e) {
                    // Логирование ошибки
                    error_log("Excel generation error: " . $e->getMessage());

                    // Отправка HTTP-кода ошибки
                    http_response_code(500);
                    die("Error generating Excel file: " . $e->getMessage());
                }

        }
    }
    public static function tbl_status_country__month(string $mode = null)
    {
        switch ($mode) {
            case "pdf":
                $view = (new TblStatusCountryMonth())->render();
                $sections = $view->renderSections();          // получаем все секции
                $html = $sections['content'] ?? $view->render(); // берем 'content' или весь html как fallback
                $html .= $sections['styles'] ?? '';

                $htmlFinal = view('layouts.layout_pdf', [
                    'html' => $html
                ])->render();

                $tmp = storage_path('temp');
                $snappy = app('snappy.pdf'); // Knp\Snappy\Pdf
                $snappy->setBinary(config('snappy.pdf.binary'));
                $snappy->setTemporaryFolder($tmp);
                $snappy->setOption('enable-local-file-access', true);
                $snappy->setOption('page-size', 'A3'); // размер листа A3
                $snappy->setOption('orientation', 'Landscape');
                $snappy->setOption('margin-top', '10mm');
                $snappy->setOption('margin-right', '10mm');
                $snappy->setOption('margin-bottom', '10mm');
                $snappy->setOption('margin-left', '10mm');
                $snappy->setOption('encoding', 'utf-8');

                $out = $snappy->getOutputFromHtml($htmlFinal);

                $filename = 'temp/tbl_country_status__quarter-' . date('Ymd-His') . '.pdf';
                Storage::put($filename, $out);

                return $filename;

                break;
            default:
                $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];
                try {
                    $service = new DashboardDataService();
                    $data = $service->status_country_month();

                    $template = $_SERVER["DOCUMENT_ROOT"] . '/../resources/stubs/excel/tbl_two_columns.xlsx';

                    // Проверка существования файла шаблона
                    if (!file_exists($template)) {
                        throw new Exception("Template file not found");
                    }

                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
                    $spreadsheet = $reader->load($template);
                    $sheet = $spreadsheet->getSheet(0);
                    $sheet->setTitle("Статусы и страны помесячно");

                    $rowStart = 2;
                    $columnStart = 3;
                    $iteration = 0;
                    $block_i = 0;
                    $column_total = collect();


                    // Отрисовываем заголовок
                    $sheet->setCellValue("B" . ($rowStart + $iteration), Str::replace(' ', "\r\n", "Страна"));
                    $sheet->setCellValue("C" . ($rowStart + $iteration), Str::replace(' ', "\r\n", "Статус"));

                    foreach ($data['columns'] as $column_i => $column) {
                        $letter = $letters[$columnStart + $column_i];
                        $sheet->setCellValue($letter . ($rowStart + $iteration), Str::replace(' ', "\r\n", $column));
                    }

                    // Колонка итого
                    $letter = $letters[$columnStart + $column_i + 1];
                    $sheet->setCellValue($letter . ($rowStart + $iteration), "Итого");
                    $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                        ],
                        'font' => [
                            'bold' => true,
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                            ]
                        ]
                    ]);

                    $sheet->getStyle("B2:" . $letters[$columnStart + $column_i] . "2")->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        ],
                        'font' => [
                            'bold' => true,
                            'size' => 10,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => 'FFDAE9F8', // Формат ARGB (Alpha + RGB)
                            ]
                        ]
                    ])->getAlignment()->setWrapText(true);


                    // отрисовываем матрицу
                    foreach($data['matrix'] as $country => $line1) {
                        $subtotal = collect();
                        foreach($data['columns'] as $column)
                            $subtotal[$column] = 0;

                        $p = 0;
                        foreach($line1 as $status => $line2) {
                            $row_total = 0;
                            $iteration++;
                            $p++;

                            // если первая строка в блоке, то склеим и выведем
                            if($p == 1) {
                                $rowspan = count($line1) - 1;

                                if($rowspan >= 1) {
                                    $startCell = "B" . ($rowStart + $iteration);
                                    $endCell = "B" . ($rowStart + $iteration + $rowspan);
                                    $sheet->mergeCells($startCell . ':' . $endCell);
                                }
                                $sheet->setCellValue("B" . ($rowStart + $iteration), $country);

                                // нарисуем линию
                                $sheet->getStyle("B" . ($rowStart + $iteration + $rowspan) . ":" . $letters[count($data['columns']) + 3]. ($rowStart + $iteration + $rowspan))->applyFromArray([
                                    'borders' => [
                                        'bottom' => [
                                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                            'color' => ['argb' => 'FF000000'], // Черный цвет (можно изменить)
                                        ],
                                    ],
                                ]);
                            }

                            $sheet->setCellValue("C" . ($rowStart + $iteration), $status);


                            // выводим колонки
                            foreach($data['columns'] as $column_i => $column) {
                                // определим букву
                                $letter = $letters[$columnStart + $column_i];
                                if(empty($line2[$column])) continue;

                                // инициализируем переменные
                                $row_total += $line2[$column]['amount'];
                                if(empty($column_total[$column])) $column_total[$column] = 0;
                                $column_total[$column] += $line2[$column]['amount'];
                                $subtotal[$column] += $line2[$column]['amount'];

                                // выводим значение
                                $sheet->setCellValue($letter . ($rowStart + $iteration), round($line2[$column]['amount']));
                            }



                            // выводим итого в строке
                            $letter = $letters[$columnStart + $column_i + 1];
                            $sheet->setCellValue($letter . ($rowStart + $iteration), round($row_total));
                            $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                                'alignment' => [
                                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                                ],
                                'font' => [
                                    'bold' => true,
                                    'size' => 12,
                                ],
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => [
                                        'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                                    ]
                                ]
                            ]);

                        }

                    }

                    // выведем общую сумму
                    $iteration++;

                    foreach ($data['columns'] as $column_i => $column) {
                        // определим букву
                        $letter = $letters[$columnStart + $column_i];

                        // выводим значение
                        if(!empty($column_total[$column]))
                            $sheet->setCellValue($letter . ($rowStart + $iteration), round($column_total[$column]));
                        $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                            ],
                            'font' => [
                                'bold' => true,
                                'size' => 12,
                            ],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => [
                                    'argb' => 'FFEEEEEE', // Формат ARGB (Alpha + RGB)
                                ]
                            ]
                        ])->getAlignment()->setWrapText(true);

                        // отрисуем полоски
                        if((int)$column % 3 == 0) {
                            $sheet->getStyle("{$letter}2:{$letter}" . ($rowStart + $iteration))->applyFromArray([
                                'borders' => [
                                    'right' => [
                                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                        'color' => ['argb' => 'FF000000'], // Черный цвет (можно изменить)
                                    ],
                                ],
                            ]);
                        }


                    }

                    $letter = $letters[$columnStart + $column_i + 1];
                    $sheet->setCellValue($letter . ($rowStart + $iteration), round($column_total->sum()));
                    $sheet->getStyle($letter . ($rowStart + $iteration))->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                        ],
                        'font' => [
                            'bold' => true,
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => 'FFE0E0E0', // Формат ARGB (Alpha + RGB)
                            ]
                        ]
                    ])->getAlignment()->setWrapText(true);


                    // выставим формат
                    $sheet->getStyle("C3:" . $letter . ($rowStart + $iteration))
                        ->getNumberFormat()
                        ->setFormatCode('### ### ### ##0');


                    // Очищаем буфер вывода
                    if (ob_get_length()) {
                        ob_end_clean();
                    }

                    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
                    $filename = 'temp/tbl-status-country-month-' . date('Ymd-His') . '.xlsx';
                    $writer->save(Storage::path($filename));

                    return $filename;

                } catch (Exception $e) {
                    // Логирование ошибки
                    error_log("Excel generation error: " . $e->getMessage());

                    // Отправка HTTP-кода ошибки
                    http_response_code(500);
                    die("Error generating Excel file: " . $e->getMessage());
                }

        }
    }



}
