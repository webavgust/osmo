<?php
/*
 * Правка дублей «партнёр = компания» (решения владельца от 22.09.2026, patches/PLAN.md):
 *  1. МТ Интеграция остаётся только партнёром: всё с компании #869 разносится по ЦОДД и Метро,
 *     на #869 остаётся один PG631 — внутренний запрос МТИ, компания переименовывается;
 *  2. ЦОДД: остаётся #42 (переходит к МТИ), с #876 переносится AK650, #876 и #888 удаляются;
 *  3. договоры L-PG-303.25 и S-PG-313.25 — рамка с партнёром: заказчик договора убирается,
 *     спецификации, ключи и проект раскладываются по ЦОДД и Метро;
 *  4. партнёр КП PG611, PG631, PG671, PG675 — МТИ (было OSMOVIEW);
 *  5. явные совпадения партнёров с компаниями Битрикса — в partner_crm_companies.
 *
 * Все изменения — через модели (журнал изменений пишет, что и где поменялось), в одной транзакции.
 * Журнал сбрасывается до фиксации, поэтому пробный прогон откатывает и его.
 * Перед выполнением сверяется исходное состояние; любое расхождение — остановка без изменений.
 *
 * Запуск из корня сайта (по SSH в stdin):
 *   php patches/fix-companies-2026-09-22/fix_companies.php            — пробно, с откатом
 *   php patches/fix-companies-2026-09-22/fix_companies.php --apply    — выполнить
 */

foreach (['STDIN' => 'php://stdin', 'STDOUT' => 'php://stdout', 'STDERR' => 'php://stderr'] as $name => $stream) {
    if (!defined($name)) define($name, fopen($stream, $name === 'STDIN' ? 'r' : 'w'));
}

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\EntityLog\Services\EntityLogService;
use App\Modules\Pub\LicenseKey\Models\LicenseKey;
use App\Modules\Pub\Partner\Models\PartnerCrmCompany;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv ?? [], true);

const MTI = 839;          // партнёр «МТ Интеграция»
const LURE = 6;           // партнёр «LURE IT»
const CODD = 42;          // ЦОДД, который остаётся
const CODD_DUP = [876, 888];
const MTI_COMPANY = 869;  // компания «МТ Интеграция» → внутренний запрос
const METRO = 968;        // «Метрополитен Москва»
const MTI_COMPANY_NAME = 'МТ Интеграция (внутренний запрос)';

// номер КП → [компания сейчас, компания после, партнёр после (null — не менять)]
const PROPOSALS = [
    'PG611' => [MTI_COMPANY, METRO, MTI],
    'PG671' => [MTI_COMPANY, METRO, MTI],
    'PG675' => [MTI_COMPANY, METRO, MTI],
    'PG631' => [MTI_COMPANY, MTI_COMPANY, MTI],
    'AK710' => [MTI_COMPANY, CODD, null],
    'AK650' => [876, CODD, null],
];
const CONTRACTS = [51 => 'L-PG-303.25', 52 => 'S-PG-313.25'];
// спецификация → [название, компания после]
const SPECS = [
    57 => ['С.2_Метро (Нейронки)', METRO], 58 => ['С.1_Метро (Платформа)', METRO],
    59 => ['С.1_Метро', METRO], 82 => ['С.2_Метро (доп.работы)', METRO],
    81 => ['С.3_Лидары', CODD], 83 => ['С.3_Лидары', CODD],
    101 => ['С.4_Лидары. перенос точки', CODD], 108 => ['С.4_Лидары. перенос точки', CODD],
];
// ключ → компания после
const KEYS = [121 => CODD, 122 => CODD, 126 => METRO];
const PROJECT = [5, 'Лидары. перенос точки', CODD];
// компания Битрикса → [название в Б24, партнёр портала, название партнёра]
const CRM_LINKS = [
    303 => ['ГК «МТ-Интеграция»', 839, 'МТ Интеграция'],
    289 => ['Trafcoo', 841, 'Trafcoo (Taraf AI-Bader)'],
    321 => ['Риверстарт Диджитал', 843, 'Риверстарт'],
    639 => ['ПроЭнерджи Digicity', 853, 'ПроЭнерджи'],
    251 => ['Телеком Мастер', 42, 'ООО «Телеком-Мастер»'],
    483 => ['TechWizer India', 846, 'Techwizer'],
    425 => ['Shanghai Bifu Testing Technology Co., Ltd.', 855, 'Bifu'],
    241 => ['iFab', 38, 'i-fab'],
    619 => ['ООО «ВНВ ГРУППА»', 848, 'ВНВ групп'],
    633 => ['Фалькон тех', 852, 'Фалькон'],
];

$problems = [];
$check = function (bool $ok, string $message) use (&$problems) {
    if (!$ok) $problems[] = $message;
};

/*** 1. Сверка исходного состояния ***/

