<?php

namespace App\Modules\Bitrix\CrmDeal\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Выгрузка реестра сделок в Excel (patch v22).
 *
 * Колонки выбираются в попапе, выгружается ровно то, что показывает
 * страница: тот же фильтр, тот же порядок строк. Пишем во временный файл,
 * а не в php://output — при потоковой отдаче в начало xlsx попадает всё,
 * что успело вывести приложение, и Excel отказывается открывать файл
 * (та же история, что в ProposalExcelService).
 */
class CrmDealExportService
{
    /**
     * Каталог колонок: код => подпись.
     *
     * Подписи полей Битрикса взяты из дашборда (DashboardDataService и его
     * компоненты) — чтобы в выгрузке и на экране одно и то же называлось
     * одинаково.
     */
    public const COLUMNS = [
        'id' => 'ID сделки',
        'title' => 'Название',
        'url' => 'Ссылка в Битрикс24',
        'stage_name' => 'Стадия',
        'stage_semantic' => 'Итог стадии',
        'manager' => 'Ответственный менеджер',
        'company_name' => 'Партнёр',
        'customer_name' => 'Конечный заказчик',
        'country' => 'Страна получения средств',
        'opportunity' => 'Сумма сделки',
        'currency_id' => 'Валюта',
        'amount_licenses' => 'Стоимость лицензий',
        'amount_services' => 'Стоимость услуг',
        'amount_development' => 'Стоимость разработки',
        'amount_platform' => 'Стоимость доработки платформы',
        'probability' => 'Вероятность, %',
        'plan_quarter' => 'Плановый квартал',
        'plan_month' => 'Плановый месяц',
        'date_create' => 'Дата создания',
        'begindate' => 'Дата начала',
        'closedate' => 'Дата закрытия',
        'source_name' => 'Источник',
        'category_name' => 'Направление',
        'comments' => 'Комментарий',
        'proposal_number' => 'КП, номер',
        'proposal_name' => 'КП, название',
        'proposal_status' => 'КП, статус',
    ];

    /** Колонки с деньгами — им нужен числовой формат */
    public const MONEY = [
        'opportunity', 'amount_licenses', 'amount_services',
        'amount_development', 'amount_platform',
    ];

    /** Расшифровка stage_semantic_id */
    public const SEMANTIC = [
        'S' => 'Успех',
        'F' => 'Провал',
        'P' => 'В работе',
    ];

    /**
     * Оставить из запрошенных колонок только известные, в порядке каталога
     *
     * @param array $codes
     * @return array
     */
    public static function columns(array $codes = []): array
    {
        $codes = array_values(array_intersect(array_keys(static::COLUMNS), $codes));

        return empty($codes) ? array_keys(static::COLUMNS) : $codes;
    }

    /**
     * Собрать книгу
     *
     * @param Collection $rows Сделки из CrmDealRegistryService::rows()
     * @param array $columns Коды колонок
     * @return Spreadsheet
     */
    public static function build(Collection $rows, array $columns = []): Spreadsheet
    {
        $columns = static::columns($columns);

        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Сделки');

        static::head($sheet, $columns);

        $line = 2;
        foreach ($rows as $row) {
            foreach ($columns as $i => $code) {
                $value = static::value($code, $row);

                // пустое не пишем вовсе: пустая ячейка в Excel честнее нуля
                if ($value === null || $value === '') continue;

                $cell = $sheet->getCell([$i + 1, $line]);

                // длинные ID и номера Excel любит превращать в числа —
                // текстовые поля пишем явным типом
                if (is_string($value) && static::text($code)) {
                    $cell->setValueExplicit($value, DataType::TYPE_STRING);
                } else {
                    $cell->setValue($value);
                }
            }
            $line++;
        }

        static::style($sheet, $columns, $line - 1);
        $book->setActiveSheetIndex(0);

        return $book;
    }

    /**
     * Отдать книгу в браузер
     *
     * @param Collection $rows
     * @param array $columns
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public static function download(Collection $rows, array $columns = [])
    {
        $book = static::build($rows, $columns);

        $path = tempnam(sys_get_temp_dir(), 'deals_xlsx_');

        $writer = new Xlsx($book);
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);

        $book->disconnectWorksheets();
        unset($book, $writer);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response()->download($path, static::fileName(), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, must-revalidate',
            'Pragma' => 'public',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Имя файла выгрузки
     *
     * @return string
     */
    public static function fileName(): string
    {
        return 'bitrix_deals_' . date('Y-m-d_H-i') . '.xlsx';
    }

