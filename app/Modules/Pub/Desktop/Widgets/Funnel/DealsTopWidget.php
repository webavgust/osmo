<?php

namespace App\Modules\Pub\Desktop\Widgets\Funnel;

use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Services\ProposalDealService;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Крупные сделки (patch v30): топ сделок реестра по сумме за период.
 *
 * Данные — CrmDealRegistryService::rows() (тот же реестр сделок, что на странице
 * «Сделки»), суммы пересчитываются в валюту виджета по текущим курсам — так же,
 * как их считает страница воронки. Ссылка строки ведёт в реестр, «↗» — в Битрикс24.
 */
class DealsTopWidget extends Widget
{
    /** Отбор стадий: воронка или всё подряд */
    public const SCOPE = [
        'active' => 'Активные стадии воронки',
        'all' => 'Все стадии',
    ];

    /** По какому полю сделки отбирается период */
    public const DATES = [
        'create' => 'Дате создания',
        'close' => 'Дате закрытия',
    ];

    /** Порядок строк */
    public const SORTS = [
        'amount' => 'По сумме',
        'closedate' => 'По дате закрытия',
    ];

    public static function id(): string { return 'deals_top'; }

    public static function name(): string { return 'Крупные сделки'; }

    public static function category(): string { return 'funnel'; }

    public static function description(): string
    {
        return 'Топ сделок по сумме за период: стадия, заказчик, ссылка';
    }

    public static function icon(): string { return 'fa-arrow-up-wide-short'; }

    public static function sizes(): array { return ['8x8', '8x4', '16x8']; }

    public static function defaultSize(): string { return '8x8'; }

    public static function order(): int { return 350; }

    public static function ttl(): int { return 600; }

    public static function usesCurrency(): bool { return true; }

    public static function usesPeriod(): bool { return true; }