$partner = fn(int $id) => DB::table('partners')->where('id', $id)->value('name');
$check($partner(MTI) === 'МТ Интеграция', 'партнёр #839 — не «МТ Интеграция»');
$check($partner(LURE) === 'LURE IT', 'партнёр #6 — не «LURE IT»');

$company = fn(int $id) => DB::table('companies')->where('id', $id)->first(['id', 'name', 'partner_id']);
foreach ([[CODD, 'ЦОДД', LURE], [876, 'ЦОДД', MTI], [888, 'ЦОДД', MTI], [MTI_COMPANY, 'МТ Интеграция', MTI], [METRO, 'Метрополитен Москва', MTI]] as [$id, $name, $pid]) {
    $row = $company($id);
    $check($row && $row->name === $name && (int) $row->partner_id === $pid, "компания #{$id}: ожидалась «{$name}» у партнёра #{$pid}");
}

$groups = [];
foreach (PROPOSALS as $number => [$from, $to, $newPartner]) {
    $found = DB::table('proposals')->where('number', $number)->distinct()->pluck('group');
    $check($found->count() === 1, "КП {$number}: групп " . $found->count() . ', ожидалась одна');
    if ($found->count() !== 1) continue;
    $groups[$number] = $found->first();
    $companies = DB::table('proposals')->where('group', $groups[$number])->distinct()->pluck('company_id')->map(fn($id) => (int) $id)->all();
    $check($companies === [$from], "КП {$number}: компания " . implode(',', $companies) . ", ожидалась #{$from}");
}

foreach (CONTRACTS as $id => $number) {
    $row = DB::table('contracts')->where('id', $id)->first(['number', 'partner_id', 'company_id']);
    $check($row && $row->number === $number && (int) $row->partner_id === MTI && (int) $row->company_id === MTI_COMPANY, "договор #{$id}: ожидался {$number}, партнёр МТИ, компания #869");
}
foreach (SPECS as $id => [$name]) {
    $row = DB::table('contract_specifications')->where('id', $id)->first(['name', 'company_id', 'contract_id']);
    $check($row && $row->name === $name && (int) $row->company_id === MTI_COMPANY && isset(CONTRACTS[$row->contract_id]), "спецификация #{$id}: ожидалась «{$name}» у #869 в договорах рамки");
}
foreach (KEYS as $id => $to) {
    $check((int) DB::table('license_keys')->where('id', $id)->value('company_id') === MTI_COMPANY, "ключ #{$id}: ожидалась компания #869");
}
$project = DB::table('projects')->where('id', PROJECT[0])->first(['name', 'company_id']);
$check($project && $project->name === PROJECT[1] && (int) $project->company_id === MTI_COMPANY, 'проект #5: ожидался «Лидары. перенос точки» у #869');

$bx = DB::connection('bitrix');
foreach (CRM_LINKS as $crm => [$title, $pid, $pname]) {
    $check($bx->table('crm_company')->where('id', $crm)->value('title') === $title, "компания Б24 #{$crm}: ожидалась «{$title}»");
    $check($partner($pid) === $pname, "партнёр #{$pid}: ожидался «{$pname}»");
    $check(!DB::table('partner_crm_companies')->where('crm_company_id', $crm)->exists(), "компания Б24 #{$crm} уже сопоставлена");
}

// после переноса на #869 не должно остаться ничего, кроме PG631; на дублях ЦОДД — ничего, кроме AK650
$refs = function (int $id): array {
    $out = [];
    foreach (['proposals', 'contracts', 'contract_specifications', 'license_keys', 'logs', 'projects', 'deal_projects'] as $table) {
        $n = DB::table($table)->where('company_id', $id)->count();
        if ($n) $out[$table] = $n;
    }
    return $out;
};
$expected869 = DB::table('proposals')->where('company_id', MTI_COMPANY)->whereNotIn('group', array_values(array_intersect_key($groups, array_flip(['PG611', 'PG671', 'PG675', 'PG631', 'AK710']))))->count();
$check($expected869 === 0, "на #869 есть КП, которых нет в плане: {$expected869}");
$extra869 = array_diff_key($refs(MTI_COMPANY), array_flip(['proposals', 'contracts', 'contract_specifications', 'license_keys', 'projects']));
$check(empty($extra869), 'на #869 есть ссылки вне плана: ' . json_encode($extra869));
$check(DB::table('contract_specifications')->where('company_id', MTI_COMPANY)->count() === count(SPECS), 'на #869 спецификаций больше, чем в плане');
$check(DB::table('license_keys')->where('company_id', MTI_COMPANY)->count() === count(KEYS), 'на #869 ключей больше, чем в плане');
$check(DB::table('contracts')->where('company_id', MTI_COMPANY)->count() === count(CONTRACTS), 'на #869 договоров больше, чем в плане');
$check(DB::table('projects')->where('company_id', MTI_COMPANY)->count() === 1, 'на #869 проектов больше, чем в плане');
$check(array_keys($refs(876)) === ['proposals'] && DB::table('proposals')->where('company_id', 876)->where('group', '!=', $groups['AK650'] ?? '')->doesntExist(), 'на ЦОДД #876 есть что-то кроме AK650: ' . json_encode($refs(876)));
$check($refs(888) === [], 'ЦОДД #888 не пустая: ' . json_encode($refs(888)));

