<?php
/*
 * Партнёры, заведённые заказчиками у себя, — вторая правка (решения владельца от 23.09.2026,
 * patches/PLAN.md «Партнёры, заведённые заказчиками у себя»):
 *  - КП переносятся к настоящим заказчикам: AK678 → Indorama; PG668, PG669, PG672 → новые заказчики СТК;
 *    AM644 → новый заказчик Ростелекома; AK655 → новый заказчик Trafcoo; AA497 → #65 (Телеком-Мастер),
 *    карточка #65 переименовывается в «ООО Советская агрофирма»; PG617 → новый заказчик Softline;
 *  - партнёр КП: PG627, PG628, PG668, PG669 → СТК; AM644 → Ростелеком; AA497 → Телеком-Мастер;
 *  - удаляются тестовые данные (КП AA670, компания Test, её проект test2, договор «111», партнёр test),
 *    партнёр-дубль «T1» (латиница) и пустые карточки «Полипластик», «STARINCO», «Ростелеком»;
 *  - сопоставления с Битриксом: Борлас АФС, 1С:Северо-Запад, АДТ Секьюрити Солюшнз, Т1 Интеграция.
 * Не трогаются (владелец собирает данные): Борлас AK638, Билайн AM621, Данон; названия карточек
 * «внутренний запрос» / «заказчик не указан» — после решения владельца.
 *
 * Изменения — через модели (журнал), одна транзакция, журнал сбрасывается до фиксации.
 * Сверка исходного состояния; расхождение — остановка без изменений (повторный запуск безопасен).
 *   php fix_partner_customers.php            — пробно, с откатом
 *   php fix_partner_customers.php --apply    — выполнить
 */

foreach (['STDIN' => 'php://stdin', 'STDOUT' => 'php://stdout', 'STDERR' => 'php://stderr'] as $name => $stream) {
    if (!defined($name)) define($name, fopen($stream, $name === 'STDIN' ? 'r' : 'w'));
}

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\EntityLog\Services\EntityLogService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Models\PartnerCrmCompany;
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv ?? [], true);

const RUSSIA = 185, SAUDI = 197;
const S_PROD = 2, S_LOGISTICS = 11, S_RETAIL = 15, S_OTHER = 17;

// партнёры: id => ожидаемое название
const PARTNERS = [8 => 'OSMOVIEW', 11 => 'STARINCO', 13 => 'Softline', 21 => 'СТК', 25 => 'T1', 28 => 'Ростелеком',
    41 => '1С:Дистрибьюция Северо-Запад', 42 => 'ООО «Телеком-Мастер»', 835 => 'Neirolis', 840 => 'Т1',
    841 => 'Trafcoo (Taraf AI-Bader)', 842 => 'АФС Борлас', 845 => 'АДТ', 847 => 'test'];

// компании: id => [название, партнёр]
const COMPANIES = [17 => ['Полипластик', 8], 56 => ['СТК', 8], 65 => ['Птицефабрика', 42], 864 => ['Neirolis', 835],
    874 => ['Test', 847], 886 => ['Ростелеком', 8], 890 => ['Trafcoo', 841], 896 => ['STARINCO', 11], 900 => ['Indorama', 11]];

// новые заказчики: ключ => [название, партнёр, страна, сектор]
const NEW_COMPANIES = [
    'ulan' => ['МУП «Городские маршруты» (Улан-Удэ)', 21, RUSSIA, S_LOGISTICS],
    'ivanovo' => ['БЦ Иваново', 21, RUSSIA, S_RETAIL],
    'kundrat' => ['ПК КУНДРАТ', 21, RUSSIA, S_PROD],
    'cheese' => ['Сыроварня (уточнить)', 28, RUSSIA, S_PROD],
    'faris' => ['Faris Al-Reem', 841, SAUDI, S_OTHER],
    'norilsk' => ['Норильск промка (временное)', 13, RUSSIA, S_PROD],
];

// КП: номер => [компания сейчас, партнёр сейчас, компания после (id или ключ NEW_COMPANIES), партнёр после]
const PROPOSALS = [
    'AK678' => [896, 11, 900, 11],
    'PG668' => [56, 8, 'ulan', 21],
    'PG669' => [56, 8, 'ivanovo', 21],
    'PG672' => [56, 21, 'kundrat', 21],
    'AM644' => [886, 8, 'cheese', 28],
    'AK655' => [890, 841, 'faris', 841],
    'AA497' => [864, 835, 65, 42],
    'PG617' => [874, 13, 'norilsk', 13],
    'PG627' => [56, 8, 56, 21],
    'PG628' => [56, 8, 56, 21],
];
// старые редакции КП, оставшиеся на удаляемой карточке, — к заказчику последней редакции:
// номер => [компания старых редакций, компания последней, компании всех редакций по порядку]
const ALIGN = ['AK656' => [896, 897, [896, 897, 897]]];
const RENAME = [65, 'ООО Советская агрофирма'];
const TEST_PROPOSAL = 'AA670';
const TEST_CONTRACT = [59, '111'];
const TEST_PROJECT = [6, 'test2', 13];   // проект, его конфигурация
const DELETE_COMPANIES = [874, 17, 896, 886];
const DELETE_PARTNERS = [847, 25];
const CRM_LINKS = [55 => ['Борлас АФС', 842], 101 => ['1С:Северо-Запад', 41], 437 => ["ООО 'АДТ Секьюрити Солюшнз'", 845], 247 => ['Т1 Интеграция', 840]];

