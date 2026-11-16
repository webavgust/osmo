<?php

namespace App\Modules\Bitrix\Dashboard\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Выгрузка вкладки «Таблица» отчёта «Движение сделок» в Excel и PDF (patch v51).
 *
 * Данные приходят из DealMovementController::tableData() — ровно то, что
 * показывает страница: когорта года, движения за год и (только администратору)
 * разбивка по менеджерам. Подписи и порядок — как на экране.
 * Вкладка «Диаграмма» — ganttData() / ganttXlsx() / ganttPdf(): ровно то, что на экране
 * (присланные id видимых сделок в порядке экрана, раскрытые, период шкалы).
 * Excel пишем во временный файл, а не в php://output (см. CrmDealExportService).
 */
class DealMovementExportService
{
    /** Группы когорты: ключ => подпись, в порядке плиток на странице */
    public const COHORT = [
        'total' => 'Всего',
        DealMovementService::GROUP_WORK => 'В работе',
        DealMovementService::GROUP_LOST => 'Отвалились',
        DealMovementService::GROUP_DONE => 'Закончились',
    ];

    /** Группы движений: ключ => подпись */
    public const MOVES = [
        DealMovementService::GROUP_LOST => 'Отвалились',
        DealMovementService::GROUP_DONE => 'Закончились',
    ];

    /** Серый фон шапок таблиц */
    private const HEAD_FILL = 'E9ECEF';

    /**
     * Отдать книгу Excel
     *
     * @param array $data данные вкладки из DealMovementController::tableData()
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function xlsx(array $data)
    {
        $book = $this->book($data);

        $path = tempnam(sys_get_temp_dir(), 'movement_xlsx_');

        $writer = new Xlsx($book);
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);

        $book->disconnectWorksheets();
        unset($book, $writer);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response()->download($path, $this->fileName($data, 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, must-revalidate',
            'Pragma' => 'public',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Отдать PDF (A4, альбомная).
     * Не через download() обёртки: она отдаёт файл мимо ответа Laravel (echo + header).
     *
     * @param array $data данные вкладки из DealMovementController::tableData()
     * @return \Illuminate\Http\Response
     */
    public function pdf(array $data)
    {
        $content = \PDF::loadView('bitrix.dashboard.pdf.movement_table', array_merge($data, [
            'mode_label' => $this->modeLabel($data),
            'mode_note' => $this->modeNote($data),
            'created_at' => Carbon::now()->format('d.m.Y H:i'),
        ]), [], [
            'format' => 'A4-L',
            'orientation' => 'L',
            'default_font' => 'dejavusans',
            'default_font_size' => 9,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ])->output();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->fileName($data, 'pdf') . '"',
            'Cache-Control' => 'max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Имя файла: dvizhenie-sdelok-{год}-{now|year_end}.{ext}
     *
     * @param array $data
     * @param string $ext
     * @return string
     */
    public function fileName(array $data, string $ext): string
    {
        return 'dvizhenie-sdelok-' . $data['year'] . '-' . $data['at'] . '.' . $ext;
    }

    /**
     * Подпись среза для шапки файла
     *
     * @param array $data
     * @return string
     */
    public function modeLabel(array $data): string
    {
        $today = Carbon::now()->format('d.m.Y');

        if ($data['at'] !== DealMovementService::AT_YEAR_END) {
            return 'Сейчас — текущий статус сделок на ' . $today;
        }

        // текущий год ещё не закончился — снимок на сегодня, а не на 31 декабря
        return $data['is_current_year']
            ? 'На конец года — снимок на ' . $today
            : 'На конец года — снимок на 31.12.' . $data['year'];
    }

    /**
     * Примечание к срезу: текущий год «на конец года» — снимок на сегодня (как бейдж на странице)
     *
     * @param array $data
     * @return string|null
     */
    public function modeNote(array $data): ?string
    {
        return $data['is_current_year'] && $data['at'] === DealMovementService::AT_YEAR_END
            ? 'Год ещё не закончился — для него «на конец года» = снимок на сегодня.'
            : null;
    }

    /**
     * Собрать книгу: один лист «Таблица», блоки друг под другом
     *
     * @param array $data
     * @return Spreadsheet
     */
    public function book(array $data): Spreadsheet
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Таблица');
        $year = $data['year'];

        // шапка файла
        $sheet->setCellValue('A1', 'Движение сделок ' . $year);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', 'Срез: ' . $this->modeLabel($data));
        $line = 3;
        if ($note = $this->modeNote($data)) {
            $sheet->setCellValue('A' . $line, $note);
            $sheet->getStyle('A' . $line)->getFont()->setItalic(true)->getColor()->setRGB('B7791F');
            $line++;
        }
        $sheet->setCellValue('A' . $line, 'Сформировано: ' . Carbon::now()->format('d.m.Y H:i'));
        $sheet->getStyle('A2:A' . $line)->getFont()->getColor()->setRGB('5E6278');
        $line += 2;

        // Новые запросы года
        $line = $this->title($sheet, $line, 'Новые запросы ' . $year);
        $this->head($sheet, $line, ['Группа', 'Количество', 'Доля, %']);
        $first = ++$line;
        foreach (static::COHORT as $key => $label) {
            $sheet->setCellValue([1, $line], $label);
            $sheet->setCellValue([2, $line], (int) $data['cohort'][$key]);
            if ($key !== 'total') $sheet->setCellValue([3, $line], (int) $data['cohort'][$key . '_pct']);
            $line++;
        }
        $this->body($sheet, $first, $line - 1, 3);
        $line++;

        // Движения за год
        $line = $this->title($sheet, $line, 'Движения за ' . $year);
        $sheet->setCellValue('A' . $line, 'Сделки, которые в ' . $year . ' году перешли в группу (по первому переходу), — включая запросы прошлых лет.');
        $sheet->getStyle('A' . $line)->getFont()->setItalic(true)->getColor()->setRGB('5E6278');
        $line++;
        $this->head($sheet, $line, ['Группа', 'Всего', 'Из запросов ' . $year, 'Прошлых лет']);
        $first = ++$line;
        foreach (static::MOVES as $key => $label) {
            $sheet->setCellValue([1, $line], $label);
            $sheet->setCellValue([2, $line], (int) $data['movements'][$key]['all']);
            $sheet->setCellValue([3, $line], (int) $data['movements'][$key]['this']);
            $sheet->setCellValue([4, $line], (int) $data['movements'][$key]['past']);
            $line++;
        }
        $this->body($sheet, $first, $line - 1, 4);
        $line++;

        // По менеджерам — только администратору (контроллер отдаёт пусто иначе)
        if ($data['by_manager']) {
            $this->managers($sheet, $line, $data);
        }

        // ширины колонок
        $sheet->getColumnDimension('A')->setWidth(34);
        foreach (range('B', 'L') as $letter) {
            $sheet->getColumnDimension($letter)->setWidth(14);
        }

        $sheet->setSelectedCell('A1');
        $book->setActiveSheetIndex(0);

        return $book;
    }

