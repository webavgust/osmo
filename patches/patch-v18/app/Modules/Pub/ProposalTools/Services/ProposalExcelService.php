<?php

namespace App\Modules\Pub\ProposalTools\Services;

use App\Modules\Pub\Proposal\Models\Proposal;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Выгрузка КП в Excel.
 *
 * Тот же расчёт, что в карточке и в PDF: процент заказчику снимается с прайса,
 * процент партнёру — с уже уменьшенной цены; у платформы, ПО и нейросервисов
 * процент партнёра общий на вариант, у работ — свой на каждой позиции.
 * Формулы повторены, а не переиспользованы из вида, чтобы выгрузка не зависела
 * от вёрстки, но цифры обязаны сходиться с PDF.
 *
 * Два шаблона:
 *  - default — внутренний: видны обе скидки, прайс и итог;
 *  - client_discount — для заказчика: цена сразу со скидкой, партнёрская
 *    скидка не показывается вообще.
 *
 * Каждый выбранный вариант КП — отдельный лист.
 */
class ProposalExcelService
{
    public const TEMPLATES = [
        'default' => 'По умолчанию (обе скидки)',
        'client_discount' => 'Со скидкой клиента (без партнёрской)',
    ];

    /** Блоки в порядке вывода */
    public const BLOCKS = [
        'platform' => ['label' => 'ПЛАТФОРМА', 'relation' => 'proposal_platforms', 'discount' => 'discount', 'partner_p' => 'platform_discount_partner_p'],
        'soft' => ['label' => 'ПО', 'relation' => 'proposal_software', 'discount' => 'discount_customer', 'partner_p' => 'soft_discount_partner_p'],
        'neuro' => ['label' => 'НЕЙРОСЕРВИСЫ', 'relation' => 'proposal_scenarios', 'discount' => 'discount', 'partner_p' => 'neuro_discount_partner_p'],
        'work' => ['label' => 'РАБОТЫ', 'relation' => 'proposal_works', 'discount' => 'discount_customer', 'partner_p' => null],
    ];

    /**
     * Собрать книгу
     *
     * @param Proposal $proposal
     * @param array $variant_ids какие варианты выгружать
     * @param string $template ключ из TEMPLATES
     * @param bool $show_unprocessed выводить неактивные позиции
     * @return Spreadsheet
     */
    public static function build(Proposal $proposal, array $variant_ids, string $template = 'default', bool $show_unprocessed = true): Spreadsheet
    {
        $book = new Spreadsheet();

        $variants = $proposal->variants->whereIn('id', $variant_ids);
        if ($variants->isEmpty()) $variants = $proposal->variants;

        // первый лист у книги уже есть — переиспользуем его, иначе останется пустой
        foreach ($variants->values() as $i => $variant) {
            $sheet = $i === 0 ? $book->getActiveSheet() : $book->createSheet();
            $sheet->setTitle(static::sheetTitle($variant, $i + 1));
            static::variant($sheet, $proposal, $variant, $template, $show_unprocessed);
        }

        $book->setActiveSheetIndex(0);

        return $book;
    }

    /**
     * Отдать книгу в браузер.
     *
     * Пишем во временный файл, а не в php://output: при потоковой отдаче в
     * начало xlsx попадает всё, что успело вывести приложение (пробел из
     * Blade, отладочный вывод, BOM), и Excel отказывается открывать файл.
     * Буферы вывода на всякий случай гасим.
     *
     * @param Proposal $proposal
     * @param array $variant_ids
     * @param string $template
     * @param bool $show_unprocessed
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public static function download(Proposal $proposal, array $variant_ids, string $template = 'default', bool $show_unprocessed = true)
    {
        $book = static::build($proposal, $variant_ids, $template, $show_unprocessed);

        $path = tempnam(sys_get_temp_dir(), 'kp_xlsx_');

        $writer = new Xlsx($book);
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);

        $book->disconnectWorksheets();
        unset($book, $writer);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response()->download($path, static::fileName($proposal), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, must-revalidate',
            'Pragma' => 'public',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Имя файла: номер КП, редакция, дата
     *
     * @param Proposal $proposal
     * @return string
     */
    public static function fileName(Proposal $proposal): string
    {
        $parts = array_filter([
            'KP',
            preg_replace('/[^\w\-]+/u', '_', (string) ($proposal->number ?: $proposal->id)),
            'r' . $proposal->iteration,
            $proposal->sended_at?->format('Y-m-d'),
        ]);

        return implode('_', $parts) . '.xlsx';
    }