    public static function fields(): array
    {
        return [
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько сделок', 'default' => 10, 'min' => 3, 'max' => 30],
            ['key' => 'scope', 'type' => 'select', 'label' => 'Стадии', 'default' => 'active', 'options' => static::SCOPE],
            ['key' => 'sort', 'type' => 'select', 'label' => 'Сортировка', 'default' => 'amount', 'options' => static::SORTS],
            ['key' => 'date', 'type' => 'select', 'label' => 'Период считать по', 'default' => 'create', 'options' => static::DATES],
            ['key' => 'manager', 'type' => 'list', 'label' => 'Менеджеры', 'default' => [],
                'options' => fn() => static::managerOptions(), 'hint' => 'Пусто — все менеджеры'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('crm-deal.index');
    }

    /**
     * Менеджеры сделок для настройки: «[83] Анна Август.» → чистое имя
     *
     * @return array значение фильтра => подпись
     */
    public static function managerOptions(): array
    {
        return ProposalDealService::managers()
            ->mapWithKeys(fn($item) => [(string) $item => trim(Str::afterLast((string) $item, ']')) ?: (string) $item])
            ->all();
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // 30 строк (предел настройки «Сколько сделок»): высокому блоку есть чем заполниться
        $titles = ['Пилот. Прессовый цех', 'Модернизация АСУ ТП', 'Видеоаналитика склада', 'Платформа мониторинга',
            'Обновление лицензий', 'Пусконаладка участка', 'Цифровой двойник линии', 'Контроль качества сварки',
            'Учёт энергоресурсов', 'Складская логистика', 'Расширение лицензий', 'Диспетчеризация котельной'];
        $companies = ['СТК', 'Северная верфь', 'Логист Плюс', 'ГазСервис', 'Агрохолдинг', 'Метизы', 'Уралмаш', ''];
        $stages = ['TCP', 'Contracting', 'Presentation', 'Pilot project', 'Invoice + Specification', 'Competition/tender'];

        $rows = [];
        for ($i = 0; $i < 30; $i++) {
            $rows[] = [
                'id' => 900 + $i,
                'title' => $titles[$i % count($titles)] . ($i >= count($titles) ? ' · этап ' . intdiv($i, count($titles)) + 1 : ''),
                'company' => $companies[$i % count($companies)],
                'stage' => $stages[$i % count($stages)],
                'amount' => round(18_400_000 * 0.88 ** $i, -4),
                'date' => Carbon::create(2026, 9, 15)->addDays($i * 9)->format('d.m.Y'),
                'manager' => 'Анна Август.',
                'project' => $i % 3 === 0,
                'proposal' => $i % 2 === 0,
                'url' => null,
                'deal_url' => null,
            ];
        }

        return [
            'rows' => $rows,
            'total' => array_sum(array_column($rows, 'amount')),
            'found' => count($rows),
            'skipped' => 0,
            'symbol' => '₽',
            'dates' => $ctx->periodFor($settings)['dates'],
            'label' => $ctx->periodFor($settings)['label'],
        ];
    }

    /**
     * Крупнейшие сделки периода
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [['id', 'title', 'company', 'stage', 'amount', 'date', 'manager', 'project', 'proposal', 'url', 'deal_url']], 'total', 'found', 'skipped', 'symbol', 'dates', 'label']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $period = $ctx->periodFor($settings);
        $limit = max(1, (int) $settings['limit']);

        // курсы берём текущие — так же, как страница воронки (DashboardDataService)
        $rates = CurrencyService::getConvertRates();

        $params = ['has_proposal' => 'all', 'manager' => array_map('strval', (array) $settings['manager'])];
        if ($settings['scope'] !== 'all') {
            $params['stage'] = FunnelTableWidget::STAGES;
        }

        $field = $settings['date'] === 'close' ? 'closedate' : 'date_create';
        $rows = [];
        $skipped = 0;

        foreach (CrmDealRegistryService::rows($params) as $deal) {
            $date = static::date($deal->{$field});
            if (!$date || $date->lt($period['from']) || $date->gt($period['to'])) continue;

            $rate = $rates[(string) $deal->currency_id][$currency] ?? null;
            if ($rate === null) {
                $skipped++;
                continue;
            }

            $closes = static::date($deal->closedate);

            $rows[] = [
                'id' => (int) $deal->id,
                'title' => (string) ($deal->title ?: 'Сделка ' . $deal->id),
                'company' => (string) ($deal->customer_name ?: $deal->company_name ?: ''),
                'stage' => (string) $deal->stage_name,
                'amount' => (float) $deal->opportunity * $rate,
                'date' => $closes?->format('d.m.Y'),
                'sort_date' => $closes?->timestamp ?? 0,
                'manager' => trim(Str::afterLast((string) $deal->assigned_by, ']')),
                'project' => $deal->project !== null,
                'proposal' => $deal->proposal !== null,
                'url' => route('crm-deal.index', ['q' => $deal->id]),
                'deal_url' => CrmDealRegistryService::url($deal->id),
            ];
        }

        $found = count($rows);

        usort($rows, fn($a, $b) => $settings['sort'] === 'closedate'
            ? [$b['sort_date'], $b['amount']] <=> [$a['sort_date'], $a['amount']]
            : [$b['amount'], $b['sort_date']] <=> [$a['amount'], $a['sort_date']]);

        $rows = array_map(fn($row) => array_diff_key($row, ['sort_date' => null]), array_slice($rows, 0, $limit));

        return [
            'rows' => $rows,
            'total' => array_sum(array_column($rows, 'amount')),
            'found' => $found,
            'skipped' => $skipped,
            'symbol' => $ctx->symbol($currency),
            'dates' => $period['dates'],
            'label' => $period['label'],
        ];
    }

    /**
     * Дата из поля зеркала Битрикса; пустое или кривое значение — null
     *
     * @param mixed $value
     * @return Carbon|null
     */
    protected static function date($value): ?Carbon
    {
        if (empty($value)) return null;

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