    /**
     * Блок «По менеджерам»: двухуровневая шапка, строки менеджеров, «Итого».
     * Доли и «прошлых лет» — отдельными колонками, чтобы в ячейках были числа.
     *
     * @param Worksheet $sheet
     * @param int $line
     * @param array $data
     * @return int следующая свободная строка
     */
    private function managers(Worksheet $sheet, int $line, array $data): int
    {
        $year = $data['year'];
        $line = $this->title($sheet, $line, 'По менеджерам');

        // верхний уровень шапки
        $sheet->setCellValue([1, $line], 'Менеджер');
        $sheet->setCellValue([2, $line], 'Новые запросы ' . $year);
        $sheet->setCellValue([9, $line], 'Движения за ' . $year);
        $sheet->mergeCells('A' . $line . ':A' . ($line + 1));
        $sheet->mergeCells('B' . $line . ':H' . $line);
        $sheet->mergeCells('I' . $line . ':L' . $line);
        $this->headStyle($sheet, 'A' . $line . ':L' . $line);
        $sheet->getStyle('B' . $line . ':L' . $line)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $line++;

        $this->head($sheet, $line, [
            null, 'Всего', 'В работе', 'В работе, %', 'Отвалились', 'Отвалились, %', 'Закончились', 'Закончились, %',
            'Отвалились', 'из них прошлых лет', 'Закончились', 'из них прошлых лет',
        ]);
        $first = ++$line;

        $rows = $data['managers'];
        $rows['Итого'] = ['cohort' => $data['cohort'], 'movements' => $data['movements']];

        foreach ($rows as $name => $row) {
            $c = $row['cohort'];
            $m = $row['movements'];

            $values = [(string) $name, (int) ($c['total'] ?? 0)];
            foreach ([DealMovementService::GROUP_WORK, DealMovementService::GROUP_LOST, DealMovementService::GROUP_DONE] as $key) {
                $values[] = (int) ($c[$key] ?? 0);
                // доля — только если у менеджера есть новые запросы (как на экране)
                $values[] = !empty($c['total']) ? (int) $c[$key . '_pct'] : null;
            }
            foreach (array_keys(static::MOVES) as $key) {
                $values[] = (int) ($m[$key]['all'] ?? 0);
                $values[] = (int) ($m[$key]['past'] ?? 0);
            }

            foreach ($values as $i => $value) {
                if ($value !== null) $sheet->setCellValue([$i + 1, $line], $value);
            }
            $line++;
        }

        $this->body($sheet, $first, $line - 1, 12);

        // итоговая строка — жирная на сером, как на странице
        $total = 'A' . ($line - 1) . ':L' . ($line - 1);
        $sheet->getStyle($total)->getFont()->setBold(true);
        $sheet->getStyle($total)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F5F8FA');

        // доли — бледнее, как на странице
        foreach (['D', 'F', 'H', 'J', 'L'] as $letter) {
            $sheet->getStyle($letter . $first . ':' . $letter . ($line - 1))->getFont()->getColor()->setRGB('7E8299');
        }

        $sheet->freezePane('B' . $first);

        return $line + 1;
    }

    /**
     * Заголовок блока
     *
     * @return int следующая строка
     */
    private function title(Worksheet $sheet, int $line, string $text): int
    {
        $sheet->setCellValue('A' . $line, $text);
        $sheet->getStyle('A' . $line)->getFont()->setBold(true)->setSize(12);

        return $line + 1;
    }

    /**
     * Строка шапки таблицы с подписями колонок
     */
    private function head(Worksheet $sheet, int $line, array $labels): void
    {
        foreach ($labels as $i => $label) {
            // null — ячейка под объединённой («Менеджер» заполнен строкой выше)
            if ($label === null) continue;
            $sheet->setCellValue([$i + 1, $line], $label);
        }

        $last = $sheet->getCell([count($labels), $line])->getColumn();
        $this->headStyle($sheet, 'A' . $line . ':' . $last . $line);
        $sheet->getStyle('B' . $line . ':' . $last . $line)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    /**
     * Оформление шапки: жирный, серый фон, перенос, рамка
     */
    private function headStyle(Worksheet $sheet, string $range): void
    {
        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::HEAD_FILL);
        $style->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D6D9E0');
    }

