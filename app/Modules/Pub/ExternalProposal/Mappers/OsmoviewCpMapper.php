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
 *
 * Варианты (patch v25): ответ может содержать несколько вариантов КП —
 * дополнительные под числовыми ключами верхнего уровня, основной развёрнут в
 * корне (см. `variants()`). Переносятся все: позиция в КП одна на все варианты
 * (сценарии сопоставляются по `scenarioId`, работы — по id), а количество,
 * цена и скидка — своя ячейка в каждом варианте. Общее у КП (партнёр,
 * компания, валюта, НДС, язык) берётся из основного варианта.
 */
class OsmoviewCpMapper
{
    public const SOURCE = ExternalProposal::SOURCE_OSMOVIEW_CP;

    /**
     * Служебный сценарий для кастомных позиций (scenarioId "0").
     * По умолчанию; рабочее значение — consts.osmoview_scenario_custom_id (читать через scenarioCustomId())
     */
    public const SCENARIO_CUSTOM_ID = 97;

    /** НДС по умолчанию, если константы нет (рабочее значение — consts.nds_rate) */
    public const NDS_DEFAULT = 22;

    /**
     * Выше этой цены ручная цена в валютном КП считается рублёвой.
     * По умолчанию; рабочее значение — consts.osmoview_manual_rub_threshold (читать через manualRubThreshold())
     */
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
     * Ключ `variants` — варианты КП (номер 1..N, основной последний), в них
     * тип лицензии, скидки, платформа и итоги. Позиции (`scenarios`, `works`)
     * общие на всё КП, у каждой в `cells[номер варианта]` своя ячейка.
     * Поля `period`, `platform`, `totals`, `partner_discount` в корне — это
     * основной вариант (совместимость с одновариантным выводом).
     *
     * @param ExternalProposal $external
     * @param array $choices выбор пользователя: partner, company, manager, number, scenario[i]
     * @return array
     */
    public static function preview(ExternalProposal $external, array $choices = []): array
    {
        $payload = $external->payload ?? [];
        $warnings = $price_warnings = [];

        // варианты КП: сначала под числовыми ключами, последним — корневой (он же основной)
        $variant_payloads = static::variants($payload);
        $main = count($variant_payloads);
        $multi = $main > 1;
        $root = $variant_payloads[$main - 1];

        // общее у всего КП берём из основного варианта
        $lang = static::lang($root);
        $currency = static::currency($root);
        $rate = static::rate($root, $currency);
        $nds = !empty($root['isVatIncluded']) ? (int) (Constant::get('nds_rate') ?? static::NDS_DEFAULT) : 0;
        $vat = !empty($root['isVatIncluded']);

        $map = ExternalScenarioMap::where('source', static::SOURCE)->get()->keyBy('external_scenario_id');
        $catalog = static::catalog();
        $platform_rules = static::platformRules();

        $variants = [];         // варианты, ключ — номер варианта (1..N)
        $scenario_rows = [];    // позиции сценариев, общие для всех вариантов
        $work_rows = [];        // позиции работ, общие для всех вариантов

        foreach ($variant_payloads as $index => $vp) {
            $v = $index + 1;
            // в КП варианты идут основным вперёд — нумеруем так же, чтобы
            // подписи предпросмотра совпали с тем, что увидим в карточке КП
            $position = $v === $main ? 1 : $v + 1;
            $prefix = static::vprefix($multi, $position);

            // тип лицензии — свой у каждого варианта, «по большинству» считается внутри варианта
            [$period, $period_mixed] = static::period($vp);
            if ($period_mixed) {
                $warnings[] = $prefix . 'У позиций разные типы лицензий (годовые и бессрочные) — вариант выбран по большинству: ' . ($period === 'unlimited' ? 'бессрочный' : 'годовой') . '.';
            }

            $client_discount = (float) ($vp['clientDiscount'] ?? 0);
            if ($client_discount > 0) {
                $warnings[] = $prefix . "Скидка клиента на весь проект {$client_discount}% не переносится — у нас нет общей скидки, примените вручную.";
            }

            if ($multi && !empty($vp['isVatIncluded']) !== $vat) {
                $warnings[] = $prefix . 'НДС в источнике отличается от основного варианта, а у нас он общий на всё КП — взят из основного.';
            }
            if ($multi && !empty($vp['currency']) && static::currency($vp) !== $currency) {
                $warnings[] = $prefix . 'Валюта в источнике (' . static::currency($vp) . ') отличается от основного варианта — взята валюта основного.';
            }

            // ПЛАТФОРМА — позиция одна, ячейка своя в каждом варианте
            $vw = [];
            $platform_count = (int) ($vp['totalCameras'] ?? $external->cameras ?? 0);
            $platform_manual = static::manual(static::number($vp['platformManualPrice'] ?? null), $currency, $rate, $vw, 'Платформа');
            $platform_cost = $platform_manual !== null
                ? $platform_manual
                : static::convert(static::priceByRules($platform_rules, $platform_count, $period), $rate);
            $platform_discount = static::percent($vp['platformDiscount'] ?? 0);
            foreach ($vw as $warning) $price_warnings[] = $prefix . $warning;

            $variants[$v] = [
                'v' => $v,
                'position' => $position,
                'is_main' => $v === $main,
                'title' => $multi ? ('Вариант ' . $position . ($v === $main ? ' (основной)' : '')) : 'Вариант',
                'name' => Str::limit(trim((string) ($vp['projectName'] ?? '')), 200, ''),
                'delivery_weeks' => $vp['deliveryWeeks'] ?? null,
                'grand_total' => static::number($vp['grandTotal'] ?? null),
                'period' => $period,
                'period_mixed' => $period_mixed,
                'partner_discount' => static::percent($vp['partnerDiscount'] ?? 0),
                'platform' => [
                    'count' => $platform_count,
                    'discount' => $platform_discount,
                    'cost' => $platform_cost,
                    'cost_source' => $platform_manual !== null ? 'manual' : 'price',
                    'total' => round($platform_cost * $platform_count * (1 - $platform_discount / 100), 2),
                ],
                'totals' => [],
            ];

            // СЦЕНАРИИ: один и тот же scenarioId в разных вариантах — одна позиция КП
            $neuro_discount = static::percent($vp['neuroDiscount'] ?? 0);
            $items = is_array($vp['items'] ?? null) ? array_values($vp['items']) : [];
            $seen = [];
            foreach ($items as $item) {
                $external_id = trim((string) ($item['scenarioId'] ?? ''));
                $name = trim((string) ($item['name'] ?? ''));
                $retrain = ($item['customizationType'] ?? 'none') === 'retrain';
                $comment = trim((string) ($item['comments'] ?? ''));
                $key = static::rowKey($external_id, $name, $seen);

                if (!isset($scenario_rows[$key])) {
                    $scenario_rows[$key] = [
                        'i' => count($scenario_rows) + 1,
                        'key' => $key,
                        'external_id' => $external_id,
                        'name' => $name,
                        'retrain' => $retrain,
                        'comment' => $comment,
                        'cells' => [],
                    ];
                } elseif ($v === $main) {
                    // название, комментарий и признак дообучения — из основного варианта
                    $scenario_rows[$key]['name'] = $name;
                    $scenario_rows[$key]['retrain'] = $retrain;
                    $scenario_rows[$key]['comment'] = $comment;
                }

                $scenario_rows[$key]['cells'][$v] = [
                    'name' => $name,
                    'count' => (int) ($item['cameras'] ?? 0),
                    'discount' => $neuro_discount,
                    'manual' => static::number($item['manualPrice'] ?? null),
                    'retrain' => $retrain,
                    'comment' => $comment,
                ];
            }

            // РАБОТЫ: одинаковый id работы (3.1, 4.1, AI.1 …) — одна позиция КП
            $vw = [];
            $seen = [];
            foreach (static::works($vp, $lang, $vw, $currency, $rate) as $work) {
                $key = static::rowKey($work['id'], $work['name'], $seen);

                if (!isset($work_rows[$key])) {
                    $row = $work;
                    $row['key'] = $key;
                    $row['cells'] = [];
                    $row['i'] = count($work_rows) + 1;
                    $work_rows[$key] = $row;
                } else {
                    if ($multi && static::normalizeName($work_rows[$key]['name']) !== static::normalizeName($work['name'])) {
                        $warnings[] = 'Работа ' . ($work['id'] !== '' ? $work['id'] : $work['name']) . ' называется в вариантах по-разному — в КП это одна позиция, описание взято из основного варианта.';
                    }
                    // описание позиции — общее на КП, поэтому его тоже берём из основного варианта
                    if ($v === $main) {
                        $row = $work;
                        $row['key'] = $key;
                        $row['cells'] = $work_rows[$key]['cells'];
                        $row['i'] = $work_rows[$key]['i'];
                        $work_rows[$key] = $row;
                    }
                }

                $work_rows[$key]['cells'][$v] = [
                    'name' => $work['name'],
                    'count' => $work['count'],
                    'cost' => $work['cost'],
                    'discount' => $work['discount'],
                    'hours' => $work['hours'],
                    'rate' => $work['rate'],
                    'notice' => $work['notice'],
                    'total' => $work['total'],
                ];
            }
            foreach ($vw as $warning) $price_warnings[] = $prefix . $warning;
        }

        // СОПОСТАВЛЕНИЕ СЦЕНАРИЕВ И ЦЕНЫ ПО ВАРИАНТАМ
        $scenarios = [];
        foreach ($scenario_rows as $row) {
            $i = $row['i'];

            $scenario = null; $match = null;
            if (!empty($choices['scenario'][$i])) {
                $scenario = $catalog->get((int) $choices['scenario'][$i]) ?? Scenario::find((int) $choices['scenario'][$i]);
                $match = $scenario ? 'choice' : null;
            }
            if (empty($scenario)) {
                [$scenario, $match] = static::matchScenario($row['external_id'], $row['name'], $map, $catalog);
            }

            $row['scenario_id'] = $scenario?->id;
            $row['scenario_name'] = $scenario?->name;
            $row['match'] = $match;

            foreach ($row['cells'] as $v => $cell) {
                $prefix = static::vprefix($multi, $variants[$v]['position']);

                $vw = [];
                $manual = static::manual($cell['manual'], $currency, $rate, $vw, "Позиция {$i} «{$cell['name']}»");
                foreach ($vw as $warning) $price_warnings[] = $prefix . $warning;

                if ($manual !== null) {
                    $cost = $manual; $cost_source = 'manual';
                } elseif ($scenario && (int) $scenario->id !== static::scenarioCustomId()) {
                    $cost = static::convert(static::priceByRules($scenario->cost_rules, $cell['count'], $variants[$v]['period']), $rate);
                    $cost_source = 'price';
                } else {
                    $cost = 0; $cost_source = 'none';
                    if ($scenario) $price_warnings[] = $prefix . "Позиция {$i} «{$cell['name']}»: кастомный сценарий без manualPrice — цена 0, укажите вручную после переноса.";
                }

                $cell['cost'] = $cost;
                $cell['cost_source'] = $cost_source;
                $cell['total'] = round($cost * $cell['count'] * (1 - $cell['discount'] / 100), 2);
                $row['cells'][$v] = $cell;
            }

            $scenarios[] = static::flatten($row, $main);
        }

        $works = [];
        foreach ($work_rows as $row) {
            $works[] = static::flatten($row, $main);
        }

        // ИТОГИ ПО ВАРИАНТАМ
        foreach (array_keys($variants) as $v) {
            $neuro = $works_total = 0;
            foreach ($scenarios as $row) $neuro += $row['cells'][$v]['total'] ?? 0;
            foreach ($works as $row) $works_total += $row['cells'][$v]['total'] ?? 0;

            $totals = [
                'platform' => $variants[$v]['platform']['total'],
                'neuro' => round($neuro, 2),
                'works' => round($works_total, 2),
            ];
            $totals['total'] = round($totals['platform'] + $totals['neuro'] + $totals['works'], 2);
            $totals['nds'] = $vat ? round($totals['total'] * $nds / 100, 2) : 0;
            $variants[$v]['totals'] = $totals;
        }

        // показываем варианты в том же порядке, что и карточка КП (основной первым);
        // ключи — номера вариантов формы — сохраняются
        uasort($variants, fn($a, $b) => $a['position'] <=> $b['position']);

        if ($currency !== Currency::CURRENCY_DEFAULT && empty($root['exchangeRate'])) {
            $warnings[] = "В ответе нет курса для {$currency}, взят последний курс из справочника: {$rate}.";
        }

        // ПАРТНЁР И КОМПАНИЯ
        $partner_source = trim((string) ($root['recipientCompany'] ?? ''));
        $partner = null; $partner_match = null;
        if (!empty($choices['partner'])) {
            $partner = Partner::find((int) $choices['partner']);
            $partner_match = $partner ? 'choice' : null;
        }
        if (empty($partner) && $partner_source !== '') {
            [$partner, $partner_match] = static::matchPartner($partner_source);
        }

        $company_source = trim((string) ($root['customerName'] ?? $external->customer ?? ''));
        $company = null; $company_match = null;
        if (!empty($choices['company'])) {
            $company = Company::find((int) $choices['company']);
            $company_match = $company ? 'choice' : null;
        }
        // компанию выбрали пустой при переносе — значит, заказчик не указан, догадкой не подменяем
        if (empty($company) && !array_key_exists('company', $choices) && $company_source !== '') {
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

        // ОБОРУДОВАНИЕ
        $hardware = static::hardware($external, ['lang' => $lang]);

        // ДОПОЛНИТЕЛЬНЫЕ НАЧИСЛЕНИЯ — доп. сборы источника
        $charges = static::charges($root);
        if (!empty($charges['skipped'])) {
            $warnings[] = 'В источнике доп. сборы отключены (taxMode = ' . ($charges['mode'] !== '' ? $charges['mode'] : '—') . '): '
                . static::chargesLabel($charges['skipped']) . ' — в КП они не переносятся.';
        }

        $warnings = array_merge($warnings, $price_warnings);

        $unresolved = collect($scenarios)->whereNull('scenario_id')->pluck('i')->all();

        return [
            'external' => $external,
            'number_external' => $external->external_number ?: (string) ($root['id'] ?? ''),
            'number_default' => !empty($choices['number']) ? (string) $choices['number'] : static::nextNumber(),
            'name' => Str::limit(trim((string) ($root['projectName'] ?? $external->name ?? '')), 200, ''),
            'date' => static::date($root['createdAt'] ?? null, $external->created_at_remote),
            'lang' => $lang,
            'currency' => $currency,
            'rate' => $rate,
            'nds' => $nds,
            'vat' => $vat,
            'hardware' => $hardware,
            'multi' => $multi,
            'main' => $main,
            'variants' => $variants,
            'charges' => $charges,
            'period' => $variants[$main]['period'],
            'period_mixed' => $variants[$main]['period_mixed'],
            'partner' => $partner,
            'partner_match' => $partner_match,
            'partner_source' => $partner_source,
            'company' => $company,
            'company_match' => $company_match,
            'company_source' => $company_source,
            'manager' => (int) ($choices['manager'] ?? auth()->id() ?? 0),
            'partner_discount' => $variants[$main]['partner_discount'],
            'platform' => $variants[$main]['platform'],
            'scenarios' => $scenarios,
            'works' => $works,
            'totals' => $variants[$main]['totals'],
            'warnings' => $warnings,
            'unresolved' => $unresolved,
            'ready' => !empty($partner) && empty($unresolved),
        ];
    }

    /*** REQUEST ***/

    /**
     * Сборка Request в формате формы создания КП (все варианты сразу: второй
     * индекс в `cell` / `cost` / `work_cell` / `platform_cell` — номер варианта)
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
        if (!empty($preview['unresolved'])) throw new \RuntimeException('Не сопоставлены сценарии: позиции ' . implode(', ', $preview['unresolved']));

        $vat = $preview['vat'] ? 1 : 0;
        $lang = $preview['lang'];
        $variants = $preview['variants'];
        $main = (int) $preview['main'];

        $data = [
            'name' => $preview['name'] !== '' ? $preview['name'] : ('КП ' . $preview['number_external']),
            'name_alt' => $preview['number_external'] ?: null,
            'date' => $preview['date'],
            'number' => (string) ($choices['number'] ?? $preview['number_default']),
            'manager' => (int) $preview['manager'],
            'company' => $preview['company']?->id,
            'partner' => (int) $preview['partner']->id,
            'nds' => $preview['nds'],
            'lang' => $lang,

            'period_main' => $main,
        ];

        // ВАРИАНТЫ — свой тип лицензии и свои скидки партнёра у каждого
        $data['period'] = $data['period_active'] = $data['period_value'] = [];
        $data['partner_platform_discount'] = $data['partner_neuro_discount'] = $data['partner_soft_discount'] = [];
        foreach ($variants as $v => $variant) {
            $data['period'][$v] = $variant['period'];
            $data['period_active'][$v] = 1;
            $data['period_value'][$v] = $variant['period'] === 'unlimited' ? null : 1;
            $data['partner_platform_discount'][$v] = $variant['partner_discount'];
            $data['partner_neuro_discount'][$v] = $variant['partner_discount'];
            $data['partner_soft_discount'][$v] = $variant['partner_discount'];
        }

        // ПЛАТФОРМА — одна позиция, ячейка в каждом варианте
        $data['platform'] = [1 => [
            'cb_process' => 1,
            'extended' => __('proposal.textarea__platform_extended', [], $lang),
            'notice' => __('proposal.textarea__platform_notice', [], $lang),
            'sort' => 0,
        ]];
        $data['platform_cell'] = $data['platform_cost'] = [1 => []];
        foreach ($variants as $v => $variant) {
            $data['platform_cell'][1][$v] = [
                'active' => 1,
                'count' => max(0, (int) $variant['platform']['count']),
                'discount' => (int) $variant['platform']['discount'],
                'nds' => $vat,
            ];
            $data['platform_cost'][1][$v] = static::money($variant['platform']['cost']);
        }

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

            $data['cell'][$i] = $data['cost'][$i] = [];
            foreach ($variants as $v => $variant) {
                // позиции нет в этом варианте — ячейка выключена (так же делает форма КП)
                $cell = $row['cells'][$v] ?? null;
                $data['cell'][$i][$v] = [
                    'active' => $cell ? 1 : 0,
                    'count' => $cell ? max(0, (int) $cell['count']) : 0,
                    'discount' => $cell ? (int) $cell['discount'] : 0,
                    'nds' => $cell ? $vat : 0,
                ];
                $data['cost'][$i][$v] = static::money($cell['cost'] ?? $row['cost']);
            }
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

            $data['work_cell'][$i] = [];
            foreach ($variants as $v => $variant) {
                $cell = $row['cells'][$v] ?? null;
                $data['work_cell'][$i][$v] = [
                    'active' => $cell ? 1 : 0,
                    'cost' => static::money($cell['cost'] ?? $row['cost']),
                    'count' => $cell ? $cell['count'] : 0,
                    'discount' => $cell ? (int) $cell['discount'] : 0,
                    'discount_partner' => 0,
                    'nds' => $cell ? $vat : 0,
                ];
            }
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
            $scenario = $catalog->get(static::scenarioCustomId()) ?? Scenario::find(static::scenarioCustomId());
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
            if ((int) $scenario->id === static::scenarioCustomId()) continue;

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
     * Служебный сценарий для кастомных позиций
     * (consts.osmoview_scenario_custom_id, по умолчанию SCENARIO_CUSTOM_ID)
     *
     * @return int
     */
    public static function scenarioCustomId(): int
    {
        return Constant::int('osmoview_scenario_custom_id', static::SCENARIO_CUSTOM_ID);
    }

    /**
     * Порог «рублёвой» ручной цены в валютном КП
     * (consts.osmoview_manual_rub_threshold, по умолчанию MANUAL_RUB_THRESHOLD)
     *
     * @return float
     */
    public static function manualRubThreshold(): float
    {
        return Constant::float('osmoview_manual_rub_threshold', (float) static::MANUAL_RUB_THRESHOLD);
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
        if ($value <= static::manualRubThreshold()) return $value;

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
     * Варианты КП из ответа detail.
     *
     * Многовариантный ответ выглядит как `{"0": {…вариант…}, "id": "AK904", …}`:
     * дополнительные варианты лежат под числовыми ключами верхнего уровня, а
     * основной развёрнут прямо в корне. Одновариантный ответ даёт список из
     * одного элемента — самого payload.
     *
     * @param array $payload
     * @return array<int, array> варианты по возрастанию ключей, последний — основной (корневой)
     */
    public static function variants(array $payload): array
    {
        $keys = [];
        foreach (array_keys($payload) as $key) {
            if (is_int($key) || (is_string($key) && ctype_digit($key))) $keys[] = (int) $key;
        }
        sort($keys);

        $root = $payload;
        $list = [];
        foreach ($keys as $key) {
            $sub = $payload[$key] ?? null;
            unset($root[$key]);
            if (is_array($sub)) $list[] = $sub;
        }
        $list[] = $root;

        return $list;
    }

    /**
     * Дополнительные сборы источника (`additionalCharges`) для блока
     * «дополнительные начисления». Переносим только при `taxMode` =
     * `custom_charges`; при выключенном режиме сборы показываем, но не переносим.
     *
     * @param array $payload основной (корневой) вариант payload
     * @return array{mode: string, transfer: array<int, array{name: string, percent: float}>, skipped: array}
     */
    public static function charges(array $payload): array
    {
        $mode = trim((string) ($payload['taxMode'] ?? ''));

        $rows = [];
        foreach (is_array($payload['additionalCharges'] ?? null) ? $payload['additionalCharges'] : [] as $charge) {
            if (!is_array($charge)) continue;

            $percent = (float) ($charge['ratePercent'] ?? 0);
            if ($percent <= 0) continue;

            $name = trim((string) ($charge['description'] ?? '')) ?: trim((string) ($charge['id'] ?? ''));
            if ($name === '') continue;

            $rows[] = ['name' => mb_substr($name, 0, 200), 'percent' => $percent];
        }

        $enabled = $mode === 'custom_charges';

        return [
            'mode' => $mode,
            'transfer' => $enabled ? $rows : [],
            'skipped' => $enabled ? [] : $rows,
        ];
    }

    /**
     * Сборы одной строкой: «название 11%, название 15%»
     *
     * @param array $rows
     * @return string
     */
    public static function chargesLabel(array $rows): string
    {
        return implode(', ', array_map(fn($row) => $row['name'] . ' ' . ($row['percent'] + 0) . '%', $rows));
    }

    /**
     * Ключ общей позиции: по id источника, а если его нет — по названию.
     * Повторы внутри одного варианта не склеиваются: у каждого свой ключ.
     */
    private static function rowKey(string $id, string $name, array &$seen): string
    {
        $id = trim($id);
        $key = ($id !== '' && $id !== '0') ? 'id:' . $id : 'name:' . static::normalizeName($name);
        $seen[$key] = ($seen[$key] ?? -1) + 1;

        return $key . '#' . $seen[$key];
    }

    /**
     * Поля ячейки основного варианта — в корень строки позиции: так строка
     * остаётся совместимой с одновариантным выводом (count, cost, total …)
     */
    private static function flatten(array $row, int $main): array
    {
        $cells = $row['cells'];
        $cell = $cells[$main] ?? (reset($cells) ?: []);

        foreach ($cell as $key => $value) {
            // название и служебную «сырую» цену строка держит свои
            if (in_array($key, ['name', 'manual'], true)) continue;
            $row[$key] = $value;
        }

        return $row;
    }

    /**
     * Номер варианта в тексте предупреждения (в одновариантном КП его нет)
     */
    private static function vprefix(bool $multi, int $v): string
    {
        return $multi ? "Вариант {$v}: " : '';
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
        return Scenario::where('active', 1)->orWhere('id', static::scenarioCustomId())->get()->keyBy('id');
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
