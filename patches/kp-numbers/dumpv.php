<?php
// Разбор КП по номеру: итоги вариантов и позиции. php dumpv.php AA772 [ред]
require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$num = $argv[1];
$it = $argv[2] ?? null;
$q = DB::table('proposals')->where('number', $num);
if ($it) $q->where('iteration', $it);
foreach ($q->orderBy('iteration')->get() as $p) {
    echo "=== {$p->number} ред.{$p->iteration} id={$p->id} создано {$p->created_at} валюта {$p->currency_slug} курс {$p->currency_rate} НДС {$p->nds}%\n";
    $ext = DB::getSchemaBuilder()->hasTable('external_proposals') ? DB::table("external_proposals")->where("proposal_group", $p->group)->count() : 0;
    echo "   из внешнего КП: $ext\n";
    foreach (DB::table('proposal_variants')->where('proposal_id', $p->id)->get() as $v) {
        $a = (array) $v; unset($a['task']);
        echo "  вариант: " . json_encode(array_filter($a, fn($x) => $x !== null && $x !== 0 && $x !== '0'), JSON_UNESCAPED_UNICODE) . "\n";
        foreach (['proposal_variant_platforms', 'proposal_variant_scenarios', 'proposal_variant_works', 'proposal_variant_software'] as $t) {
            foreach (DB::table($t)->where('proposal_variant_id', $v->id)->get() as $r) {
                $a = (array) $r; unset($a['description'], $a['notice'], $a['comment'], $a['proposal_variant_id'], $a['cost_rules']);
                if (isset($a['proposal_work_id'])) $a['proc'] = DB::table('proposal_works')->where('id', $a['proposal_work_id'])->value('cb_process');
                if (isset($a['proposal_software_id'])) $a['proc'] = DB::table('proposal_software')->where('id', $a['proposal_software_id'])->value('cb_process');
                echo "     $t: " . json_encode($a, JSON_UNESCAPED_UNICODE) . "\n";
            }
        }
        foreach (DB::table('proposal_variant_extra_pays')->where('proposal_variant_id', $v->id)->get() as $x) echo "     доплата: " . json_encode($x, JSON_UNESCAPED_UNICODE) . "\n";
    }
}