    /**
     * Тело таблицы: рамки, числа вправо
     */
    private function body(Worksheet $sheet, int $first, int $last, int $columns): void
    {
        $letter = $sheet->getCell([$columns, $first])->getColumn();
        $range = 'A' . $first . ':' . $letter . $last;

        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D6D9E0');
        $sheet->getStyle('B' . $first . ':' . $letter . $last)->getNumberFormat()->setFormatCode('0');
        $sheet->getStyle('B' . $first . ':' . $letter . $last)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    // ================= Диаграмма Ганта (patch v51, этап 2) =================

    /**
     * Порог шкалы PDF: дольше — шаг «месяц» вместо «неделя». Не из-за скорости (178 сделок × 133 недели —
     * 0,7 с, подряд идущие ячейки склеены colspan), а из-за ширины: при 160 неделях колонка ~2 мм на A3
     */
    public const PDF_MAX_WEEKS = 160;

    /** Подписи сортировок диаграммы (как в селекте на странице) */
    public const GANTT_SORTS = [
        'funnel' => 'По стадии воронки',
        'created_desc' => 'По дате создания: новые сверху',
        'created_asc' => 'По дате создания: старые сверху',
        'changed' => 'По последнему изменению',
        'duration' => 'По длительности сделки',
        'manager' => 'По менеджеру',
    ];

    /** Подписи групп */
    public const GROUPS = [
        DealMovementService::GROUP_WORK => 'В работе',
        DealMovementService::GROUP_LOST => 'Отвалились',
        DealMovementService::GROUP_DONE => 'Закончились',
    ];

    private const MONTHS = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
    private const MONTHS_SHORT = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];

    /**
     * Данные выгрузки диаграммы: сделки из присланного списка в присланном порядке,
     * отрезки, обрезанные периодом, дни по стадиям, стадии и легенда.
     *
     * @param array $gantt DealMovementService::gantt($year)
     * @param array $params year, at, is_current_year, range, start, end, ids, open, sort, conditions (см. контроллер)
     * @param string $deal_url шаблон ссылки на сделку (%s — id)
     * @return array
     */
    public function ganttData(array $gantt, array $params, string $deal_url): array
    {
        $year = (int) $params['year'];
        $at = $params['at'];
        $today = $gantt['today'];
        $group_field = $at === DealMovementService::AT_YEAR_END ? 'group_ye' : 'group';
        // момент среза — как CUTOFF на странице
        $cutoff = $at === DealMovementService::AT_YEAR_END && ($year . '-12-31') < $today ? $year . '-12-31' : $today;

        $by_id = [];
        foreach ($gantt['deals'] as $deal) $by_id[$deal['id']] = $deal;

        // только сделки страницы, в порядке экрана
        $picked = [];
        foreach ($params['ids'] as $id) {
            if (isset($by_id[$id])) $picked[] = $by_id[$id];
        }

        [$start, $end] = $this->ganttPeriod($params, $picked, $year, $today);
        $p0 = $this->dayNum($start);
        $p1 = $this->dayNum($end) + 1;   // конец периода — не включая

        $stages = [];
        foreach ($gantt['stages'] as $i => $stage) $stages[$stage['name']] = $stage + ['order' => $i];

        $open = array_flip($params['open']);
        $deals = [];
        $used = [];        // стадии с днями за период — колонки листа «Сделки»
        $legend = [];      // стадии, видимые на диаграмме

        foreach ($picked as $deal) {
            $segments = $deal['segments'];
            $current = $segments[0];
            foreach ($segments as $segment) {
                if ($segment['from'] <= $cutoff) $current = $segment;
            }
            $last = end($segments);

            $clipped = [];
            $stage_days = [];
            foreach ($segments as $i => $segment) {
                $from = $this->dayNum($segment['from']);
                $to = $this->dayNum($segment['to']);
                if ($segment['final']) {
                    if ($from < $p0 || $from >= $p1) continue;
                    $a = $b = $from;
                } else {
                    if ($to < $p0 || $from >= $p1) continue;
                    $a = max($from, $p0);
                    $b = min($to, $p1);
                }
                $days = $segment['final'] ? 0 : $b - $a;
                $clipped[] = [
                    'stage' => $segment['stage'],
                    'from' => $segment['from'],
                    'to' => $segment['to'],
                    'a' => $a,
                    'b' => $b,
                    'days_all' => $to - $from,
                    'days' => $days,
                    'final' => (bool) $segment['final'],
                    'open' => $i === count($segments) - 1 && !$segment['final'],
                ];
                $legend[$segment['stage']] = true;
                if (!$segment['final']) {
                    $stage_days[$segment['stage']] = ($stage_days[$segment['stage']] ?? 0) + $days;
                    if ($days > 0) $used[$segment['stage']] = true;
                }
            }

            // строки стадий раскрытой сделки — в порядке первого посещения, как на экране;
            // дни в подписи — полные длительности отрезков (как на экране), null — у стадии только итог
            $rows = [];
            foreach ($segments as $segment) {
                $rows[$segment['stage']] ??= null;
                if (!$segment['final']) {
                    $rows[$segment['stage']] = ($rows[$segment['stage']] ?? 0)
                        + $this->dayNum($segment['to']) - $this->dayNum($segment['from']);
                }
            }

            $deals[] = [
                'id' => $deal['id'],
                'title' => $deal['title'],
                'url' => str_replace('%s', (string) $deal['id'], $deal_url),
                'manager' => $deal['manager'],
                'company' => $deal['company'],
                'created' => $deal['created'],
                'final_date' => $last['final'] ? $last['from'] : null,
                'stage' => $current['stage'],
                'group' => $deal[$group_field],
                'segments' => $clipped,
                'stage_days' => $stage_days,
                'total_days' => array_sum($stage_days),
                'open' => isset($open[$deal['id']]),
                'rows' => $rows,
            ];
        }

        $order = fn($a, $b) => ($stages[$a]['order'] ?? 999) <=> ($stages[$b]['order'] ?? 999);
        $used = array_keys($used);
        usort($used, $order);
        $legend = array_keys($legend);
        usort($legend, $order);

        return [
            'year' => $year,
            'at' => $at,
            'is_current_year' => (bool) $params['is_current_year'],
            'range' => $params['range'],
            'start' => $start,
            'end' => $end,
            'today' => $today,
            'sort' => isset(static::GANTT_SORTS[$params['sort']]) ? $params['sort'] : 'funnel',
            'conditions' => $params['conditions'],
            'deals' => $deals,
            'stages' => $stages,
            'used' => $used,
            'legend' => $legend,
        ];
    }

