<?php
/*
 * Удаление пустого партнёра «White Cirrus» (#844) и его связи с компанией Битрикса #385 (там это End user).
 * Решение владельца 23.09.2026. Запуск: php delete_white_cirrus_partner.php [--apply]
 */

foreach (['STDIN' => 'php://stdin', 'STDOUT' => 'php://stdout', 'STDERR' => 'php://stderr'] as $name => $stream) {
    if (!defined($name)) define($name, fopen($stream, $name === 'STDIN' ? 'r' : 'w'));
}

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Pub\EntityLog\Services\EntityLogService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Models\PartnerCrmCompany;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv ?? [], true);

$cols = collect(DB::select("SELECT TABLE_NAME t, COLUMN_NAME c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME LIKE '%partner_id' AND TABLE_NAME <> 'partners'"));
$refs = [];
foreach ($cols as $r) { $n = DB::table($r->t)->where($r->c, 844)->count(); if ($n) $refs["{$r->t}.{$r->c}"] = $n; }

if (DB::table('partners')->where('id', 844)->value('name') !== 'White Cirrus' || $refs !== ['partner_crm_companies.partner_id' => 1]
    || DB::table('partner_crm_companies')->where('partner_id', 844)->value('crm_company_id') != 385) {
    echo "Исходное состояние не совпало — ничего не менял: ", json_encode($refs), "\n";
    exit(1);
}

DB::beginTransaction();
try {
    PartnerCrmCompany::where('partner_id', 844)->get()->each->delete();
    Partner::findOrFail(844)->delete();
    EntityLogService::flush();
    echo "партнёр «White Cirrus» #844 и связь с Б24 #385 удалены\n";
    $apply ? DB::commit() : DB::rollBack();
    echo $apply ? "ВЫПОЛНЕНО\n" : "пробный прогон — всё откачено\n";
} catch (Throwable $e) {
    DB::rollBack();
    echo "ОШИБКА, всё откачено: ", $e->getMessage(), "\n";
    exit(1);
}
