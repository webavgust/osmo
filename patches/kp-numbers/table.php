<?php
// Сводная таблица ошибок: прогон до правок (report.jsonl) против прогона после (report4.jsonl)
// Из корня проекта: php patches/kp-numbers/table.php {до.jsonl} {после.jsonl, прогон с --seen} {выход.xlsx}
require getcwd() . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$read = fn($f) => array_map(fn($l) => json_decode($l, true), array_filter(file($f), 'trim'));
$before = $read($argv[1]);
$after = $read($argv[2]);

$still = [];
$seen = [];
$partnerOpen = [];
foreach ($after as $r) {
    foreach ($r['seen'] ?? [] as $k => $v) $seen["{$r['pid']}|{$r['template']}|$k"] = $v;
    foreach ($r['findings'] as $f) {
        $still["{$r['pid']}|{$r['template']}|{$f['area']}|{$f['code']}|{$f['where']}"] = $f;
        if (str_starts_with($f['code'], 'приложение в ценах партнёра')) $partnerOpen["{$r['pid']}|{$r['template']}"] = true;
    }
}

$num = function ($v, $cur) {
    if ($v === null || $v === '') return '';
    if (!is_numeric($v)) return (string) $v;
    $v = (float) $v;
    $prec = $cur === 'RUB' ? 0 : 2;
    $s = number_format(round($v, $prec), $prec, ',', ' ');
    if ($cur === 'RUB' && abs($v - round($v)) > 0.001) $s = number_format($v, 2, ',', ' ');
    return $s;
};