    /**
     * Отдать диаграмму в Excel: листы «Сделки», «Диаграмма», «Переходы»
     *
     * @param array $data ganttData()
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function ganttXlsx(array $data)
    {
        $book = new Spreadsheet();
        $this->ganttDealsSheet($book->getActiveSheet(), $data);
        $this->ganttChartSheet($book->createSheet(), $data);
        $this->ganttMovesSheet($book->createSheet(), $data);
        $book->setActiveSheetIndex(0);

        $path = tempnam(sys_get_temp_dir(), 'movement_gantt_xlsx_');
        $writer = new Xlsx($book);
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);
        $book->disconnectWorksheets();
        unset($book, $writer);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response()->download($path, $this->ganttFileName($data, 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, must-revalidate',
            'Pragma' => 'public',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Отдать диаграмму в PDF (A3, альбомная). Шкала — по неделям; период длиннее
     * PDF_MAX_WEEKS недель — по месяцам (сказано в шапке файла).
     *
     * @param array $data ganttData()
     * @return \Illuminate\Http\Response
     */
    public function ganttPdf(array $data)
    {
        $weeks = $this->buckets($data['start'], $data['end'], 'week');
        $step = count($weeks) > static::PDF_MAX_WEEKS ? 'month' : 'week';
        $buckets = $step === 'week' ? $weeks : $this->buckets($data['start'], $data['end'], 'month');

        $content = \PDF::loadView('bitrix.dashboard.pdf.movement_gantt', array_merge($data, [
            'head' => $this->ganttHead($data),
            'step' => $step,
            'buckets' => $buckets,
            'bands' => $this->bands($buckets),
            'lines' => $this->ganttLines($data, $buckets),
            'colors' => $this->colors($data),
        ]), [], [
            'format' => 'A3-L',
            'orientation' => 'L',
            'default_font' => 'dejavusans',
            'default_font_size' => 7,
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
        ])->output();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->ganttFileName($data, 'pdf') . '"',
            'Cache-Control' => 'max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Имя файла диаграммы: dvizhenie-sdelok-gantt-{год}-{at}-{year|all}.{ext}
     *
     * @param array $data ganttData()
     * @param string $ext
     * @return string
     */
    public function ganttFileName(array $data, string $ext): string
    {
        return 'dvizhenie-sdelok-gantt-' . $data['year'] . '-' . $data['at'] . '-' . $data['range'] . '.' . $ext;
    }

    /**
     * Строки шапки файла диаграммы: срез, период, условия, сортировка, число сделок, дата формирования
     *
     * @param array $data ganttData()
     * @return string[]
     */
    public function ganttHead(array $data): array
    {
        $lines = ['Срез: ' . $this->modeLabel($data)];
        if ($note = $this->modeNote($data)) $lines[] = $note;
        $lines[] = 'Период шкалы: ' . ($data['range'] === 'all' ? 'вся история' : 'только ' . $data['year'])
            . ', ' . $this->ru($data['start']) . ' — ' . $this->ru($data['end']);
        $lines[] = 'Условия: ' . ($data['conditions'] !== '' ? $data['conditions'] : 'все сделки года, без фильтра');
        $lines[] = 'Сортировка: ' . mb_strtolower(static::GANTT_SORTS[$data['sort']]);
        $lines[] = 'Сделок: ' . count($data['deals']);
        $lines[] = 'Сформировано: ' . Carbon::now()->format('d.m.Y H:i');

        return $lines;
    }

    /**
     * Период шкалы. «Только год» — 1.01…31.12; «Вся история» — присланный (не уже выбранного года),
     * без присланного — с начала месяца самой ранней даты сделок до конца месяца последней (не раньше сегодня).
     *
     * @return string[] [start, end] — Y-m-d, end включительно
     */
    private function ganttPeriod(array $params, array $deals, int $year, string $today): array
    {
        $y0 = $year . '-01-01';
        $y1 = $year . '-12-31';
        if ($params['range'] !== 'all') return [$y0, $y1];

        $min = $y0;
        $max = max($today, $y1);
        foreach ($deals as $deal) {
            foreach ($deal['segments'] as $segment) {
                $min = min($min, $segment['from']);
                $max = max($max, $segment['to']);
            }
        }
        $start = $params['start'] ?: substr($min, 0, 8) . '01';
        $end = $params['end'] ?: Carbon::parse($max)->endOfMonth()->format('Y-m-d');

        // год виден целиком; разумный предел — 20 лет
        $start = min($start, $y0);
        $end = max($end, $y1);
        if ($this->dayNum($end) - $this->dayNum($start) > 366 * 20) $start = Carbon::parse($end)->subYears(20)->format('Y-m-d');

        return [$start, $end];
    }

    /** Номер дня (UTC) для даты Y-m-d */
    private function dayNum(string $date): int
    {
        return intdiv((int) strtotime($date . ' 00:00:00 UTC'), 86400);
    }

    /** Дата Y-m-d по номеру дня */
    private function dayDate(int $num): string
    {
        return gmdate('Y-m-d', $num * 86400);
    }

    /** Y-m-d → d.m.Y */
    private function ru(?string $date): string
    {
        return $date ? substr($date, 8, 2) . '.' . substr($date, 5, 2) . '.' . substr($date, 0, 4) : '';
    }

    /**
     * Колонки шкалы: недели (с понедельника; первая и последняя обрезаны периодом) или месяцы
     *
     * @return array [[a, b, from, label, year, month]] — a/b номера дней (b не включая)
     */
    private function buckets(string $start, string $end, string $step): array
    {
        $p0 = $this->dayNum($start);
        $p1 = $this->dayNum($end) + 1;
        $out = [];

        if ($step === 'week') {
            // 1970-01-01 — четверг: понедельник — день, где (n + 3) % 7 == 0
            for ($a = $p0 - (($p0 + 3) % 7); $a < $p1; $a += 7) {
                $from = $this->dayDate(max($a, $p0));
                $out[] = [
                    'a' => max($a, $p0), 'b' => min($a + 7, $p1), 'from' => $from,
                    // номер недели ISO по её понедельнику
                    'label' => (int) gmdate('W', $a * 86400),
                    'year' => (int) substr($from, 0, 4), 'month' => (int) substr($from, 5, 2),
                ];
            }
            return $out;
        }

        $date = Carbon::parse($start)->startOfMonth();
        while ($this->dayNum($date->format('Y-m-d')) < $p1) {
            $next = $date->copy()->addMonth();
            $out[] = [
                'a' => max($this->dayNum($date->format('Y-m-d')), $p0), 'b' => min($this->dayNum($next->format('Y-m-d')), $p1),
                'from' => $date->format('Y-m-d'), 'label' => self::MONTHS_SHORT[$date->month - 1],
                'year' => $date->year, 'month' => $date->month,
            ];
            $date = $next;
        }

        return $out;
    }

    /**
     * Полосы шапки над колонками: годы и месяцы (подряд идущие колонки одного года / месяца)
     *
     * @return array{years: array, months: array} [[label, span]]
     */
    private function bands(array $buckets): array
    {
        $years = $months = [];
        foreach ($buckets as $b) {
            $y = (string) $b['year'];
            $m = $b['year'] . '-' . $b['month'];
            if ($years && end($years)['key'] === $y) $years[count($years) - 1]['span']++;
            else $years[] = ['key' => $y, 'label' => $y, 'span' => 1];
            if ($months && end($months)['key'] === $m) $months[count($months) - 1]['span']++;
            else $months[] = ['key' => $m, 'label' => self::MONTHS[$b['month'] - 1], 'short' => self::MONTHS_SHORT[$b['month'] - 1], 'span' => 1];
        }

        return ['years' => $years, 'months' => $months];
    }

    /**
     * Стадия ячейки шкалы: в которой сделка провела больше всего дней колонки;
     * в колонке только метка итога — стадия итога; null — пусто.
     *
     * @param array $segments отрезки сделки из ganttData (a/b — номера дней)
     * @param array $bucket колонка из buckets()
     * @param string|null $only только эта стадия (строка стадии раскрытой сделки)
     */
    private function cellStage(array $segments, array $bucket, ?string $only = null): ?string
    {
        $days = [];
        $final = null;
        foreach ($segments as $s) {
            if ($only !== null && $s['stage'] !== $only) continue;
            if ($s['final']) {
                if ($s['a'] >= $bucket['a'] && $s['a'] < $bucket['b']) $final = $s['stage'];
                continue;
            }
            // открытый отрезок (по сегодня) занимает и сегодняшний день, как на экране
            $b = $s['open'] ? $s['b'] + 1 : $s['b'];
            $n = min($b, $bucket['b']) - max($s['a'], $bucket['a']);
            if ($n > 0) $days[$s['stage']] = ($days[$s['stage']] ?? 0) + $n;
        }
        if (!$days) return $final;
        arsort($days);

        return array_key_first($days);
    }

    /**
     * Строки шкалы: заголовки менеджеров (сортировка «По менеджеру»), сделки и строки стадий раскрытых;
     * cells — стадия по колонкам
     *
     * @return array [[type: head|deal|stage, ...]]
     */
    private function ganttLines(array $data, array $buckets): array
    {
        $lines = [];
        $manager = null;
        $counts = array_count_values(array_column($data['deals'], 'manager'));

        foreach ($data['deals'] as $deal) {
            if ($data['sort'] === 'manager' && $deal['manager'] !== $manager) {
                $manager = $deal['manager'];
                $lines[] = ['type' => 'head', 'label' => $manager, 'count' => $counts[$manager]];
            }
            $lines[] = ['type' => 'deal', 'deal' => $deal,
                'cells' => array_map(fn($b) => $this->cellStage($deal['segments'], $b), $buckets)];

            if (!$deal['open']) continue;
            foreach ($deal['rows'] as $stage => $days) {
                $lines[] = ['type' => 'stage', 'stage' => $stage, 'days' => $days,
                    'cells' => array_map(fn($b) => $this->cellStage($deal['segments'], $b, $stage), $buckets)];
            }
        }

        return $lines;
    }

    /**
     * Цвета стадий: name => [hex без #, light — светлее для паузы в PDF, pause]
     */
    private function colors(array $data): array
    {
        $out = [];
        foreach ($data['stages'] as $name => $stage) {
            $hex = ltrim((string) ($stage['color'] ?? DealMovementService::GANTT_COLOR_UNKNOWN), '#');
            if (strlen($hex) !== 6) $hex = ltrim(DealMovementService::GANTT_COLOR_UNKNOWN, '#');
            $out[$name] = ['hex' => strtoupper($hex), 'light' => $this->lighten($hex, 0.6), 'pause' => !empty($stage['pause'])];
        }

        return $out;
    }

    /** Смешать цвет с белым: share — доля белого */
    private function lighten(string $hex, float $share): string
    {
        $out = '';
        foreach ([0, 2, 4] as $i) {
            $c = hexdec(substr($hex, $i, 2));
            $out .= sprintf('%02X', (int) round($c + (255 - $c) * $share));
        }

        return $out;
    }

    /**
     * Шапка листа диаграммы: заголовок и строки ganttHead
     *
     * @return int следующая свободная строка
     */
    private function ganttSheetHead(Worksheet $sheet, array $data, string $title): int
    {
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $line = 2;
        foreach ($this->ganttHead($data) as $text) {
            $sheet->setCellValue('A' . $line, $text);
            $sheet->getStyle('A' . $line)->getFont()->getColor()->setRGB('5E6278');
            $line++;
        }

        return $line + 1;
    }

    /**
     * Лист «Сделки»: строка на сделку, дни по стадиям за период
     */
    private function ganttDealsSheet(Worksheet $sheet, array $data): void
    {
        $sheet->setTitle('Сделки');
        $line = $this->ganttSheetHead($sheet, $data, 'Движение сделок ' . $data['year'] . ' — диаграмма');

        $stage_label = $data['at'] === DealMovementService::AT_YEAR_END ? 'Стадия на конец года' : 'Текущая стадия';
        $head = array_merge(
            ['№', 'Сделка', 'Менеджер', 'Партнёр', $stage_label, 'Группа', 'Дата создания', 'Дата итога', 'Всего дней за период'],
            $data['used']
        );
        $this->head($sheet, $line, $head);
        $last_col = $sheet->getCell([count($head), $line])->getColumn();
        $sheet->getStyle('A' . $line . ':' . $last_col . $line)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($line)->setRowHeight(45);
        $head_line = $line;
        $first = ++$line;

        foreach ($data['deals'] as $n => $deal) {
            $sheet->setCellValue([1, $line], $n + 1);
            $sheet->setCellValue([2, $line], $deal['title']);
            $sheet->getCell([2, $line])->getHyperlink()->setUrl($deal['url']);
            $sheet->getStyle([2, $line])->getFont()->setUnderline(true)->getColor()->setRGB('1B84FF');
            $sheet->setCellValue([3, $line], $deal['manager']);
            if ($deal['company']) $sheet->setCellValue([4, $line], $deal['company']);
            $sheet->setCellValue([5, $line], $deal['stage']);
            $sheet->setCellValue([6, $line], static::GROUPS[$deal['group']] ?? $deal['group']);
            $this->dateCell($sheet, 7, $line, $deal['created']);
            $this->dateCell($sheet, 8, $line, $deal['final_date']);
            $sheet->setCellValue([9, $line], (int) $deal['total_days']);
            foreach ($data['used'] as $i => $stage) {
                if (!empty($deal['stage_days'][$stage])) $sheet->setCellValue([10 + $i, $line], (int) $deal['stage_days'][$stage]);
            }
            $line++;
        }

        if ($line > $first) {
            $this->body($sheet, $first, $line - 1, count($head));
            // текстовые колонки — влево
            $sheet->getStyle('B' . $first . ':F' . ($line - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('G' . $first . ':H' . ($line - 1))->getNumberFormat()->setFormatCode('dd.mm.yyyy');
            $sheet->setAutoFilter('A' . $head_line . ':' . $last_col . ($line - 1));
        } else {
            $sheet->setCellValue('A' . $line, 'Нет сделок под фильтр');
        }

        foreach (['A' => 6, 'B' => 45, 'C' => 22, 'D' => 30, 'E' => 24, 'F' => 13, 'G' => 12, 'H' => 12, 'I' => 11] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        foreach ($data['used'] as $i => $stage) {
            $sheet->getColumnDimension($sheet->getCell([10 + $i, $head_line])->getColumn())->setWidth(12);
        }
        $sheet->freezePane('C' . $first);
        $sheet->setSelectedCell('A1');
    }

    /**
     * Лист «Диаграмма» (решение владельца 16.11): у каждой сделки слева «+» (группировка строк Excel),
     * под строкой сделки — по строке на каждый отрезок истории за период в хронологическом порядке
     * (повторный заход в стадию — отдельная строка). Строка сделки — сводная полоса: неделя залита цветом
     * стадии большей части недели; строка отрезка — только недели, с которыми отрезок пересекается.
     * Свёрнуты все сделки, кроме раскрытых на экране. Легенда снизу.
     */
    private function ganttChartSheet(Worksheet $sheet, array $data): void
    {
        $sheet->setTitle('Диаграмма');
        $line = $this->ganttSheetHead($sheet, $data, 'Движение сделок ' . $data['year'] . ' — диаграмма по неделям');

        $buckets = $this->buckets($data['start'], $data['end'], 'week');
        $bands = $this->bands($buckets);
        $colors = $this->colors($data);
        $c0 = 6;   // первая колонка недель: A — сделка / стадия, B — менеджер, C — с, D — по, E — дней
        $last = $sheet->getCell([$c0 + count($buckets) - 1, 1])->getColumn();
        $c0l = $sheet->getCell([$c0, 1])->getColumn();

        // «+» у строки сделки сверху, а не под группой
        $sheet->setShowSummaryBelow(false);

        // шапка: год / месяц / номер недели ISO; левые колонки — на три строки
        foreach (['Сделка / стадия', 'Менеджер', 'С', 'По', 'Дней за период'] as $i => $label) {
            $sheet->setCellValue([$i + 1, $line], $label);
            $sheet->mergeCells([$i + 1, $line, $i + 1, $line + 2]);
        }
        foreach (['years' => 0, 'months' => 1] as $key => $shift) {
            $col = $c0;
            foreach ($bands[$key] as $band) {
                // узкий месяц (обрезанный край периода) — короткая подпись или буква
                $label = $key === 'months' && $band['span'] < 3 ? ($band['span'] < 2 ? mb_substr($band['short'], 0, 1) : $band['short']) : $band['label'];
                $sheet->setCellValue([$col, $line + $shift], $label);
                if ($band['span'] > 1) $sheet->mergeCells([$col, $line + $shift, $col + $band['span'] - 1, $line + $shift]);
                $col += $band['span'];
            }
        }
        foreach ($buckets as $i => $bucket) {
            $sheet->setCellValue([$c0 + $i, $line + 2], $bucket['label']);
        }
        $this->headStyle($sheet, 'A' . $line . ':' . $last . ($line + 2));
        $sheet->getStyle('C' . $line . ':' . $last . ($line + 2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($c0l . $line . ':' . $last . ($line + 2))->getAlignment()->setWrapText(false);
        $sheet->getStyle($c0l . ($line + 2) . ':' . $last . ($line + 2))->getFont()->setSize(7);
        $first = $line + 3;
        $line = $first;

        $manager = null;
        $counts = array_count_values(array_column($data['deals'], 'manager'));

        foreach ($data['deals'] as $deal) {
            // «По менеджеру» — строка-заголовок группы, как на экране
            if ($data['sort'] === 'manager' && $deal['manager'] !== $manager) {
                $manager = $deal['manager'];
                $sheet->setCellValue('A' . $line, $manager . ' — ' . $counts[$manager]);
                $sheet->getStyle('A' . $line . ':' . $last . $line)->getFont()->setBold(true);
                $sheet->getStyle('A' . $line . ':' . $last . $line)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F5F8FA');
                $line++;
            }

            // строка сделки: создание / итог / всего дней за период, сводная полоса
            $sheet->setCellValue('A' . $line, $deal['title']);
            $sheet->getCell('A' . $line)->getHyperlink()->setUrl($deal['url']);
            $sheet->getStyle('A' . $line)->getFont()->setBold(true);
            $sheet->setCellValue('B' . $line, $deal['manager']);
            $this->dateCell($sheet, 3, $line, $deal['created']);
            $this->dateCell($sheet, 4, $line, $deal['final_date']);
            $sheet->setCellValue('E' . $line, (int) $deal['total_days']);
            $this->paintRun($sheet, $line, $c0, array_map(fn($b) => $this->cellStage($deal['segments'], $b), $buckets), $colors);
            $deal_line = $line++;

            // строки отрезков — уровень 1 группировки
            foreach ($deal['segments'] as $s) {
                $sheet->setCellValue('A' . $line, $s['stage'] . ($s['final'] ? ' (итог)' : ''));
                $this->dateCell($sheet, 3, $line, $s['from']);
                if (!$s['final']) {
                    $this->dateCell($sheet, 4, $line, $s['to']);
                    $sheet->setCellValue('E' . $line, (int) $s['days']);
                }
                $sheet->getStyle('A' . $line)->getAlignment()->setIndent(1);
                $sheet->getStyle('A' . $line . ':E' . $line)->getFont()->setSize(8)->getColor()->setRGB('7E8299');
                $this->paintRun($sheet, $line, $c0, array_map(fn($b) => $this->segmentCell($s, $b), $buckets), $colors);

                $row = $sheet->getRowDimension($line);
                $row->setOutlineLevel(1);
                if (!$deal['open']) $row->setVisible(false);
                $line++;
            }
            if ($deal['segments'] && !$deal['open']) $sheet->getRowDimension($deal_line)->setCollapsed(true);

            // пустая строка-разделитель в несколько пикселей между сделками (решение владельца 16.11); вне группировки — видна всегда
            $sheet->getRowDimension($line)->setRowHeight(4);
            $line++;
        }
        if ($line === $first) {
            $sheet->setCellValue('A' . $line++, 'Нет сделок под фильтр');
        } else {
            $sheet->getStyle('C' . $first . ':D' . ($line - 1))->getNumberFormat()->setFormatCode('dd.mm.yyyy');
            $sheet->getStyle('C' . $first . ':E' . ($line - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // легенда
        $line++;
        $sheet->setCellValue('A' . $line, 'Легенда');
        $sheet->getStyle('A' . $line)->getFont()->setBold(true);
        $line++;
        foreach ($data['legend'] as $stage) {
            $sheet->setCellValue('A' . $line, $stage . (!empty($colors[$stage]['pause']) ? ' (пауза — штриховка)' : ''));
            $this->paintCell($sheet, 'B' . $line, $colors[$stage] ?? null);
            $line++;
        }

        foreach (['A' => 45, 'B' => 18, 'C' => 11, 'D' => 11, 'E' => 8] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        for ($i = 0; $i < count($buckets); $i++) {
            $sheet->getColumnDimension($sheet->getCell([$c0 + $i, 1])->getColumn())->setWidth(2.7);
        }
        $sheet->freezePane($c0l . $first);
        $sheet->setSelectedCell('A1');
    }

    /**
     * Стадия ячейки строки отрезка: неделя закрашена, если отрезок пересекается с ней хотя бы днём;
     * отрезок короче дня и метка итога — одна ячейка (неделя начала)
     *
     * @param array $s отрезок из ganttData (a/b — номера дней, b не включая)
     * @param array $bucket колонка из buckets()
     */
    private function segmentCell(array $s, array $bucket): ?string
    {
        // открытый отрезок (по сегодня) занимает и сегодняшний день, как на экране
        $b = $s['final'] ? $s['a'] + 1 : max($s['open'] ? $s['b'] + 1 : $s['b'], $s['a'] + 1);

        return $s['a'] < $bucket['b'] && $b > $bucket['a'] ? $s['stage'] : null;
    }

    /**
     * Залить ячейки строки по стадиям; подряд идущие одинаковые — одним диапазоном (быстрее)
     */
    private function paintRun(Worksheet $sheet, int $line, int $c0, array $cells, array $colors): void
    {
        $n = count($cells);
        for ($i = 0; $i < $n;) {
            $stage = $cells[$i];
            $j = $i;
            while ($j + 1 < $n && $cells[$j + 1] === $stage) $j++;
            if ($stage !== null) {
                $range = $sheet->getCell([$c0 + $i, $line])->getColumn() . $line
                    . ($j > $i ? ':' . $sheet->getCell([$c0 + $j, $line])->getColumn() . $line : '');
                $this->paintCell($sheet, $range, $colors[$stage] ?? null);
            }
            $i = $j + 1;
        }
    }

    /** Заливка цветом стадии; пауза — штриховка LIGHTUP её цветом по белому */
    private function paintCell(Worksheet $sheet, string $range, ?array $color): void
    {
        $color ??= ['hex' => ltrim(DealMovementService::GANTT_COLOR_UNKNOWN, '#'), 'pause' => false];
        $fill = $sheet->getStyle($range)->getFill();
        if ($color['pause']) {
            $fill->setFillType(Fill::FILL_PATTERN_LIGHTUP);
            $fill->getStartColor()->setRGB($color['hex']);
            $fill->getEndColor()->setRGB('FFFFFF');
        } else {
            $fill->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color['hex']);
        }
    }

    /**
     * Лист «Переходы»: каждый отрезок сделки, попавший в период
     */
    private function ganttMovesSheet(Worksheet $sheet, array $data): void
    {
        $sheet->setTitle('Переходы');
        $line = $this->ganttSheetHead($sheet, $data, 'Движение сделок ' . $data['year'] . ' — переходы по стадиям');

        $this->head($sheet, $line, ['№ сделки', 'Сделка', 'Менеджер', 'Стадия', 'С', 'По', 'Дней', 'Дней за период', 'Примечание']);
        $head_line = $line;
        $first = ++$line;

        foreach ($data['deals'] as $n => $deal) {
            foreach ($deal['segments'] as $s) {
                $sheet->setCellValue([1, $line], $n + 1);
                $sheet->setCellValue([2, $line], $deal['title']);
                $sheet->getCell([2, $line])->getHyperlink()->setUrl($deal['url']);
                $sheet->setCellValue([3, $line], $deal['manager']);
                $sheet->setCellValue([4, $line], $s['stage']);
                $this->dateCell($sheet, 5, $line, $s['from']);
                if (!$s['final']) {
                    $this->dateCell($sheet, 6, $line, $s['to']);
                    $sheet->setCellValue([7, $line], (int) $s['days_all']);
                    $sheet->setCellValue([8, $line], (int) $s['days']);
                }
                $note = $s['final'] ? 'итог' : ($s['open'] ? 'по сегодня' : '');
                if ($note !== '') $sheet->setCellValue([9, $line], $note);
                $line++;
            }
        }

        if ($line > $first) {
            $this->body($sheet, $first, $line - 1, 9);
            $sheet->getStyle('B' . $first . ':D' . ($line - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('I' . $first . ':I' . ($line - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('E' . $first . ':F' . ($line - 1))->getNumberFormat()->setFormatCode('dd.mm.yyyy');
            $sheet->setAutoFilter('A' . $head_line . ':I' . ($line - 1));
        }
        foreach (['A' => 9, 'B' => 45, 'C' => 22, 'D' => 28, 'E' => 12, 'F' => 12, 'G' => 9, 'H' => 10, 'I' => 13] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->freezePane('A' . $first);
        $sheet->setSelectedCell('A1');
    }

    /** Дата Y-m-d в ячейку как дата Excel (пусто — не пишем) */
    private function dateCell(Worksheet $sheet, int $col, int $line, ?string $date): void
    {
        if (!$date) return;
        $sheet->setCellValue([$col, $line], \PhpOffice\PhpSpreadsheet\Shared\Date::stringToExcel($date));
    }
}
