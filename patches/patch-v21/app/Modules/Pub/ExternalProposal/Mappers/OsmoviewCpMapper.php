<?php

namespace App\Modules\Pub\ExternalProposal\Mappers;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Constant\Models\Constant;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use App\Modules\Pub\ExternalProposal\Models\ExternalProposal;
use App\Modules\Pub\ExternalProposal\Models\ExternalScenarioMap;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Requests\ProposalRequest;
use App\Modules\Pub\Scenario\Models\Scenario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Перенос КП OSMOVIEW CP в наше КП (patch v21).
 *
 * Два шага. `preview()` показывает, что будет создано: партнёр и компания по
 * нестрогому совпадению названий, сценарии через `external_scenario_map` /
 * совпадение названий, работы, цены по нашему прайсу. `request()` собирает
 * `Illuminate\Http\Request` в формате формы создания КП (`create.blade.php`,
 * правила `ProposalRequest`) — дальше работает `ProposalRepository::create()`,
 * поэтому расчёт совпадает с ручным вводом.
 *
 * Цены: у Алексея в ответе нет итогов — стандартные позиции считаются по
 * нашему прайсу (`scenarios.cost_rules`, `platform_cost_per_year`: порог по
 * числу камер, `u` бессрочно, `y` за год), `manualPrice` / `platformManualPrice`
 * идут точечной ценой. В валютном КП прайс делится на `exchangeRate`.
 */
class OsmoviewCpMapper
{
    public const SOURCE = ExternalProposal::SOURCE_OSMOVIEW_CP;

    /** Служебный сценарий для кастомных позиций (scenarioId "0") */
    public const SCENARIO_CUSTOM_ID = 97;

    /** НДС по умолчанию, если константы нет */
    public const NDS_DEFAULT = 22;

    /** Выше этой цены ручная цена в валютном КП считается рублёвой */
    public const MANUAL_RUB_THRESHOLD = 3000;

    /** Подписи полей hardwareRequirements в блоке «Оборудование» */
    public const HARDWARE_LABELS = [
        'ru' => [
            'cpu' => 'Процессор (CPU)',
            'gpu' => 'Графический ускоритель (GPU)',
            'ram' => 'Оперативная память (RAM)',
            'storage' => 'Накопители',
            'os' => 'Операционная система',
            'access' => 'Доступ',
            'remoteAccess' => 'Удалённый доступ',
        ],
        'en' => [
            'cpu' => 'CPU',
            'gpu' => 'GPU',
            'ram' => 'RAM',
            'storage' => 'Storage',
            'os' => 'Operating system',
            'access' => 'Access',
            'remoteAccess' => 'Remote access',
        ],
    ];

    /** Группы работ по префиксу id позиции */
    public const WORK_GROUPS = [
        'ru' => [
            '3' => 'Разовые работы',
            '4' => 'Поддержка платформы / обучение',
            '5' => 'Гарантия и техподдержка',
            'AI' => 'Дообучение нейросервисов',
        ],
        'en' => [
            '3' => 'One time payment',
            '4' => 'Platform support / training',
            '5' => 'Warranty and technical support',
            'AI' => 'Further training of the neuroservices',
        ],
    ];

    /** Слова, которые не участвуют в сравнении названий компаний */
    private const NAME_STOP_WORDS = [
        'ооо', 'зао', 'оао', 'пао', 'ао', 'ип', 'нко', 'гк', 'ltd', 'llc', 'inc', 'gmbh', 'co', 'sl', 'sa', 'srl', 'limited', 'company',
    ];

    /*** PREVIEW ***/