$problems = [];
$check = function (bool $ok, string $message) use (&$problems) { if (!$ok) $problems[] = $message; };

/*** 1. Сверка исходного состояния ***/

foreach (PARTNERS as $id => $name) $check(DB::table('partners')->where('id', $id)->value('name') === $name, "партнёр #{$id}: ожидался «{$name}»");
foreach (COMPANIES as $id => [$name, $pid]) {
    $row = DB::table('companies')->where('id', $id)->first(['name', 'partner_id']);
    $check($row && $row->name === $name && (int) $row->partner_id === $pid, "компания #{$id}: ожидалась «{$name}» у партнёра #{$pid}");
}
foreach (NEW_COMPANIES as [$name, $pid]) $check(!DB::table('companies')->where('name', $name)->exists(), "компания «{$name}» уже есть");

$groups = [];
foreach (PROPOSALS + [TEST_PROPOSAL => [874, 8, null, null]] as $number => [$company, $partner]) {
    $found = DB::table('proposals')->where('number', $number)->distinct()->pluck('group');
    $check($found->count() === 1, "КП {$number}: групп " . $found->count());
    if ($found->count() !== 1) continue;
    $groups[$number] = $found->first();
    $pairs = DB::table('proposals')->where('group', $groups[$number])->get(['company_id', 'partner_id'])
        ->map(fn($r) => (int) $r->company_id . '/' . (int) $r->partner_id)->unique()->values()->all();
    $check($pairs === ["{$company}/{$partner}"], "КП {$number}: компания/партнёр " . implode(',', $pairs) . ", ожидалось {$company}/{$partner}");
}

foreach (ALIGN as $number => [$from, $to, $sequence]) {
    $actual = DB::table('proposals')->where('number', $number)->orderBy('id')->pluck('company_id')->map(fn($id) => (int) $id)->all();
    $check($actual === $sequence, "КП {$number}: компании редакций " . implode(',', $actual) . ', ожидалось ' . implode(',', $sequence));
}

$contract = DB::table('contracts')->where('id', TEST_CONTRACT[0])->first(['number', 'partner_id', 'company_id']);
$check($contract && $contract->number === TEST_CONTRACT[1] && (int) $contract->partner_id === 847 && $contract->company_id === null, 'договор #59: ожидался «111» у партнёра test');
$check(!DB::table('contract_specifications')->where('contract_id', TEST_CONTRACT[0])->exists(), 'у договора #59 есть спецификации');
$project = DB::table('projects')->where('id', TEST_PROJECT[0])->first(['name', 'company_id']);
$check($project && $project->name === TEST_PROJECT[1] && (int) $project->company_id === 874, 'проект #6: ожидался «test2» у компании Test');
$check(DB::table('project_configurations')->where('project_id', TEST_PROJECT[0])->pluck('id')->all() === [TEST_PROJECT[2]], 'у проекта #6 не одна конфигурация #13');
$check(DB::table('project_configurations')->where('id', TEST_PROJECT[2])->value('contract_specification_id') === null, 'конфигурация #13 привязана к спецификации');

$bx = DB::connection('bitrix');
foreach (CRM_LINKS as $crm => [$title, $pid]) {
    $check($bx->table('crm_company')->where('id', $crm)->value('title') === $title, "компания Б24 #{$crm}: ожидалась «{$title}»");
    $check(!DB::table('partner_crm_companies')->where('crm_company_id', $crm)->exists(), "компания Б24 #{$crm} уже сопоставлена");
}

// все ссылки на компанию или партнёра — по всем колонкам *_company_id / *_partner_id базы
$cols = collect(DB::select("SELECT TABLE_NAME t, COLUMN_NAME c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND (COLUMN_NAME LIKE '%partner_id' OR COLUMN_NAME LIKE '%company_id') AND COLUMN_NAME <> 'crm_company_id'"));
$refs = function (string $kind, int $id) use ($cols): array {
    $out = [];
    foreach ($cols->filter(fn($r) => str_ends_with($r->c, $kind . '_id')) as $r) {
        if ($r->t === ($kind === 'company' ? 'companies' : 'partners')) continue;
        $n = DB::table($r->t)->where($r->c, $id)->count();
        if ($n) $out["{$r->t}.{$r->c}"] = $n;
    }
    return $out;
};
// до правки на удаляемых — только то, что сценарий уберёт сам
$check($refs('company', 17) === [], 'на «Полипластик» #17 есть ссылки');
$check(array_keys($refs('company', 896)) === ['proposals.company_id'], 'на STARINCO #896 есть что-то кроме КП');
$check(array_keys($refs('company', 886)) === ['proposals.company_id'], 'на Ростелеком #886 есть что-то кроме КП');
$check($refs('partner', 25) === [], 'на партнёра T1 #25 есть ссылки');