$areaName = ['PDF' => 'PDF «По умолчанию»', 'PDF клиент' => 'PDF «Со скидкой клиента»', 'Excel' => 'Excel «По умолчанию»', 'Excel клиент' => 'Excel «Со скидкой клиента»'];
$rows = [];
$hit = 0;
$falseAlarm = 0;
$miss = [];
foreach ($before as $r) {
    $cur = $r['currency'] ?? 'RUB';
    $lost = in_array('нет таблицы приложения', array_column($r['findings'], 'code'), true);
    foreach ($r['findings'] as $f) {
        if ($f['area'] === 'КП' || $f['code'] === 'нет таблицы приложения') continue; // данные базы и сбой параллельного прогона
        // прогон, потерявший таблицы под нагрузкой: «нет строки» там — сбой стенда, а не документа
        if ($f['code'] === 'нет строки в сводной' || ($lost && $f['code'] === 'нет строки ПО в сводной')) continue;
        $code = $f['code'];
        $exp = $f['expected'];
        $act = $f['actual'];
        $key = "{$r['pid']}|{$r['template']}|{$f['area']}|{$code}|{$f['where']}";
        $status = 'исправлено';
        $was = $num($act, $cur);
        $now = $num($exp, $cur);
        $err = $code . ($f['note'] && !str_starts_with($f['note'], 'после всех скидок') ? " ({$f['note']})" : '');

        if (str_contains($code, '— партнёра (после всех скидок)')) {
            $now = $was;
            $err = 'цена партнёра (после всех скидок) под пометкой скидки клиента; цена клиента ' . $num($exp, $cur);
            $status = 'решает владелец';
        } elseif (str_contains($code, 'ни клиента, ни партнёра') || $code === 'итог строки в приложении — нет') {
            $now = preg_match('/после всех скидок (-?[\d.]+)/', $f['note'], $m) ? $num($m[1], $cur) : $now;
            $err = 'неверная цена работы: ни цена клиента (' . $num($exp, $cur) . '), ни партнёра';
            $status = 'исправлено; цена партнёра — решает владелец';
            if ($was === '') $was = 'пусто';
        } elseif (in_array($code, ['ИТОГО сводной ≠ сумме строк', 'ИТОГО приложения ≠ сумме строк'])) {
            $was = 'строки ' . $num($exp, $cur) . ', ИТОГО ' . $num($act, $cur);
            $now = 'строки = ИТОГО';
        } elseif ($code === 'в ИТОГО приложения нет НДС') {
            $was = 'нет';
            $now = '+ НДС: ' . $num($exp, $cur);
        } elseif (in_array($code, ['нет строки ПО в сводной', 'нет строки в сводной'])) {
            $was = 'нет строки';
            $now = is_numeric($exp) ? $num($exp, $cur) : str_replace(' / ', '; ', (string) $exp);
        } elseif ($code === 'сумма напечатана дважды') {
            $now = 'один раз';
        } elseif ($code === 'подсветка неактивной не та') {
            $was = 'белая';
            $now = 'жёлтая';
        } elseif ($code === 'дробное количество округлено') {
            $was = $num($act, $cur);
            $now = str_replace('.', ',', (string) $exp);
        } elseif ($code === 'ИТОГО приложения ≠ итогу клиента') {
            $err = 'ИТОГО приложения не сходится со строками и карточкой; в ценах клиента ' . $num($exp, $cur);
            $status = 'исправлено; цена партнёра — решает владелец';
        } elseif ($code === 'в ИТОГО попали неактивные (жёлтые) строки') {
            $err = 'в ИТОГО вошли неактивные (жёлтые) позиции';
        }

        // что документ печатает сейчас — из прогона после правок
        $sk = "{$r['pid']}|{$r['template']}|{$f['area']}|{$f['where']}";
        $textual = ['ИТОГО сводной ≠ сумме строк', 'ИТОГО приложения ≠ сумме строк', 'в ИТОГО приложения нет НДС', 'нет строки ПО в сводной', 'нет строки в сводной', 'сумма напечатана дважды', 'подсветка неактивной не та', 'дробное количество округлено'];
        if (array_key_exists($sk, $seen) && !in_array($code, $textual, true) && $status !== 'решает владелец') {
            $now = $num($seen[$sk], $cur);
            $hit++;
        } elseif (!in_array($code, $textual, true) && $status !== 'решает владелец') {
            $miss[$f['area'] . ' | ' . preg_replace('/\d+/', 'N', $f['where'])] = ($miss[$f['area'] . ' | ' . preg_replace('/\d+/', 'N', $f['where'])] ?? 0) + 1;
        }
        if (isset($still[$key])) {
            $now = $num($still[$key]['actual'], $cur);
            $status = 'осталось: старые данные в КП';
        }
        // значение не изменилось и прогон после правок его принял: первый прогон ошибся в ожидании, документ был верен
        if ($was === $now && str_starts_with($status, 'исправлено')) {
            if ($code === 'ИТОГО приложения ≠ итогу клиента') {
                $status = 'решает владелец';
                $err = 'ИТОГО в ценах партнёра под пометкой скидки клиента; в ценах клиента ' . $num($exp, $cur);
            } elseif (str_contains($code, '— партнёра (после всех скидок)')) {
                $status = 'решает владелец';
            } else {
                $falseAlarm++;
                continue;
            }
        }

        $rows[] = [
            'kp' => "{$r['number']} ред.{$r['iteration']}",
            'date' => substr($r['created'] ?? '', 0, 10),
            'where' => $areaName[$f['area']] ?? $f['area'],
            'field' => preg_replace('/^вариант 1 /u', '', $f['where']) . (preg_match('/^вариант (\d+)/u', $f['where'], $m) && $m[1] !== '1' ? '' : ''),
            'was' => $was, 'now' => $now, 'err' => $err, 'status' => $status, 'cur' => $cur, 'kind' => $code,
        ];
    }
}

// Ручные строки: то, что стенд до правок не ловил
$rows[] = ['kp' => 'все КП', 'date' => '', 'where' => 'Excel, оба шаблона', 'field' => 'колонки «НДС» и «Итого с НДС»', 'was' => 'нет', 'now' => 'есть', 'err' => 'НДС не выгружался', 'status' => 'исправлено', 'cur' => ''];
$rows[] = ['kp' => 'AK753 ред.2', 'date' => '2026-04-15', 'where' => 'PDF, оба шаблона', 'field' => 'приложение: нулевая сумма работы', 'was' => '0 ₽', 'now' => '$ 0.00', 'err' => 'нулевая сумма в валютном КП печаталась в рублях', 'status' => 'исправлено', 'cur' => 'USD'];