    /**
     * Что будет создано при переносе
     *
     * @param ExternalProposal $external
     * @param array $choices выбор пользователя: partner, company, manager, number, scenario[i]
     * @return array
     */
    public static function preview(ExternalProposal $external, array $choices = []): array
    {
        $payload = $external->payload ?? [];
        $warnings = [];

        $lang = static::lang($payload);
        $currency = static::currency($payload);
        $rate = static::rate($payload, $currency);
        $nds = !empty($payload['isVatIncluded']) ? (int) (Constant::get('nds_rate') ?? static::NDS_DEFAULT) : 0;
        $vat = !empty($payload['isVatIncluded']);

        [$period, $period_mixed] = static::period($payload);
        if ($period_mixed) {
            $warnings[] = 'У позиций разные типы лицензий (годовые и бессрочные) — вариант выбран по большинству: ' . ($period === 'unlimited' ? 'бессрочный' : 'годовой') . '.';
        }

        $client_discount = (float) ($payload['clientDiscount'] ?? 0);
        if ($client_discount > 0) {
            $warnings[] = "Скидка клиента на весь проект {$client_discount}% не переносится — у нас нет общей скидки, примените вручную.";
        }

        if ($currency !== Currency::CURRENCY_DEFAULT && empty($payload['exchangeRate'])) {
            $warnings[] = "В ответе нет курса для {$currency}, взят последний курс из справочника: {$rate}.";
        }

        // ПАРТНЁР И КОМПАНИЯ
        $partner_source = trim((string) ($payload['recipientCompany'] ?? ''));
        $partner = null; $partner_match = null;
        if (!empty($choices['partner'])) {
            $partner = Partner::find((int) $choices['partner']);
            $partner_match = $partner ? 'choice' : null;
        }
        if (empty($partner) && $partner_source !== '') {
            [$partner, $partner_match] = static::matchPartner($partner_source);
        }

        $company_source = trim((string) ($payload['customerName'] ?? $external->customer ?? ''));
        $company = null; $company_match = null;
        if (!empty($choices['company'])) {
            $company = Company::find((int) $choices['company']);
            $company_match = $company ? 'choice' : null;
        }
        if (empty($company) && $company_source !== '') {
            [$company, $company_match] = static::matchCompany($company_source, $partner?->id);
        }
        // партнёра по адресату не нашли — берём владельца найденной компании
        if (empty($partner) && !empty($company?->partner_id)) {
            $partner = $company->partner ?? Partner::find($company->partner_id);
            if (!empty($partner)) {
                $partner_match = 'company';
                if ($partner_source === '') $partner_source = $company_source;
            }
        }

        if (!empty($company) && !empty($partner) && $company->partner_id && $company->partner_id !== $partner->id) {
            $warnings[] = 'Компания «' . $company->name . '» числится у другого партнёра (' . ($company->partner?->name ?? '#' . $company->partner_id) . ').';
        }

        // ПЛАТФОРМА
        $platform_count = (int) ($payload['totalCameras'] ?? $external->cameras ?? 0);
        $platform_manual = static::manual(static::number($payload['platformManualPrice'] ?? null), $currency, $rate, $warnings, 'Платформа');
        $platform_rules = static::platformRules();
        $platform_cost = $platform_manual !== null
            ? $platform_manual
            : static::convert(static::priceByRules($platform_rules, $platform_count, $period), $rate);
        $platform_discount = static::percent($payload['platformDiscount'] ?? 0);

        $platform = [
            'count' => $platform_count,
            'discount' => $platform_discount,
            'cost' => $platform_cost,
            'cost_source' => $platform_manual !== null ? 'manual' : 'price',
            'total' => round($platform_cost * $platform_count * (1 - $platform_discount / 100), 2),
        ];

        // СЦЕНАРИИ
        $scenarios = [];
        $neuro_discount = static::percent($payload['neuroDiscount'] ?? 0);
        $items = is_array($payload['items'] ?? null) ? array_values($payload['items']) : [];
        $map = ExternalScenarioMap::where('source', static::SOURCE)->get()->keyBy('external_scenario_id');
        $catalog = static::catalog();

        foreach ($items as $index => $item) {
            $i = $index + 1;
            $external_id = trim((string) ($item['scenarioId'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));
            $count = (int) ($item['cameras'] ?? 0);
            $retrain = ($item['customizationType'] ?? 'none') === 'retrain';

            $scenario = null; $match = null;
            if (!empty($choices['scenario'][$i])) {
                $scenario = $catalog->get((int) $choices['scenario'][$i]) ?? Scenario::find((int) $choices['scenario'][$i]);
                $match = $scenario ? 'choice' : null;
            }
            if (empty($scenario)) {
                [$scenario, $match] = static::matchScenario($external_id, $name, $map, $catalog);
            }

            $manual = static::manual(static::number($item['manualPrice'] ?? null), $currency, $rate, $warnings, "Позиция {$i} «{$name}»");
            if ($manual !== null) {
                $cost = $manual; $cost_source = 'manual';
            } elseif ($scenario && (int) $scenario->id !== static::SCENARIO_CUSTOM_ID) {
                $cost = static::convert(static::priceByRules($scenario->cost_rules, $count, $period), $rate);
                $cost_source = 'price';
            } else {
                $cost = 0; $cost_source = 'none';
                if ($scenario) $warnings[] = "Позиция {$i} «{$name}»: кастомный сценарий без manualPrice — цена 0, укажите вручную после переноса.";
            }

            $scenarios[] = [
                'i' => $i,
                'external_id' => $external_id,
                'name' => $name,
                'count' => $count,
                'retrain' => $retrain,
                'comment' => trim((string) ($item['comments'] ?? '')),
                'scenario_id' => $scenario?->id,
                'scenario_name' => $scenario?->name,
                'match' => $match,
                'cost' => $cost,
                'cost_source' => $cost_source,
                'discount' => $neuro_discount,
                'total' => round($cost * $count * (1 - $neuro_discount / 100), 2),
            ];
        }

        // РАБОТЫ
        $works = static::works($payload, $lang, $warnings, $currency, $rate);

        // ОБОРУДОВАНИЕ
        $hardware = static::hardware($external, ['lang' => $lang]);

        $totals = [
            'platform' => $platform['total'],
            'neuro' => round(collect($scenarios)->sum('total'), 2),
            'works' => round(collect($works)->sum('total'), 2),
        ];
        $totals['total'] = round($totals['platform'] + $totals['neuro'] + $totals['works'], 2);
        $totals['nds'] = $vat ? round($totals['total'] * $nds / 100, 2) : 0;

        $unresolved = collect($scenarios)->whereNull('scenario_id')->pluck('i')->all();

        return [
            'external' => $external,
            'number_external' => $external->external_number ?: (string) ($payload['id'] ?? ''),
            'number_default' => !empty($choices['number']) ? (string) $choices['number'] : static::nextNumber(),
            'name' => Str::limit(trim((string) ($payload['projectName'] ?? $external->name ?? '')), 200, ''),
            'date' => static::date($payload['createdAt'] ?? null, $external->created_at_remote),
            'lang' => $lang,
            'currency' => $currency,
            'rate' => $rate,
            'nds' => $nds,
            'vat' => $vat,
            'hardware' => $hardware,
            'period' => $period,
            'period_mixed' => $period_mixed,
            'partner' => $partner,
            'partner_match' => $partner_match,
            'partner_source' => $partner_source,
            'company' => $company,
            'company_match' => $company_match,
            'company_source' => $company_source,
            'manager' => (int) ($choices['manager'] ?? auth()->id() ?? 0),
            'partner_discount' => static::percent($payload['partnerDiscount'] ?? 0),
            'platform' => $platform,
            'scenarios' => $scenarios,
            'works' => $works,
            'totals' => $totals,
            'warnings' => $warnings,
            'unresolved' => $unresolved,
            'ready' => !empty($partner) && !empty($company) && empty($unresolved),
        ];
    }

    /*** REQUEST ***/

    /**
     * Сборка Request в формате формы создания КП
     *
     * @param ExternalProposal $external
     * @param array $choices
     * @param array|null $preview уже собранный preview (чтобы не считать дважды)
     * @return Request
     * @throws \RuntimeException если данных не хватает или они не проходят правила ProposalRequest
     */
    public static function request(ExternalProposal $external, array $choices, ?array $preview = null): Request
    {
        $preview = $preview ?? static::preview($external, $choices);
        $payload = $external->payload ?? [];

        if (empty($preview['partner'])) throw new \RuntimeException('Не выбран партнёр');
        if (empty($preview['company'])) throw new \RuntimeException('Не выбрана компания');
        if (!empty($preview['unresolved'])) throw new \RuntimeException('Не сопоставлены сценарии: позиции ' . implode(', ', $preview['unresolved']));

        $vat = $preview['vat'] ? 1 : 0;
        $lang = $preview['lang'];

        $data = [
            'name' => $preview['name'] !== '' ? $preview['name'] : ('КП ' . $preview['number_external']),
            'name_alt' => $preview['number_external'] ?: null,
            'date' => $preview['date'],
            'number' => (string) ($choices['number'] ?? $preview['number_default']),
            'manager' => (int) $preview['manager'],
            'company' => (int) $preview['company']->id,
            'partner' => (int) $preview['partner']->id,
            'nds' => $preview['nds'],
            'lang' => $lang,

            'period_main' => 1,
            'period' => [1 => $preview['period']],
            'period_active' => [1 => 1],
            'period_value' => [1 => $preview['period'] === 'unlimited' ? null : 1],
            'partner_platform_discount' => [1 => $preview['partner_discount']],
            'partner_neuro_discount' => [1 => $preview['partner_discount']],
            'partner_soft_discount' => [1 => $preview['partner_discount']],
        ];

        // ПЛАТФОРМА — одна позиция
        $platform = $preview['platform'];
        $data['platform'] = [1 => [
            'cb_process' => 1,
            'extended' => __('proposal.textarea__platform_extended', [], $lang),
            'notice' => __('proposal.textarea__platform_notice', [], $lang),
            'sort' => 0,
        ]];
        $data['platform_cell'] = [1 => [1 => [
            'active' => 1,
            'count' => max(0, (int) $platform['count']),
            'discount' => (int) $platform['discount'],
            'nds' => $vat,
        ]]];
        $data['platform_cost'] = [1 => [1 => static::money($platform['cost'])]];

        // СЦЕНАРИИ
        $data['scenario'] = $data['cell'] = $data['cost'] = [];
        foreach ($preview['scenarios'] as $row) {
            $i = $row['i'];
            $scenario_name = (string) $row['scenario_name'];

            $comment = $row['comment'];
            if ($row['retrain']) {
                $comment = trim($comment . "\n" . ($lang === 'en' ? 'Neural service retraining (fine-tuning)' : 'Дообучение нейросервиса'));
            }

            $data['scenario'][$i] = [
                'cb_process' => 1,
                'scenario' => (int) $row['scenario_id'],
                'real_sync' => 1,
                // его название показываем как альтернативное, если оно отличается от нашего
                'mnemonic_name' => static::normalizeName($row['name']) !== static::normalizeName($scenario_name) ? $row['name'] : null,
                'comment' => $comment !== '' ? nl2br(e($comment)) : null,
                'sort' => $i * 100,
            ];
            $data['cell'][$i] = [1 => [
                'active' => 1,
                'count' => max(0, (int) $row['count']),
                'discount' => (int) $row['discount'],
                'nds' => $vat,
            ]];
            $data['cost'][$i] = [1 => static::money($row['cost'])];
        }

        // РАБОТЫ
        $data['work'] = $data['work_cell'] = [];
        foreach ($preview['works'] as $row) {
            $i = $row['i'];
            $data['work'][$i] = [
                'cb_process' => 1,
                'extended' => $row['extended'],
                'notice' => $row['notice'],
                'group' => $row['group'],
                'sort' => $i * 100,
            ];
            $data['work_cell'][$i] = [1 => [
                'active' => 1,
                'cost' => static::money($row['cost']),
                'count' => $row['count'],
                'discount' => (int) $row['discount'],
                'discount_partner' => 0,
                'nds' => $vat,
            ]];
        }

        // проверяем теми же правилами, что и форму создания
        $validator = Validator::make($data, (new ProposalRequest())->rules());
        if ($validator->fails()) {
            throw new \RuntimeException('Данные не проходят правила формы КП: ' . implode('; ', $validator->errors()->all()));
        }

        return Request::create('/', 'POST', $data);
    }

    /**
     * Текст блока «Задачи» (task варианта и КП): исходный запрос + описание,
     * цели, результат, техтребования, условия — чтобы ничего не потерять
     *
     * @param ExternalProposal $external
     * @param array $preview
     * @return string HTML
     */
    /**
     * Блок «Оборудование»: сервер из hardwareRequirements и камеры из
     * cameraTypes. Поля хранятся как HTML редактора (см. попап
     * `pub/hardware/boxes/add.blade.php`), поэтому размечаем списком.
     *
     * @return array<int, array{name: string, count: ?string, params: ?string, sort: int}>
     */
    public static function hardware(ExternalProposal $external, array $preview = []): array
    {
        $payload = $external->payload ?? [];
        $lang = $preview['lang'] ?? static::lang($payload);
        $labels = static::HARDWARE_LABELS[$lang] ?? static::HARDWARE_LABELS['ru'];
        $rows = [];
        $sort = 0;

        // сервер: параметры кластера одним списком
        $requirements = is_array($payload['hardwareRequirements'] ?? null) ? $payload['hardwareRequirements'] : [];
        $items = [];
        foreach ($requirements as $key => $value) {
            $value = trim((string) $value);
            if ($value === '') continue;
            $items[] = '<li><p><strong>' . e($labels[$key] ?? Str::headline((string) $key)) . ':</strong>&nbsp;' . e($value) . '</p></li>';
        }
        if ($items) {
            $count = (int) ($payload['serverCount'] ?? 0);
            $rows[] = [
                'name' => '<p>' . ($lang === 'en' ? 'Server' : 'Сервер') . '</p>',
                'count' => $count > 0 ? '<p>' . $count . '</p>' : null,
                'params' => '<ul>' . implode('', $items) . '</ul>',
                'sort' => $sort++,
            ];
        }

        // камеры: по типам, а если типов нет — общие требования одной строкой
        $types = is_array($payload['cameraTypes'] ?? null) ? array_values($payload['cameraTypes']) : [];
        if (!$types && trim((string) ($payload['cameraRequirements'] ?? '')) !== '') {
            $types = [[
                'type' => $lang === 'en' ? 'IP camera' : 'IP-камера',
                'count' => (int) ($payload['totalCameras'] ?? 0),
                'specs' => $payload['cameraRequirements'],
            ]];
        }
        foreach ($types as $type) {
            $name = trim((string) ($type['type'] ?? '')) ?: ($lang === 'en' ? 'IP camera' : 'IP-камера');
            $count = (int) ($type['count'] ?? 0);
            $rows[] = [
                'name' => '<p>' . e($name) . '</p>',
                'count' => $count > 0 ? '<p>' . $count . '</p>' : null,
                'params' => static::specs((string) ($type['specs'] ?? '')),
                'sort' => $sort++,
            ];
        }

        return $rows;
    }

    /**
     * «Разрешение: 4 Мп, Частота кадров: 25 к/с» → маркированный список
     */
    private static function specs(string $specs): ?string
    {
        $specs = trim($specs);
        if ($specs === '') return null;

        $parts = array_values(array_filter(array_map('trim', (array) preg_split('/,\s+(?=[^,:]{2,40}:)/u', $specs))));
        if (count($parts) < 2) return '<p>' . nl2br(e($specs)) . '</p>';

        $items = [];
        foreach ($parts as $part) {
            $part = rtrim($part, '.');
            if (str_contains($part, ':')) {
                [$label, $value] = explode(':', $part, 2);
                $items[] = '<li><p><strong>' . e(trim($label)) . ':</strong>&nbsp;' . e(trim($value)) . '</p></li>';
            } else {
                $items[] = '<li><p>' . e($part) . '</p></li>';
            }
        }

        return '<ul>' . implode('', $items) . '</ul>';
    }

    public static function task(ExternalProposal $external, array $preview): string
    {
        $payload = $external->payload ?? [];
        $parts = [];

        $parts[] = '<p><b>Перенесено из OSMOVIEW CP</b> — КП ' . e($preview['number_external']) . ', создано ' . e($preview['date'])
            . ', валюта ' . e($preview['currency']) . ($preview['currency'] !== Currency::CURRENCY_DEFAULT ? ' (курс ' . e($preview['rate']) . ')' : '')
            . ', лицензии ' . ($preview['period'] === 'unlimited' ? 'бессрочные' : 'годовые') . '.</p>';

        $sections = [
            'Исходный запрос' => $payload['projectRawInput'] ?? null,
            'Описание проекта' => $payload['projectDescription'] ?? null,
            'Цели проекта' => $payload['projectGoals'] ?? null,
            'Ожидаемый результат' => $payload['projectResult'] ?? null,
            'Вступление' => $payload['introText'] ?? null,
            'Требования к камерам' => $payload['cameraRequirements'] ?? null,
        ];

        foreach ($sections as $title => $text) {
            $text = trim((string) $text);
            if ($text === '') continue;
            $parts[] = '<p><b>' . e($title) . '</b><br>' . nl2br(e($text)) . '</p>';
        }

        // требования к железу и типы камер уходят в блок «Оборудование» (hardware())

        $tech = array_filter([
            'GPU' => $payload['gpuModel'] ?? null,
            'FPS' => $payload['fps'] ?? null,
            'Аналитик на камеру' => $payload['analyticsPerCamera'] ?? null,
            'Серверов' => $payload['serverCount'] ?? null,
            'Отказоустойчивость' => isset($payload['isHighAvailability']) ? ($payload['isHighAvailability'] ? 'да' : 'нет') : null,
            'Один узел' => isset($payload['isSingleNodeDeployment']) ? ($payload['isSingleNodeDeployment'] ? 'да' : 'нет') : null,
        ], fn($v) => $v !== null && $v !== '');

        $terms = array_filter([
            'Аванс' => isset($payload['paymentPrepaymentPercent']) ? $payload['paymentPrepaymentPercent'] . '%' : null,
            'Остаток' => isset($payload['paymentPostpaymentPercent']) ? $payload['paymentPostpaymentPercent'] . '%' : null,
            'Срок сдачи' => isset($payload['deliveryWeeks']) ? $payload['deliveryWeeks'] . ' нед.' : null,
            'Скидка клиента' => !empty($payload['clientDiscount']) ? $payload['clientDiscount'] . '% (не перенесена)' : null,
            'Скидка партнёра' => !empty($payload['partnerDiscount']) ? $payload['partnerDiscount'] . '%' : null,
            'Обучение' => !empty($payload['includeTraining']) ? ('да, ' . ($payload['trainingPrice'] ?? '?')) : null,
            'Гарантия' => !empty($payload['includeWarranty']) ? (($payload['warrantyMonths'] ?? '?') . ' мес.' . (!empty($payload['includeWarrantySecondYear']) ? ', со 2-го года ' . ($payload['warrantyPrice'] ?? 0) : '')) : null,
            'Налоговый режим' => $payload['taxMode'] ?? null,
            'Адресат' => trim(implode(', ', array_filter([$payload['recipientCompany'] ?? null, $payload['recipientName'] ?? null, $payload['recipientPosition'] ?? null]))) ?: null,
            'Отправитель' => trim(implode(', ', array_filter([$payload['senderName'] ?? null, $payload['senderPosition'] ?? null]))) ?: null,
        ], fn($v) => $v !== null && $v !== '');

        $charges = $payload['additionalCharges'] ?? null;
        if (is_array($charges) && count($charges)) {
            $terms['Доп. сборы'] = collect($charges)->map(fn($c) => ($c['description'] ?? '') . ' ' . ($c['ratePercent'] ?? '') . '%')->implode('; ');
        }

        foreach (['Техника' => $tech, 'Условия' => $terms] as $title => $list) {
            if (empty($list)) continue;
            $lines = [];
            foreach ($list as $key => $value) $lines[] = '<li>' . e($key) . ': ' . e($value) . '</li>';
            $parts[] = '<p><b>' . $title . '</b></p><ul>' . implode('', $lines) . '</ul>';
        }

        return implode('', $parts);
    }

    /*** MATCHING ***/

    /**
     * Партнёр по названию адресата (нестрого)
     *
     * @param string $name
     * @return array [Partner|null, 'exact'|'fuzzy'|null]
     */
    public static function matchPartner(string $name): array
    {
        return static::matchByName(Partner::orderBy('name')->get(['id', 'name', 'active']), $name);
    }

    /**
     * Компания по названию заказчика (нестрого); при равных — компания выбранного партнёра
     *
     * @param string $name
     * @param int|null $partner_id
     * @return array [Company|null, 'exact'|'fuzzy'|null]
     */
    public static function matchCompany(string $name, ?int $partner_id = null): array
    {
        $companies = Company::orderBy('name')->get(['id', 'name', 'partner_id', 'active']);

        if ($partner_id) {
            [$company, $how] = static::matchByName($companies->where('partner_id', $partner_id), $name);
            if ($company) return [$company, $how];
        }

        return static::matchByName($companies, $name);
    }

    /**
     * Сценарий: карта соответствий → точное название → похожее; "0" — служебный
     *
     * @param string $external_id
     * @param string $name
     * @param \Illuminate\Support\Collection|null $map карта, keyBy external_scenario_id
     * @param \Illuminate\Support\Collection|null $catalog активные сценарии, keyBy id
     * @return array [Scenario|null, 'map'|'exact'|'similar'|'custom'|null]
     */
    public static function matchScenario(string $external_id, string $name, $map = null, $catalog = null): array
    {
        $map = $map ?? ExternalScenarioMap::where('source', static::SOURCE)->get()->keyBy('external_scenario_id');
        $catalog = $catalog ?? static::catalog();

        if ($external_id !== '' && $external_id !== '0' && $map->has($external_id)) {
            $scenario = $catalog->get((int) $map[$external_id]->scenario_id) ?? Scenario::find((int) $map[$external_id]->scenario_id);
            if ($scenario) return [$scenario, 'map'];
        }

        if ($external_id === '0') {
            $scenario = $catalog->get(static::SCENARIO_CUSTOM_ID) ?? Scenario::find(static::SCENARIO_CUSTOM_ID);
            return [$scenario, $scenario ? 'custom' : null];
        }

        $needle = static::normalizeName($name);
        if ($needle === '') return [null, null];

        // точное совпадение (без регистра и лишних пробелов)
        foreach ($catalog as $scenario) {
            if (static::normalizeName($scenario->name) === $needle) return [$scenario, 'exact'];
        }

        // похожее: пересечение слов либо вложение названий
        $best = null; $best_score = 0;
        $needle_words = static::words($name);
        foreach ($catalog as $scenario) {
            if ((int) $scenario->id === static::SCENARIO_CUSTOM_ID) continue;

            $hay = static::normalizeName($scenario->name);
            $score = 0;
            if (mb_strlen($needle) >= 8 && (str_contains($hay, $needle) || str_contains($needle, $hay))) {
                $score = 0.9;
            } else {
                $words = static::words($scenario->name);
                $union = count(array_unique(array_merge($needle_words, $words)));
                $score = $union ? count(array_intersect($needle_words, $words)) / $union : 0;
            }

            if ($score > $best_score) {
                $best_score = $score; $best = $scenario;
            }
        }

        return $best_score >= 0.5 ? [$best, 'similar'] : [null, null];
    }

    /**
     * Нестрогое совпадение названия по коллекции моделей с полем name
     *
     * @param \Illuminate\Support\Collection $rows
     * @param string $name
     * @return array [model|null, 'exact'|'fuzzy'|null]
     */
    private static function matchByName($rows, string $name): array
    {
        $needle = static::normalizeCompany($name);
        if ($needle === '') return [null, null];

        foreach ($rows as $row) {
            if (static::normalizeCompany($row->name) === $needle) return [$row, 'exact'];
        }

        $best = null; $best_score = 0;
        foreach ($rows as $row) {
            $hay = static::normalizeCompany($row->name);
            if ($hay === '') continue;

            if (mb_strlen($needle) >= 4 && (str_starts_with($hay, $needle) || str_starts_with($needle, $hay))) {
                $score = 0.9;
            } else {
                similar_text($needle, $hay, $percent);
                $score = $percent / 100;
            }

            if ($score > $best_score) {
                $best_score = $score; $best = $row;
            }
        }

        return $best_score >= 0.85 ? [$best, 'fuzzy'] : [null, null];
    }

    /**
     * Нормализация названия сценария: регистр, пробелы, пунктуация
     */
    public static function normalizeName(?string $name): string
    {
        $name = mb_strtolower(trim((string) $name));
        $name = str_replace(['ё'], ['е'], $name);
        $name = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name);

        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    /**
     * Нормализация названия компании: без регистра, пробелов, кавычек и форм собственности
     */
    public static function normalizeCompany(?string $name): string
    {
        $name = static::normalizeName($name);
        $words = array_filter(explode(' ', $name), fn($w) => $w !== '' && !in_array($w, self::NAME_STOP_WORDS, true));

        return implode('', $words);
    }

    /**
     * Слова названия для сравнения (короткие и служебные отбрасываются)
     */
    private static function words(?string $name): array
    {
        $words = explode(' ', static::normalizeName($name));

        return array_values(array_unique(array_filter($words, fn($w) => mb_strlen($w) >= 3)));
    }

    /*** PRICES ***/

    /**
     * Цена за единицу по правилам прайса: наибольший порог ≤ count
     *
     * @param array|null $rules {"1":{"u":..,"y":..},"11":...}
     * @param int $count
     * @param string $period unlimited|year
     * @return float
     */
    public static function priceByRules(?array $rules, int $count, string $period): float
    {
        if (empty($rules)) return 0;

        $key = $period === 'unlimited' ? 'u' : 'y';
        $best = null; $best_threshold = null;

        foreach ($rules as $threshold => $rule) {
            $threshold = (int) $threshold;
            if ($best_threshold === null || ($threshold <= max(1, $count) && $threshold > $best_threshold) || ($best_threshold > max(1, $count) && $threshold < $best_threshold)) {
                $best_threshold = $threshold; $best = $rule;
            }
        }

        return (float) ($best[$key] ?? 0);
    }

    /**
     * Правила платформы из константы
     */
    public static function platformRules(): array
    {
        $rules = json_decode((string) Constant::get('platform_cost_per_year'), true);

        return is_array($rules) ? $rules : [];
    }

    /**
     * Ручная цена генератора (manualPrice / platformManualPrice): в валютных
     * КП она нередко остаётся рублёвой (порядок цен прайса — десятки тысяч),
     * тогда пересчитываем по курсу и предупреждаем. Пример: AK361 — USD,
     * manualPrice 57 600 за камеру.
     */
    private static function manual(?float $value, string $currency, float $exchange, array &$warnings, string $label): ?float
    {
        if ($value === null || $currency === Currency::CURRENCY_DEFAULT || $exchange <= 1) return $value;
        if ($value <= static::MANUAL_RUB_THRESHOLD) return $value;

        $converted = static::convert($value, $exchange);
        $warnings[] = "{$label}: цена {$value} похожа на рублёвую (КП в {$currency}) — пересчитана в {$converted} по курсу {$exchange}, проверьте.";

        return $converted;
    }

    /**
     * Рублёвая цена прайса в валюту КП
     */
    private static function convert(float $rub, float $rate): float
    {
        if ($rate <= 0 || $rate == 1) return round($rub, 2);

        return round($rub / $rate, 2);
    }

    /*** HELPERS ***/

    /**
     * Ставки работ в ответе даны в рублях, хотя КП в валюте?
     * Признак: ставка позиции кратно выше общей ставки часа (globalWorkRate
     * генератор держит в валюте КП). Пример: AK361 — USD, globalWorkRate 92,
     * а в detailedWorks rate 7000.
     */
    private static function worksInRub(array $payload, string $currency, float $exchange): bool
    {
        if ($currency === Currency::CURRENCY_DEFAULT || $exchange <= 1) return false;

        $rates = array_filter(array_map(
            fn($work) => (float) ($work['rate'] ?? 0),
            is_array($payload['detailedWorks'] ?? null) ? $payload['detailedWorks'] : []
        ));
        if (!$rates) return false;

        $global = (float) ($payload['globalWorkRate'] ?? 0);

        return $global > 0 ? max($rates) > $global * 3 : max($rates) > 1000;
    }

    /**
     * Работы из detailedWorks (+ обучение, если его нет среди работ)
     */
    private static function works(array $payload, string $lang, array &$warnings, string $currency = Currency::CURRENCY_DEFAULT, float $exchange = 1): array
    {
        $rows = [];
        $work_discount = static::percent($payload['workDiscount'] ?? 0);
        // в валютных КП генератор иногда оставляет рублёвые ставки работ — пересчитываем
        $in_rub = static::worksInRub($payload, $currency, $exchange);
        if ($in_rub) {
            $warnings[] = "Ставки работ в ответе указаны в рублях (при валюте {$currency}) — пересчитаны по курсу {$exchange}; проверьте суммы работ.";
        }
        $groups = static::WORK_GROUPS[$lang] ?? static::WORK_GROUPS['ru'];
        $works = is_array($payload['detailedWorks'] ?? null) ? array_values($payload['detailedWorks']) : [];
        $has_training = false;

        foreach ($works as $index => $work) {
            $i = $index + 1;
            $id = trim((string) ($work['id'] ?? ''));
            $name = trim((string) ($work['name'] ?? ''));
            $hours = (float) ($work['hours'] ?? 0);
            $rate = (float) ($work['rate'] ?? 0);
            $cost = (float) ($work['cost'] ?? 0);
            if ($in_rub) {
                $rate = static::convert($rate, $exchange);
                $cost = static::convert($cost, $exchange);
            }
            $tasks = array_values(array_filter(array_map('trim', (array) ($work['tasks'] ?? [])), fn($t) => $t !== ''));
            $note = trim((string) ($work['note'] ?? ''));

            $prefix = str_starts_with(strtoupper($id), 'AI') ? 'AI' : (string) Str::before($id, '.');
            if ($prefix === '4') $has_training = true;

            // часы × ставка; если сумма позиции другая (фиксированная цена) — 1 × сумма
            $notice_extra = null;
            if ($hours > 0 && $rate > 0 && abs($hours * $rate - $cost) < 1) {
                $count = $hours; $unit = $rate;
            } elseif ($cost > 0) {
                $count = 1; $unit = $cost;
                $notice_extra = ($lang === 'en' ? 'Fixed price' : 'Фиксированная цена') . ($hours > 0 ? " ({$hours} " . ($lang === 'en' ? 'h' : 'ч') . ($rate > 0 ? " × {$rate}" : '') . ')' : '');
            } else {
                $count = $hours > 0 ? $hours : 1; $unit = 0;
            }

            $extended = '<p><b>' . e($name) . '</b></p>';
            if ($tasks) {
                $extended .= '<ul>' . implode('', array_map(fn($t) => '<li>' . e($t) . '</li>', $tasks)) . '</ul>';
            }

            $notice_parts = array_filter([$note !== '' ? nl2br(e($note)) : null, $notice_extra ? e($notice_extra) : null]);

            $discount = isset($work['discount']) && $work['discount'] !== null && $work['discount'] !== '' ? static::percent($work['discount']) : $work_discount;

            $rows[] = [
                'i' => $i,
                'id' => $id,
                'name' => $name,
                'hours' => $hours,
                'rate' => $rate,
                'cost_external' => $cost,
                'tasks' => $tasks,
                'note' => $note,
                'group' => $groups[$prefix] ?? null,
                'count' => $count,
                'cost' => $unit,
                'discount' => $discount,
                'extended' => $extended,
                'notice' => $notice_parts ? implode('<br>', $notice_parts) : null,
                'total' => round($unit * $count * (1 - $discount / 100), 2),
            ];
        }

        if (!empty($payload['includeTraining']) && !$has_training) {
            $price = (float) ($payload['trainingPrice'] ?? 0);
            if ($in_rub) $price = static::convert($price, $exchange);
            $name = $lang === 'en' ? 'Platform training' : 'Обучение платформе';
            $rows[] = [
                'i' => count($rows) + 1,
                'id' => '4.0',
                'name' => $name,
                'hours' => 0,
                'rate' => 0,
                'cost_external' => $price,
                'tasks' => [],
                'note' => '',
                'group' => $groups['4'],
                'count' => 1,
                'cost' => $price,
                'discount' => $work_discount,
                'extended' => '<p><b>' . e($name) . '</b></p>',
                'notice' => null,
                'total' => round($price * (1 - $work_discount / 100), 2),
            ];
            $warnings[] = 'Обучение (includeTraining) добавлено отдельной работой — среди detailedWorks его не было.';
        }

        return $rows;
    }

    /**
     * Тип варианта по позициям: unlimited / year (по большинству) и признак смешения
     */
    public static function period(array $payload): array
    {
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        if (empty($items)) return ['year', false];

        $perpetual = collect($items)->filter(fn($item) => !empty($item['isPerpetual']))->count();
        $total = count($items);

        if ($perpetual === 0) return ['year', false];
        if ($perpetual === $total) return ['unlimited', false];

        return [$perpetual * 2 >= $total ? 'unlimited' : 'year', true];
    }

    public static function lang(array $payload): string
    {
        return strtolower((string) ($payload['language'] ?? 'ru')) === 'en' ? 'en' : 'ru';
    }

    public static function currency(array $payload): string
    {
        $currency = strtoupper(trim((string) ($payload['currency'] ?? '')));

        return $currency !== '' ? $currency : Currency::CURRENCY_DEFAULT;
    }

    /**
     * Курс валюты КП к рублю: exchangeRate из ответа, иначе последний из справочника
     */
    public static function rate(array $payload, string $currency): float
    {
        if ($currency === Currency::CURRENCY_DEFAULT) return 1;

        $rate = static::number($payload['exchangeRate'] ?? null);
        if ($rate !== null && $rate > 0) return $rate;

        $rates = CurrencyRepository::getRates();

        return (float) ($rates[$currency] ?? 1);
    }

    /**
     * Дата КП: createdAt "dd.mm.yyyy" → Y-m-d
     */
    public static function date($created_at, $fallback = null): string
    {
        $created_at = trim((string) $created_at);

        if ($created_at !== '') {
            foreach (['d.m.Y', 'Y-m-d', 'd/m/Y'] as $format) {
                try {
                    return Carbon::createFromFormat($format, $created_at)->format('Y-m-d');
                } catch (\Throwable) {
                }
            }
        }

        if (!empty($fallback)) return Carbon::parse($fallback)->format('Y-m-d');

        return now()->format('Y-m-d');
    }

    /**
     * Наш следующий номер: инициалы менеджера + max(number_int) + 1
     */
    public static function nextNumber(): string
    {
        $initials = trim((string) (auth()->user()?->initials ?? ''));

        return ($initials !== '' ? $initials : 'AA') . ((int) Proposal::max('number_int') + 1);
    }

    /**
     * Активные сценарии (+ служебный), keyBy id
     */
    public static function catalog()
    {
        return Scenario::where('active', 1)->orWhere('id', static::SCENARIO_CUSTOM_ID)->get()->keyBy('id');
    }

    private static function number($value): ?float
    {
        if ($value === null || $value === '' || $value === false) return null;
        if (!is_numeric($value)) return null;

        return (float) $value;
    }

    private static function percent($value): int
    {
        $value = (int) round((float) $value);

        return max(0, min(100, $value));
    }

    private static function money($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