    /**
     * Заголовок листа. Excel не терпит длинных имён и части символов.
     *
     * @param mixed $variant
     * @param int $number
     * @return string
     */
    public static function sheetTitle($variant, int $number): string
    {
        $title = 'Вариант ' . $number . ($variant->is_main ? ' (осн)' : '');

        return mb_substr(str_replace(['*', ':', '/', '\\', '?', '[', ']', "'"], ' ', $title), 0, 31);
    }

    /**
     * Один вариант на своём листе
     *
     * @param Worksheet $sheet
     * @param Proposal $proposal
     * @param mixed $variant
     * @param string $template
     * @param bool $show_unprocessed
     * @return void
     */
    protected static function variant(Worksheet $sheet, Proposal $proposal, $variant, string $template, bool $show_unprocessed): void
    {
        $client = $template === 'client_discount';
        $columns = $client
            ? ['№', 'Наименование', 'Цена', 'Кол-во', 'Итого', 'Примечание']
            : ['№', 'Наименование', 'Прайс', 'Скидка заказчику', 'Скидка партнёру', 'Цена итог', 'Кол-во', 'Итого', 'Примечание'];

        $last = chr(ord('A') + count($columns) - 1);
        $symbol = $proposal->currency->symbol ?? '';
        $row = 1;

        // шапка
        $sheet->setCellValue('A' . $row, 'Коммерческое предложение № ' . ($proposal->number ?: '—'));
        $sheet->mergeCells('A' . $row . ':' . $last . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $row++;

        $head = array_filter([
            $proposal->name,
            $proposal->company->name ?? null,
            $proposal->partner->name ?? null,
            $proposal->sended_at ? 'от ' . $proposal->sended_at->format('d.m.Y') : null,
            'редакция ' . $proposal->iteration,
            $proposal->currency->name ?? null,
        ]);

        $sheet->setCellValue('A' . $row, implode(' · ', $head));
        $sheet->mergeCells('A' . $row . ':' . $last . $row);
        $sheet->getStyle('A' . $row)->getFont()->setSize(10);
        $row += 2;

        $totals = ['list' => 0.0, 'customer' => 0.0, 'partner' => 0.0, 'total' => 0.0];

        foreach (static::BLOCKS as $code => $block) {
            $items = $variant->{$block['relation']} ?? collect();
            $items = $items->filter(fn($item) => (float) ($item->count ?? 0) > 0);
            if (!$show_unprocessed) {
                $items = $items->filter(fn($item) => static::processed($item));
            }
            if ($items->isEmpty()) continue;

            // название блока
            $sheet->setCellValue('A' . $row, $block['label']);
            $sheet->mergeCells('A' . $row . ':' . $last . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('AEBDDC');
            $row++;

            // заголовки
            foreach ($columns as $i => $title) {
                $sheet->setCellValue(chr(ord('A') + $i) . $row, $title);
            }
            $sheet->getStyle('A' . $row . ':' . $last . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':' . $last . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F1F1');
            $row++;

            $number = 0;
            foreach ($items as $item) {
                $number++;
                $count = (float) $item->count;
                $price = (float) ($item->cost ?? 0);

                $pct_customer = (float) ($item->{$block['discount']} ?? 0);
                $customer = $pct_customer > 0 ? $price / 100 * $pct_customer : 0.0;

                $pct_partner = $block['partner_p']
                    ? (float) ($variant->{$block['partner_p']} ?? 0)
                    : (float) ($item->discount_partner ?? 0);
                $partner = $pct_partner > 0 ? ($price - $customer) / 100 * $pct_partner : 0.0;

                $final = $price - $customer - $partner;

                $totals['list'] += $price * $count;
                $totals['customer'] += $customer * $count;
                $totals['partner'] += $partner * $count;
                $totals['total'] += $final * $count;

                if ($client) {
                    // заказчику показываем цену со его скидкой, партнёрскую не раскрываем
                    $price_out = $price - $customer;
                    $values = [$number, static::name($code, $item), $price_out, $count, $price_out * $count, static::notice($code, $item)];
                } else {
                    $values = [$number, static::name($code, $item), $price, $customer ?: null, $partner ?: null, $final, $count, $final * $count, static::notice($code, $item)];
                }

                foreach ($values as $i => $value) {
                    $sheet->setCellValue(chr(ord('A') + $i) . $row, $value);
                }
                $row++;
            }

            $row++;
        }

        // итоги
        $sheet->setCellValue('A' . $row, 'ИТОГО');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        if ($client) {
            $sheet->setCellValue('E' . $row, $totals['list'] - $totals['customer']);
            $sheet->getStyle('E' . $row)->getFont()->setBold(true);
        } else {
            $sheet->setCellValue('C' . $row, $totals['list']);
            $sheet->setCellValue('D' . $row, $totals['customer']);
            $sheet->setCellValue('E' . $row, $totals['partner']);
            $sheet->setCellValue('H' . $row, $totals['total']);
            $sheet->getStyle('C' . $row . ':' . $last . $row)->getFont()->setBold(true);
        }
        $row += 2;

        $rate_note = !empty($proposal->currency_rate) && (float) $proposal->currency_rate != 1
            ? ', курс 1 ' . $symbol . ' = ' . $proposal->currency_rate . ' руб.'
            : '';

        $sheet->setCellValue('A' . $row, 'Суммы в ' . ($proposal->currency->name ?? 'валюте КП')
            . ($symbol ? ' (' . $symbol . ')' : '') . $rate_note);
        $sheet->getStyle('A' . $row)->getFont()->setSize(9)->setItalic(true);

        // оформление
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(56);
        foreach (range('C', $last) as $letter) {
            if ($letter === 'A' || $letter === 'B') continue;
            $sheet->getColumnDimension($letter)->setWidth(16);
        }
        $sheet->getColumnDimension($last)->setWidth(40);
        $sheet->getStyle('B1:B' . $row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle($last . '1:' . $last . $row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->freezePane('A4');
    }

    /**
     * Название позиции. У каждого блока оно лежит по-своему.
     *
     * @param string $code
     * @param mixed $item
     * @return string
     */
    protected static function name(string $code, $item): string
    {
        $raw = match ($code) {
            'platform' => $item->description ?? '',
            'soft' => $item->proposal_software->description ?? '',
            'work' => $item->proposal_work->description ?? '',
            'neuro' => $item->real_name ?: ($item->mnemonic_name ?: ($item->scenario->name ?? '')),
            default => '',
        };

        return static::plain($raw);
    }

    /**
     * Примечание к позиции
     *
     * @param string $code
     * @param mixed $item
     * @return string
     */
    protected static function notice(string $code, $item): string
    {
        $raw = match ($code) {
            'platform' => $item->notice ?? '',
            'soft' => $item->proposal_software->notice ?? '',
            'work' => $item->proposal_work->notice ?? '',
            'neuro' => $item->comment ?? '',
            default => '',
        };

        return static::plain($raw);
    }

    /**
     * Позиция обработана (не помечена как неактивная)
     *
     * @param mixed $item
     * @return bool
     */
    protected static function processed($item): bool
    {
        foreach (['cb_process'] as $field) {
            if (isset($item->{$field})) return (bool) $item->{$field};
        }

        // у ПО признак лежит на справочной записи
        if (isset($item->proposal_software)) return (bool) ($item->proposal_software->cb_process ?? true);
        if (isset($item->proposal_work)) return (bool) ($item->proposal_work->cb_process ?? true);

        return true;
    }

    /**
     * Описания в КП хранятся с разметкой — в Excel нужен текст
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