if ($problems) {
    echo "Исходное состояние не совпало — ничего не менял:\n  - ", implode("\n  - ", $problems), "\n";
    exit(1);
}
echo "Сверка исходного состояния: ок\n", $apply ? "РЕЖИМ: выполнить\n" : "РЕЖИМ: пробный, с откатом\n";

/*** 2. Правка ***/

$logsBefore = DB::table('entity_logs')->max('id');

DB::beginTransaction();
try {
    // перенос данных — не правка пользователя: дату изменения записей не трогаем
    $move = function ($model, array $attrs) {
        $model->timestamps = false;
        foreach ($attrs as $key => $value) $model->{$key} = $value;
        $model->save();
    };

    // ЦОДД #42 переходит к МТИ
    $codd = Company::findOrFail(CODD);
    $codd->partner_id = MTI;
    $codd->save();
    echo "ЦОДД #42: партнёр LURE IT → МТ Интеграция\n";

    // КП: компания и партнёр всех редакций группы
    foreach (PROPOSALS as $number => [$from, $to, $newPartner]) {
        $rows = Proposal::where('group', $groups[$number])->get();
        foreach ($rows as $row) {
            $attrs = ['company_id' => $to];
            if ($newPartner) $attrs['partner_id'] = $newPartner;
            $move($row, $attrs);
        }
        echo "КП {$number}: редакций {$rows->count()}, компания #{$from} → #{$to}", $newPartner ? ', партнёр → МТИ' : '', "\n";
    }

    // договоры-рамки: без заказчика
    foreach (CONTRACTS as $id => $number) {
        $move(Contract::findOrFail($id), ['company_id' => null]);
        echo "договор {$number}: заказчик #869 убран (рамка с партнёром)\n";
    }

    foreach (SPECS as $id => [$name, $to]) {
        $move(ContractSpecification::findOrFail($id), ['company_id' => $to]);
        echo "спецификация #{$id} «{$name}» → #{$to}\n";
    }

    foreach (KEYS as $id => $to) {
        $move(LicenseKey::findOrFail($id), ['company_id' => $to]);
        echo "ключ #{$id} → #{$to}\n";
    }

    // у проектов конфигураций журнала нет
    DB::table('projects')->where('id', PROJECT[0])->update(['company_id' => PROJECT[2]]);
    echo "проект #5 «" . PROJECT[1] . "» → #" . PROJECT[2] . "\n";

    // #869 остаётся только под внутренний запрос МТИ (PG631)
    $mti = Company::findOrFail(MTI_COMPANY);
    $mti->name = MTI_COMPANY_NAME;
    $mti->save();
    echo "компания #869 переименована в «" . MTI_COMPANY_NAME . "»\n";

    // дубли ЦОДД: к этому моменту ссылок на них нет
    foreach (CODD_DUP as $id) {
        $left = $refs($id);
        if ($left) throw new RuntimeException("на ЦОДД #{$id} остались ссылки: " . json_encode($left));
        Company::findOrFail($id)->delete();
        echo "ЦОДД #{$id} удалена\n";
    }

    foreach (CRM_LINKS as $crm => [$title, $pid, $pname]) {
        PartnerCrmCompany::create(['partner_id' => $pid, 'crm_company_id' => $crm, 'created_at' => now()]);
        echo "Б24 #{$crm} «{$title}» ↔ партнёр #{$pid} «{$pname}»\n";
    }

    // журнал — до фиксации, чтобы пробный прогон откатил и его
    EntityLogService::flush();

    /*** 3. Проверка результата ***/
    $after = [];
    foreach ([CODD => 'ЦОДД', METRO => 'Метро', MTI_COMPANY => 'внутр. МТИ'] as $id => $label) {
        $after[] = "{$label} #{$id}: " . json_encode($refs($id));
    }
    echo "\nпосле:\n  ", implode("\n  ", $after), "\n";
    echo "  компаний с названием «ЦОДД»: ", DB::table('companies')->where('name', 'ЦОДД')->count(), "\n";
    echo "  компаний с названием «МТ Интеграция»: ", DB::table('companies')->where('name', 'МТ Интеграция')->count(), "\n";
    echo "  записей журнала: ", DB::table('entity_logs')->where('id', '>', (int) $logsBefore)->count(), "\n";

    if ($apply) {
        DB::commit();
        echo "\nВЫПОЛНЕНО\n";
    } else {
        DB::rollBack();
        echo "\nпробный прогон — всё откачено\n";
    }
} catch (Throwable $e) {
    DB::rollBack();
    echo "\nОШИБКА, всё откачено: ", $e->getMessage(), "\n  ", $e->getFile(), ':', $e->getLine(), "\n";
    exit(1);
}
