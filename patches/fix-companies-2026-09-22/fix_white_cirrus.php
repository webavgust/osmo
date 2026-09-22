<?php
/*
 * White Cirrus — заказчик партнёра SINAPSYSTEC, а не партнёр (решение владельца 23.09.2026).
 * Компания #901 «White Cirrus» переходит к SINAPSYSTEC (#19), во всех редакциях КП AK666 партнёр — SINAPSYSTEC.
 * Партнёр «White Cirrus» (#844) и его связь с компанией Битрикса #385 не трогаются — ждут решения владельца.
 *   php fix_white_cirrus.php            — пробно, с откатом
 *   php fix_white_cirrus.php --apply    — выполнить
 */

foreach (['STDIN' => 'php://stdin', 'STDOUT' => 'php://stdout', 'STDERR' => 'php://stderr'] as $name => $stream) {
    if (!defined($name)) define($name, fopen($stream, $name === 'STDIN' ? 'r' : 'w'));
}

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\EntityLog\Services\EntityLogService;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv ?? [], true);

$problems = [];
if (DB::table('partners')->where('id', 19)->value('name') !== 'SINAPSYSTEC DIGITAL SOLUTIONS S.L.') $problems[] = 'партнёр #19 — не SINAPSYSTEC';
$company = DB::table('companies')->where('id', 901)->first(['name', 'partner_id']);
if (!$company || $company->name !== 'White Cirrus' || (int) $company->partner_id !== 844) $problems[] = 'компания #901: ожидалась «White Cirrus» у партнёра #844';
$rows = DB::table('proposals')->where('number', 'AK666')->get(['group', 'company_id', 'partner_id']);
if ($rows->pluck('group')->unique()->count() !== 1 || $rows->count() !== 5
    || $rows->map(fn($r) => (int) $r->company_id . '/' . (int) $r->partner_id)->unique()->values()->all() !== ['901/844']) $problems[] = 'КП AK666: ожидались 5 редакций на #901 у партнёра #844';
if (DB::table('proposals')->where('company_id', 901)->where('number', '!=', 'AK666')->exists()) $problems[] = 'на #901 есть другие КП';
if ($problems) { echo "Исходное состояние не совпало — ничего не менял:\n  - ", implode("\n  - ", $problems), "\n"; exit(1); }

DB::beginTransaction();
try {
    $card = Company::findOrFail(901);
    $card->partner_id = 19;
    $card->save();
    echo "компания «White Cirrus» #901: партнёр White Cirrus → SINAPSYSTEC\n";

    $list = Proposal::where('number', 'AK666')->get();
    foreach ($list as $row) {
        $row->timestamps = false;
        $row->partner_id = 19;
        $row->save();
    }
    echo "КП AK666: редакций {$list->count()}, партнёр → SINAPSYSTEC\n";

    EntityLogService::flush();
    echo "ссылки на партнёра White Cirrus #844 после: компаний ", DB::table('companies')->where('partner_id', 844)->count(),
        ", КП ", DB::table('proposals')->where('partner_id', 844)->count(), ", связей Б24 ", DB::table('partner_crm_companies')->where('partner_id', 844)->count(), "\n";

    $apply ? DB::commit() : DB::rollBack();
    echo $apply ? "ВЫПОЛНЕНО\n" : "пробный прогон — всё откачено\n";
} catch (Throwable $e) {
    DB::rollBack();
    echo "ОШИБКА, всё откачено: ", $e->getMessage(), "\n";
    exit(1);
}
