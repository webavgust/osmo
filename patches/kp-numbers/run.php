<?php
/**
 * Прогон worker.php по всем КП в обоих шаблонах, параллельно.
 *
 * php patches/kp-numbers/run.php [--jobs=8] [--since=2025-06-01] [--out=storage/logs/kp-numbers.jsonl] [id ...]
 * Результат — JSONL (по строке на КП×шаблон), сводку печатает summary.py.
 */

$jobs = 8;
$since = null;
$outFile = getcwd() . '/storage/logs/kp-numbers.jsonl';
$ids = [];
$extra = [];
foreach (array_slice($argv, 1) as $a) {
    if (str_starts_with($a, '--jobs=')) $jobs = (int) substr($a, 7);
    elseif (str_starts_with($a, '--since=')) $since = substr($a, 8);
    elseif (str_starts_with($a, '--out=')) $outFile = substr($a, 6);
    elseif ($a === '--seen') $extra[] = '--seen';
    elseif (ctype_digit($a)) $ids[] = (int) $a;
}

if (!$ids) {
    require getcwd() . '/vendor/autoload.php';
    $app = require getcwd() . '/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $q = Illuminate\Support\Facades\DB::table('proposals')->orderByDesc('id');
    if ($since) $q->where('created_at', '>=', $since);
    $ids = $q->pluck('id')->all();
}

$tasks = [];
foreach ($ids as $id) foreach (['default', 'client_discount'] as $t) $tasks[] = [$id, $t];
$total = count($tasks);
$worker = __DIR__ . '/worker.php';
$results = [];
$running = [];
$done = 0;
$started = microtime(true);

while ($tasks || $running) {
    while ($tasks && count($running) < $jobs) {
        [$id, $t] = array_shift($tasks);
        $cmd = array_merge([PHP_BINARY, $worker, (string) $id, $t], $extra);
        $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, getcwd());
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $running[] = ['proc' => $proc, 'pipes' => $pipes, 'out' => '', 'err' => '', 'id' => $id, 't' => $t];
    }
    foreach ($running as $k => &$r) {
        $r['out'] .= stream_get_contents($r['pipes'][1]);
        $r['err'] .= stream_get_contents($r['pipes'][2]);
        $st = proc_get_status($r['proc']);
        if (!$st['running']) {
            $r['out'] .= stream_get_contents($r['pipes'][1]);
            $r['err'] .= stream_get_contents($r['pipes'][2]);
            fclose($r['pipes'][1]); fclose($r['pipes'][2]);
            proc_close($r['proc']);
            $line = trim($r['out']);
            $json = json_decode($line, true);
            if (!is_array($json)) {
                $json = ['pid' => $r['id'], 'template' => $r['t'], 'findings' => [], 'error' => 'worker: ' . mb_substr(trim($r['err'] . ' ' . $line), 0, 400)];
            }
            $results[] = $json;
            unset($running[$k]);
            $done++;
            if ($done % 50 === 0) fprintf(STDERR, "%d / %d за %.0f с\n", $done, $total, microtime(true) - $started);
        }
    }
    unset($r);
    usleep(20000);
}
// параллельный прогон изредка теряет таблицы (рендер под нагрузкой) — такие перепроверяем по одному
foreach ($results as $k => $json) {
    $codes = array_column($json['findings'] ?? [], 'code');
    if (empty($json['error']) && !in_array('нет таблицы приложения', $codes, true)) continue;
    $line = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($worker) . ' ' . (int) $json['pid'] . ' ' . escapeshellarg($json['template']) . ($extra ? ' --seen' : ''));
    $again = json_decode(trim((string) $line), true);
    if (is_array($again)) { $again['retried'] = true; $results[$k] = $again; }
}
$fh = fopen($outFile, 'w');
foreach ($results as $json) fwrite($fh, json_encode($json, JSON_UNESCAPED_UNICODE) . "\n");
fclose($fh);
fprintf(STDERR, "готово: %d прогонов за %.0f с → %s\n", $total, microtime(true) - $started, $outFile);