if ($problems) {
    echo "Исходное состояние не совпало — ничего не менял:\n  - ", implode("\n  - ", $problems), "\n";
    exit(1);
}
echo "Сверка исходного состояния: ок\n", $apply ? "РЕЖИМ: выполнить\n" : "РЕЖИМ: пробный, с откатом\n";

/*** 2. Правка ***/

$logsBefore = (int) DB::table('entity_logs')->max('id');

DB::beginTransaction();
try {
    // перенос — не правка пользователя: дату изменения КП не трогаем
    $move = function ($model, array $attrs) {
        $model->timestamps = false;
        foreach ($attrs as $key => $value) $model->{$key} = $value;
        $model->save();
    };

    $created = [];
    foreach (NEW_COMPANIES as $key => [$name, $pid, $country, $sector]) {
        $company = new Company(['active' => 1, 'name' => $name]);
        $company->partner_id = $pid;
        $company->country_id = $country;
        $company->sector_id = $sector;
        $company->save();
        $created[$key] = $company->id;
        echo "новый заказчик #{$company->id} «{$name}» у «" . PARTNERS[$pid] . "»\n";
    }

    foreach (PROPOSALS as $number => [$from, $fromPartner, $to, $toPartner]) {
        $to = is_string($to) ? $created[$to] : $to;
        $rows = Proposal::where('group', $groups[$number])->get();
        foreach ($rows as $row) $move($row, ['company_id' => $to, 'partner_id' => $toPartner]);
        echo "КП {$number}: редакций {$rows->count()}, компания #{$from} → #{$to}", $fromPartner !== $toPartner ? ', партнёр «' . PARTNERS[$fromPartner] . '» → «' . PARTNERS[$toPartner] . '»' : '', "\n";
    }

    foreach (ALIGN as $number => [$from, $to]) {
        $rows = Proposal::where('number', $number)->where('company_id', $from)->get();
        foreach ($rows as $row) $move($row, ['company_id' => $to]);
        echo "КП {$number}: старых редакций {$rows->count()} с #{$from} → #{$to}, к последней редакции\n";
    }

    $rename = Company::findOrFail(RENAME[0]);
    $rename->name = RENAME[1];
    $rename->save();
    echo "компания #" . RENAME[0] . " «Птицефабрика» → «" . RENAME[1] . "»\n";

    // тестовые данные
    Proposal::where('group', $groups[TEST_PROPOSAL])->get()->each->delete();
    echo "КП " . TEST_PROPOSAL . " «test 2» удалено\n";
    DB::table('project_configurations')->where('id', TEST_PROJECT[2])->delete();
    DB::table('projects')->where('id', TEST_PROJECT[0])->delete();
    echo "проект «test2» и его конфигурация удалены\n";
    Contract::findOrFail(TEST_CONTRACT[0])->delete();
    echo "договор «111» партнёра test удалён\n";

    foreach (DELETE_COMPANIES as $id) {
        $left = $refs('company', $id);
        if ($left) throw new RuntimeException("на компании #{$id} остались ссылки: " . json_encode($left));
        Company::findOrFail($id)->delete();
        echo "компания #{$id} «" . COMPANIES[$id][0] . "» удалена\n";
    }
    foreach (DELETE_PARTNERS as $id) {
        $left = $refs('partner', $id);
        if ($left) throw new RuntimeException("на партнёре #{$id} остались ссылки: " . json_encode($left));
        Partner::findOrFail($id)->delete();
        echo "партнёр #{$id} «" . PARTNERS[$id] . "» удалён\n";
    }

    foreach (CRM_LINKS as $crm => [$title, $pid]) {
        PartnerCrmCompany::create(['partner_id' => $pid, 'crm_company_id' => $crm, 'created_at' => now()]);
        echo "Б24 #{$crm} «{$title}» ↔ партнёр «" . PARTNERS[$pid] . "»\n";
    }

    EntityLogService::flush();

    /*** 3. Проверка результата ***/
    echo "\nпосле:\n";
    foreach ($created + ['Indorama' => 900, 'Советская агрофирма' => 65, 'СТК (карточка)' => 56, 'Neirolis' => 864] as $label => $id)
        echo "  #{$id} ", DB::table('companies')->where('id', $id)->value('name'), ": групп КП ", DB::table('proposals')->where('company_id', $id)->distinct()->count('group'), "\n";
    echo "  партнёров test/T1: ", DB::table('partners')->whereIn('id', DELETE_PARTNERS)->count(), ", компаний удалено: ", count(DELETE_COMPANIES) - DB::table('companies')->whereIn('id', DELETE_COMPANIES)->count(), "\n";
    echo "  записей журнала: ", DB::table('entity_logs')->where('id', '>', $logsBefore)->count(), "\n";

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