    /**
     * Значение колонки для сделки
     *
     * @param string $code
     * @param mixed $row
     * @return mixed
     */
    public static function value(string $code, $row)
    {
        $proposal = $row->proposal ?? null;

        return match ($code) {
            'id' => (int) $row->id,
            'url' => CrmDealRegistryService::url($row->id),
            'stage_semantic' => static::SEMANTIC[$row->stage_semantic_id] ?? (string) $row->stage_semantic_id,
            'manager' => (string) $row->manager,
            'country' => (string) ($row->country ?: CrmDealRegistryService::COUNTRY_EMPTY),
            'opportunity' => (float) $row->opportunity,
            'probability' => $row->probability === null ? null : (float) $row->probability,

            // деньги по видам лежат текстом и с разделителями — приводим к числу
            'amount_licenses', 'amount_services', 'amount_development', 'amount_platform'
                => static::number($row->{$code}),

            'date_create', 'begindate', 'closedate' => static::date($row->{$code}),
            'comments' => static::plain($row->comments),

            'proposal_number' => $proposal?->number ?: '',
            'proposal_name' => $proposal?->name ?: '',
            'proposal_status' => $proposal ? ($proposal->status_enum->data()['label'] ?? '') : '',

            default => is_null($row->{$code}) ? '' : (string) $row->{$code},
        };
    }

    /**
     * Шапка листа
     *
     * @param Worksheet $sheet
     * @param array $columns
     * @return void
     */
    protected static function head(Worksheet $sheet, array $columns): void
    {
        foreach ($columns as $i => $code) {
            $sheet->setCellValue([$i + 1, 1], static::COLUMNS[$code]);
        }

        $last = $sheet->getCell([count($columns), 1])->getColumn();

        $sheet->getStyle('A1:' . $last . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $last . '1')->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('AEBDDC');
        $sheet->getStyle('A1:' . $last . '1')->getAlignment()
            ->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(30);
    }

    /**
     * Ширины, форматы, автофильтр
     *
     * @param Worksheet $sheet
     * @param array $columns
     * @param int $last_line
     * @return void
     */
    protected static function style(Worksheet $sheet, array $columns, int $last_line): void
    {
        $last_line = max($last_line, 1);

        foreach ($columns as $i => $code) {
            $letter = $sheet->getCell([$i + 1, 1])->getColumn();

            $width = match (true) {
                in_array($code, ['title', 'company_name', 'customer_name', 'proposal_name', 'comments'], true) => 45,
                $code === 'url' => 50,
                in_array($code, ['stage_name', 'manager', 'country', 'source_name', 'category_name'], true) => 24,
                in_array($code, static::MONEY, true) => 18,
                default => 14,
            };

            $sheet->getColumnDimension($letter)->setWidth($width);

            if (in_array($code, static::MONEY, true)) {
                $sheet->getStyle($letter . '2:' . $letter . $last_line)
                    ->getNumberFormat()->setFormatCode('#,##0');
            }

            if (in_array($code, ['title', 'comments', 'proposal_name', 'customer_name', 'company_name'], true)) {
                $sheet->getStyle($letter . '2:' . $letter . $last_line)
                    ->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            }
        }

        $last = $sheet->getCell([count($columns), 1])->getColumn();

        $sheet->setAutoFilter('A1:' . $last . $last_line);
        $sheet->freezePane('A2');
    }

    /**
     * Колонка выводится текстом (Excel не должен трогать её как число)
     *
     * @param string $code
     * @return bool
     */
    protected static function text(string $code): bool
    {
        return in_array($code, ['plan_quarter', 'plan_month', 'proposal_number', 'currency_id'], true);
    }

    /**
     * Число из строки Битрикса («1 234,50 руб.» → 1234.5)
     *
     * @param mixed $value
     * @return float|null
     */
    protected static function number($value): ?float
    {
        if ($value === null || $value === '') return null;

        return (float) tools()->parseNumberFromString((string) $value);
    }

    /**
     * Дата в человеческом виде
     *
     * @param mixed $value
     * @return string
     */
    protected static function date($value): string
    {
        if (empty($value)) return '';

        try {
            return \Carbon\Carbon::parse($value)->format('d.m.Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    /**
     * Комментарий сделки хранится с разметкой — в Excel нужен текст
     *
     * @param string|null $html
     * @return string
     */
    protected static function plain(?string $html): string
    {
        $text = preg_replace('/<(br|\/p|\/div|\/li)[^>]*>/i', "\n", (string) $html);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text);
        $text = preg_replace("/\n{2,}/", "\n", $text);

        return trim($text);
    }
}