$rows[] = ['kp' => 'AK706 ред.2', 'date' => '2025-09-17', 'where' => 'PDF, оба шаблона', 'field' => 'приложение: работа — цена за час', 'was' => '$ 1.00', 'now' => '$ 0.89', 'err' => 'в валютном КП цена за единицу округлялась до целых, но печаталась с «.00»', 'status' => 'исправлено', 'cur' => 'USD', 'kind' => 'валюта: цена округлена'];
$rows[] = ['kp' => 'все валютные КП', 'date' => '', 'where' => 'PDF, оба шаблона', 'field' => 'заголовки колонок цены и итога', 'was' => 'РУБ.', 'now' => 'символ валюты КП', 'err' => 'в долларовых и прочих КП колонки подписаны «РУБ.»', 'status' => 'исправлено', 'cur' => '', 'kind' => 'валюта: заголовки'];
usort($rows, fn($a, $b) => [$b['date'], $a['kp'], $a['where'], $a['field']] <=> [$a['date'], $b['kp'], $b['where'], $b['field']]);

$book = new Spreadsheet();
$sh = $book->getActiveSheet();
$sh->setTitle('Ошибки');
$head = ['КП', 'Дата КП', 'Валюта', 'Где', 'Поле', 'Было', 'Стало', 'Ошибка', 'Статус'];
$sh->fromArray($head, null, 'A1');
$i = 2;
foreach ($rows as $x) {
    $sh->fromArray([$x['kp'], $x['date'], $x['cur'], $x['where'], $x['field'], $x['was'], $x['now'], $x['err'], $x['status']], null, "A$i", true);
    $i++;
}
$sh->getStyle('A1:I1')->getFont()->setBold(true);
$sh->setAutoFilter("A1:I" . ($i - 1));
$sh->freezePane('A2');
foreach (['A' => 14, 'B' => 11, 'C' => 7, 'D' => 26, 'E' => 48, 'F' => 26, 'G' => 22, 'H' => 60, 'I' => 30] as $c => $w) $sh->getColumnDimension($c)->setWidth($w);
$sh->getStyle("F2:G$i")->getAlignment()->setHorizontal('right');

// Сводка: вид ошибки → сколько строк и КП, пример с самым крупным расхождением (свежие КП в приоритете)
$groups = [];
foreach ($rows as $x) {
    $g = &$groups[$x['where'] . '|' . ($x['kind'] ?? $x['err']) . '|' . $x['status']];
    $g['where'] = $x['where']; $g['kind'] = $x['kind'] ?? $x['err']; $g['status'] = $x['status'];
    $g['n'] = ($g['n'] ?? 0) + 1;
    $g['kp'][$x['kp']] = true;
    if ($x['date'] >= '2025-06-01') $g['fresh'][$x['kp']] = true;
    $d = abs((float) str_replace([' ', ','], ['', '.'], $x['was']) - (float) str_replace([' ', ','], ['', '.'], $x['now']));
    $score = [$x['date'] >= '2025-06-01' ? 1 : 0, $d];
    if (!isset($g['ex']) || $score > $g['score']) { $g['ex'] = $x; $g['score'] = $score; }
    unset($g);
}
uasort($groups, fn($a, $b) => [$a['where'], -count($a['kp'])] <=> [$b['where'], -count($b['kp'])]);
$sv = $book->createSheet();
$sv->setTitle('Сводка');
$sv->fromArray(['Где', 'Ошибка', 'Статус', 'Строк', 'КП', 'КП с 2025-06-01', 'Пример: КП', 'Поле', 'Было', 'Стало'], null, 'A1');
$i = 2;
foreach ($groups as $g) {
    $e = $g['ex'];
    $sv->fromArray([$g['where'], $e['err'], $g['status'], $g['n'], count($g['kp']), count($g['fresh'] ?? []), $e['kp'], $e['field'], $e['was'], $e['now']], null, "A$i", true);
    $i++;
}
$sv->getStyle('A1:J1')->getFont()->setBold(true);
$sv->freezePane('A2');
foreach (['A' => 26, 'B' => 60, 'C' => 30, 'D' => 7, 'E' => 6, 'F' => 9, 'G' => 14, 'H' => 44, 'I' => 24, 'J' => 22] as $c => $w) $sv->getColumnDimension($c)->setWidth($w);

(new Xlsx($book))->save($argv[3]);
echo count($rows), " строк\n";
$st = array_count_values(array_column($rows, 'status'));
print_r($st);
echo "стало из прогона: $hit, ложных срабатываний первого прогона: $falseAlarm
";
arsort($miss);
print_r(array_slice($miss, 0, 25));
